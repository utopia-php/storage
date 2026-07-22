<?php

declare(strict_types=1);

namespace Utopia\Storage\Device;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Utopia\Client\Adapter\Curl\Client as CurlAdapter;
use Utopia\Client as HttpClient;
use Utopia\Client\Decorator\Retry;
use Utopia\Psr7\Method;
use Utopia\Psr7\Request;
use Utopia\Psr7\Stream;
use Utopia\Psr7\Uri;
use Utopia\Storage\Acl;
use Utopia\Storage\Device;
use Utopia\Storage\DeviceType;
use Utopia\Storage\Exception\NotFoundException;
use Utopia\Storage\Exception\RemoteException;
use Utopia\Storage\Exception\StorageException;
use Utopia\Storage\Exception\TransportException;
use Utopia\Storage\Exception\UploadException;
use Utopia\Storage\FileInfo;
use Utopia\Storage\FileList;

/**
 * @see \Utopia\Tests\Storage\Device\S3Test
 *
 * @phpstan-import-type UploadMetadata from Device
 */
class S3 extends Device
{
    protected const MAX_PAGE_SIZE = 1000;

    private readonly string $fqdn;

    private readonly string $host;

    private readonly ClientInterface $client;

    /**
     * S3 Constructor
     *
     * @param  ClientInterface|null  $client  PSR-18 client used for every request; defaults to `utopia-php/client` with the cURL adapter — no overall deadline (large transfers may take arbitrarily long), a stall watchdog that aborts once no bytes move for 60s, TCP keepalive, and transient-error retries via `S3\RetryStrategy`
     */
    public function __construct(
        protected readonly string $root,
        private readonly string $accessKey,
        #[\SensitiveParameter]
        private readonly string $secretKey,
        string $host,
        protected readonly string $region,
        protected readonly Acl $acl = Acl::Private,
        ?ClientInterface $client = null,
    ) {
        if (str_starts_with($host, 'http://') || str_starts_with($host, 'https://')) {
            $this->fqdn = $host;
            $this->host = str_replace(['http://', 'https://'], '', $host);
        } else {
            $this->fqdn = 'https://' . $host;
            $this->host = $host;
        }

        $this->client = $client ?? new Retry(
            new HttpClient(new CurlAdapter(options: [
                \CURLOPT_TIMEOUT_MS => 0,
                \CURLOPT_LOW_SPEED_LIMIT => 1,
                \CURLOPT_LOW_SPEED_TIME => 60,
                \CURLOPT_TCP_KEEPALIVE => 1,
            ])),
            new S3\RetryStrategy(),
        );
    }

    public function getType(): DeviceType
    {
        return DeviceType::S3;
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function getPath(string $filename): string
    {
        return $this->getRoot() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @param  UploadMetadata  $metadata
     */
    public function prepareUpload(string $path, string $contentType, int $chunks = 1, array &$metadata = []): void
    {
        $metadata['parts'] ??= [];
        $metadata['chunks'] ??= 0;
        $metadata['content_type'] ??= $contentType;

        if ($chunks === 1 || isset($metadata['uploadId']) && !\in_array($metadata['uploadId'], ['', '0', [], 0], true)) {
            return;
        }

        $metadata['uploadId'] = $this->createMultipartUpload($path, $contentType);
    }

    public function finalizeUpload(string $path, int $chunks = 1, array &$metadata = []): bool
    {
        if ($this->exists($path)) {
            return true;
        }

        if ($chunks === 1) {
            return false;
        }

        if (empty($metadata['uploadId'])) {
            throw new UploadException('Missing multipart upload ID');
        }

        $metadata['parts'] ??= [];
        for ($i = 1; $i <= $chunks; ++$i) {
            if (! \array_key_exists($i, $metadata['parts'])) {
                throw new UploadException('Missing chunk ' . $i);
            }
        }

        $this->completeMultipartUpload($path, $metadata['uploadId'], $metadata['parts']);

        return true;
    }

    /**
     * @param  UploadMetadata  $metadata
     */
    public function uploadChunk(string $data, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        $contentType = $metadata['content_type'] ?? '';

        if ($chunk === 1 && $chunks === 1) {
            $this->write($path, $data, $contentType);
            $metadata['parts'][$chunk] = true;
            $metadata['chunks'] = 1;

            return 1;
        }

        if (empty($metadata['uploadId'])) {
            throw new UploadException('Missing multipart upload ID');
        }

        $metadata['parts'] ??= [];
        $metadata['chunks'] ??= 0;

        $etag = $this->uploadPart($data, $path, $contentType, $chunk, $metadata['uploadId']);
        // skip incrementing if the chunk was re-uploaded
        if (! \array_key_exists($chunk, $metadata['parts'])) {
            ++$metadata['chunks'];
        }
        $metadata['parts'][$chunk] = $etag;

        return $metadata['chunks'];
    }

    /**
     * Transfer
     */
    public function transfer(string $path, string $destination, Device $device, int $chunkSize = self::TRANSFER_CHUNK_SIZE): bool
    {
        if ($chunkSize <= 0) {
            throw new \InvalidArgumentException('Chunk size must be greater than zero');
        }

        $response = $this->getInfo($path);
        $size = (int) ($response['content-length'] ?? 0);
        $contentType = $response['content-type'] ?? '';

        if ($size <= $chunkSize) {
            $source = $this->read($path);

            return $device->write($destination, $source, $contentType);
        }

        $totalChunks = (int) ceil($size / $chunkSize);
        $metadata = ['content_type' => $contentType];
        try {
            for ($counter = 0; $counter < $totalChunks; ++$counter) {
                $start = $counter * $chunkSize;
                $data = $this->read($path, $start, $chunkSize);
                $device->uploadData($data, $destination, $contentType, $counter + 1, $totalChunks, $metadata);
            }
        } catch (\Throwable $e) {
            // Best effort, and only once a multipart upload was actually started —
            // its unclaimed parts are billed until aborted. Aborting without one
            // could delete a pre-existing destination the transfer never touched.
            $uploadId = $metadata['uploadId'] ?? null;
            if (\is_string($uploadId) && $uploadId !== '') {
                try {
                    $device->abort($destination, $uploadId);
                } catch (\Throwable) {
                }
            }
            throw $e;
        }

        return true;
    }

    /**
     * Start Multipart Upload
     *
     * Initiate a multipart upload and return an upload ID.
     *
     *
     * @throws StorageException
     */
    protected function createMultipartUpload(string $path, string $contentType): string
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';

        $response = $this->call(
            Method::POST,
            $uri,
            '',
            ['uploads' => ''],
            headers: ['content-type' => $contentType],
            amzHeaders: ['x-amz-acl' => $this->acl->value],
        );

        $uploadId = \is_array($response->body) ? ($response->body['UploadId'] ?? null) : null;
        if (! \is_string($uploadId)) {
            throw new RemoteException('Missing upload ID in S3 response');
        }

        return $uploadId;
    }

    /**
     * Upload Part
     *
     *
     * @throws StorageException
     */
    protected function uploadPart(string $data, string $path, string $contentType, int $chunk, string $uploadId): string
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';

        // ACL header is not allowed in parts, only createMultipartUpload accepts this header.
        $response = $this->call(
            Method::PUT,
            $uri,
            $data,
            [
                'partNumber' => $chunk,
                'uploadId' => $uploadId,
            ],
            headers: ['content-type' => $contentType],
        );

        return $response->headers['etag'] ?? throw new RemoteException('Missing ETag in S3 response');
    }

    /**
     * Complete Multipart Upload
     *
     * @param  array<int, bool|string>  $parts
     *
     * @throws StorageException
     */
    protected function completeMultipartUpload(string $path, string $uploadId, array $parts): bool
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';

        ksort($parts, SORT_NUMERIC);

        $body = '<CompleteMultipartUpload>';
        foreach ($parts as $key => $etag) {
            if (! \is_string($etag)) {
                throw new UploadException('Missing ETag for part ' . $key);
            }
            $body .= "<Part><ETag>{$etag}</ETag><PartNumber>{$key}</PartNumber></Part>";
        }
        $body .= '</CompleteMultipartUpload>';

        $this->call(
            Method::POST,
            $uri,
            $body,
            ['uploadId' => $uploadId],
            headers: ['content-type' => 'application/xml'],
        );

        return true;
    }

    /**
     * Abort Chunked Upload
     *
     *
     * @throws StorageException
     */
    public function abort(string $path, string $uploadId = ''): bool
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';
        $this->call(Method::DELETE, $uri, '', ['uploadId' => $uploadId]);

        return true;
    }

    /**
     * Read file or part of file by given path, offset and length.
     *
     *
     * @throws StorageException
     */
    public function read(string $path, int $offset = 0, ?int $length = null): string
    {
        $uri = ($path !== '') ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';

        $headers = [];
        if ($length !== null) {
            $end = $offset + $length - 1;
            $headers['range'] = "bytes=$offset-$end";
        }
        $response = $this->call(Method::GET, $uri, headers: $headers, decode: false);

        if (! \is_string($response->body)) {
            throw new RemoteException('Unexpected S3 read response');
        }

        return $response->body;
    }

    /**
     * Write file by given path.
     *
     *
     * @throws StorageException
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';

        $this->call(
            Method::PUT,
            $uri,
            $data,
            headers: ['content-type' => $contentType],
            amzHeaders: ['x-amz-acl' => $this->acl->value],
        );

        return true;
    }

    /**
     * Delete file in given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @throws StorageException
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        $uri = ($path !== '') ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';

        $this->call(Method::DELETE, $uri);

        return true;
    }

    /**
     * Get list of objects in the given path.
     *
     * @return array<mixed>
     *
     * @throws StorageException
     */
    protected function listObjects(string $prefix = '', int $maxKeys = self::MAX_PAGE_SIZE, string $continuationToken = ''): array
    {
        if ($maxKeys > self::MAX_PAGE_SIZE) {
            throw new \InvalidArgumentException('Cannot list more than ' . self::MAX_PAGE_SIZE . ' objects');
        }

        $uri = '/';
        $prefix = ltrim($prefix, '/'); /** S3 specific requirement that prefix should never contain a leading slash */

        $parameters = [
            'list-type' => 2,
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
        ];

        if ($continuationToken !== '' && $continuationToken !== '0') {
            $parameters['continuation-token'] = $continuationToken;
        }

        $response = $this->call(Method::GET, $uri, '', $parameters, headers: ['content-type' => 'text/plain']);

        if (! \is_array($response->body)) {
            throw new RemoteException('Unexpected S3 list response');
        }

        return $response->body;
    }

    /**
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     *
     * @throws StorageException
     */
    public function deletePath(string $path): bool
    {
        $path = $this->getRoot() . '/' . $path;

        $uri = '/';
        $continuationToken = '';
        do {
            $objects = $this->listObjects($path, continuationToken: $continuationToken);
            $token = $objects['NextContinuationToken'] ?? '';
            $continuationToken = \is_string($token) ? $token : '';

            // A single object is returned as one associative entry, multiple objects as a list of them.
            $contents = $objects['Contents'] ?? [];
            $entries = \is_array($contents) ? (isset($contents['Key']) ? [$contents] : $contents) : [];

            $keys = [];
            foreach ($entries as $object) {
                $key = \is_array($object) ? ($object['Key'] ?? null) : null;
                if (\is_string($key)) {
                    $keys[] = $key;
                }
            }

            if ($keys === []) {
                break;
            }

            $body = '<Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/">';
            foreach ($keys as $key) {
                $body .= "<Object><Key>{$key}</Key></Object>";
            }
            $body .= '<Quiet>true</Quiet>';
            $body .= '</Delete>';
            $this->call(Method::POST, $uri, $body, ['delete' => ''], headers: ['content-type' => 'application/xml']);
        } while ($continuationToken !== '');

        return true;
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        try {
            $this->getInfo($path);
        } catch (NotFoundException) {
            return false;
        }

        return true;
    }

    /**
     * Returns given file path its size.
     *
     * @see http://php.net/manual/en/function.filesize.php
     */
    public function getFileSize(string $path): int
    {
        $response = $this->getInfo($path);

        return (int) ($response['content-length'] ?? 0);
    }

    /**
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
     */
    public function getFileMimeType(string $path): string
    {
        $response = $this->getInfo($path);

        return $response['content-type'] ?? '';
    }

    /**
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
     */
    public function getFileHash(string $path): string
    {
        $etag = $this->getInfo($path)['etag'] ?? '';

        return $etag === '' ? $etag : substr($etag, 1, -1);
    }

    /**
     * List objects under the given prefix, one page at a time.
     *
     * The cursor is the S3 continuation token.
     *
     * @throws StorageException
     */
    public function listFiles(string $prefix = '', int $max = self::MAX_PAGE_SIZE, ?string $cursor = null): FileList
    {
        $data = $this->listObjects($prefix, $max, $cursor ?? '');

        // A single object is returned as one associative entry, multiple objects as a list of them.
        $contents = $data['Contents'] ?? [];
        $entries = \is_array($contents) ? (isset($contents['Key']) ? [$contents] : $contents) : [];

        $files = [];
        foreach ($entries as $object) {
            if (! \is_array($object)) {
                continue;
            }
            if (! \is_string($object['Key'] ?? null)) {
                continue;
            }
            $size = $object['Size'] ?? null;
            $modified = $object['LastModified'] ?? null;
            $etag = $object['ETag'] ?? null;
            $files[] = new FileInfo(
                path: $object['Key'],
                size: is_numeric($size) ? (int) $size : 0,
                modifiedAt: \is_string($modified) ? new \DateTimeImmutable($modified) : null,
                etag: \is_string($etag) ? trim($etag, '"') : null,
            );
        }

        $token = $data['NextContinuationToken'] ?? null;

        return new FileList(
            files: $files,
            cursor: ($data['IsTruncated'] ?? null) === 'true' && \is_string($token) ? $token : null,
        );
    }

    /**
     * Get file info
     *
     * @return array<string, string>
     *
     * @throws StorageException
     */
    private function getInfo(string $path): array
    {
        $uri = $path !== '' ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';
        $response = $this->call(Method::HEAD, $uri);

        return $response->headers;
    }

    /**
     * Generate the headers for AWS Signature V4
     *
     * @param  non-empty-string  $method
     * @param  array<string, int|string>  $parameters
     * @param  array<string, string>  $headers
     * @param  array<string, string>  $amzHeaders
     */
    private function getSignatureV4(string $method, string $uri, array $parameters, array $headers, array $amzHeaders): string
    {
        $service = 's3';
        $region = $this->region;

        $algorithm = 'AWS4-HMAC-SHA256';
        $combinedHeaders = [];

        $amzDateStamp = substr($amzHeaders['x-amz-date'] ?? '', 0, 8);

        // CanonicalHeaders
        foreach ($headers as $k => $v) {
            $combinedHeaders[strtolower($k)] = trim($v);
        }

        foreach ($amzHeaders as $k => $v) {
            $combinedHeaders[strtolower($k)] = trim($v);
        }

        uksort($combinedHeaders, $this->sortMetaHeadersCmp(...));

        // Convert null query string parameters to strings and sort
        uksort($parameters, $this->sortMetaHeadersCmp(...));
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        // Payload
        $amzPayload = [$method];

        $qsPos = strpos($uri, '?');
        $amzPayload[] = ($qsPos === false ? $uri : substr($uri, 0, $qsPos));

        $amzPayload[] = $queryString;

        foreach ($combinedHeaders as $k => $v) { // add header as string to requests
            $amzPayload[] = $k . ':' . $v;
        }

        $amzPayload[] = ''; // add a blank entry so we end up with an extra line break
        $amzPayload[] = implode(';', array_keys($combinedHeaders)); // SignedHeaders
        $amzPayload[] = $amzHeaders['x-amz-content-sha256'] ?? ''; // payload hash

        $amzPayloadStr = implode("\n", $amzPayload); // request as string

        // CredentialScope
        $credentialScope = [$amzDateStamp, $region, $service, 'aws4_request'];

        // stringToSign
        $stringToSignStr = implode("\n", [
            $algorithm,
            $amzHeaders['x-amz-date'] ?? '',
            implode('/', $credentialScope),
            hash('sha256', $amzPayloadStr),
        ]);

        // Make Signature
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $amzDateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', mb_convert_encoding($stringToSignStr, 'utf-8'), $kSigning);

        return $algorithm . ' ' . implode(',', [
            'Credential=' . $this->accessKey . '/' . implode('/', $credentialScope),
            'SignedHeaders=' . implode(';', array_keys($combinedHeaders)),
            'Signature=' . $signature,
        ]);
    }

    /**
     * Execute a signed S3 request and return its response.
     *
     * Headers are built per call — the instance holds no request state, so a
     * single device is safe to share across concurrent coroutines.
     *
     * @param  non-empty-string  $method
     * @param  array<string, int|string>  $parameters
     * @param  array<string, string>  $headers
     * @param  array<string, string>  $amzHeaders
     *
     * @throws StorageException
     */
    protected function call(string $method, string $uri, string $data = '', array $parameters = [], array $headers = [], array $amzHeaders = [], bool $decode = true): S3\Response
    {
        $uri = $this->getAbsolutePath($uri);
        $url = $this->fqdn . $uri . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        $headers = array_filter($headers, fn(string $value): bool => $value !== '');
        $headers['host'] = $this->host;
        $headers['date'] = gmdate('D, d M Y H:i:s T');
        $headers['content-md5'] = base64_encode(md5($data, true));

        $amzHeaders = array_filter($amzHeaders, fn(string $value): bool => $value !== '');
        $amzHeaders['x-amz-date'] = gmdate('Ymd\THis\Z');
        $amzHeaders['x-amz-content-sha256'] = hash('sha256', $data);

        $request = new Request($method, Uri::parse($url), new Stream($data));
        foreach ([...$amzHeaders, ...$headers] as $header => $value) {
            $request = $request->withHeader($header, $value);
        }
        $request = $request
            ->withHeader('authorization', $this->getSignatureV4($method, $uri, $parameters, $headers, $amzHeaders))
            ->withHeader('user-agent', 'utopia-php/storage');

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException($e->getMessage(), $e->getCode(), $e);
        }

        $code = $response->getStatusCode();
        $responseBody = (string) $response->getBody();

        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            $responseHeaders[strtolower((string) $name)] = implode(', ', $values);
        }

        if ($code >= 400) {
            $this->parseAndThrowS3Error($responseBody, $code, $responseHeaders);
        }

        $contentType = $responseHeaders['content-type'] ?? '';
        $isXml = $contentType === 'application/xml' || (str_starts_with($responseBody, '<?xml') && $contentType !== 'image/svg+xml');

        return new S3\Response(
            code: $code,
            headers: $responseHeaders,
            body: $decode && $isXml ? $this->decodeXml($responseBody) : $responseBody,
        );
    }

    /**
     * Decode an XML response body into an associative array.
     *
     * @return array<mixed>
     *
     * @throws StorageException
     */
    private function decodeXml(string $body): array
    {
        $xml = @simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        $decoded = $xml === false ? null : $this->xmlToArray($xml);
        if (! \is_array($decoded)) {
            throw new RemoteException('Failed to decode S3 XML response');
        }

        return $decoded;
    }

    /**
     * Convert an XML element into nested arrays: repeated child names become
     * lists, text-only elements become strings and empty elements become
     * empty arrays.
     *
     * @return array<mixed>|string
     */
    private function xmlToArray(\SimpleXMLElement $element): array|string
    {
        $children = $element->children();
        if (\count($children) === 0) {
            $text = (string) $element;

            return $text === '' ? [] : $text;
        }

        $grouped = [];
        foreach ($children as $name => $child) {
            $grouped[$name][] = $this->xmlToArray($child);
        }

        $result = [];
        foreach ($grouped as $name => $values) {
            $result[$name] = \count($values) === 1 ? $values[0] : $values;
        }

        return $result;
    }

    /**
     * Parse an S3 error response and throw the matching exception.
     *
     * @param  string  $errorBody  The error response body
     * @param  int  $statusCode  The HTTP status code
     * @param  array<string, string>  $headers  The response headers, for the S3 request IDs provider support asks for
     *
     * @throws NotFoundException When the object does not exist (404, or a NoSuchKey error code)
     * @throws RemoteException For every other error response
     */
    private function parseAndThrowS3Error(string $errorBody, int $statusCode, array $headers = []): never
    {
        $errorCode = null;
        $errorMessage = null;
        if (str_starts_with(ltrim($errorBody), '<?xml') || str_starts_with(ltrim($errorBody), '<Error')) {
            $xml = @simplexml_load_string($errorBody, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if ($xml !== false) {
                $errorCode = (string) ($xml->Code ?? '') ?: null;
                $errorMessage = (string) ($xml->Message ?? '') ?: null;
            }
        }

        $requestIds = array_filter([
            'request-id' => $headers['x-amz-request-id'] ?? '',
            'id-2' => $headers['x-amz-id-2'] ?? '',
        ]);
        $suffix = $requestIds === [] ? '' : ' [' . implode(', ', array_map(
            fn(string $key, string $value): string => "$key: $value",
            array_keys($requestIds),
            $requestIds,
        )) . ']';

        // HEAD error responses carry no body, so the status code is the only signal.
        if ($statusCode === 404 || $errorCode === 'NoSuchKey') {
            throw new NotFoundException(($errorMessage ?? 'File not found') . $suffix, $statusCode);
        }

        throw new RemoteException(($errorMessage ?? ($errorBody !== '' ? $errorBody : 'S3 request failed')) . $suffix, $statusCode, $errorCode);
    }

    /**
     * Sort compare for meta headers
     *
     * @internal Used to sort x-amz meta headers
     *
     * @param  string  $a  String A
     * @param  string  $b  String B
     */
    protected function sortMetaHeadersCmp(string $a, string $b): int
    {
        $lenA = \strlen($a);
        $lenB = \strlen($b);
        $minLen = min($lenA, $lenB);
        $ncmp = strncmp($a, $b, $minLen);
        if ($lenA === $lenB) {
            return $ncmp;
        }

        if ($ncmp === 0) {
            return $lenA < $lenB ? -1 : 1;
        }

        return $ncmp;
    }
}

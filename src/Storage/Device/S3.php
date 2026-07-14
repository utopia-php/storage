<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;
use Utopia\Storage\Exception\NotFoundException;
use Utopia\Storage\Storage;

class S3 extends Device
{
    public const METHOD_GET = 'GET';

    public const METHOD_POST = 'POST';

    public const METHOD_PUT = 'PUT';

    public const METHOD_PATCH = 'PATCH';

    public const METHOD_DELETE = 'DELETE';

    public const METHOD_HEAD = 'HEAD';

    public const METHOD_OPTIONS = 'OPTIONS';

    public const METHOD_CONNECT = 'CONNECT';

    public const METHOD_TRACE = 'TRACE';

    public const HTTP_VERSION_1_1 = CURL_HTTP_VERSION_1_1;

    public const HTTP_VERSION_2_0 = CURL_HTTP_VERSION_2_0;

    public const HTTP_VERSION_2 = CURL_HTTP_VERSION_2;

    public const HTTP_VERSION_1_0 = CURL_HTTP_VERSION_1_0;

    /**
     * AWS ACL Flag constants
     */
    public const ACL_PRIVATE = 'private';

    public const ACL_PUBLIC_READ = 'public-read';

    public const ACL_PUBLIC_READ_WRITE = 'public-read-write';

    public const ACL_AUTHENTICATED_READ = 'authenticated-read';

    protected const MAX_PAGE_SIZE = 1000;

    protected static int $retryAttempts = 3;

    protected static int $retryDelay = 500;

    protected array $headers = [
        'host' => '',
        'date' => '',
        'content-md5' => '',
        'content-type' => '',
    ];

    protected string $fqdn;

    protected array $amzHeaders = [];

    /**
     * Http version
     */
    protected ?int $curlHttpVersion = null;

    /**
     * S3 Constructor
     */
    public function __construct(protected string $root, protected string $accessKey, protected string $secretKey, string $host, protected string $region, protected string $acl = self::ACL_PRIVATE)
    {
        parent::__construct();

        if (str_starts_with($host, 'http://') || str_starts_with($host, 'https://')) {
            $this->fqdn = $host;
            $this->headers['host'] = str_replace(['http://', 'https://'], '', $host);
        } else {
            $this->fqdn = 'https://' . $host;
            $this->headers['host'] = $host;
        }
    }

    public function getName(): string
    {
        return 'S3 Storage';
    }

    public function getType(): string
    {
        return Storage::DEVICE_S3;
    }

    public function getDescription(): string
    {
        return 'S3 Storage drive for generic S3-compatible provider';
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function getPath(string $filename, ?string $prefix = null): string
    {
        return $this->getRoot() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Set http version
     */
    public function setHttpVersion(?int $httpVersion): self
    {
        $this->curlHttpVersion = $httpVersion;

        return $this;
    }

    /**
     * Set retry attempts
     */
    public static function setRetryAttempts(int $attempts): void
    {
        self::$retryAttempts = $attempts;
    }

    /**
     * Set retry delay in milliseconds
     */
    public static function setRetryDelay(int $delay): void
    {
        self::$retryDelay = $delay;
    }

    /**
     * Upload.
     *
     * Upload a file to desired destination in the selected disk.
     * return number of chunks uploaded or 0 if it fails.
     *
     *
     * @throws Exception
     */
    public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        $contentType = mime_content_type($source) ?: '';
        $this->prepareUpload($path, $contentType, $chunks, $metadata);
        $chunksReceived = $this->uploadChunk($source, $path, $chunk, $chunks, $metadata);

        if ($chunks > 1 && $chunks === $chunksReceived && ! $this->finalizeUpload($path, $chunks, $metadata)) {
            throw new Exception('Failed to finalize upload ' . $path);
        }

        return $chunksReceived;
    }

    public function prepareUpload(string $path, string $contentType, int $chunks = 1, array &$metadata = []): void
    {
        $metadata['parts'] ??= [];
        $metadata['chunks'] ??= 0;
        $metadata['content_type'] ??= $contentType;

        if ($chunks === 1 || ! empty($metadata['uploadId'])) {
            return;
        }

        $metadata['uploadId'] = $this->createMultipartUpload($path, $contentType);
    }

    public function uploadChunk(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        $data = file_get_contents($source);
        if ($data === false) {
            throw new Exception('Can\'t read file ' . $source);
        }

        return $this->uploadChunkData($data, $path, $metadata['content_type'] ?? (mime_content_type($source) ?: ''), $chunk, $chunks, $metadata);
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
            throw new Exception('Missing multipart upload ID');
        }

        $metadata['parts'] ??= [];
        for ($i = 1; $i <= $chunks; $i++) {
            if (! \array_key_exists($i, $metadata['parts'])) {
                throw new Exception('Missing chunk ' . $i);
            }
        }

        $this->completeMultipartUpload($path, $metadata['uploadId'], $metadata['parts']);

        return true;
    }

    /**
     * Upload Data.
     *
     * Upload file contents to desired destination in the selected disk.
     * return number of chunks uploaded or 0 if it fails.
     *
     *
     * @throws Exception
     */
    public function uploadData(string $data, string $path, string $contentType, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        $this->prepareUpload($path, $contentType, $chunks, $metadata);
        $chunksReceived = $this->uploadChunkData($data, $path, $contentType, $chunk, $chunks, $metadata);

        if ($chunks > 1 && $chunks === $chunksReceived && ! $this->finalizeUpload($path, $chunks, $metadata)) {
            throw new Exception('Failed to finalize upload ' . $path);
        }

        return $chunksReceived;
    }

    private function uploadChunkData(string $data, string $path, string $contentType, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        if ($chunk === 1 && $chunks === 1) {
            $this->write($path, $data, $contentType);
            $metadata['parts'][$chunk] = true;
            $metadata['chunks'] = 1;

            return 1;
        }

        if (empty($metadata['uploadId'])) {
            throw new Exception('Missing multipart upload ID');
        }

        $metadata['parts'] ??= [];
        $metadata['chunks'] ??= 0;

        $etag = $this->uploadPart($data, $path, $contentType, $chunk, $metadata['uploadId']);
        // skip incrementing if the chunk was re-uploaded
        if (! \array_key_exists($chunk, $metadata['parts'])) {
            $metadata['chunks']++;
        }
        $metadata['parts'][$chunk] = $etag;

        return $metadata['chunks'];
    }

    /**
     * Transfer
     */
    public function transfer(string $path, string $destination, Device $device): bool
    {
        $response = [];
        try {
            $response = $this->getInfo($path);
        } catch (\Throwable) {
            throw new NotFoundException('File not found');
        }
        $size = (int) ($response['content-length'] ?? 0);
        $contentType = $response['content-type'] ?? '';

        if ($size <= $this->transferChunkSize) {
            $source = $this->read($path);

            return $device->write($destination, $source, $contentType);
        }

        $totalChunks = (int) ceil($size / $this->transferChunkSize);
        $metadata = ['content_type' => $contentType];
        for ($counter = 0; $counter < $totalChunks; $counter++) {
            $start = $counter * $this->transferChunkSize;
            $data = $this->read($path, $start, $this->transferChunkSize);
            $device->uploadData($data, $destination, $contentType, $counter + 1, $totalChunks, $metadata);
        }

        return true;
    }

    /**
     * Start Multipart Upload
     *
     * Initiate a multipart upload and return an upload ID.
     *
     *
     * @throws Exception
     */
    protected function createMultipartUpload(string $path, string $contentType): string
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';

        $this->headers['content-md5'] = base64_encode(md5('', true));
        unset($this->amzHeaders['x-amz-content-sha256']);
        $this->headers['content-type'] = $contentType;
        $this->amzHeaders['x-amz-acl'] = $this->acl;
        $response = $this->call('s3:createMultipartUpload', self::METHOD_POST, $uri, '', ['uploads' => '']);

        return $response->body['UploadId'];
    }

    /**
     * Upload Part
     *
     *
     * @throws Exception
     */
    protected function uploadPart(string $data, string $path, string $contentType, int $chunk, string $uploadId): string
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';

        $this->headers['content-type'] = $contentType;
        $this->headers['content-md5'] = base64_encode(md5($data, true));
        $this->amzHeaders['x-amz-content-sha256'] = hash('sha256', $data);
        unset($this->amzHeaders['x-amz-acl']); // ACL header is not allowed in parts, only createMultipartUpload accepts this header.

        $response = $this->call('s3:uploadPart', self::METHOD_PUT, $uri, $data, [
            'partNumber' => $chunk,
            'uploadId' => $uploadId,
        ]);

        return $response->headers['etag'];
    }

    /**
     * Complete Multipart Upload
     *
     *
     * @throws Exception
     */
    protected function completeMultipartUpload(string $path, string $uploadId, array $parts): bool
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';

        ksort($parts, SORT_NUMERIC);

        $body = '<CompleteMultipartUpload>';
        foreach ($parts as $key => $etag) {
            $body .= "<Part><ETag>{$etag}</ETag><PartNumber>{$key}</PartNumber></Part>";
        }
        $body .= '</CompleteMultipartUpload>';

        $this->headers['content-type'] = 'application/xml';
        $this->amzHeaders['x-amz-content-sha256'] = hash('sha256', $body);
        $this->headers['content-md5'] = base64_encode(md5($body, true));
        $this->call('s3:completeMultipartUpload', self::METHOD_POST, $uri, $body, ['uploadId' => $uploadId]);

        return true;
    }

    /**
     * Abort Chunked Upload
     *
     *
     * @throws Exception
     */
    public function abort(string $path, string $extra = ''): bool
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';
        unset($this->headers['content-type']);
        $this->headers['content-md5'] = base64_encode(md5('', true));
        $this->call('s3:abort', self::METHOD_DELETE, $uri, '', ['uploadId' => $extra]);

        return true;
    }

    /**
     * Read file or part of file by given path, offset and length.
     *
     *
     * @throws Exception
     */
    public function read(string $path, int $offset = 0, ?int $length = null): string
    {
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        unset($this->headers['content-type']);
        $this->headers['content-md5'] = base64_encode(md5('', true));
        $uri = ($path !== '') ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';
        if ($length !== null) {
            $end = $offset + $length - 1;
            $this->headers['range'] = "bytes=$offset-$end";
        }
        $response = $this->call('s3:read', self::METHOD_GET, $uri, decode: false);

        return $response->body;
    }

    /**
     * Write file by given path.
     *
     *
     * @throws Exception
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        $uri = $path !== '' ? '/' . str_replace(['%2F', '%3F'], ['/', '?'], rawurlencode($path)) : '/';

        $this->headers['content-type'] = $contentType;
        $this->headers['content-md5'] = base64_encode(md5($data, true)); // TODO whould this work well with big file? can we skip it?
        $this->amzHeaders['x-amz-content-sha256'] = hash('sha256', $data);
        $this->amzHeaders['x-amz-acl'] = $this->acl;

        $this->call('s3:write', self::METHOD_PUT, $uri, $data);

        return true;
    }

    /**
     * Delete file in given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @throws Exception
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        $uri = ($path !== '') ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';

        unset($this->headers['content-type']);
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        $this->headers['content-md5'] = base64_encode(md5('', true));
        $this->call('s3:delete', self::METHOD_DELETE, $uri);

        return true;
    }

    /**
     * Get list of objects in the given path.
     *
     *
     * @throws Exception
     */
    protected function listObjects(string $prefix = '', int $maxKeys = self::MAX_PAGE_SIZE, string $continuationToken = ''): array
    {
        if ($maxKeys > self::MAX_PAGE_SIZE) {
            throw new Exception('Cannot list more than ' . self::MAX_PAGE_SIZE . ' objects');
        }

        $uri = '/';
        $prefix = ltrim($prefix, '/'); /** S3 specific requirement that prefix should never contain a leading slash */
        $this->headers['content-type'] = 'text/plain';
        $this->headers['content-md5'] = base64_encode(md5('', true));

        unset($this->amzHeaders['x-amz-content-sha256']);
        unset($this->amzHeaders['x-amz-acl']);

        $parameters = [
            'list-type' => 2,
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
        ];

        if ($continuationToken !== '' && $continuationToken !== '0') {
            $parameters['continuation-token'] = $continuationToken;
        }

        $response = $this->call('s3:list', self::METHOD_GET, $uri, '', $parameters);

        return $response->body;
    }

    /**
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     *
     * @throws Exception
     */
    public function deletePath(string $path): bool
    {
        $path = $this->getRoot() . '/' . $path;

        $uri = '/';
        $continuationToken = '';
        do {
            $objects = $this->listObjects($path, continuationToken: $continuationToken);
            $count = (int) ($objects['KeyCount'] ?? 1);
            if ($count < 1) {
                break;
            }
            $continuationToken = $objects['NextContinuationToken'] ?? '';
            $body = '<Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/">';
            if ($count > 1) {
                foreach ($objects['Contents'] as $object) {
                    $body .= "<Object><Key>{$object['Key']}</Key></Object>";
                }
            } else {
                $body .= "<Object><Key>{$objects['Contents']['Key']}</Key></Object>";
            }
            $body .= '<Quiet>true</Quiet>';
            $body .= '</Delete>';
            $this->amzHeaders['x-amz-content-sha256'] = hash('sha256', $body);
            $this->headers['content-md5'] = base64_encode(md5($body, true));
            $this->call('s3:deletePath', self::METHOD_POST, $uri, $body, ['delete' => '']);
        } while (! empty($continuationToken));

        return true;
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        try {
            $this->getInfo($path);
        } catch (\Throwable) {
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

        return (empty($etag)) ? $etag : substr((string) $etag, 1, -1);
    }

    /**
     * Create a directory at the specified path.
     *
     * Returns true on success or if the directory already exists and false on error
     */
    public function createDirectory(string $path): bool
    {
        /* S3 is an object store and does not have the concept of directories */
        return true;
    }

    /**
     * Get directory size in bytes.
     *
     * Return -1 on error
     *
     * Based on http://www.jonasjohn.de/snippets/php/dir-size.htm
     */
    public function getDirectorySize(string $path): int
    {
        return -1;
    }

    /**
     * Get Partition Free Space.
     *
     * disk_free_space — Returns available space on filesystem or disk partition
     */
    public function getPartitionFreeSpace(): float
    {
        return -1;
    }

    /**
     * Get Partition Total Space.
     *
     * disk_total_space — Returns the total size of a filesystem or disk partition
     */
    public function getPartitionTotalSpace(): float
    {
        return -1;
    }

    /**
     * Get all files and directories inside a directory.
     *
     * @param  string  $dir  Directory to scan
     * @return array<mixed>
     *
     * @throws Exception
     */
    public function getFiles(string $dir, int $max = self::MAX_PAGE_SIZE, string $continuationToken = ''): array
    {
        $data = $this->listObjects($dir, $max, $continuationToken);

        // Set to false if all the results were returned. Set to true if more keys are available to return.
        $data['IsTruncated'] = $data['IsTruncated'] === 'true';

        // KeyCount is the number of keys returned with this request.
        $data['KeyCount'] = \intval($data['KeyCount']);

        // Sets the maximum number of keys returned to the response. By default, the action returns up to 1,000 key names.
        $data['MaxKeys'] = \intval($data['MaxKeys']);

        return $data;
    }

    /**
     * Get file info
     *
     *
     * @throws Exception
     */
    private function getInfo(string $path): array
    {
        unset($this->headers['content-type']);
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        $this->headers['content-md5'] = base64_encode(md5('', true));
        $uri = $path !== '' ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';
        $response = $this->call('s3:info', self::METHOD_HEAD, $uri);

        return $response->headers;
    }

    /**
     * Generate the headers for AWS Signature V4
     *
     */
    private function getSignatureV4(string $method, string $uri, array $parameters = []): string
    {
        $service = 's3';
        $region = $this->region;

        $algorithm = 'AWS4-HMAC-SHA256';
        $combinedHeaders = [];

        $amzDateStamp = substr((string) $this->amzHeaders['x-amz-date'], 0, 8);

        // CanonicalHeaders
        foreach ($this->headers as $k => $v) {
            $combinedHeaders[strtolower((string) $k)] = trim((string) $v);
        }

        foreach ($this->amzHeaders as $k => $v) {
            $combinedHeaders[strtolower((string) $k)] = trim((string) $v);
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
        $amzPayload[] = $this->amzHeaders['x-amz-content-sha256']; // payload hash

        $amzPayloadStr = implode("\n", $amzPayload); // request as string

        // CredentialScope
        $credentialScope = [$amzDateStamp, $region, $service, 'aws4_request'];

        // stringToSign
        $stringToSignStr = implode("\n", [
            $algorithm,
            $this->amzHeaders['x-amz-date'],
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
     * Get the S3 response
     *
     *
     * @throws Exception
     */
    protected function call(string $operation, string $method, string $uri, string $data = '', array $parameters = [], bool $decode = true): \stdClass
    {
        $startTime = microtime(true);

        $uri = $this->getAbsolutePath($uri);
        $url = $this->fqdn . $uri . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $response = new \stdClass();
        $response->body = '';
        $response->headers = [];

        // Basic setup
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERAGENT, 'utopia-php/storage');
        curl_setopt($curl, CURLOPT_URL, $url);

        // Headers
        $httpHeaders = [];
        $this->amzHeaders['x-amz-date'] = gmdate('Ymd\THis\Z');

        if (! isset($this->amzHeaders['x-amz-content-sha256'])) {
            $this->amzHeaders['x-amz-content-sha256'] = hash('sha256', $data);
        }

        foreach ($this->amzHeaders as $header => $value) {
            if ((string) $value !== '') {
                $httpHeaders[] = $header . ': ' . $value;
            }
        }

        $this->headers['date'] = gmdate('D, d M Y H:i:s T');

        foreach ($this->headers as $header => $value) {
            if ((string) $value !== '') {
                $httpHeaders[] = $header . ': ' . $value;
            }
        }

        $httpHeaders[] = 'Authorization: ' . $this->getSignatureV4($method, $uri, $parameters);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);

        if ($this->curlHttpVersion != null) {
            curl_setopt($curl, CURLOPT_HTTP_VERSION, $this->curlHttpVersion);
        }

        curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, string $data) use ($response): int {
            $response->body .= $data;

            return \strlen($data);
        });
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, string $header) use (&$response): int {
            $len = \strlen($header);
            $header = explode(':', $header, 2);

            if (\count($header) < 2) { // ignore invalid headers
                return $len;
            }

            $response->headers[strtolower(trim($header[0]))] = trim($header[1]);

            return $len;
        });
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        // Request types
        switch ($method) {
            case self::METHOD_PUT:
            case self::METHOD_POST: // POST only used for CloudFront
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case self::METHOD_HEAD:
            case self::METHOD_DELETE:
                curl_setopt($curl, CURLOPT_NOBODY, true);
                break;
        }

        $result = curl_exec($curl);

        $response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $attempt = 0;
        while (
            $attempt < self::$retryAttempts
            && $this->isTransientError($response->code, $response->body)
        ) {
            usleep(self::$retryDelay * 1000);
            $attempt++;
            $result = curl_exec($curl);
            $response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }

        try {
            if (! $result) {
                throw new Exception(curl_error($curl));
            }

            if ($response->code >= 400) {
                $this->parseAndThrowS3Error($response->body, $response->code);
            }

            // Parse body into XML
            if ($decode && ((isset($response->headers['content-type']) && $response->headers['content-type'] == 'application/xml') || (str_starts_with((string) $response->body, '<?xml') && ($response->headers['content-type'] ?? '') !== 'image/svg+xml'))) {
                $response->body = simplexml_load_string((string) $response->body);
                $response->body = json_decode(json_encode($response->body), true);
            }

            return $response;
        } finally {

            $this->storageOperationTelemetry->record(
                microtime(true) - $startTime,
                [
                    'storage' => $this->getType(),
                    'operation' => $operation,
                    'attempts' => $attempt,
                ],
            );
        }
    }

    /**
     * Parse S3 XML error response and throw appropriate exception
     *
     * @param  string  $errorBody  The error response body
     * @param  int  $statusCode  The HTTP status code
     *
     * @throws NotFoundException When the error is NoSuchKey
     * @throws Exception For other S3 errors
     */
    private function parseAndThrowS3Error(string $errorBody, int $statusCode): void
    {
        if (str_starts_with($errorBody, '<?xml')) {
            try {
                $xml = simplexml_load_string($errorBody);
                $errorCode = (string) ($xml->Code ?? '');
                $errorMessage = (string) ($xml->Message ?? '');

                if ($errorCode === 'NoSuchKey') {
                    throw new NotFoundException($errorMessage ?: 'File not found', $statusCode);
                }
            } catch (NotFoundException $e) {
                throw $e;
            } catch (\Throwable) {
                // If XML parsing fails, fall through to original error
            }
        }

        throw new Exception($errorBody, $statusCode);
    }

    /**
     * Determine whether an S3 response indicates a transient rate-limiting error
     * (e.g. SlowDown, ServiceUnavailable) that should be retried with exponential backoff.
     *
     * The XML body is parsed first so that specific S3 error codes are detected regardless
     * of HTTP status. A 503/429 with a parseable but non-transient error code is NOT retried.
     * Unparseable 429/503 responses fall back to status-code detection.
     *
     * @param  int  $statusCode  HTTP response status code
     * @param  string  $body  Response body
     */
    protected function isTransientError(int $statusCode, string $body): bool
    {
        $trimmed = ltrim($body);
        if (str_starts_with($trimmed, '<?xml') || str_starts_with($trimmed, '<Error')) {
            $xml = @simplexml_load_string($body);
            if ($xml !== false) {
                $code = (string) ($xml->Code ?? '');
                if (\in_array($code, ['SlowDown', 'ServiceUnavailable', 'Throttling', 'RequestThrottled'], true)) {
                    return true;
                }
                // Successfully parsed XML with a non-transient error code — do not retry.
                if ($code !== '') {
                    return false;
                }
            }
        }

        // Fall back to HTTP status code for responses that cannot be parsed as XML.
        return $statusCode === 429 || $statusCode === 503;
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

<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;

class Generic extends Device
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_HEAD = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_CONNECT = 'CONNECT';
    const METHOD_TRACE = 'TRACE';


    /**
     * AWS ACL Flag constants
     */
    const ACL_PRIVATE = 'private';
    const ACL_PUBLIC_READ = 'public-read';
    const ACL_PUBLIC_READ_WRITE = 'public-read-write';
    const ACL_AUTHENTICATED_READ = 'authenticated-read';

    /**
     * @var string
     */
    protected string $accessKey;

    /**
     * @var string
     */
    protected string $secretKey;

    /**
     * @var string
     */
    protected string $bucket;

    /**
     * @var string
     */
    protected string $region;

    /**
     * @var string
     */
    protected string $acl = self::ACL_PRIVATE;

    /**
     * @var string
     */
    protected string $hostName;

    /**
     * @var string
     */
    protected string $root = 'temp';


    /**
     * S3 Constructor
     *
     * @param string $root
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $region
     * @param string $acl
     * @param string|null $hostName
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region, string $acl = self::ACL_PRIVATE, string $hostName = null)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->root = $root;
        $this->acl = $acl;
        $this->hostName = $hostName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'S3 compatible Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'S3 Generic Bucket Storage drive for AWS compatible solutions';
    }

    /**
     * @param string $filename
     * @param string|null $prefix
     *
     * @return string
     */
    public function getPath(string $filename, string $prefix = null): string
    {
        $path = '';

        for ($i = 0; $i < 4; ++$i) {
            $path = ($i < \strlen($filename)) ? $path . DIRECTORY_SEPARATOR . $filename[$i] : $path . DIRECTORY_SEPARATOR . 'x';
        }

        if (!is_null($prefix)) {
            $path = $prefix . DIRECTORY_SEPARATOR . $path;
        }

        return $this->getRoot() . $path . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Upload.
     *
     * Upload a file to desired destination in the selected disk.
     * return number of chunks uploaded or 0 if it fails.
     *
     * @param string $source
     * @param string $path
     * @param int chunk
     * @param int chunks
     * @param array $metadata
     *
     * @return int
     * @throws \Exception
     *
     */
    public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        if ($chunk == 1 && $chunks == 1) {
            return $this->write($path, \file_get_contents($source), \mime_content_type($source));
        }
        $uploadId = $metadata['uploadId'] ?? null;
        if (empty($uploadId)) {
            $uploadId = $this->createMultipartUpload($path, $metadata['content_type']);
            $metadata['uploadId'] = $uploadId;
        }

        $etag = $this->uploadPart($source, $path, $chunk, $uploadId);
        $metadata['parts'] ??= [];
        $metadata['parts'][] = ['partNumber' => $chunk, 'etag' => $etag];
        $metadata['chunks'] ??= 0;
        $metadata['chunks']++;
        if ($metadata['chunks'] == $chunks) {
            $this->completeMultipartUpload($path, $uploadId, $metadata['parts'], $source);
        }
        return $metadata['chunks'];
    }

    /**
     * Write file by given path.
     *
     * @param string $path
     * @param string $data
     *
     * @return bool
     * @throws \Exception
     *
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';

        $this->call(self::METHOD_PUT, $uri, $data, [], [
            'content-type' => $contentType,
            'content-md5' => \base64_encode(md5($data, true)),
            'x-amz-content-sha256' => \hash('sha256', $data),
            'x-amz-acl' => $this->acl
        ]);

        return true;
    }

    /**
     * Get the S3 response
     *
     * @param string $method
     * @param string $uri
     * @param string $data
     * @param array $parameters
     * @param array $headers
     *
     * @return  object
     * @throws \Exception
     *
     */
    private function call(string $method, string $uri, string $data = '', array $parameters = [], array $headers = [])
    {
        $url = 'https://' . $this->hostName . $uri . '?' . \http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $response = new \stdClass;
        $response->body = '';
        $response->headers = [];

        // Basic setup
        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_USERAGENT, 'utopia-php/storage');
        \curl_setopt($curl, CURLOPT_URL, $url);

        // Headers
        $httpHeaders = [];
        $headers['x-amz-date'] = \gmdate('Ymd\THis\Z');
        $headers['date'] = \gmdate('D, d M Y H:i:s T');
        $headers['host'] = $this->hostName;

        if (!isset($headers['x-amz-content-sha256'])) {
            $headers['x-amz-content-sha256'] = \hash('sha256', $data);
        }

        foreach ($headers as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header . ': ' . $value;
            }
        }

        $httpHeaders[] = 'Authorization: ' . $this->getSignatureV4($method, $uri, $parameters, $headers);

        \curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeaders);
        \curl_setopt($curl, CURLOPT_HEADER, false);
        \curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        \curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, string $data) use ($response) {
            $response->body .= $data;
            return \strlen($data);
        });
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, string $header) use (&$response) {
            $len = strlen($header);
            $header = explode(':', $header, 2);

            if (count($header) < 2) { // ignore invalid headers
                return $len;
            }

            $response->headers[strtolower(trim($header[0]))] = trim($header[1]);

            return $len;
        });
        \curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        // Request types
        switch ($method) {
            case self::METHOD_PUT:
            case self::METHOD_POST: // POST only used for CloudFront
                \curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case self::METHOD_HEAD:
            case self::METHOD_DELETE:
                \curl_setopt($curl, CURLOPT_NOBODY, true);
                break;
        }

        $result = \curl_exec($curl);

        if (!$result) {
            throw new Exception(\curl_error($curl));
        }

        $response->code = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($response->code >= 400) {
            throw new Exception($response->body, $response->code);
        }

        \curl_close($curl);

        // Parse body into XML
        if ((isset($response->headers['content-type']) && $response->headers['content-type'] == 'application/xml') || (str_starts_with($response->body, '<?xml') && ($response->headers['content-type'] ?? '') !== 'image/svg+xml')) {
            $response->body = \simplexml_load_string($response->body);
            $response->body = json_decode(json_encode($response->body), true);
        }

        return $response;
    }

    /**
     * Generate the headers for AWS Signature V4
     * @param string $method
     * @param string $uri
     * @param array parameters
     * @param array headers
     *
     * @return string
     */
    private function getSignatureV4(string $method, string $uri, array $parameters = [], $headers = []): string
    {
        $service = 's3';
        $region = $this->region;
        $algorithm = 'AWS4-HMAC-SHA256';
        $combinedHeaders = [];
        $amzDateStamp = \substr($headers['x-amz-date'], 0, 8);

        // CanonicalHeaders
        foreach ($headers as $k => $v) {
            $combinedHeaders[\strtolower($k)] = \trim($v);
        }

        uksort($combinedHeaders, [& $this, 'sortMetaHeadersCmp']);

        // Convert null query string parameters to strings and sort
        uksort($parameters, [& $this, 'sortMetaHeadersCmp']);
        $queryString = \http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        // Payload
        $amzPayload = [$method];

        $qsPos = \strpos($uri, '?');
        $amzPayload[] = ($qsPos === false ? $uri : \substr($uri, 0, $qsPos));
        $amzPayload[] = $queryString;

        foreach ($combinedHeaders as $k => $v) { // add header as string to requests
            $amzPayload[] = $k . ':' . $v;
        }

        $amzPayload[] = ''; // add a blank entry so we end up with an extra line break
        $amzPayload[] = \implode(';', \array_keys($combinedHeaders)); // SignedHeaders
        $amzPayload[] = $headers['x-amz-content-sha256']; // payload hash

        $amzPayloadStr = \implode("\n", $amzPayload); // request as string

        // CredentialScope
        $credentialScope = [$amzDateStamp, $region, $service, 'aws4_request'];

        // stringToSign
        $stringToSignStr = \implode("\n", [$algorithm, $headers['x-amz-date'],
            \implode('/', $credentialScope), \hash('sha256', $amzPayloadStr)]);

        // Make Signature
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = \hash_hmac('sha256', $amzDateStamp, $kSecret, true);
        $kRegion = \hash_hmac('sha256', $region, $kDate, true);
        $kService = \hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = \hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = \hash_hmac('sha256', \utf8_encode($stringToSignStr), $kSigning);

        return $algorithm . ' ' . \implode(',', [
                'Credential=' . $this->accessKey . '/' . \implode('/', $credentialScope),
                'SignedHeaders=' . \implode(';', \array_keys($combinedHeaders)),
                'Signature=' . $signature,
            ]);
    }

    /**
     * Start Multipart Upload
     *
     * Initiate a multipart upload and return an upload ID.
     *
     * @param string $path
     * @param string $contentType
     *
     * @return string
     * @throws \Exception
     *
     */
    protected function createMultipartUpload(string $path, string $contentType): string
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';
        $response = $this->call(self::METHOD_POST, $uri, '', ['uploads' => ''], [
            'content-type' => $contentType,
            'content-md5' => \base64_encode(md5('', true)),
            'x-amz-acl' => $this->acl
        ]);
        return $response->body['UploadId'];
    }

    /**
     * Upload Part
     *
     * @param string $source
     * @param string $path
     * @param int $chunk
     * @param string $uploadId
     *
     * @return string
     * @throws \Exception
     *
     */
    protected function uploadPart(string $source, string $path, int $chunk, string $uploadId): string
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';
        $data = \file_get_contents($source);
        $response = $this->call(self::METHOD_PUT, $uri, $data, [
            'partNumber' => $chunk,
            'uploadId' => $uploadId
        ], [
            'content-type' => \mime_content_type($source),
            'content-md5' => \base64_encode(md5($data, true)),
            'x-amz-content-sha256' => \hash('sha256', $data)
        ]);

        return $response->headers['etag'];
    }

    /**
     * Complete Multipart Upload
     *
     * @param string $path
     * @param string $uploadId
     * @param array $parts
     *
     * @return bool
     * @throws \Exception
     *
     */
    protected function completeMultipartUpload(string $path, string $uploadId, array $parts, $source): bool
    {

        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';

        $body = '<CompleteMultipartUpload>';
        foreach ($parts as $part) {
            $body .= "<Part><ETag>{$part['etag']}</ETag><PartNumber>{$part['partNumber']}</PartNumber></Part>";
        }
        $body .= '</CompleteMultipartUpload>';

        $this->call(self::METHOD_POST, $uri, $body, [
            'uploadId' => $uploadId
        ], [
            'content-md5' => \base64_encode(md5($body, true)),
            'content-type' => \mime_content_type($source),
            'x-amz-content-sha256' => \hash('sha256', $body)
        ]);

        return true;
    }

    /**
     * Abort Chunked Upload
     *
     * @param string $path
     * @param string $extra
     *
     * @return bool
     * @throws \Exception
     *
     */
    public function abort(string $path, string $extra = ''): bool
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';
        $this->call(self::METHOD_DELETE, $uri, '', [
            'uploadId' => $extra
        ], [
            'content-md5' => \base64_encode(md5('', true))
        ]);
        return true;
    }

    /**
     * Move file from given source to given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $source
     * @param string $target
     *
     * @throw \Exception
     *
     * @return bool
     */
    public function move(string $source, string $target): bool
    {
        $type = $this->getFileMimeType($source);

        if ($this->write($target, $this->read($source), $type)) {
            $this->delete($source);
        }

        return true;
    }

    /**
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
     *
     * @param string $path
     *
     * @return string
     */
    public function getFileMimeType(string $path): string
    {
        $response = $this->getInfo($path);
        return $response['content-type'] ?? '';
    }

    /**
     * Get file info
     * @return array
     */
    private function getInfo(string $path): array
    {
        $uri = $path !== '' ? '/' . \str_replace('%2F', '/', \rawurlencode($path)) : '/';

        $response = $this->call(self::METHOD_HEAD, $uri, '', [], ['content-md5' => \base64_encode(md5('', true))]);

        return $response->headers;
    }

    /**
     * Read file or part of file by given path, offset and length.
     *
     * @param string $path
     * @param int offset
     * @param int length
     *
     * @return string
     * @throws \Exception
     *
     */
    public function read(string $path, int $offset = 0, int $length = null): string
    {
        $uri = ($path !== '') ? '/' . \str_replace('%2F', '/', \rawurlencode($path)) : '/';
        $headers = [
            'content-md5' => \base64_encode(md5('', true))
        ];

        if ($length !== null) {
            $end = $offset + $length - 1;
            $this->headers['range'] = "bytes=$offset-$end";
            $headers['range'] = "bytes=$offset-$end";
        }

        $response = $this->call(self::METHOD_GET, $uri, '', [], $headers);
        return $response->body;
    }

    /**
     * Delete file in given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $path
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        $uri = ($path !== '') ? '/' . \str_replace('%2F', '/', \rawurlencode($path)) : '/';

        $this->call(self::METHOD_DELETE, $uri, '', [], [
            'content-md5' => \base64_encode(md5('', true)),
        ]);

        return true;
    }

    /**
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     * @param string $path
     *
     * @return bool
     * @throws \Exception
     *
     */
    public function deletePath(string $path): bool
    {
        $path = $this->getRoot() . '/' . $path;

        $uri = '/';

        $continuationToken = '';
        do {
            $objects = $this->listObjects($path, continuationToken: $continuationToken);
            $count = (int)($objects['KeyCount'] ?? 1);
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

            $this->call(self::METHOD_POST, $uri, $body, [
                'delete' => ''
            ], [
                'content-md5' => \base64_encode(md5($body, true)),
                'content-type' => 'text/plain',
                'x-amz-content-sha256' => \hash('sha256', $body)
            ]);
        } while (!empty($continuationToken));

        return true;
    }

    /**
     * Get list of objects in the given path.
     *
     * @param string $path
     *
     * @return array
     * @throws \Exception
     *
     */
    private function listObjects($prefix = '', $maxKeys = 1000, $continuationToken = '')
    {
        $uri = '/';

        $parameters = [
            'list-type' => 2,
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
        ];

        if (!empty($continuationToken)) {
            $parameters['continuation-token'] = $continuationToken;
        }

        $response = $this->call(self::METHOD_GET, $uri, '', $parameters, [
            'content-type' => 'text/plain',
            'content-md5' => \base64_encode(md5('', true))
        ]);
        return $response->body;
    }

    /**
     * Check if file exists
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists(string $path): bool
    {
        try {
            $this->getInfo($path);
        } catch (\Throwable $th) {
            return false;
        }

        return true;
    }

    /**
     * Returns given file path its size.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $path
     *
     * @return int
     */
    public function getFileSize(string $path): int
    {
        $response = $this->getInfo($path);
        return (int)($response['content-length'] ?? 0);
    }

    /**
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
     *
     * @param string $path
     *
     * @return string
     */
    public function getFileHash(string $path): string
    {
        $etag = $this->getInfo($path)['etag'] ?? '';
        return (!empty($etag)) ? substr($etag, 1, -1) : $etag;
    }

    /**
     * Get directory size in bytes.
     *
     * Return -1 on error
     *
     * Based on http://www.jonasjohn.de/snippets/php/dir-size.htm
     *
     * @param string $path
     *
     * @return int
     */
    public function getDirectorySize(string $path): int
    {
        return -1;
    }

    /**
     * Get Partition Free Space.
     *
     * disk_free_space — Returns available space on filesystem or disk partition
     *
     * @return float
     */
    public function getPartitionFreeSpace(): float
    {
        return -1;
    }

    /**
     * Get Partition Total Space.
     *
     * disk_total_space — Returns the total size of a filesystem or disk partition
     *
     * @return float
     */
    public function getPartitionTotalSpace(): float
    {
        return -1;
    }

    /**
     * Sort compare for meta headers
     *
     * @param string $a String A
     * @param string $b String B
     * @return integer
     * @internal Used to sort x-amz meta headers
     */
    private function sortMetaHeadersCmp($a, $b)
    {
        $lenA = \strlen($a);
        $lenB = \strlen($b);
        $minLen = \min($lenA, $lenB);
        $ncmp = \strncmp($a, $b, $minLen);
        if ($lenA == $lenB) {
            return $ncmp;
        }

        if (0 == $ncmp) {
            return $lenA < $lenB ? -1 : 1;
        }

        return $ncmp;
    }
}

<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;

class S3 extends Device
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
     * AWS Regions constants
     */
    const US_EAST_1 = 'us-east-1';
    const US_EAST_2 = 'us-east-2';
    const US_WEST_1 = 'us-west-1';
    const US_WEST_2 = 'us-west-2';
    const AF_SOUTH_1 = 'af-south-1';
    const AP_EAST_1 = 'ap-east-1';
    const AP_SOUTH_1 = 'ap-south-1';
    const AP_NORTHEAST_3 = 'ap-northeast-3';
    const AP_NORTHEAST_2 = 'ap-northeast-2';
    const AP_NORTHEAST_1 = 'ap-northeast-1';
    const AP_SOUTHEAST_1 = 'ap-southeast-1';
    const AP_SOUTHEAST_2 = 'ap-southeast-2';
    const CA_CENTRAL_1 = 'ca-central-1';
    const EU_CENTRAL_1 = 'eu-central-1';
    const EU_WEST_1 = 'eu-west-1';
    const EU_SOUTH_1 = 'eu-south-1';
    const EU_WEST_2 = 'eu-west-2';
    const EU_WEST_3 = 'eu-west-3';
    const EU_NORTH_1 = 'eu-north-1';
    const SA_EAST_1 = 'eu-north-1';
    const CN_NORTH_1 = 'cn-north-1';
    const ME_SOUTH_1 = 'me-south-1';
    const CN_NORTHWEST_1 = 'cn-northwest-1';
    const US_GOV_EAST_1 = 'us-gov-east-1';
    const US_GOV_WEST_1 = 'us-gov-west-1';

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
    protected $accessKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var string
     */
    protected $bucket;
    
    /**
     * @var string
     */
    protected $region;
    
    /**
     * @var string
     */
    protected $acl = self::ACL_PRIVATE;
    
    /**
     * @var string
     */
    protected $root = 'temp';
    
    /**
     * @var array
     */
    protected $headers = [
        'host' => '', 'date' => '', 'content-md5' => '', 'content-type' => '',
    ];

    /**
     * @var array
     */
    protected $amzHeaders;

    /**
     * S3 Constructor
     *
     * @param string $root
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $region
     * @param string $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::US_EAST_1, string $acl = self::ACL_PRIVATE)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->root = $root;
        $this->acl = $acl;
        $this->headers['host'] = $this->bucket . '.s3.'.$this->region.'.amazonaws.com';
        $this->amzHeaders = [];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'S3 Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'S3 Bucket Storage drive for AWS or on premise solution';
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @param string $filename
     * @param string $prefix
     *
     * @return string
     */
    public function getPath(string $filename, string $prefix = null): string
    {
        return $this->getRoot() . DIRECTORY_SEPARATOR . $filename;
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
     * @throws \Exception
     *
     * @return int
     */
    public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        if($chunk == 1 && $chunks == 1) {
            return $this->write($path, \file_get_contents($source), \mime_content_type($source));
        }
        $uploadId = $metadata['uploadId'] ?? null;
        if(empty($uploadId)) {
            $uploadId = $this->createMultipartUpload($path, $metadata['content_type']);
            $metadata['uploadId'] = $uploadId;
        }

        $etag = $this->uploadPart($source, $path, $chunk, $uploadId);
        $metadata['parts'] ??= [];
        $metadata['parts'][] = ['partNumber' => $chunk, 'etag' => $etag];
        $metadata['chunks'] ??= 0;
        $metadata['chunks']++;
        if($metadata['chunks'] == $chunks) {
            $this->completeMultipartUpload($path, $uploadId, $metadata['parts']);
        }
        return $metadata['chunks'];
    }

    /**
     * Start Multipart Upload
     * 
     * Initiate a multipart upload and return an upload ID.
     * 
     * @param string $path
     * @param string $contentType
     * 
     * @throws \Exception
     * 
     * @return string
     */
    protected function createMultipartUpload(string $path, string $contentType): string
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';

        $this->headers['content-md5'] = \base64_encode(md5('', true));
        unset($this->amzHeaders['x-amz-content-sha256']);
        $this->headers['content-type'] = $contentType;
        $this->amzHeaders['x-amz-acl'] = $this->acl;
        $response = $this->call(self::METHOD_POST, $uri, '', ['uploads' => '']);
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
     * @throws \Exception
     * 
     * @return string
     */
    protected function uploadPart(string $source, string $path, int $chunk, string $uploadId) : string
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';
        
        $data = \file_get_contents($source);
        $this->headers['content-type'] = \mime_content_type($source);
        $this->headers['content-md5'] = \base64_encode(md5($data, true));
        $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $data);
        unset($this->amzHeaders['x-amz-acl']); // ACL header is not allowed in parts, only createMultipartUpload accepts this header.

        $response = $this->call(self::METHOD_PUT, $uri, $data, [
            'partNumber'=>$chunk,
            'uploadId' => $uploadId
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
     * @throws \Exception
     * 
     * @return bool
     */
    protected function completeMultipartUpload(string $path, string $uploadId, array $parts): bool
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';

        $body = '<CompleteMultipartUpload>';
        foreach ($parts as $part) {
            $body .= "<Part><ETag>{$part['etag']}</ETag><PartNumber>{$part['partNumber']}</PartNumber></Part>";
        }
        $body .= '</CompleteMultipartUpload>';

        $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $body);
        $this->headers['content-md5'] = \base64_encode(md5($body, true));
        $this->call(self::METHOD_POST, $uri, $body , ['uploadId' => $uploadId]);
        return true;
    }

    /**
     * Abort Chunked Upload
     * 
     * @param string $path
     * @param string $extra
     * 
     * @throws \Exception
     * 
     * @return bool
     */
    public function abort(string $path, string $extra = ''): bool
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';
        unset($this->headers['content-type']);
        $this->headers['content-md5'] = \base64_encode(md5('', true));
        $this->call(self::METHOD_DELETE, $uri, '', ['uploadId' => $extra]);
        return true;
    }

    /**
     * Read file or part of file by given path, offset and length.
     *
     * @param string $path
     * @param int offset
     * @param int length
     * 
     * @throws \Exception
     *
     * @return string
     */
    public function read(string $path, int $offset = 0, int $length = null): string
    {
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        unset($this->headers['content-type']);
        $this->headers['content-md5'] = \base64_encode(md5('', true));
        $uri = ($path !== '') ? '/' . \str_replace('%2F', '/', \rawurlencode($path)) : '/';
        if($length !== null) {
            $end = $offset + $length - 1;
            $this->headers['range'] = "bytes=$offset-$end";
        }
        $response = $this->call(self::METHOD_GET, $uri);
        return $response->body;
    }

    /**
     * Write file by given path.
     *
     * @param string $path
     * @param string $data
     * 
     * @throws \Exception
     * 
     * @return bool
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        $uri = $path !== '' ? '/' . \str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';
        
        $this->headers['content-type'] = $contentType;
        $this->headers['content-md5'] = \base64_encode(md5($data, true)); //TODO whould this work well with big file? can we skip it?
        $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $data);
        $this->amzHeaders['x-amz-acl'] = $this->acl;

        $this->call(self::METHOD_PUT, $uri, $data);

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
        
        unset($this->headers['content-type']);
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        $this->headers['content-md5'] = \base64_encode(md5('', true));
        $this->call(self::METHOD_DELETE, $uri);

        return true;
    }

    /**
     * Get list of objects in the given path.
     *
     * @param string $path
     * 
     * @throws \Exception
     *
     * @return array
     */
    private function listObjects($prefix = '', $maxKeys = 1000, $continuationToken = '')
    {
        $uri = '/';
        $this->headers['content-type'] = 'text/plain';
        $this->headers['content-md5'] = \base64_encode(md5('', true));

        $parameters = [
            'list-type' => 2,
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
        ];
        if(!empty($continuationToken)) {
            $parameters['continuation-token'] = $continuationToken;
        }
        $response = $this->call(self::METHOD_GET, $uri, '', $parameters);
        return $response->body;
    }

    /**
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     * @param string $path
     * 
     * @throws \Exception
     *
     * @return bool
     */
    public function deletePath(string $path): bool
    {
        $path = $this->getRoot() . '/' . $path;
        $uri = '/';
        $continuationToken = '';
        do {
            $objects = $this->listObjects($path, continuationToken: $continuationToken);
            $count = (int) ($objects['KeyCount'] ?? 1);
            if($count < 1) {
                break;
            }
            $continuationToken = $objects['NextContinuationToken'] ?? '';
            $body = '<Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/">';
            if($count > 1) {
                foreach ($objects['Contents'] as $object) {
                    $body .= "<Object><Key>{$object['Key']}</Key></Object>";
                }
            } else {
                $body .= "<Object><Key>{$objects['Contents']['Key']}</Key></Object>"; 
            }
            $body .= '<Quiet>true</Quiet>';
            $body .= '</Delete>';
            $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $body);
            $this->headers['content-md5'] = \base64_encode(md5($body, true));
            $this->call(self::METHOD_POST, $uri, $body, ['delete'=>'']);
        } while(!empty($continuationToken));

        return true;
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
        return  (!empty($etag)) ? substr($etag, 1, -1) : $etag;
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
     * Get file info
     * @return array
     */
    private function getInfo(string $path): array
    {
        unset($this->headers['content-type']);
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        $this->headers['content-md5'] = \base64_encode(md5('', true));
        $uri = $path !== '' ? '/' . \str_replace('%2F', '/', \rawurlencode($path)) : '/';
        $response = $this->call(self::METHOD_HEAD, $uri);

        return $response->headers;
    }

    /**
     * Generate the headers for AWS Signature V4
     * @param string $method
     * @param string $uri
     * @param array parameters
     * 
     * @return string
     */
    private function getSignatureV4(string $method, string $uri, array $parameters = []): string
    {
        $service = 's3';
        $region = $this->region;

        $algorithm = 'AWS4-HMAC-SHA256';
        $combinedHeaders = [];

        $amzDateStamp = \substr($this->amzHeaders['x-amz-date'], 0, 8);

        // CanonicalHeaders
        foreach ($this->headers as $k => $v) {
            $combinedHeaders[\strtolower($k)] = \trim($v);
        }

        foreach ($this->amzHeaders as $k => $v) {
            $combinedHeaders[\strtolower($k)] = \trim($v);
        }

        uksort($combinedHeaders, [ & $this, 'sortMetaHeadersCmp']);

        // Convert null query string parameters to strings and sort
        uksort($parameters, [ & $this, 'sortMetaHeadersCmp']);
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
        $amzPayload[] = $this->amzHeaders['x-amz-content-sha256']; // payload hash
        
        $amzPayloadStr = \implode("\n", $amzPayload); // request as string

        // CredentialScope
        $credentialScope = [$amzDateStamp, $region, $service, 'aws4_request'];

        // stringToSign
        $stringToSignStr = \implode("\n", [$algorithm, $this->amzHeaders['x-amz-date'],
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
     * Get the S3 response
     * 
     * @param string $method
     * @param string $uri
     * @param string $data
     * @param array $parameters
     * 
     * @throws \Exception
     *
     * @return  object
     */
    private function call(string $method, string $uri, string $data = '', array $parameters=[])
    {
        $url = 'https://' . $this->headers['host'] . $uri . '?' . \http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $response = new \stdClass;
        $response->body = '';
        $response->headers = [];

        // Basic setup
        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_USERAGENT, 'utopia-php/storage');
        \curl_setopt($curl, CURLOPT_URL, $url);

        // Headers
        $httpHeaders = [];
        $this->amzHeaders['x-amz-date'] = \gmdate('Ymd\THis\Z');

        if (!isset($this->amzHeaders['x-amz-content-sha256'])) {
            $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $data);
        }

        foreach ($this->amzHeaders as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header . ': ' . $value;
            }
        }

        $this->headers['date'] = \gmdate('D, d M Y H:i:s T');
        foreach ($this->headers as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header . ': ' . $value;
            }
        }

        $httpHeaders[] = 'Authorization: ' . $this->getSignatureV4($method, $uri, $parameters);

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
     * Sort compare for meta headers
     *
     * @internal Used to sort x-amz meta headers
     * @param string $a String A
     * @param string $b String B
     * @return integer
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

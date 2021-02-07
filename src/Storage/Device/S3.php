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
        $this->headers['host'] = $this->bucket . '.s3.amazonaws.com';
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
     *
     * @return string
     */
    public function getPath(string $filename): string
    {
        $path = '';

        for ($i = 0; $i < 4; ++$i) {
            $path = ($i < \strlen($filename)) ? $path . DIRECTORY_SEPARATOR . $filename[$i] : $path . DIRECTORY_SEPARATOR . 'x';
        }

        return $this->getRoot() . $path . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Upload.
     *
     * Upload a file to desired destination in the selected disk.
     *
     * @param string $source
     * @param string $path
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function upload($source, $path): bool
    {
        return $this->write($path, \file_get_contents($source), \mime_content_type($source));
    }

    /**
     * Read file by given path.
     *
     * @param string $path
     * 
     * @throws \Exception
     *
     * @return string
     */
    public function read(string $path): string
    {
        $uri = ($path !== '') ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';
        $response = $this->call(self::METHOD_GET, $uri);

        return $response->body;
    }

    /**
     * Write file by given path.
     *
     * @param string $path
     * @param string $data
     * @throws \Exception
     * 
     * @return bool
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        $metaHeaders = [];
        $uri = $path !== '' ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';
        $this->headers['date'] = gmdate('D, d M Y H:i:s T');
        $input = [
            'data' => $data, 'size' => strlen($data),
            'md5sum' => base64_encode(md5($data, true)),
            'sha256sum' => hash('sha256', $data),
        ];
        $input['type'] = $contentType;
        if (isset($input['size']) && $input['size'] >= 0) {
            $this->headers['content-type'] = $input['type'];
            if (isset($input['md5sum'])) {
                $this->headers['content-md5'] = $input['md5sum'];
            }

            if (isset($input['sha256sum'])) {
                $this->amzHeaders['x-amz-content-sha256'] = $input['sha256sum'];
            }

            $this->amzHeaders['x-amz-acl'] = $this->acl;
            foreach ($metaHeaders as $h => $v) {
                $this->amzHeaders['x-amz-meta-' . $h] = $v;
            }

        } else {
            throw new Exception('Missing input parameters');
        }

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
     * @return bool
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        $uri = ($path !== '') ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';

        $this->call(self::METHOD_DELETE, $uri);

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
     * @param $path
     *
     * @return int
     */
    public function getFileSize(string $path): int
    {
        $response = $this->getInfo($path);
        return $response['size'] ?? 0;
    }

    /**
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
     *
     * @param $path
     *
     * @return string
     */
    public function getFileMimeType(string $path): string
    {
        $response = $this->getInfo($path);
        return $response['type'] ?? '';
    }

    /**
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
     *
     * @param $path
     *
     * @return string
     */
    public function getFileHash(string $path): string
    {
        $response = $this->getInfo($path);
        return $response['hash'] ?? '';
    }

    /**
     * Get directory size in bytes.
     *
     * Return -1 on error
     *
     * Based on http://www.jonasjohn.de/snippets/php/dir-size.htm
     *
     * @param $path
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
        $uri = $path !== '' ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';
        $response = $this->call(self::METHOD_HEAD, $uri);

        return $response->headers;
    }

    /**
     * Generate the headers for AWS Signature V4
     * @param string $method
     * @param string $uri
     * @return string
     */
    private function getSignatureV4(string $method, string $uri): string
    {
        $service = 's3';
        $region = $this->region;

        $algorithm = 'AWS4-HMAC-SHA256';
        $combinedHeaders = [];

        $amzDateStamp = substr($this->amzHeaders['x-amz-date'], 0, 8);

        // CanonicalHeaders
        foreach ($this->headers as $k => $v) {
            $combinedHeaders[strtolower($k)] = trim($v);
        }

        foreach ($this->amzHeaders as $k => $v) {
            $combinedHeaders[strtolower($k)] = trim($v);
        }

        uksort($combinedHeaders, [ & $this, 'sortMetaHeadersCmp']);

        // Convert null query string parameters to strings and sort
        $parameters = [];
        uksort($parameters, [ & $this, 'sortMetaHeadersCmp']);
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
        $stringToSignStr = implode("\n", [$algorithm, $this->amzHeaders['x-amz-date'],
            implode('/', $credentialScope), hash('sha256', $amzPayloadStr)]);

        // Make Signature
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $amzDateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSignStr, $kSigning);

        return $algorithm . ' ' . implode(',', [
            'Credential=' . $this->accessKey . '/' . implode('/', $credentialScope),
            'SignedHeaders=' . implode(';', array_keys($combinedHeaders)),
            'Signature=' . $signature,
        ]);
    }

    /**
     * Get the S3 response
     *
     * @return  object
     */
    private function call(string $method, string $uri, string $data = '')
    {
        $url = 'https://' . $this->headers['host'] . $uri;
        $response = new \stdClass;
        $response->body = '';
        $response->headers = [];

        // Basic setup
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERAGENT, 'utopia-php/storage');
        curl_setopt($curl, CURLOPT_URL, $url);

        // Headers
        $httpHeaders = [];
        $this->amzHeaders['x-amz-date'] = gmdate('Ymd\THis\Z');

        if (!isset($this->amzHeaders['x-amz-content-sha256'])) {
            $this->amzHeaders['x-amz-content-sha256'] = hash('sha256', $data);
        }

        foreach ($this->amzHeaders as $header => $value) {
            if (strlen($value) > 0) {
                $httpHeaders[] = $header . ': ' . $value;
            }
        }

        foreach ($this->headers as $header => $value) {
            if (strlen($value) > 0) {
                $httpHeaders[] = $header . ': ' . $value;
            }
        }

        $httpHeaders[] = 'Authorization: ' . $this->getSignatureV4($method, $uri);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, function (&$curl, &$data) use ($response) {
            $response->body .= $data;
            return strlen($data);
        });
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $data) use ($response) {
            $strlen = strlen($data);
            if ($strlen <= 2) {
                return $strlen;
            }

            if (substr($data, 0, 4) == 'HTTP') {
                $response->code = (int) substr($data, 9, 3);
            } else {
                $data = trim($data);
                if (strpos($data, ': ') === false) {
                    return $strlen;
                }

                list($header, $value) = explode(': ', $data, 2);
                $header = strtolower($header);
                switch ($header) {
                    case $header == 'content-length':
                        $response->headers['size'] = (int) $value;
                        break;
                    case $header == 'content-type':
                        $response->headers['type'] = $value;
                        break;
                    case $header == 'etag':
                        $response->headers['hash'] = $value[0] == '"' ? substr($value, 1, -1) : $value;
                        break;
                }
            }

            return $strlen;
        });
        \curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        
        // Request types
        switch ($method) {
            case self::METHOD_PUT:
            case self::METHOD_POST: // POST only used for CloudFront
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case self::METHOD_HEAD:
                curl_setopt($curl, CURLOPT_NOBODY, true);
                break;
        }

        // Execute, grab errors
        if (!curl_exec($curl)) {
            throw new Exception(curl_error($curl));
        }
        
        $response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($response->code >= 400) {
            throw new Exception('HTTP request failed');
        }

        curl_close($curl);

        // Parse body into XML
        if (isset($response->headers['type']) && $response->headers['type'] == 'application/xml') {
            $response->body = simplexml_load_string($response->body);
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
        $lenA = strlen($a);
        $lenB = strlen($b);
        $minLen = min($lenA, $lenB);
        $ncmp = strncmp($a, $b, $minLen);
        if ($lenA == $lenB) {
            return $ncmp;
        }

        if (0 == $ncmp) {
            return $lenA < $lenB ? -1 : 1;
        }

        return $ncmp;
    }
}

<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device;

class S3 extends Device
{
    protected $accessKey;
    protected $secretKey;
    protected $bucket;
    protected $region;
    protected $acl = 'private';
    protected $root = 'temp';
    protected $headers = [
        'Host' => '', 'Date' => '', 'Content-MD5' => '', 'Content-Type' => '',
    ];
    protected $response;
    protected $amzHeaders;

    public function __construct($root, $accessKey, $secretKey, $bucket, $region, $acl)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->root = $root;
        $this->acl = $acl;
        $this->headers['Host'] = $this->bucket . '.s3.amazonaws.com';
        $this->resetResponse();
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
        return $this->write($path, $source);
    }

    /**
     * Read file by given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function read(string $path): string
    {
        $this->resetResponse();
        $verb = 'GET';
        $uri = $path;
        $uri = $uri !== '' ? '/' . str_replace('%2F', '/', rawurlencode($uri)) : '/';
        $this->getResponse($uri, $verb, false);

        if ($this->response->error === false && $this->response->code !== 200) {
            ['code' => $this->response->code, 'message' => 'Unexpected HTTP status'];
        }

        if ($this->response->error !== false) {
            return false;
        }
        return $this->response->body;
    }

    /**
     * Write file by given path.
     *
     * @param string $path
     * @param string $data
     *
     * @return bool
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        $this->resetResponse();
        $metaHeaders = [];
        $uri = $path !== '' ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';
        $this->headers['Date'] = gmdate('D, d M Y H:i:s T');
        $this->response = new \stdClass;
        $this->response->error = false;
        $this->response->body = null;
        $this->response->headers = [];
        $input = [
            'data' => $data, 'size' => strlen($data),
            'md5sum' => base64_encode(md5($data, true)),
            'sha256sum' => hash('sha256', $data),
        ];
        $input['type'] = $contentType;
        if (isset($input['size']) && $input['size'] >= 0) {
            $this->headers['Content-Type'] = $input['type'];
            if (isset($input['md5sum'])) {
                $this->headers['Content-MD5'] = $input['md5sum'];
            }

            if (isset($input['sha256sum'])) {
                $this->amzHeaders['x-amz-content-sha256'] = $input['sha256sum'];
            }

            $this->amzHeaders['x-amz-acl'] = $this->acl;
            foreach ($metaHeaders as $h => $v) {
                $this->amzHeaders['x-amz-meta-' . $h] = $v;
            }

        } else {
            $this->response->error = ['code' => 0, 'message' => 'Missing input parameters'];
        }
        $this->getResponse($uri, 'PUT', $data);
        if ($this->response->error === false && $this->response->code !== 200) {
            $this->response->error = ['code' => $this->response->code, 'message' => 'Unexpected HTTP status'];
        }

        if ($this->response->error !== false) {
            return false;
        }
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
        return false;
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
        $this->resetResponse();
        $uri = $path !== '' ? '/' . str_replace('%2F', '/', rawurlencode($path)) : '/';

        $rest = $this->getResponse($uri, 'DELETE', false);
        if ($rest->error === false && $rest->code !== 204) {
            $rest->error = ['code' => $rest->code, 'message' => 'Unexpected HTTP status'];
        }

        if ($rest->error !== false) {
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
        $res = $this->getInfo($path);
        return $res['size'];
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
        $res = $this->getInfo($path);
        return $res['type'];
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
        $res = $this->getInfo($path);
        return $res['hash'];
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
        return 0;
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
        return 0.0;
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
        return 0.0;
    }

    private function resetResponse()
    {
        $this->response = new \stdClass;
        $this->response->error = false;
        $this->response->body = null;
        $this->response->headers = [];
    }

    private function getInfo(string $path)
    {
        $this->resetResponse();
        $verb = 'HEAD';
        $uri = $path;
        $uri = $uri !== '' ? '/' . str_replace('%2F', '/', rawurlencode($uri)) : '/';
        $rest = $this->getResponse($uri, $verb, false);
        if ($rest->error === false && ($rest->code !== 200 && $rest->code !== 404)) {
            $rest->error = ['code' => $rest->code, 'message' => 'Unexpected HTTP status'];
        }

        if ($rest->error !== false) {
            return false;
        }
        return $rest->code == 200 ? $rest->headers : false;
    }

    /**
     * Generate the headers for AWS Signature V4
     * @param string $method
     * @param string $uri
     * @return string
     */
    private function getSignatureV4(string $method, string $uri): string
    {
        $parameters = [];
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
        $parameters = array_map('strval', $parameters);
        uksort($parameters, [ & $this, 'sortMetaHeadersCmp']);
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        // Payload
        $amzPayload = [$method];

        $qsPos = strpos($uri, '?');
        $amzPayload[] = ($qsPos === false ? $uri : substr($uri, 0, $qsPos));

        $amzPayload[] = $queryString;
        // add header as string to requests
        foreach ($combinedHeaders as $k => $v) {
            $amzPayload[] = $k . ':' . $v;
        }
        // add a blank entry so we end up with an extra line break
        $amzPayload[] = '';
        // SignedHeaders
        $amzPayload[] = implode(';', array_keys($combinedHeaders));
        // payload hash
        $amzPayload[] = $this->amzHeaders['x-amz-content-sha256'];
        // request as string
        $amzPayloadStr = implode("\n", $amzPayload);

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
     * @return object | false
     */
    private function getResponse(string $uri, string $verb = 'GET', $data = '')
    {
        $url = 'https://' . ($this->headers['Host'] !== '' ? $this->headers['Host'] : $this->endpoint) . $uri;

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

        $httpHeaders[] = 'Authorization: ' . $this->getSignatureV4(
            $verb,
            $uri
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeaders);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, function (&$curl, &$data) {
            $this->response->body .= $data;
            return strlen($data);
        });
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $data) {
            $strlen = strlen($data);
            if ($strlen <= 2) {
                return $strlen;
            }

            if (substr($data, 0, 4) == 'HTTP') {
                $this->response->code = (int) substr($data, 9, 3);
            } else {
                $data = trim($data);
                if (strpos($data, ': ') === false) {
                    return $strlen;
                }

                list($header, $value) = explode(': ', $data, 2);
                $header = strtolower($header);
                switch ($header) {
                    case $header == 'last-modified':
                        $this->response->headers['time'] = strtotime($value);
                        break;
                    case $header == 'date':
                        $this->response->headers['date'] = strtotime($value);
                        break;
                    case $header == 'content-length':
                        $this->response->headers['size'] = (int) $value;
                        break;
                    case $header == 'content-type':
                        $this->response->headers['type'] = $value;
                        break;
                    case $header == 'etag':
                        $this->response->headers['hash'] = $value[0] == '"' ? substr($value, 1, -1) : $value;
                        break;
                    case preg_match('/^x-amz-meta-.*$/', $header):
                        $this->response->headers[$header] = $value;
                        break;
                }

            }
            return $strlen;
        });
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        // Request types
        switch ($verb) {
            case 'GET':break;
            case 'PUT':case 'POST': // POST only used for CloudFront
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $verb);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case 'HEAD':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
                curl_setopt($curl, CURLOPT_NOBODY, true);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:break;
        }

        // Execute, grab errors
        if (curl_exec($curl)) {
            $this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } else {
            $this->response->error = [
                'code' => curl_errno($curl),
                'message' => curl_error($curl),
                'resource' => $uri,
            ];
        }

        @curl_close($curl);

        // Parse body into XML
        if ($this->response->error === false && isset($this->response->headers['type']) &&
            $this->response->headers['type'] == 'application/xml' && isset($this->response->body)) {
            $this->response->body = simplexml_load_string($this->response->body);

            // Grab S3 errors
            if (!in_array($this->response->code, [200, 204, 206]) &&
                isset($this->response->body->Code, $this->response->body->Message)) {
                $this->response->error = [
                    'code' => (string) $this->response->body->Code,
                    'message' => (string) $this->response->body->Message,
                ];
                if (isset($this->response->body->Resource)) {
                    $this->response->error['resource'] = (string) $this->response->body->Resource;
                }

                unset($this->response->body);
            }
        }
        return $this->response;
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

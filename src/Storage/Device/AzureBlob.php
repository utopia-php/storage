<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;
use Utopia\Storage\Storage;

class AzureBlob extends Device
{
    /**
     * HTTP method constants
     */
    const METHOD_GET = 'GET';

    const METHOD_POST = 'POST';

    const METHOD_PUT = 'PUT';

    const METHOD_DELETE = 'DELETE';

    const METHOD_HEAD = 'HEAD';

    /*
     * Blob Type Constants (used for Put Blob operation)
     */
    const X_MS_VERSION = '2023-01-03';

    const BLOCK_BLOB = 'BlockBlob';

    const PAGE_BLOB = 'PageBlob';

    const APPEND_BLOB = 'AppendBlob';

    /**
     * @var string
     */
    protected string $accessKey;

    /**
     * @var string
     */
    protected string $storageAccount;

    /**
     * @var string
     */
    protected string $container;

    /**
     * @var string
     */
    protected string $root = 'temp';

    protected array $headers = [
        'content-encoding' => '',
        'content-language' => '',
        'content-length' => '',
        'content-md5' => '',
        'content-type' => '',
        'date' => '',
        'if-modified-since' => '',
        'if-match' => '',
        'if-none-match' => '',
        'if-unmodified-since' => '',
        'range' => '',
    ];

    /**
     * @var array
     */
    protected string $host;

    /**
     * @var array
     */
    protected array $azureHeaders;

    /**
     * Azure Blob Constructor.
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $storageAccount
     * @param  string  $container
     */
    public function __construct(string $root, string $accessKey, string $storageAccount, string $container)
    {
        $this->accessKey = $accessKey;
        $this->container = $container;
        $this->storageAccount = $storageAccount;
        $this->root = $root;
        $this->host = $storageAccount.'.blob.core.windows.net/'.$container;
        $this->azureHeaders = [];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Azure Blob Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Azure Blob Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_AZURE_BLOB;
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @param  string  $filename
     * @param  string|null  $prefix
     * @return string
     */
    public function getPath(string $filename, string $prefix = null): string
    {
        return $this->getRoot().DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * Upload.
     *
     * Upload a file to desired destination in the selected disk.
     * Return number of chunks uploaded or 0 if it fails.
     *
     * @param  string  $source
     * @param  string  $path
     * @param  int|1  $chunk
     * @param  int|1  $chunks
     * @param  array|[]  $metadata
     * @return int
     *
     * @throws \Exception
     */
    public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        // Case 1: Small blob, only one upload needed
        if ($chunk == 1 && $chunks == 1) {
            return $this->write($path, \file_get_contents($source), \mime_content_type($source));
        }

        // Case 2: Big blob that are broken into small blocks, multiple uploads needed
        unset($this->azureHeaders['x-ms-blob-type']);
        // 1. Upload the block
        $blockId = \base64_encode(\random_bytes(32).(\time() % 1000000));   //block Ids must be of the same size
        $this->putBlock(\file_get_contents($source), $path, $blockId);
        $metadata['chunks'] ??= 0;
        $metadata['chunks']++;
        $metadata['parts'][] = $blockId;
        // 2. Once all blocks are uploaded, commit them
        if ($metadata['chunks'] == $chunks) {
            $this->commitBlocks($path, $metadata['parts']);
        }

        return $metadata['chunks'];
    }

    /**
     * Put Block.
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-block?tabs=azure-ad
     *
     * @param  string  $content
     * @param  string  $path
     * @param  int  $blockId
     *
     * @throws \Exception
     */
    private function putBlock(string $content, string $path, string $blockId): void
    {
        $this->headers['content-length'] = \strlen($content);
        $params = [
            'comp' => 'block',
            'blockid' => $blockId,
        ];
        $this->call(self::METHOD_PUT, $path, $content, $params);
    }

    /**
     * Commit all blocks into a blob.
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-block-list?tabs=azure-ad
     *
     * @param  string  $path
     * @param  array  $blockList
     *
     * @throws \Exception
     */
    private function commitBlocks(string $path, array $blockList): void
    {
        $params = ['comp' => 'blocklist'];
        $body = $this->buildBlockListBody($blockList);
        $this->headers['content-length'] = \strlen($body);
        $this->call(self::METHOD_PUT, $path, $body, $params);
    }

    /**
     * Build an XML body for commitBlocks()
     *
     * @param  array  $blockList
     * @return string
     */
    private function buildBlockListBody(array $blockList): string
    {
        $result = '<?xml version="1.0" encoding="utf-8"?>';
        $result .= '<BlockList>';
        foreach ($blockList as $block) {
            $result = $result.'<Latest>'.$block.'</Latest>';
        }
        $result .= '</BlockList>';

        return $result;
    }

    /**
     * Abort Abort Chunked Upload.
     *
     * @param  string  $path
     * @param  string|''  $extra
     * @return bool
     *
     * @throws \Exception
     */
    public function abort(string $path, string $extra = ''): bool
    {
        /* Azure Blob does not support the operation of aborting a chunked upload. */
        return false;
    }

    /**
     * Read file or part of file by given path, offset and length.
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-blob?tabs=azure-ad
     *
     * @param  string  $path
     * @param  int|0  $offset
     * @param  int|null  $length
     * @return  string
     *
     * @throws \Exception
     */
    public function read(string $path, int $offset = 0, int $length = null): string
    {
        $uri = ($path !== '') ? '/'.\str_replace('%2F', '/', \rawurlencode($path)) : '/';

        if ($length !== null) {
            $end = $offset + $length - 1;
            $this->headers['range'] = "bytes=$offset-$end";  //Can have either, or both, of the headers
            $this->azureHeaders['x-ms-range'] = "bytes=$offset-$end";
        }
        $response = $this->call(self::METHOD_GET, $uri, decode: false);

        return $response->body;
    }

    /**
     * Write file by given path.
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/put-blob?tabs=azure-ad
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string|''   $contentType
     * @return bool
     *
     * @throws \Exception
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        $uri = $path !== '' ? '/'.\str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';

        $this->headers['content-type'] = $contentType;
        $this->headers['content-length'] = \strlen($data);
        $this->azureHeaders['x-ms-blob-type'] = self::BLOCK_BLOB;

        $this->call(self::METHOD_PUT, $uri, $data);

        return true;
    }

    /**
     * Move file from given source to given path, Return true on success and false on failure.
     *
     * @param  string  $source
     * @param  string  $target
     * @return bool
     *
     * @throws \Exception
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
     * Delete blob in given path, return true on success and false on failure.
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/delete-blob?tabs=azure-ad
     *
     * @param  string  $path
     * @return bool|false   $recursive
     *
     * @throws \Exception
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        $uri = ($path !== '') ? '/'.\str_replace('%2F', '/', \rawurlencode($path)) : '/';
        $this->call(self::METHOD_DELETE, $uri, '');

        return true;
    }

    /**
     * Get a list of blobs in the container that match the prefix.
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/list-blobs?tabs=azure-ad
     *
     * @param  string|''  $prefix
     * @param  int|5000  $maxresults
     * @param  string|''  $marker
     * @return array
     *
     * @throws \Exception
     */
    private function listBlobs($prefix = '', $maxresults = 5000, $marker = '') //Note: marker = continuationToken
    {
        $uri = '';
        unset($this->azureHeaders['x-ms-blob-type']);
        $this->headers['content-type'] = '';
        $this->headers['content-length'] = '';
        $parameters = [
            'restype' => 'container',
            'comp' => 'list',
            'prefix' => $prefix,
            'maxresults' => $maxresults,
        ];
        if (! empty($marker)) {
            $parameters['marker'] = $marker;
        }

        $response = $this->call(self::METHOD_GET, $uri, '', $parameters);

        return $response->body;
    }

    /**
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     * @param  string  $path
     * @return bool
     *
     * @throws \Exception
     */
    public function deletePath(string $path): bool
    {
        $path = $this->getRoot().DIRECTORY_SEPARATOR.$path;
        $marker = '';

        do {
            $matchedBlobs = $this->listBlobs($path, marker: $marker);
            $marker = $matchedBlobs['NextMarker']; //retrieve the continuation token for next call
            $blobNamesToDelete = [];

            //Retrieve all blob names for current $matchedBlobs
            foreach ($matchedBlobs as $k => $v) {
                if ($k == 'Blobs') {
                    foreach ($v as $k2 => $v2) {  //Each value of $v is a Blob or Blob-Prefix tag
                        if ($k2 == 'Blob') {
                            foreach ($v2 as $k3 => $v3) {
                                //Case 1: More than 1 blobs, each blob will have an integer key
                                if (gettype($k3) == 'integer') {
                                    foreach ($v3 as $k4 => $v4) {
                                        if ($k4 == 'Name') {
                                            $blobNamesToDelete[] = $v4;
                                            break;  //No need to search for other attributes
                                        }
                                    }
                                }
                                //Case 2: Just 1 blob
                                else {
                                    $blobNamesToDelete[] = $v2['Name'];
                                    break;
                                }
                            }
                        }
                    }
                    break;
                }
            }

            foreach ($blobNamesToDelete as $blobName) {
                /*  There is an invisible blob that has the same name as the directory.
                    That blob is empty and we will get an error if attempting to delete it.
                    For example, if the directory is "/root/bucket", then there is an empty,
                    invisible blob named "root/bucket".   */
                if ($blobName != \ltrim($path, DIRECTORY_SEPARATOR)) {
                    $this->delete($blobName);
                }
            }
        } while (! empty($marker));

        return true;
    }

    /**
     * Check if file exists
     *
     * @param  string  $path
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
     * @param  string  $path
     * @return int
     */
    public function getFileSize(string $path): int
    {
        $response = $this->getInfo($path);

        return (int) ($response['content-length'] ?? 0);
    }

    public function uploadData(string $data, string $path, string $contentType, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        // to be implemented
        return 0;
    }

    public function transfer(string $path, string $destination, Device $device): bool
    {
        // to be implemented
        return false;
    }

    public function getFiles(string $dir): array
    {
        // to be implemented
        return [];
    }

    /**
     * Returns given file path its mime type.
     *
     * @param  string  $path
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
     * @param  string  $path
     * @return string
     */
    public function getFileHash(string $path): string
    {
        $etag = $this->getInfo($path)['etag'] ?? '';

        return (! empty($etag)) ? substr($etag, 1, -1) : $etag;
    }

    /**
     * Create a directory at the specified path.
     * Returns true on success or if the directory already exists and false on error
     *
     * @param $path
     * @return bool
     */
    public function createDirectory(string $path): bool
    {
        /* Azure Blob Storage is an object store (flat storage system) and
             does not have the concept of directories */
        return true;
    }

    /**
     * Get directory size in bytes.
     * Return -1 on error
     *
     * @param  string  $path
     * @return int
     */
    public function getDirectorySize(string $path): int
    {
        return -1;
    }

    /**
     * Get Partition Free Space.
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
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/get-blob?tabs=azure-ad
     *
     * @param  string  $path
     * @return array
     */
    private function getInfo(string $path): array
    {
        $uri = $path !== '' ? '/'.\str_replace('%2F', '/', \rawurlencode($path)) : '/';
        $response = $this->call(self::METHOD_GET, $uri);

        return $response->headers;
    }

    /**
     * Computes the authorization header for blob shared key.
     * This function and all of its helper functions are retrieved and modified from an open-source library.
     *
     * @see https://github.com/Azure/azure-storage-php/blob/master/azure-storage-common/src/Common/Internal/Authentication/SharedKeyAuthScheme.php
     *
     * @param  array  $headers
     * @param  string  $url
     * @param  array  $queryParams
     * @param  string  $httpMethod
     *
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key
     *
     * @return string
     */
    private function getAuthorizationHeader(array $requestHeaders, string $url, array $queryParams, string $httpMethod)
    {
        $canonicalizedHeaders = $this->computeCanonicalizedHeaders($requestHeaders);

        $canonicalizedResource = $this->computeCanonicalizedResource($url, $queryParams);

        $stringToSign = [];
        $stringToSign[] = \strtoupper($httpMethod);

        foreach ($this->headers as $header => $value) {
            $stringToSign[] = $this->tryGetValueInsensitive($header, $this->headers);  //MUST DOUBLE-CHECK
        }

        if (count($canonicalizedHeaders) > 0) {
            $stringToSign[] = \implode("\n", $canonicalizedHeaders);
        }

        $stringToSign[] = $canonicalizedResource;
        $stringToSign = \implode("\n", $stringToSign);

        return 'SharedKey '.$this->storageAccount.':'.\base64_encode(
            \hash_hmac('sha256', $stringToSign, \base64_decode($this->accessKey), true)
        );
    }

    /**
     * Get value in an array.
     *
     * @param  array  $array
     * @param  string  $key
     * @param  any|null  $default
     * @return          $array[$key]
     */
    private function tryGetValue($array, $key, $default = null)
    {
        return (! \is_null($array)) && \is_array($array) && \array_key_exists($key, $array)
            ? $array[$key]
            : $default;
    }

    /**
     * Get value in an array when all keys are in lowercase.
     *
     * @param  string  $key
     * @param  array  $haystack
     * @param  any|null  $default
     * @return          $haystack[$key]
     */
    private function tryGetValueInsensitive($key, $haystack, $default = null)
    {
        $array = \array_change_key_case($haystack);

        return $this->tryGetValue($array, strtolower($key), $default);
    }

    /**
     * Check whether a string starts with a prefix.
     *
     * @param  string  $string
     * @param  string  $prefix
     * @param  bool|false  $ignoreCase
     * @return bool
     */
    private function startsWith(string $string, string $prefix, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $string = \strtolower($string);
            $prefix = \strtolower($prefix);
        }

        return $prefix == substr($string, 0, \strlen($prefix));
    }

    /**
     * Computes canonicalized headers for headers array.
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @param  array  $headers
     * @return array
     */
    private function computeCanonicalizedHeaders(array $headers): array
    {
        $canonicalizedHeaders = [];
        $normalizedHeaders = [];
        $validPrefix = 'x-ms-';

        foreach ($headers as $header => $value) {
            // Convert header to lower case.
            $header = \strtolower($header);

            // Retrieve all headers for the resource that begin with x-ms-,
            // including the x-ms-date header.
            if ($this->startsWith($header, $validPrefix)) {
                // Unfold the string by replacing any breaking white space
                // (meaning what splits the headers, which is \r\n) with a single
                // space.
                $value = \str_replace("\r\n", ' ', $value);

                // Trim any white space around the colon in the header.
                $value = \ltrim($value);
                $header = \rtrim($header);

                $normalizedHeaders[$header] = $value;
            }
        }

        // Sort the headers lexicographically by header name, in ascending order.
        // Note that each header may appear only once in the string.
        \ksort($normalizedHeaders);

        foreach ($normalizedHeaders as $key => $value) {
            $canonicalizedHeaders[] = $key.':'.$value;
        }

        return $canonicalizedHeaders;
    }

    /**
     * Computes canonicalized resources from URL.
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @param  string  $url
     * @param  array  $queryParams
     * @return string
     */
    protected function computeCanonicalizedResource(string $url, array $queryParams): string
    {
        $queryParams = \array_change_key_case($queryParams);

        // 1. Beginning with an empty string (""), append a forward slash (/),
        //    followed by the name of the account that owns the accessed resource.
        $canonicalizedResource = '/'.$this->storageAccount;

        // 2. Append the resource's encoded URI path, without any query parameters.
        $canonicalizedResource .= \parse_url($url, PHP_URL_PATH);

        // 3. Retrieve all query parameters on the resource URI, including the comp
        //    parameter if it exists.
        // 4. Sort the query parameters lexicographically by parameter name, in
        //    ascending order.
        if (\count($queryParams) > 0) {
            \ksort($queryParams);
        }

        // 5. Convert all parameter names to lowercase.
        // 6. URL-decode each query parameter name and value.
        // 7. Append each query parameter name and value to the string in the
        //    following format:
        //      parameter-name:parameter-value
        // 9. Group query parameters
        // 10. Append a new line character (\n) after each name-value pair.
        foreach ($queryParams as $key => $value) {
            // $value must already be ordered lexicographically
            // See: ServiceRestProxy::groupQueryValues
            $canonicalizedResource .= "\n".$key.':'.$value;
        }

        return $canonicalizedResource;
    }

    /**
     * Make an API request to Azure Blob and get a response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param string|''  $data
     * @param array|[]   $parameters
     * @param  bool|true  $decode
     *
     * @see http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return object
     */
    private function call(string $method, string $uri, string $data = '', array $parameters = [], bool $decode = true)
    {
        // initialization of endpoint url and response object
        $uri = $this->getAbsolutePath($uri);
        $url = 'https://'.$this->host.$uri.'?'.\http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $response = new \stdClass;
        $response->body = '';
        $response->headers = [];

        // setup of curl session and properties
        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_USERAGENT, 'utopia-php/storage');
        \curl_setopt($curl, CURLOPT_URL, $url);

        // headers
        $httpHeaders = [];
        $this->azureHeaders['x-ms-date'] = \gmdate('D, d M Y H:i:s T');
        $this->azureHeaders['x-ms-version'] = self::X_MS_VERSION;

        foreach ($this->azureHeaders as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header.': '.$value;
            }
        }
        foreach ($this->headers as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header.': '.$value;
            }
        }

        $httpHeaders[] = 'Authorization: '.$this->getAuthorizationHeader($this->azureHeaders, $url, $parameters, $method);

        // set up cURL request options
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

        // HTTP request types
        switch ($method) {
            case self::METHOD_PUT:
            case self::METHOD_POST:
                \curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case self::METHOD_HEAD:
            case self::METHOD_DELETE:
                \curl_setopt($curl, CURLOPT_NOBODY, true);
                break;
        }

        //executing curl command
        $result = \curl_exec($curl);

        //error handling, evaluating response
        if (! $result) {
            throw new Exception(\curl_error($curl));
        }

        $response->code = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($response->code >= 400) {
            throw new Exception($response->body, $response->code);
        }

        // closing curl session
        \curl_close($curl);

        // Parse body into XML
        if ($decode && ((isset($response->headers['content-type']) && $response->headers['content-type'] == 'application/xml') || (str_starts_with($response->body, '<?xml') && ($response->headers['content-type'] ?? '') !== 'image/svg+xml'))) {
            $response->body = \simplexml_load_string($response->body);
            $response->body = json_decode(json_encode($response->body), true);
        }

        return $response;
    }
}

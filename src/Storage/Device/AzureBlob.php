<?php 

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;
use Utopia\Storage\Storage;

class AzureBlob extends Device
{
    /**
     * DONE
     * HTTP method constants
     */
    const METHOD_GET = 'GET';

    const METHOD_POST = 'POST';

    const METHOD_PUT = 'PUT';

    const METHOD_PATCH = 'PATCH';   // never used

    const METHOD_DELETE = 'DELETE';

    const METHOD_HEAD = 'HEAD';

    const METHOD_OPTIONS = 'OPTIONS'; // never used

    const METHOD_CONNECT = 'CONNECT'; // never used

    const METHOD_TRACE = 'TRACE';   // never used

    /**
     * (WE ARE IN THE PROCESS OF CHECKING IF THESE ARE COMPATIBLE WITH AZURE OR NOT)
     * Microsoft Azure regions constants (taken from AWS Regions in S3 file)
     * 
     * Tam: seems like we never need to use the region throughout our AzureBlob file
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

    const CN_NORTH_4 = 'cn-north-4';

    const CN_NORTHWEST_1 = 'cn-northwest-1';

    const ME_SOUTH_1 = 'me-south-1';

    const US_GOV_EAST_1 = 'us-gov-east-1';

    const US_GOV_WEST_1 = 'us-gov-west-1';

    /**
     * (WE ARE IN THE PROCESS OF CHECKING IF THESE ARE COMPATIBLE WITH AZURE OR NOT)
     * Microsoft Azure ACL flag constants (taken from AWS ACL Flag in S3 file)
     */
    /* Tam's note: For Azure, there are only 3 access levels: private, full public read access, and 
        public read access for blobs only. However, the way we set the access level is very different
        from S3. Overall, it's not necessary to have the acl variable/constants. */
    /** 
     * Other constants
    */
    const X_MS_VERSION = '2023-01-03';
    const BLOCK_BLOB = 'BlockBlob';
    const PAGE_BLOB = 'PageBlob';
    const APPEND_BLOB = 'AppendBlob';

    /**
     * DONE
     * @var string
     */
    protected string $accessKey;

    /**
     * DONE
     * @var string
     */
    protected string $storageAccount;

    /**
     * DONE
     * @var string
     */
    protected string $container;

    /**
     * DONE
     * @var string
     */
    protected string $root = 'temp';

    // New $headers, probably more compatible with AzureBlob
    //Tam: should headers be in properly case (e.g. Content-Length instead of content-length)
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
     * Tam's note: remove the 'host' header as it is not a part of Azure standard headers.
     * Make a host variable to save the API endpoint 
     * 
     * @var array
     */
    protected string $host;

    /**
     * Newly created for Azure. Need to verify if fully compatible.
     * @var array
     */
    protected array $azureHeaders;

    /**
     * DONE
     * Azure Blob Constructor
     * @param string $root
     * @param string $sharedKey
     * @param string $storageAccount
     * @param string $acl
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
     * DONE
     * @return string
     */
    public function getName(): string
    {
        return 'Azure Blob Storage';
    }

    /**
     * DONE
     * @return string
     */
    public function getDescription(): string
    {
        return 'Azure Blob Storage';
    }

    /**
     * DONE
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_AZURE_BLOB;
    }

    /**
     * DONE
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Need to verify if this works.
     * DONE; Tam believes it is correct. - James
     * @param  string  $filename
     * @param  string|null  $prefix
     * @return string
     */
    public function getPath(string $filename, string $prefix = null): string
    {
        return $this->getRoot().DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
     * DONE by Tam
     * Upload.
     *
     * Upload a file to desired destination in the selected disk.
     * return number of chunks uploaded or 0 if it fails.
     *
     * @param  string  $source
     * @param  string  $path
     * @param int chunk
     * @param int chunks
     * @param  array  $metadata
     * @return int
     *
     * @throws \Exception
     */
    public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        //This is the case for small blob 
        if ($chunk == 1 && $chunks == 1) {
            return $this->write($path, \file_get_contents($source), \mime_content_type($source));
        }

        //Tam's note: The rest is the case for big blob that can be broken into small blocks
        //1. Create an empty blob. Similar to createMultipartUpload. 
        // Also create an array that holds all block IDs
        $blockList = [];

        //2. Upload the first block. Similar to uploadPart
        //generate a unique blockId, make sure they are of the same size
        $blockId = \base64_encode(\random_bytes(32).(\time() % 1000000));   
        $blockId = \urlencode($blockId);
        $this->putBlock($source, $path, $blockId); 
        $metadata['chunks'] ??= 0;
        $metadata['chunks']++;
        $blockList[] = $blockId;

        //3. If all parts (ie. blocks) are uploaded, commit all blocks, similar to completeMultipartUpload
        if ($metadata['chunks'] == $chunks) {
            $this->commitBlocks($path, $blockList);
        }

        return $metadata['chunks'];
    }

    /* Tam's helper functions for upload */
    private function putBlock(string $content, string $path, string $blockId): void
    {
        $this->headers['content-length'] = \strlen($content);
        $params = [
            'comp' => 'block',
            'blockid' => $blockId,
        ];
        $this->call(self::METHOD_PUT, $path, $content, $params);
    }


    private function commitBlocks(string $path, array $blockList): void
    {
        $params = [ 'comp' => 'blocklist' ];
        $body = $this->buildBlockListBody($blockList); //will need to build this as an XML file, appending several block IDs
        $this->headers['content-length'] = \strlen($body);
        $this->call(self::METHOD_PUT, $path, $body, $params);
    }

    private function buildBlockListBody(array $blockList): string
    {
        $result = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
        $result .= "<BlockList>";
        foreach ($blockList as $block)
        {
            $result = $result."<Latest>".$block."</Latest>";
        }
        $result .= "</BlockList>";
        return $result;
    } 
    // End of helper functions

    /**
     * NEED TO VERIFY IF THIS WORKS.
     * DONE by Jaime
     * Abort "Copy Blob" operation
     * Tam: This is quite tricky. The purpose of this function in S3 is to cancel/abort a multipatUplaod
     *      operation. However, Azure does not have multipartUpload. We simple abort an upload by not
     *      commiting the blocks. The uncommitted blocks will be garabage collected automatically.
     *      The Abort Copy Blob operation isn't for this purpose. My suggestion is to simply
     *      return False for this function.
     * @param  string  $path
     * @param  string  $extra
     * @return bool
     *
     * @throws \Exception
     */
    public function abort(string $path, string $extra = ''): bool
    {
        $uri = $path !== '' ? '/'.\str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';
        
        $this->azureHeaders['x-ms-copy-action: abort'];
        $params = [
            'comp' => 'copy',
            'copyid' => ''
        ];

        $this->call(self::METHOD_PUT, $uri, '', $params); 

        return true;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
     * Read file or part of file by given path, offset and length.
     * DONE by Jaime.
     *
     * @param  string  $path
     * @param int offset
     * @param int length
     * @return string
     *
     * @throws \Exception
     */
    public function read(string $path, int $offset = 0, int $length = null): string
    {
        // Remove or unset headers that are not needed or conflicting for Azure Blob storage
        // Tam: these headers are needed for the signature, even if their values are empty
        // unset($headers['content-type']); 
        // unset($headers['content-encoding']);
        // unset($headers['content-language']);

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
     * NEED TO VERIFY IF THIS WORKS. 
     * DONE by Tam
     * Write file by given path.
     *
     * @param  string  $path
     * @param  string  $data
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
     * DONE.
     * Move file from given source to given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param  string  $source
     * @param  string  $target
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
     * NEED TO VERIFY IF THIS WORKS.
     * James: Function edited. 
     * Delete blob in given path, Return true on success and false on failure.
     *
     * @param  string  $path
     * @return bool
     *
     * @throws \Exception
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        $uri = ($path !== '') ? '/'.\str_replace('%2F', '/', \rawurlencode($path)) : '/';

        // $this->azureHeaders['x-ms-delete-snapshots: include'];
        $this->call(self::METHOD_DELETE, $uri, '');

        return true;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
     * Fairly DONE by Tam
     * Get list of objects in the given path.
     * Tam: API operation name is "List Blobs"
     *
     * @param  string  $path
     * @return array
     *
     * @throws \Exception
     */
    private function listBlobs($prefix = '', $maxresults = 5000, $marker = '') //Note: marker = continuationToken
    {
        $uri = '';
        // $this->headers['content-type'] = 'text/plain';
        // $this->headers['content-md5'] = \base64_encode(md5('', true));

        $parameters = [
            'restype' => 'container',
            'comp' => 'list',
            'prefix' => $prefix,
            'maxresults' => $maxresults
        ];

        if (! empty($marker)) {
            $parameters['marker'] = $marker;
        }
        $response = $this->call(self::METHOD_GET, $uri, '', $parameters);

        return $response->body;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
     * DONE; edited.
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     * @param  string  $path
     * @return bool
     *
     * @throws \Exception
     */
    public function deletePath(string $path): bool
    {
        // create variable that holds the path of objects to be deleted
        $path = $this->getRoot().'/'.$path;

        $uri = '/';
        $marker = '';

        /* Outer do-while loop: call listBlobs() multiple times until all matched blobs are retrieved.
           Suppose we need to delete 15000 blobs, we need to go through at least 3 outer loops*/
        do {
            $matchedBlobs = $this->listBlobs($path, marker: $marker); //retrieve a parsed array from an XML file
            $marker = $matchedBlobs['Next-Marker']; //retrieve the continuation token for next call
            $blobNamesToDelete = [];
            //Retrieve all blob names for current $matchedBlobs
            foreach ($matchedBlobs as $k => $v) 
            {
                if ($k == "Blobs") {
                    foreach ($v as $k2 => $v2) //Each value of $v is a Blob or Blob-Prefix tag
                    {
                        if ($k2 == "Blob") 
                        {
                            foreach ($v2 as $k3 => $v3) 
                            {
                                //Case 1: More than 1 blobs, each blob will have an integer key
                                if (gettype($k3) == 'integer') 
                                {
                                    foreach ($v3 as $k4 => $v4) 
                                    {
                                        if ($k4 == "Name") 
                                        {
                                            $blobNamesToDelete[] = $v4;
                                            break; //No need to search for other attributes
                                        }
                                    }
                                }
                                //Case 2: Just 1 blob
                                else 
                                {
                                    $blobNamesToDelete[] = $v2['Name'];
                                    break;
                                }
                            }
                        }
                    }
                    break;
                }
            }
            //After retrieving all names, we need to delete all blobs in the array $blobNamesToDelete
            foreach ($blobNamesToDelete as $blobName) {
                $this->delete($blobName);
            }
        } while (!empty($marker)); 

        // return True when deletePath operation is completed
        return true;
    }
    

    /**
     * DONE.
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
     * DONE.
     * Returns given file path its size.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param  string  $path
     * @return int
     */
    public function getFileSize(string $path): int
    {
        $response = $this->getInfo($path);

        return (int) ($response['content-length'] ?? 0);
    }

    /**
     * DONE.
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
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
     * DONE.
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
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
     * DONE.
     * Create a directory at the specified path.
     *
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
     * NEED TO VERIFY IF THIS WORKS.
     * Get directory size in bytes.
     *
     * Return -1 on error
     *
     * Based on http://www.jonasjohn.de/snippets/php/dir-size.htm
     *
     * @param  string  $path
     * @return int
     */
    public function getDirectorySize(string $path): int
    {
        return -1;
    }

    /**
     * DONE.
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
     * DONE.
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
     * DONE by Tam.
     * Get file info
     *
     * @return array
     */
    private function getInfo(string $path): array
    {
        // unset($this->headers['content-type']);
        // unset($this->amzHeaders['x-amz-acl']);
        // unset($this->amzHeaders['x-amz-content-sha256']);
        // $this->headers['content-md5'] = \base64_encode(md5('', true));

        $uri = $path !== '' ? '/'.\str_replace('%2F', '/', \rawurlencode($path)) : '/';
        $response = $this->call(self::METHOD_GET, $uri);

        return $response->headers;
    }

    /* AUTHENTICATION FUNCTIONS FOR AZURE (added by Tam)
        Source: https://github.com/Azure/azure-storage-php/blob/master/azure-storage-common/src/Common/Internal/Authentication/SharedKeyAuthScheme.php */
        
        // Tam's note: check whether to implement this as static or non-static function  
        private function tryGetValue($array, $key, $default = null)
        {
            return (!\is_null($array)) && \is_array($array) && \array_key_exists($key, $array)
                ? $array[$key]
                : $default;
        }
        
        // Tam's note: check whether to implement this as static or non-static function
        private function tryGetValueInsensitive($key, $haystack, $default = null)
        {
            $array = \array_change_key_case($haystack);
            return $this->tryGetValue($array, strtolower($key), $default);
        }
    
    /**
     * Computes the authorization signature for blob and queue shared key.
     *
     * @param array  $headers     request headers.
     * @param string $url         reuqest url.
     * @param array  $queryParams query variables.
     * @param string $httpMethod  request http method.
     *
     * @see Blob and Queue Services (Shared Key Authentication) at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return string
     */
    private function getAuthorizationHeader(array $requestHeaders, string $url, array $queryParams, string $httpMethod
    ) {
        $canonicalizedHeaders = $this->computeCanonicalizedHeaders($requestHeaders);

        $canonicalizedResource = $this->computeCanonicalizedResource($url, $queryParams);

        $stringToSign   = array();
        $stringToSign[] = \strtoupper($httpMethod);

        foreach ($this->headers as $header => $value) {
            $stringToSign[] = $this->tryGetValueInsensitive($header, $this->headers);  //MUST DOUBLE-CHECK
        }

        if (count($canonicalizedHeaders) > 0) {
            $stringToSign[] = \implode("\n", $canonicalizedHeaders);
        }

        $stringToSign[] = $canonicalizedResource;
        $stringToSign   = \implode("\n", $stringToSign);

        // return $stringToSign;

        return 'SharedKey ' . $this->storageAccount . ':' . \base64_encode(
            \hash_hmac('sha256', $stringToSign, \base64_decode($this->accessKey), true)
        );
    }

    /**
     * Returns authorization header to be included in the request.
     *
     * @param array  $headers     request headers.
     * @param string $url         reuqest url.
     * @param array  $queryParams query variables.
     * @param string $httpMethod  request http method.
     *
     * @see Specifying the Authorization Header section at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return string
     */

     /* Tam's note: Can we combine this and the computeSignature into a single function? 
        It depends on whether we will use the getAuthorizationHeader or computeSignature more often */
     
    // private function getAuthorizationHeader(
    //     array $headers,
    //     $url,
    //     array $queryParams,
    //     $httpMethod
    // ) {
    //     $signature = $this->computeSignature(
    //         $headers,
    //         $url,
    //         $queryParams,
    //         $httpMethod
    //     );

        
    // }

    // Helper function for computeCanonicalizedHeaders, consider chnging to private non-static
    private function startsWith($string, $prefix, $ignoreCase = false)
    {
        if ($ignoreCase) {
            $string = \strtolower($string);
            $prefix = \strtolower($prefix);
        }
        return ($prefix == substr($string, 0, \strlen($prefix)));
    }

    /**
     * Computes canonicalized headers for headers array.
     *
     * @param array $headers request headers.
     *
     * @see Constructing the Canonicalized Headers String section at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return array
     */
    private function computeCanonicalizedHeaders($headers): array
    {
        $canonicalizedHeaders = array();
        $normalizedHeaders    = array();
        $validPrefix          = 'x-ms-';

        if (\is_null($normalizedHeaders)) {     //This looks weird, consider deleting in the end
            return $canonicalizedHeaders;
        }

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
                $value  = \ltrim($value);
                $header = \rtrim($header);

                $normalizedHeaders[$header] = $value;
            }
        }

        // Sort the headers lexicographically by header name, in ascending order.
        // Note that each header may appear only once in the string.
        \ksort($normalizedHeaders);

        foreach ($normalizedHeaders as $key => $value) {
            $canonicalizedHeaders[] = $key . ':' . $value;
        }

        return $canonicalizedHeaders;
    }

    /**
     * Computes canonicalized resources from URL.
     *
     * @param string $url         request url.
     * @param array  $queryParams request query variables.
     *
     * @see Constructing the Canonicalized Resource String section at
     *      http://msdn.microsoft.com/en-us/library/windowsazure/dd179428.aspx
     *
     * @return string
     */
    protected function computeCanonicalizedResource($url, $queryParams)
    {
        $queryParams = \array_change_key_case($queryParams);

        // 1. Beginning with an empty string (""), append a forward slash (/),
        //    followed by the name of the account that owns the accessed resource.
        $canonicalizedResource = '/' . $this->storageAccount;

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
            $canonicalizedResource .= "\n" . \urlencode($key) . ':' . \urlencode($value);
        }

        return $canonicalizedResource;
    }

    private function call(string $method, string $uri, string $data = '', array $parameters = [], bool $decode = true)
    {
        // initialization of endpoint url and response object
        // Tam's note: OK
        $uri = $this->getAbsolutePath($uri);
        $url = 'https://'.$this->host.$uri.'?'.\http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $response = new \stdClass;
        $response->body = '';
        $response->headers = [];

        // setup of curl session and properties
        // Tam's note: OK
        $curl = \curl_init();
        \curl_setopt($curl, CURLOPT_USERAGENT, 'utopia-php/storage');
        \curl_setopt($curl, CURLOPT_URL, $url);

        // Headers
        $httpHeaders = [];
        $this->azureHeaders['x-ms-date'] = \gmdate('D, d M Y H:i:s T');

        //Tam's note: Azure requires a x-ms-version header
        $this->azureHeaders['x-ms-version'] = self::X_MS_VERSION;

        // Tam's note: OK
        foreach ($this->azureHeaders as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header.': '.$value;
            }
        }

        /* Tam's note: this header is optional since x-ms-header has been set.
           Source: https://learn.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key#constructing-the-canonicalized-headers-string */
        
        // $this->headers['date'] = \gmdate('D, d M Y H:i:s T');   //Might need to set this to empty

        // Tam's note: OK
        foreach ($this->headers as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header.': '.$value;
            }
        }

        /* Tam's note: call getAuthorizationHeader (new Azure function) instead.
            Arguments for the getAuthorizationHeader might be wrong */ 
        // $httpHeaders[] = 'Authorization: '.$this->getSignatureV4($method, $uri, $parameters);
        $httpHeaders[] = 'Authorization: '.$this->getAuthorizationHeader($this->azureHeaders, $url, $parameters, $method);

        /* Tam's note: seems OK */
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

        /* Tam's note: seems OK */
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

        /* Tam's note: seems OK */
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

        /* Tam's note: Azure responses usually don't have 'content-type' or XMLheaders, but
            it's fine to keep the code, as the if conditional will skip it anyways. */
        // Parse body into XML (may not be needed for Azure)
        if ($decode && ((isset($response->headers['content-type']) && $response->headers['content-type'] == 'application/xml') || (str_starts_with($response->body, '<?xml') && ($response->headers['content-type'] ?? '') !== 'image/svg+xml'))) {
            $response->body = \simplexml_load_string($response->body);
            $response->body = json_decode(json_encode($response->body), true);
        }

        return $response;
    }
}
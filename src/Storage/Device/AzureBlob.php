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

    const METHOD_PATCH = 'PATCH';

    const METHOD_DELETE = 'DELETE';

    const METHOD_HEAD = 'HEAD';

    const METHOD_OPTIONS = 'OPTIONS';

    const METHOD_CONNECT = 'CONNECT';

    const METHOD_TRACE = 'TRACE';

    /**
     * (WE ARE IN THE PROCESS OF CHECKING IF THESE ARE COMPATIBLE WITH AZURE OR NOT)
     * Microsoft Azure regions constants (taken from AWS Regions in S3 file)
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
    // const ACL_PRIVATE = 'private';

    // const ACL_PUBLIC_READ = 'public-read';

    // const ACL_PUBLIC_READ_WRITE = 'public-read-write';

    // const ACL_AUTHENTICATED_READ = 'authenticated-read';

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

    // /**
    //  * DONE
    //  * @var string
    //  */
    // protected string $acl = self::ACL_PRIVATE;

    /**
     * DONE
     * @var string
     */
    protected string $root = 'temp';

    /**
     * Taken from S3 file. Need to verify if fully compatible.
     * @var array
     */
    // protected array $headers = [
    //     'host' => '', 'date' => '',
    //     'content-md5' => '',
    //     'content-type' => '',
    // ];

    // New $headers, probably more compatible with AzureBlob
    protected array $headers = [
        //'host' => '', //Note sure if this host header is necessary for AzureBlob. Consider making a separate host variable
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
     * Taken from S3 file. Need to verify if fully compatible.
     * @var array
     */
    protected array $azureHeaders;

    /**
     * IN PROGRESS...
     * Azure Blob Constructor
     * @param string $root
     * @param string $sharedKey
     * @param string $storageAccount
     * @param string $acl
     */
    
    public function __construct(string $root, string $accessKey, string $storageAccount, string $container)
    {
        $this->$accessKey = $accessKey;
        $this->container = $container;
        $this->storageAccount = $storageAccount;
        $this->root = $root;
        $host = $storageAccount.'.blob.core.windows.net/'.$container;
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
        //Tam's note: lines 266 - 269 DONE. This is the case for small blob
        if ($chunk == 1 && $chunks == 1) {
            return $this->write($path, \file_get_contents($source), \mime_content_type($source));
        }

        // $uploadId = $metadata['uploadId'] ?? null;
        // if (empty($uploadId)) {
        //     $uploadId = $this->createMultipartUpload($path, $metadata['content_type']);
        //     $metadata['uploadId'] = $uploadId;
        // }

        //Tam's note: The rest is the case for big blob that can be broken into small blocks
        //1. Create an empty blob. Similar to createMultipartUpload. 
        // Also create an array that holds all block IDs
        $this->write($path, '');
        $blockList = [];

        // $etag = $this->uploadPart($source, $path, $chunk, $uploadId);
        // $metadata['parts'] ??= [];
        // $metadata['parts'][] = ['partNumber' => $chunk, 'etag' => $etag];
        // $metadata['chunks'] ??= 0;
        // $metadata['chunks']++;

        //2. Upload the first block. Similar to uploadPart
        $blockId = \base64_encode(\random_bytes(16).'and'.$chunk);   //generate a unique blockId
        $blockId = \urlencode($blockId);
        $this->putBlock($source, $blockId); //write a seperate helper function
        $metadata['chunks'] ??= 0;
        $metadata['chunks']++;
        $blockList[] = $blockId;

        //3. If all parts (ie. blocks) are uploaded, commit all blocks
        if ($metadata['chunks'] == $chunks) {
            // $this->completeMultipartUpload($path, $uploadId, $metadata['parts']);
            $this->commitBlocks($blockList); //write a seperate helper function
        }

        return $metadata['chunks'];
    }

    /* Tam's helper functions for upload */
    private function putBlock(string $blockId, string $content): void
    {
        $this->headers['content-length'] = \strlen($content);
        $params = [
            'comps' => 'block',
            'blockid' => $blockId,
        ];
        $this->call(self::METHOD_PUT, '', $content, $params);
    }


    private function commitBlocks(array $blockList): void
    {
        $params = [ 'comps' => 'blocklist' ];
        $body = '....'; //will need to build this as an XML file, appending several block ID's
        $this->headers['content-length'] = \strlen($body);
        $this->call(self::METHOD_PUT, '', $body, $params);
    }
    // End of helper functions

    /*  Tam's note: the set of functions: createMultipartUpload, uploadPart, and completeMultipartUpload
        are helper functions that are specific for S3. We cannot use them for Azure. We will create a set 
        of Azure functions to help the upload function. */     
    /**
     * NEED TO VERIFY IF THIS WORKS.
     * Start Multipart Upload
     *
     * Initiate a multipart upload and return an upload ID.
     *
     * @param  string  $path
     * @param  string  $contentType
     * @return string
     *
     * @throws \Exception
     */
    // protected function createMultipartUpload(string $path, string $contentType): string
    // {
    //     $uri = $path !== '' ? '/'.\str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';

    //     $this->headers['content-md5'] = \base64_encode(md5('', true));
    //     unset($this->amzHeaders['x-amz-content-sha256']);
    //     $this->headers['content-type'] = $contentType;
    //     $this->amzHeaders['x-amz-acl'] = $this->acl;
    //     $response = $this->call(self::METHOD_POST, $uri, '', ['uploads' => '']);

    //     return $response->body['UploadId'];
    // }

    // /**
    //  * NEED TO VERIFY IF THIS WORKS.
    //  * Upload Part
    //  *
    //  * @param  string  $source
    //  * @param  string  $path
    //  * @param  int  $chunk
    //  * @param  string  $uploadId
    //  * @return string
    //  *
    //  * @throws \Exception
    //  */
    // protected function uploadPart(string $source, string $path, int $chunk, string $uploadId): string
    // {
    //     $uri = $path !== '' ? '/'.\str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';

    //     $data = \file_get_contents($source);
    //     $this->headers['content-type'] = \mime_content_type($source);
    //     $this->headers['content-md5'] = \base64_encode(md5($data, true));
    //     $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $data);
    //     unset($this->amzHeaders['x-amz-acl']); // ACL header is not allowed in parts, only createMultipartUpload accepts this header.

    //     $response = $this->call(self::METHOD_PUT, $uri, $data, [
    //         'partNumber' => $chunk,
    //         'uploadId' => $uploadId,
    //     ]);

    //     return $response->headers['etag'];
    // }

    // /**
    //  * NEED TO VERIFY IF THIS WORKS.
    //  * Complete Multipart Upload
    //  *
    //  * @param  string  $path
    //  * @param  string  $uploadId
    //  * @param  array  $parts
    //  * @return bool
    //  *
    //  * @throws \Exception
    //  */
    // protected function completeMultipartUpload(string $path, string $uploadId, array $parts): bool
    // {
    //     $uri = $path !== '' ? '/'.\str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';

    //     $body = '<CompleteMultipartUpload>';
    //     foreach ($parts as $part) {
    //         $body .= "<Part><ETag>{$part['etag']}</ETag><PartNumber>{$part['partNumber']}</PartNumber></Part>";
    //     }
    //     $body .= '</CompleteMultipartUpload>';

    //     $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $body);
    //     $this->headers['content-md5'] = \base64_encode(md5($body, true));
    //     $this->call(self::METHOD_POST, $uri, $body, ['uploadId' => $uploadId]);

    //     return true;
    // }
    


    /**
     * NEED TO VERIFY IF THIS WORKS.
     * Abort Chunked Upload
     *
     * @param  string  $path
     * @param  string  $extra
     * @return bool
     *
     * @throws \Exception
     */
    public function abort(string $path, string $extra = ''): bool
    {
        $uri = $path !== '' ? '/'.\str_replace(['%2F', '%3F'], ['/', '?'], \rawurlencode($path)) : '/';
        unset($this->headers['content-type']);
        $this->headers['content-md5'] = \base64_encode(md5('', true));
        $this->call(self::METHOD_DELETE, $uri, '', ['uploadId' => $extra]);

        return true;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
     * Read file or part of file by given path, offset and length.
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
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        unset($this->headers['content-type']);
        $this->headers['content-md5'] = \base64_encode(md5('', true));
        $uri = ($path !== '') ? '/'.\str_replace('%2F', '/', \rawurlencode($path)) : '/';
        if ($length !== null) {
            $end = $offset + $length - 1;
            $this->headers['range'] = "bytes=$offset-$end";
        }
        $response = $this->call(self::METHOD_GET, $uri, decode: false);

        return $response->body;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS. 
     * Tam's note: I modified it recently, believe this will work.
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
        $this->headers['content-md5'] = \base64_encode(md5($data, true)); //TODO whould this work well with big file? can we skip it?
        $this->headers['content-length'] = \strlen($data);
        $this->azureHeaders['x-ms-blob-type'] = self::BLOCK_BLOB;

        // $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $data);
        // $this->amzHeaders['x-amz-acl'] = $this->acl;

        $this->call(self::METHOD_PUT, $uri, $data);

        return true;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
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
     * Delete file in given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param  string  $path
     * @return bool
     *
     * @throws \Exception
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        $uri = ($path !== '') ? '/'.\str_replace('%2F', '/', \rawurlencode($path)) : '/';

        unset($this->headers['content-type']);
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        $this->headers['content-md5'] = \base64_encode(md5('', true));
        $this->call(self::METHOD_DELETE, $uri);

        return true;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
     * Get list of objects in the given path.
     *
     * @param  string  $path
     * @return array
     *
     * @throws \Exception
     */
    private function listObjects($prefix = '', $maxKeys = 1000, $continuationToken = '')
    {
        $uri = '/';
        $prefix = ltrim($prefix, '/'); /** S3 specific requirement that prefix should never contain a leading slash */
        $this->headers['content-type'] = 'text/plain';
        $this->headers['content-md5'] = \base64_encode(md5('', true));

        $parameters = [
            'list-type' => 2,
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
        ];
        if (! empty($continuationToken)) {
            $parameters['continuation-token'] = $continuationToken;
        }
        $response = $this->call(self::METHOD_GET, $uri, '', $parameters);

        return $response->body;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     * @param  string  $path
     * @return bool
     *
     * @throws \Exception
     */
    public function deletePath(string $path): bool
    {
        $path = $this->getRoot().'/'.$path;

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
            $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $body);
            $this->headers['content-md5'] = \base64_encode(md5($body, true));
            $this->call(self::METHOD_POST, $uri, $body, ['delete' => '']);
        } while (! empty($continuationToken));

        return true;
    }

    /**
     * NEED TO VERIFY IF THIS WORKS.
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
     * NEED TO VERIFY IF THIS WORKS.
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
     * NEED TO VERIFY IF THIS WORKS.
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
     * NEED TO VERIFY IF THIS WORKS.
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
     * NEED TO VERIFY IF THIS WORKS.
     * Create a directory at the specified path.
     *
     * Returns true on success or if the directory already exists and false on error
     *
     * @param $path
     * @return bool
     */
    public function createDirectory(string $path): bool
    {
        /* S3 is an object store and does not have the concept of directories */
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
     * NEED TO VERIFY IF THIS WORKS.
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
     * NEED TO VERIFY IF THIS WORKS.
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
     * NEED TO VERIFY IF THIS WORKS.
     * Get file info
     *
     * @return array
     */
    private function getInfo(string $path): array
    {
        unset($this->headers['content-type']);
        unset($this->amzHeaders['x-amz-acl']);
        unset($this->amzHeaders['x-amz-content-sha256']);
        $this->headers['content-md5'] = \base64_encode(md5('', true));
        $uri = $path !== '' ? '/'.\str_replace('%2F', '/', \rawurlencode($path)) : '/';
        $response = $this->call(self::METHOD_HEAD, $uri);

        return $response->headers;
    }

    /**
     * (WE ARE IN THE PROCESS OF CHECKING IF THESE ARE COMPATIBLE WITH AZURE OR NOT)
     * Generate the headers for AWS Signature V4
     *
     * @param  string  $method
     * @param  string  $uri
     * @param array parameters
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

        uksort($combinedHeaders, [&$this, 'sortMetaHeadersCmp']);

        // Convert null query string parameters to strings and sort
        uksort($parameters, [&$this, 'sortMetaHeadersCmp']);
        $queryString = \http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        // Payload
        $amzPayload = [$method];

        $qsPos = \strpos($uri, '?');
        $amzPayload[] = ($qsPos === false ? $uri : \substr($uri, 0, $qsPos));

        $amzPayload[] = $queryString;

        foreach ($combinedHeaders as $k => $v) { // add header as string to requests
            $amzPayload[] = $k.':'.$v;
        }

        $amzPayload[] = ''; // add a blank entry so we end up with an extra line break
        $amzPayload[] = \implode(';', \array_keys($combinedHeaders)); // SignedHeaders
        $amzPayload[] = $this->amzHeaders['x-amz-content-sha256']; // payload hash

        $amzPayloadStr = \implode("\n", $amzPayload); // request as string

        // CredentialScope
        $credentialScope = [$amzDateStamp, $region, $service, 'aws4_request'];

        // stringToSign
        $stringToSignStr = \implode("\n", [
            $algorithm, $this->amzHeaders['x-amz-date'],
            \implode('/', $credentialScope), \hash('sha256', $amzPayloadStr),
        ]);

        // Make Signature
        $kSecret = 'AWS4'.$this->secretKey;
        $kDate = \hash_hmac('sha256', $amzDateStamp, $kSecret, true);
        $kRegion = \hash_hmac('sha256', $region, $kDate, true);
        $kService = \hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = \hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = \hash_hmac('sha256', \utf8_encode($stringToSignStr), $kSigning);

        return $algorithm.' '.\implode(',', [
            'Credential='.$this->accessKey.'/'.\implode('/', $credentialScope),
            'SignedHeaders='.\implode(';', \array_keys($combinedHeaders)),
            'Signature='.$signature,
        ]);
    }

    /* AUTHENTICATION FUNCTIONS FOR AZURE (added by Tam)
        Source: https://github.com/Azure/azure-storage-php/blob/master/azure-storage-common/src/Common/Internal/Authentication/SharedKeyAuthScheme.php */
        
        // Tam's note: check whether to implement this as static or non-static function  
        public static function tryGetValue($array, $key, $default = null)
        {
            return (!is_null($array)) && is_array($array) && array_key_exists($key, $array)
                ? $array[$key]
                : $default;
        }
        
        // Tam's note: check whether to implement this as static or non-static function
        public static function tryGetValueInsensitive($key, $haystack, $default = null)
        {
            $array = array_change_key_case($haystack);
            return self::tryGetValue($array, strtolower($key), $default);
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
    private function computeSignature(
        array $requestHeaders,
        $url,
        array $queryParams,
        $httpMethod
    ) {
        $canonicalizedHeaders = $this->computeCanonicalizedHeaders($requestHeaders);

        $canonicalizedResource = $this->computeCanonicalizedResource(
            $url,
            $queryParams
        );

        $stringToSign   = array();
        $stringToSign[] = \strtoupper($httpMethod);

        foreach ($this->headers as $header) {
            $stringToSign[] = AzureBlob::tryGetValueInsensitive($header, $requestHeaders);
        }

        if (count($canonicalizedHeaders) > 0) {
            $stringToSign[] = \implode("\n", $canonicalizedHeaders);
        }

        $stringToSign[] = $canonicalizedResource;
        $stringToSign   = \implode("\n", $stringToSign);

        return $stringToSign;
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
     
    private function getAuthorizationHeader(
        array $headers,
        $url,
        array $queryParams,
        $httpMethod
    ) {
        $signature = $this->computeSignature(
            $headers,
            $url,
            $queryParams,
            $httpMethod
        );

        return 'SharedKey ' . $this->storageAccount . ':' . \base64_encode(
            \hash_hmac('sha256', $signature, \base64_decode($this->accessKey), true)
        );
    }

    // Helper function for computeCanonicalizedHeaders, consider chnging to private non-static
    public static function startsWith($string, $prefix, $ignoreCase = false)
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
    private function computeCanonicalizedHeaders($headers)
    {
        $canonicalizedHeaders = array();
        $normalizedHeaders    = array();
        $validPrefix          = 'x-ms-';

        if (is_null($normalizedHeaders)) {
            return $canonicalizedHeaders;
        }

        foreach ($headers as $header => $value) {
            // Convert header to lower case.
            $header = \strtolower($header);

            // Retrieve all headers for the resource that begin with x-ms-,
            // including the x-ms-date header.
            if (self::startsWith($header, $validPrefix)) {
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
        ksort($normalizedHeaders);

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
            $canonicalizedResource .= "\n" . $key . ':' . $value;
        }

        return $canonicalizedResource;
    }
    
    // END OF NEWLY ADDED FUNCTIONS

    /**
     * (WE ARE IN THE PROCESS OF CHECKING IF THESE ARE COMPATIBLE WITH AZURE OR NOT)
     * Get the S3 response
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  string  $data
     * @param  array  $parameters
     * @param  bool  $decode
     * @return  object
     *
     * @throws \Exception
     */
    // private function call(string $method, string $uri, string $data = '', array $parameters = [], bool $decode = true)
    // {
    //     $uri = $this->getAbsolutePath($uri);
    //     $url = 'https://'.$this->headers['host'].$uri.'?'.\http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
    //     $response = new \stdClass;
    //     $response->body = '';
    //     $response->headers = [];

    //     // Basic setup
    //     $curl = \curl_init();
    //     \curl_setopt($curl, CURLOPT_USERAGENT, 'utopia-php/storage');
    //     \curl_setopt($curl, CURLOPT_URL, $url);

    //     // Headers
    //     $httpHeaders = [];
    //     $this->amzHeaders['x-amz-date'] = \gmdate('Ymd\THis\Z');

    //     if (! isset($this->amzHeaders['x-amz-content-sha256'])) {
    //         $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $data);
    //     }

    //     foreach ($this->amzHeaders as $header => $value) {
    //         if (\strlen($value) > 0) {
    //             $httpHeaders[] = $header.': '.$value;
    //         }
    //     }

    //     $this->headers['date'] = \gmdate('D, d M Y H:i:s T');

    //     foreach ($this->headers as $header => $value) {
    //         if (\strlen($value) > 0) {
    //             $httpHeaders[] = $header.': '.$value;
    //         }
    //     }

    //     $httpHeaders[] = 'Authorization: '.$this->getSignatureV4($method, $uri, $parameters);

    //     \curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeaders);
    //     \curl_setopt($curl, CURLOPT_HEADER, false);
    //     \curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
    //     \curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, string $data) use ($response) {
    //         $response->body .= $data;

    //         return \strlen($data);
    //     });
    //     curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, string $header) use (&$response) {
    //         $len = strlen($header);
    //         $header = explode(':', $header, 2);

    //         if (count($header) < 2) { // ignore invalid headers
    //             return $len;
    //         }

    //         $response->headers[strtolower(trim($header[0]))] = trim($header[1]);

    //         return $len;
    //     });
    //     \curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    //     \curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

    //     // Request types
    //     switch ($method) {
    //         case self::METHOD_PUT:
    //         case self::METHOD_POST: // POST only used for CloudFront
    //             \curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    //             break;
    //         case self::METHOD_HEAD:
    //         case self::METHOD_DELETE:
    //             \curl_setopt($curl, CURLOPT_NOBODY, true);
    //             break;
    //     }

    //     $result = \curl_exec($curl);

    //     if (! $result) {
    //         throw new Exception(\curl_error($curl));
    //     }

    //     $response->code = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
    //     if ($response->code >= 400) {
    //         throw new Exception($response->body, $response->code);
    //     }

    //     \curl_close($curl);

    //     // Parse body into XML
    //     if ($decode && ((isset($response->headers['content-type']) && $response->headers['content-type'] == 'application/xml') || (str_starts_with($response->body, '<?xml') && ($response->headers['content-type'] ?? '') !== 'image/svg+xml'))) {
    //         $response->body = \simplexml_load_string($response->body);
    //         $response->body = json_decode(json_encode($response->body), true);
    //     }

    //     return $response;
    // }

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
        //Tam's note: fix this to be compatible with Azure format (ie 'x-ms-date')
        // $this->amzHeaders['x-amz-date'] = \gmdate('Ymd\THis\Z');
        $this->azureHeaders['x-ms-date'] = \gmdate('D, d M Y H:i:s T');

        //Tam's note: Azure requires a x-ms-version header
        $this->azureHeaders['x-ms-version'] = self::X_MS_VERSION;

        // Tam's note: this header is not needed for Azure Blob
        // if (! isset($this->amzHeaders['x-amz-content-sha256'])) {
        //     $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $data);
        // }

        // Tam's note: OK
        foreach ($this->azureHeaders as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header.': '.$value;
            }
        }

        /* Tam's note: this header is optional since x-ms-header has been set.
           Source: https://learn.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key#constructing-the-canonicalized-headers-string */
        
        $this->headers['date'] = \gmdate('D, d M Y H:i:s T');

        // Tam's note: OK
        foreach ($this->headers as $header => $value) {
            if (\strlen($value) > 0) {
                $httpHeaders[] = $header.': '.$value;
            }
        }

        /* Tam's note: call getAuthorizationHeader (new Azure function) instead.
            Arguments for the getAuthorizationHeader might be wrong */ 
        // $httpHeaders[] = 'Authorization: '.$this->getSignatureV4($method, $uri, $parameters);
        $httpHeaders[] = 'Authorization: '.$this->getAuthorizationHeader($this->azureHeaders, $uri, $parameters, $method);

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
            it's fine to keep the code, as if conditional will skip it anyways. */
        // Parse body into XML (may not be needed for Azure)
        if ($decode && ((isset($response->headers['content-type']) && $response->headers['content-type'] == 'application/xml') || (str_starts_with($response->body, '<?xml') && ($response->headers['content-type'] ?? '') !== 'image/svg+xml'))) {
            $response->body = \simplexml_load_string($response->body);
            $response->body = json_decode(json_encode($response->body), true);
        }

        return $response;
    }

    /**
     * NEED TO SEE IF THIS WORKS.
     * Sort compare for meta headers
     *
     * @internal Used to sort x-amz meta headers
     *
     * @param  string  $a String A
     * @param  string  $b String B
     * @return int
     */

     /* Tam's note: This function helps with getSignatureV4. Since we use a different function to generate 
        Azure signature, we can get rid of this */ 
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



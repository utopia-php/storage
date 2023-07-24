<?php 

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class AzureBlob extends Device
{
    /**
     * Azure Blob Constructor
     *
     * 
     * 
     * 
     */
    
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $acl = self::ACL_PRIVATE)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->root = $root;
        $this->acl = $acl;

        $this->headers['host'] = $bucket.'.'.'blob.core.windows.net';
    }

    // /**
    //  * @return string
    //  */
    // public function getName(): string
    // {
    //     return 'Azure Blob Storage';
    // }

    // /**
    //  * @return string
    //  */
    // public function getDescription(): string
    // {
    //     return 'Azure Blob Storage';
    // }

    // /**
    //  * @return string
    //  */
    // public function getType(): string
    // {
    //     return Storage::DEVICE_AZURE_BLOB;
    // }
}

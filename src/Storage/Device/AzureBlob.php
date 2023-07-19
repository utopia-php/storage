<?php 

namespace Utopia\Storage\Device;

use Exception;  //check if this is needed
use Utopia\Storage\Storage;
use Utopia\Storage\Device;

class AzureBlob extends Device   //Should it extend S3 or Device?
{
    //Should have some constants here. But where can we find them?
    
    /**
     * Azure Blob Constructor
     *
     * 
     * 
     * 
     */
    
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::EU_CENTRAL_1, string $acl = self::ACL_PRIVATE)   //Needs constructor variables
    {
        //parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket.'blob.core.windows.net';
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
}

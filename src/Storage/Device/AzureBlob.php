<?php 

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

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
    
    public function __construct()   //Needs constructor variables
    {
   
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

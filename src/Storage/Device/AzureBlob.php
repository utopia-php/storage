<!-- NEW -->
<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device;

class AzureBlob extends Device
{
    /**
     * Azure Blob Constructor
     *
     * 
     * 
     * 
     */
    
    public function __construct()
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

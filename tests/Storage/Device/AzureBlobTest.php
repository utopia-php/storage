<?php 

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\AzureBlob;
use Utopia\Tests\Storage\StorageTest;

class AzureBlobTest extends StorageTest
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['AZURE_BLOB_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['AZURE_BLOB_SECRET'] ?? '';
        $bucket = 'appwriteblobtesting';

        $this->object = new AzureBlob($this->root, $key, $secret, $bucket, AzureBlob::US_WEST_1, AzureBlob::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Azure Blob Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Azure Blob Storage';
    }
} 


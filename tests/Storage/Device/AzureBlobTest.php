<?php 

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\AzureBlob;
use Utopia\Tests\Storage\S3Base;

class AzureBlobTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        // $key = $_SERVER['AZURE_BLOB_ACCESS_KEY'] ?? '';
        // $secret = $_SERVER['AZURE_BLOB_SECRET'] ?? '';
        // $bucket = 'appwriteblobtesting';
        $accessKey = 'QeXSoWLjhW+XK7pMilHkMsnoesUFMVBKU0qHrT68z1451VDoD53XxBmlXySBYg5bNZ5QunUFSfNz+AStmjNYgA==';
        $storageAccount = 'tamblobtest001';
        $container = 'azurebucket';

        $this->object = new AzureBlob($this->root, $accessKey, $storageAccount, $container);
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


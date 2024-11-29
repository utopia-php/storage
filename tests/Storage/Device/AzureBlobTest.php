<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\AzureBlob;
use Utopia\Tests\Storage\S3Base;

class AzureBlobTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $storageAccount = $_SERVER['AZURE_ACCOUNT'] ?? '';
        $accessKey = $_SERVER['AZURE_ACCESS_KEY'] ?? '';
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

    /* It seems like we cannot use the testFileHash() function in S3Base because Azure Blob generates a
    different hash value each time, even for the same file. */
    public function testFileHash()
    {
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-1.jpg')), $this->object->getFileHash($this->object->getPath('testing/kitten-1.jpg')));
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-2.jpg')), $this->object->getFileHash($this->object->getPath('testing/kitten-2.jpg')));
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-1.png')), $this->object->getFileHash($this->object->getPath('testing/kitten-1.png')));
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-2.png')), $this->object->getFileHash($this->object->getPath('testing/kitten-2.png')));
    }
}

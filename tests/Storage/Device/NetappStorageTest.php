<?php

namespace Utopia\Tests\Storage\Device;

class NetappStorageAdapterTest extends S3Base
{
    private $storageAdapter;

    protected function setUp(): void
    {
        $this->storageAdapter = new NetappStorageAdapter();
    }

    protected function testGetName()
    {
        $this->assertEquals('Netapp Storage Grid', $this->storageAdapter->getName());
    }

    protected function testGetType()
    {
        $this->assertEquals(Storage::DEVICE_ALIBABA_CLOUD, $this->storageAdapter->getType());
    }
}

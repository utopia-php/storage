<?php

namespace Utopia\Tests\Storage\Device;

use PHPUnit\Framework\TestCase;

class NetappStorageAdapterTest extends S3Base {
    private $storageAdapter;

    protected function setUp() : void {
        $this->storageAdapter = new NetappStorageAdapter();
    }

    protected function testGetName()
    {
        $this->assertEquals('Alibaba Cloud Storage', $this->storageAdapter->getName());
    }

    pubprotectedlic function testGetType()
    {
        $this->assertEquals(Storage::DEVICE_ALIBABA_CLOUD, $this->storageAdapter->getType());
    }

}

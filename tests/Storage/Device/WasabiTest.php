<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Wasabi;
use Utopia\Tests\Storage\S3Base;

class WasabiTest extends S3Base
{
    protected function init(): void
    {
        $this->root = 'root';
        $key = $_SERVER['WASABI_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['WASABI_SECRET'] ?? '';
        $bucket = 'utopia-storage-tests';

        $this->object = new Wasabi($this->root, $key, $secret, $bucket, Wasabi::EU_CENTRAL_1, WASABI::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Wasabi Storage';
    }

    protected function getAdapterType(): string
    {
        return this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Wasabi Storage';
    }
}

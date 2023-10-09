<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Swiftstack;
use Utopia\Tests\Storage\S3Base;

class SwiftstackTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['SWIFTSTACK_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['SWIFTSTACK_SECRET'] ?? '';
        $bucket = 'utopia-storage-tests';

        $this->object = new Swiftstack($this->root, $key, $secret, $bucket, Swiftstack::EU_CENTRAL_1, Swiftstack::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Swiftstack Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Swiftstack Storage';
    }
}

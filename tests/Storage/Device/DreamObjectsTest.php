<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\DreamObjects;

class DreamObjectsTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['DREAMOBJECTS_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['DREAMOBJECTS_SECRET'] ?? '';
        $bucket = 'utopia-dreamobjects-store';

        $this->object = new DreamObjects($this->root, $key, $secret, $bucket, DreamObjects::US_EAST_1, DreamObjects::ACL_PUBLIC_READ);
    }

    protected function getAdapterName(): string
    {
        return 'DreamHost Object Storage';
    }

    protected function getAdapterDescription(): string
    {
        return 'DreamHost Object Storage';
    }
}

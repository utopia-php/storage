<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Honeycomb;
use Utopia\Tests\Storage\S3Base;

class HoneycombTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['HONEYCOMB_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['HONEYCOMB_SECRET'] ?? '';
        $bucket = 'utopia-storage-tests';

        $this->object = new Honeycomb($this->root, $key, $secret, $bucket, Honeycomb::EU_CENTRAL_1, HONEYCOMB::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Honeycomb Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Honeycomb Storage';
    }
}
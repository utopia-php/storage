<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Cloudian;
use Utopia\Tests\Storage\S3Base;

class CloudianTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['CLOUDIAN_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['CLOUDIAN_SECRET'] ?? '';
        $bucket = 'utopia-storage-tests';

        $this->object = new Cloudian($this->root, $key, $secret, $bucket, Cloudian::EU_CENTRAL_1, Cloudian::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Cloudian Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Cloudian Storage';
    }
}

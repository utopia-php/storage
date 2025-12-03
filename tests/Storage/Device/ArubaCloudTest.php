<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\ArubaCloud;
use Utopia\Tests\Storage\S3Base;

class ArubaCloudTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['ARUBACLOUD_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['ARUBACLOUD_SECRET'] ?? '';
        $bucket = 'utopia-storage-test';

        $this->object = new ArubaCloud($this->root, $key, $secret, $bucket, ArubaCloud::R1_IT, ArubaCloud::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Aruba Cloud Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Aruba Cloud Storage';
    }
}

<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Ceph;
use Utopia\Tests\Storage\S3Base;

class CephTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['CEPH_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['CEPH_SECRET'] ?? '';
        $bucket = 'utopia-storage-test';

        $this->object = new Ceph($this->root, $key, $secret, $bucket, Ceph::US_EAST_1, Ceph::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Ceph Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Ceph Storage';
    }
}

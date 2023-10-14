<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\IBMCloudObject;
use Utopia\Tests\Storage\S3Base;

class IBMCloudObjectTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['CEPH_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['CEPH_SECRET'] ?? '';
        $bucket = 'utopia-storage-test';

        $this->object = new IBMCloudObject($this->root, $key, $secret, $bucket, IBMCloudObject::US_EAST_1, IBMCloudObject::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'IBM Cloud Object Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'IBM Cloud Object';
    }
}

<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\ObjectStorage;
use Utopia\Tests\S3Base;

class ObjectStorageTest extends S3Base
{
    protected function init(): void
    {
        $this->root = 'root';
        $key = $_SERVER['OBJECT_STORAGE_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['OBJECT_STORAGE_SECRET'] ?? '';
        $bucket = "everly-test";

        $this->object = new ObjectStorage($this->root, $key, $secret, $bucket, ObjectStorage::EU_CENTRAL_1, ObjectStorage::ACL_PRIVATE);

    }

    protected function getAdapterName(): string
    {
        return 'Linode Object Storage';
    }

    protected function getAdapterDescription(): string
    {
        return 'Linode Object Storage';
    }
}

<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\Generic;
use Utopia\Storage\Device\DOSpaces;
use Utopia\Tests\S3Base;


class GenericTest extends S3Base
{
    protected function init(): void
    {
        $key = $_SERVER['DO_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['DO_SECRET'] ?? '';
        $bucket = "utopia-storage-tests";
        $hostName = $bucket . '.' . DOSpaces::NYC3 . '.digitaloceanspaces.com';
        $this->object = new Generic($this->root, $key, $secret, $bucket, DOSpaces::NYC3, DOSpaces::ACL_PUBLIC_READ, $hostName);
    }

    protected function getAdapterName(): string
    {
        return 'S3 compatible Storage';
    }

    protected function getAdapterDescription(): string
    {
        return 'S3 Generic Bucket Storage drive for AWS compatible solutions';
    }
}

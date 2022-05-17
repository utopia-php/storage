<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\Linode;
use Utopia\Tests\S3Base;

class LinodeTest extends S3Base
{
    protected function init(): void
    {
        $key    = $_SERVER['LINODE_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['LINODE_SECRET'] ?? '';
        $bucket = 'appwrite-test';

        $this->object = new Linode($this->root, $key, $secret, $bucket, Linode::US_EAST_1, Linode::ACL_PRIVATE);
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

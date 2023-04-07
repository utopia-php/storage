<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Linode;
use Utopia\Tests\Storage\S3Base;

class LinodeTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['LINODE_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['LINODE_SECRET'] ?? '';
        $bucket = 'everly-test';

        $this->object = new Linode($this->root, $key, $secret, $bucket, Linode::EU_CENTRAL_1, Linode::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Linode Object Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Linode Object Storage';
    }
}

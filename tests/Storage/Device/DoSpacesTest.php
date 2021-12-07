<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\DoSpaces;

class DoSpacesTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['DO_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['DO_SECRET'] ?? '';
        $bucket = "utopia-storage-tests";

        $this->object = new DoSpaces($this->root, $key, $secret, $bucket, DoSpaces::NYC3, DoSpaces::ACL_PUBLIC_READ);

    }

    protected function getAdapterName(): string
    {
        return 'Digitalocean Spaces Storage';
    }

    protected function getAdapterDescription(): string
    {
        return 'Digitalocean Spaces Storage';
    }
}

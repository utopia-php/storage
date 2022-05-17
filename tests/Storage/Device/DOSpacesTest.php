<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\DOSpaces;
use Utopia\Tests\S3Base;

class DOSpacesTest extends S3Base
{
    protected function init(): void
    {
        $key = $_SERVER['DO_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['DO_SECRET'] ?? '';
        $bucket = "utopia-storage-tests";

        $this->object = new DOSpaces($this->root, $key, $secret, $bucket, DOSpaces::NYC3, DOSpaces::ACL_PUBLIC_READ);

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

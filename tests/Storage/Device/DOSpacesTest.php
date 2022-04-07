<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\DOSpaces;
use Utopia\Tests\S3Base;

class DOSpacesTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['DO_ACCESS_KEY'] ?? 'OYHQUUZHZAWG7HB7746V';
        $secret = $_SERVER['DO_SECRET'] ?? 'yG2Uoq/7Kxa3zHeINzj9n5aPEMr+r9ZrqVXQkSIZH8g';
        $bucket = "utopia-storage-tests";
        $bucket = 'shimon-test-space';
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

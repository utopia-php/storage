<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\Wasabi;

class WasabiTest extends S3Base
{
    protected function init(): void
    {
        $this->root = 'root';
        $key = $_SERVER['WASABI_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['WASABI_SECRET'] ?? '';
        $bucket = 'utopia-php-storage-tests';

        $this->object = new Wasabi($this->root, $key, $secret, $bucket, Wasabi::EU_CENTRAL_1, WASABI::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Wasabi Storage';
    }

    protected function getAdapterDescription(): string
    {
        return 'Wasabi Storage';
    }
}

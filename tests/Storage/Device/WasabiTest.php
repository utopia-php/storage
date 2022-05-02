<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\Wasabi;
use Utopia\Tests\S3Base;


class WasabiTest extends S3Base
{
    protected function init(): void
    {
        $key = $_SERVER['WASABI_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['WASABI_SECRET'] ?? '';
        $bucket = "appwrite";

        $this->object = new Wasabi($this->root, $key, $secret, $bucket, WASABI::US_EAST_1, WASABI::ACL_PRIVATE);

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

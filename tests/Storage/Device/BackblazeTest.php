<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Backblaze;
use Utopia\Tests\Storage\S3Base;

class BackblazeTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['BACKBLAZE_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['BACKBLAZE_SECRET'] ?? '';
        $bucket = 'backblaze-demo';

        $this->object = new Backblaze($this->root, $key, $secret, $bucket, Backblaze::US_WEST_004, Backblaze::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Backblaze B2 Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Backblaze B2 Storage';
    }
}

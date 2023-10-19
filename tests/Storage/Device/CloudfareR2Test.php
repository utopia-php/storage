<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\CloudflareR2;
use Utopia\Tests\Storage\S3Base;

class CloudfareR2Test extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['CLOUDFLARE_R2_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['CLOUDFLARE_R2_SECRET'] ?? '';
        $bucket = 'utopia-storage-test';

        $this->object = new CloudflareR2($this->root, $key, $secret, $bucket, CloudflareR2:: AUTO , CloudflareR2::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Cloudflare R2 Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Cloudflare R2 Storage';
    }
}

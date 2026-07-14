<?php

declare(strict_types=1);

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\S3;
use Utopia\Tests\Storage\S3Base;

final class S3Test extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $host = $_SERVER['S3_HOST'] ?? 'http://utopia-storage-test.localhost:9805';
        $key = $_SERVER['S3_ACCESS_KEY'] ?? 'minioadmin';
        $secret = $_SERVER['S3_SECRET'] ?? 'minioadmin';

        $this->object = new S3($this->root, $key, $secret, $host, 'us-east-1', S3::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'S3 Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'S3 Storage drive for generic S3-compatible provider';
    }
}

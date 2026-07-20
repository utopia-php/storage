<?php

declare(strict_types=1);

namespace Utopia\Tests\Storage\E2E;

use Utopia\Storage\Acl;
use Utopia\Storage\Device\S3;
use Utopia\Storage\DeviceType;

final class S3Test extends S3Base
{
    private function env(string $key, string $default): string
    {
        $value = $_SERVER[$key] ?? null;

        return \is_string($value) ? $value : $default;
    }

    protected function init(): void
    {
        $this->root = '/root';
        $host = $this->env('S3_HOST', 'http://utopia-storage-test.localhost:9805');
        $key = $this->env('S3_ACCESS_KEY', 'minioadmin');
        $secret = $this->env('S3_SECRET', 'minioadmin');

        $this->object = new S3($this->root, $key, $secret, $host, 'us-east-1', Acl::Private);
    }

    protected function getAdapterType(): DeviceType
    {
        return $this->object->getType();
    }

}

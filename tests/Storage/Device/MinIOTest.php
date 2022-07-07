<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\MinIO;
use Utopia\Tests\S3Base;

class MinIOTest extends S3Base
{
    protected function init(): void
    {
        $this->root = 'minio-test-bucket';
        $key = $_SERVER['MINIO_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['MINIO_SECRET'] ?? '';
        $protocol = $_SERVER['MINIO_PROTOCOL'] ?? '';
        $host = $_SERVER['MINIO_HOST'] ?? '';
        $bucket = 'minio-test-bucket';

        $this->object = new MinIO($this->root, $key, $secret, $protocol, $host, $bucket, MinIO::EU_CENTRAL_1);

    }

    protected function getAdapterName(): string
    {
        return 'MinIO Object Storage';
    }

    protected function getAdapterDescription(): string
    {
        return 'MinIO Object Storage';
    }
}

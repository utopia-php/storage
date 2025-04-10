<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\AWS;
use Utopia\Tests\Storage\S3Base;

class AWSTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['S3_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['S3_SECRET'] ?? '';
        $bucket = 'utopia-storage-test';

        $this->object = new AWS($this->root, $key, $secret, $bucket, AWS::EU_CENTRAL_1, AWS::ACL_PRIVATE);
    }

    /**
     * @return string
     */
    protected function getAdapterName(): string
    {
        return 'AWS S3 Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'S3 Bucket Storage drive for AWS';
    }
}

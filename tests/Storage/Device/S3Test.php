<?php

namespace Utopia\Tests;

use Utopia\Storage\Device\S3;
use Utopia\Tests\S3Base;

class S3Test extends S3Base
{
    
    protected function init(): void
    {
        $key = $_SERVER['S3_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['S3_SECRET'] ?? '';
        $bucket = 'appwrite-test-bucket';

        $this->object = new S3($this->root, $key, $secret, $bucket, S3::EU_WEST_1, S3::ACL_PRIVATE);
    }

    /**
     * @return string
     */
    protected function getAdapterName() : string
    {
        return 'S3 Storage';
    }

    protected function getAdapterDescription(): string
    {   
        return 'S3 Generic Bucket Storage drive for AWS compatible solutions';
    }
}

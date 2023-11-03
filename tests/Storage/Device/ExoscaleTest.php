<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Exoscale;
use Utopia\Tests\Storage\S3Base;

class ExoscaleTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $apiKey = $_SERVER['EXOSCALE_ACCESS_KEY'] ?? '';
        $apiSecret = $_SERVER['EXOSCALE_SECRET'] ?? '';
        $bucket = 'storage-test';

        $this->object = new Exoscale($this->root, $apiKey, $apiSecret, $bucket, Exoscale::CH_GVA_2, Exoscale::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Exoscale Simple Object Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Exoscale Simple Object Storage';
    }
}

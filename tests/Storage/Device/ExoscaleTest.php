<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Exoscale;
use Utopia\Tests\Storage\S3Base;

class ExoscaleTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $apiKey = 'YOUR_EXOSCALE_API_KEY';
        $apiSecret = 'YOUR_EXOSCALE_API_SECRET';
        $bucket = 'YOUR_EXOSCALE_BUCKET';
        $region = Exoscale::CH_GVA_2; 
        $acl = Exoscale::ACL_PRIVATE; 

        $this->object = new Exoscale($this->root, $apiKey, $apiSecret, $bucket, $region, $acl);
    }

    protected function getAdapterName(): string
    {
        return 'Exoscale Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Exoscale Storage';
    }
}
<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Yandex;
use Utopia\Tests\Storage\S3Base;

class YandexTest extends S3Base
{
    protected function init(): void
    {
        $this->root = '/root';
        $key = $_SERVER['YANDEX_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['YANDEX_SECRET'] ?? '';
        $bucket = 'utopia-storage-tests';
        $region = 'ru-central1-a';


        $this->object = new Yandex($this->root, $key, $secret, $bucket, $region, Yandex::ACL_PRIVATE);
    }

    protected function getAdapterName(): string
    {
        return 'Yandex Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Yandex Storage';
    }
}

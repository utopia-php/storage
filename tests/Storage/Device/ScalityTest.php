<?php

namespace Utopia\Tests\Device;

use Utopia\Storage\Device\Scality;
use Utopia\Tests\S3Base;

class ScalityTest extends S3Base
{
    protected function init(): void
    {
        $key = $_SERVER['SCALITY_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['SCALITY_SECRET'] ?? '';

        $this->root = 'root';
        $bucket = 'scality-tests';

        $this->object = new Scality($this->root, $key, $secret, $bucket);
    }

    protected function getAdapterName(): string
    {
        return 'Scality Storage';
    }

    protected function getAdapterDescription(): string
    {
        return 'Scality Storage';
    }
}

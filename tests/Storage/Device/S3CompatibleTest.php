<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\S3Compatible;
use Utopia\Tests\Storage\S3Base;

class S3CompatibleTest extends S3Base
{
    protected function init(): void
    {
        $root = '/root';
        $endpoint = $_SERVER['S3COMPATIBLE_ENDPOINT'] ?? '';
        $vhost = $_SERVER['S3COMPATIBLE_VHOST'] === 'true';
        $this->vhost = $vhost;
        $key = $_SERVER['S3COMPATIBLE_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['S3COMPATIBLE_SECRET'] ?? '';
        $bucket = $_SERVER['S3COMPATIBLE_BUCKET'] ?? 'appwrite-test-bucket';
        $region = $_SERVER['S3COMPATIBLE_REGION'] ?? '';

        if ($vhost) {
            $this->root = $root;
        } else {
            $this->root = $bucket.$root;
        }

        $this->object = new S3Compatible($endpoint, $root, $key, $secret, $bucket, $vhost, $region);
    }

    /**
     * @return string
     */
    public function getAdapterName(): string
    {
        return $this->object->getName();
    }

    /**
     * @return string
     */
    public function getAdapterType(): string
    {
        return $this->object->getType();
    }

    /**
     * @return string
     */
    public function getAdapterDescription(): string
    {
        return $this->object->getDescription();
    }
}

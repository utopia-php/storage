<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Ceph extends S3
{
    /**
     * Ceph Constructor
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $cephHost, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $acl);
        $this->headers['host'] = $cephHost;
    }

    public function getName(): string
    {
        return 'Ceph Storage';
    }

    public function getDescription(): string
    {
        return 'Ceph Storage';
    }

    public function getType(): string
    {
        return Storage::DEVICE_CEPH;
    }
}

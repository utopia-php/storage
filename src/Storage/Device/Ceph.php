<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Ceph extends S3
{
    /**
     * Regions constants
     */
    const US_EAST_1 = 'us-east-1';

    const US_EAST_2 = 'us-east-2';

    const US_WEST_1 = 'us-west-1';

    const US_WEST_2 = 'us-west-2';

    /**
     * Ceph Constructor
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $cephHost, string $region = self::US_WEST_001, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
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

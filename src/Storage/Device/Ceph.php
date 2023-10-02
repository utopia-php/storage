<?php

namespace Utopia\Storage\Device;

class Ceph extends S3
{
    /**
     * Regions constants
     */
    const US_EAST_1 = 'us-east-1';

    /**
     * Ceph Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $region
     * @param  string  $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::US_WEST_001, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket.'.'.'s3'.'.'.$region.'.ceph.io';
    }
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Ceph Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Ceph Storage';
    }
    
    public function getType(): string
    {
        return Storage::DEVICE_CEPH;
    }
}
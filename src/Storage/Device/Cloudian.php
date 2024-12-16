<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Cloudian extends S3
{
    /**
     * Cloudian Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $region
     * @param  string  $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::US_EAST_1, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket.'.'.'s3'.'.'.$region.'.cloudian.com';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Cloudian Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_CLOUDIAN;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Cloudian Storage';
    }
}

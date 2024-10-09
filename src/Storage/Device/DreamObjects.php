<?php

namespace Utopia\Storage\Device;

class DreamObjects extends S3
{
    /**
     * Regions constants
     */
    const US_EAST_1 = 'us-east-1';

    /**
     * Object Storage Constructor
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
        $this->headers['host'] = $bucket.'objects-'.$region.'.'.'dream.io';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'DreamHost Object Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'DreamHost Object Storage';
    }
}

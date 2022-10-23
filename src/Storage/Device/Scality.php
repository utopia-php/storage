<?php

namespace Utopia\Storage\Device;

class Scality extends S3
{
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::US_EAST_1, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Scality Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Scality Storage';
    }
}

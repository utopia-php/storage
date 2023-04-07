<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Scality extends S3
{
    public function __construct(
        string $root,
        string $accessKey,
        string $secretKey,
        string $bucket,
        string $region = self::US_EAST_1,
        string $acl = self::ACL_PRIVATE,
        ?string $host = null
    ) {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);

        $this->headers['host'] = $host ?? $bucket.'.s3.'.$region.'.amazonaws.com';
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

    public function getType(): string
    {
        return Storage::DEVICE_SCALITY;
    }
}

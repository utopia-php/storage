<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;
use Utopia\Storage\Storage;

// As NetAapp Storage grid uses S3 protocol, we can extend the S3 class
// https://docs.netapp.com/us-en/storagegrid-116/s3/index.html
class NetappStorageGrid extends S3
{
    protected string $accessKey;
    protected string $secretKey;
    protected string $bucket;

    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->root = $root;
        $this->acl = $acl;
        $this->amzHeaders = [];

        $host = match ($region) {
            self::CN_NORTH_1, self::CN_NORTH_4, self::CN_NORTHWEST_1 => $bucket.'.s3.'.$region.'.amazonaws.cn',
            default => $bucket.'.s3.'.$region.'.amazonaws.com'
        };

        $this->headers['host'] = $host;
    }

    public function getName(): string
    {
        return 'Netapp Storage Grid'
    }

    public function getType(): string
    {
        return STORAGE::DEVICE_NETAPP_STORAGE_GRID;
    }

    public function getDescription(): string
    {
        return 'NetApp Storage Grid using S3 Storage drive';
    }

}

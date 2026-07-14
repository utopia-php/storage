<?php

declare(strict_types=1);

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Linode extends S3
{
    /**
     * Regions constants
     */
    public const EU_CENTRAL_1 = 'eu-central-1';

    public const US_SOUTHEAST_1 = 'us-southeast-1';

    public const US_EAST_1 = 'us-east-1';

    public const AP_SOUTH_1 = 'ap-south-1';

    /**
     * Object Storage Constructor
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::EU_CENTRAL_1, string $acl = self::ACL_PRIVATE)
    {
        $host = $bucket . '.' . $region . '.' . 'linodeobjects.com';
        parent::__construct($root, $accessKey, $secretKey, $host, $region, $acl);
    }

    #[\Override]
    public function getName(): string
    {
        return 'Linode Object Storage';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Linode Object Storage';
    }

    #[\Override]
    public function getType(): string
    {
        return Storage::DEVICE_LINODE;
    }
}

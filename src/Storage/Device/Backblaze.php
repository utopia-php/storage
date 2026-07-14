<?php

declare(strict_types=1);

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Backblaze extends S3
{
    /**
     * Region constants
     *
     * (Technically, these are clusters. There are two Backblaze regions,
     * US West and EU Central.)
     */
    public const US_WEST_000 = 'us-west-000';

    public const US_WEST_001 = 'us-west-001';

    public const US_WEST_002 = 'us-west-002';

    public const US_WEST_004 = 'us-west-004';

    public const EU_CENTRAL_003 = 'eu-central-003';

    /**
     * Backblaze Constructor
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::US_WEST_004, string $acl = self::ACL_PRIVATE)
    {
        $host = $bucket . '.' . 's3' . '.' . $region . '.backblazeb2.com';
        parent::__construct($root, $accessKey, $secretKey, $host, $region, $acl);
    }

    #[\Override]
    public function getName(): string
    {
        return 'Backblaze B2 Storage';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Backblaze B2 Storage';
    }

    #[\Override]
    public function getType(): string
    {
        return Storage::DEVICE_BACKBLAZE;
    }
}

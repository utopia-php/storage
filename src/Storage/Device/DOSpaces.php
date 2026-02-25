<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class DOSpaces extends S3
{
    /**
     * Regions constants
     */
    const SGP1 = 'sgp1';

    const NYC3 = 'nyc3';

    const FRA1 = 'fra1';

    const SFO2 = 'sfo2';

    const SFO3 = 'sfo3';

    const AMS3 = 'AMS3';

    /**
     * DOSpaces Constructor
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::NYC3, string $acl = self::ACL_PRIVATE)
    {
        $host = $bucket.'.'.$region.'.digitaloceanspaces.com';
        parent::__construct($root, $accessKey, $secretKey, $host, $region, $acl);
    }

    public function getName(): string
    {
        return 'Digitalocean Spaces Storage';
    }

    public function getDescription(): string
    {
        return 'Digitalocean Spaces Storage';
    }

    public function getType(): string
    {
        return Storage::DEVICE_DO_SPACES;
    }
}

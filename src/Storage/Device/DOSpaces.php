<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class DOSpaces extends S3
{
    /**
     * Regions constants
     */
    public const SGP1 = 'sgp1';

    public const NYC3 = 'nyc3';

    public const FRA1 = 'fra1';

    public const SFO2 = 'sfo2';

    public const SFO3 = 'sfo3';

    public const AMS3 = 'AMS3';

    /**
     * DOSpaces Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $region
     * @param  string  $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::NYC3, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket.'.'.$region.'.digitaloceanspaces.com';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Digitalocean Spaces Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Digitalocean Spaces Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_DO_SPACES;
    }
}

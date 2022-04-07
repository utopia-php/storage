<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device\Generic;


class DOSpaces extends Generic
{
    /**
     * Regions constants
     *
     */
    const SGP1 = 'sgp1';
    const NYC3 = 'nyc3';
    const FRA1 = 'fra1';
    const SFO2 = 'sfo2';
    const SFO3 = 'sfo3';
    const AMS3 = 'AMS3';

    /**
     * DOSpaces Constructor
     *
     * @param string $root
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $region
     * @param string $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::NYC3, string $acl = self::ACL_PRIVATE)
    {
        $hostName = $bucket . '.' . $region . '.digitaloceanspaces.com';
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl, $hostName);

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
}

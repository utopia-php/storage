<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device\S3;


class BackBlaze extends S3
{
    /**
     * Regions constants
     *
     */
    const US_WEST_001 = 'us-west-001';
    const US_WEST_002 = 'us-west-002';
    const US_WEST_003 = 'us-west-003';
    const US_WEST_004 = 'us-west-004';
    const EU_CENTRAL_001 = 'eu-central-001';
    const EU_CENTRAL_002 = 'eu-central-002';
    const EU_CENTRAL_003 = 'eu-central-003';
    const EU_CENTRAL_004 = 'eu-central-004';

    /**
     * BackBlaze Constructor
     *
     * @param string $root
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $region
     * @param string $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::US_WEST_004, string $acl = self::ACL_PRIVATE)
    {
        $hostName = $bucket . '.' . 's3' . '.' . $region . '.backblazeb2.com';
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl, $hostName);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'BackBlaze B2 Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'BackBlaze B2 Storage';
    }
}

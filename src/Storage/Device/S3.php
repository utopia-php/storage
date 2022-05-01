<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device\Generic;


class S3 extends Generic
{
    /**
     * AWS Regions constants
     */
    const US_EAST_1 = 'us-east-1';
    const US_EAST_2 = 'us-east-2';
    const US_WEST_1 = 'us-west-1';
    const US_WEST_2 = 'us-west-2';
    const AF_SOUTH_1 = 'af-south-1';
    const AP_EAST_1 = 'ap-east-1';
    const AP_SOUTH_1 = 'ap-south-1';
    const AP_NORTHEAST_3 = 'ap-northeast-3';
    const AP_NORTHEAST_2 = 'ap-northeast-2';
    const AP_NORTHEAST_1 = 'ap-northeast-1';
    const AP_SOUTHEAST_1 = 'ap-southeast-1';
    const AP_SOUTHEAST_2 = 'ap-southeast-2';
    const CA_CENTRAL_1 = 'ca-central-1';
    const EU_CENTRAL_1 = 'eu-central-1';
    const EU_WEST_1 = 'eu-west-1';
    const EU_SOUTH_1 = 'eu-south-1';
    const EU_WEST_2 = 'eu-west-2';
    const EU_WEST_3 = 'eu-west-3';
    const EU_NORTH_1 = 'eu-north-1';
    const SA_EAST_1 = 'eu-north-1';
    const CN_NORTH_1 = 'cn-north-1';
    const ME_SOUTH_1 = 'me-south-1';
    const CN_NORTHWEST_1 = 'cn-northwest-1';
    const US_GOV_EAST_1 = 'us-gov-east-1';
    const US_GOV_WEST_1 = 'us-gov-west-1';



    /**
     * S3 Constructor
     *
     * @param string $root
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $region
     * @param string $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::EU_WEST_1, string $acl = self::ACL_PRIVATE)
    {
        $hostName = $bucket . '.s3.'.$region.'.amazonaws.com';
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl,  $hostName);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'S3 Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'S3 Bucket Storage drive for AWS or on premise solution...';
    }


}

<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device\S3;

class GoogleCloud extends S3
{
    // Google Cloud regions
    const ASIA_EAST_1_A = 'asia-east1-a';
    const ASIA_EAST_1_B = 'asia-east1-b';
    const ASIA_EAST_1_C = 'asia-east1-c';
    const ASIA_EAST_2_A = 'asia-east2-a';
    const ASIA_EAST_2_B = 'asia-east2-b';
    const ASIA_EAST_2_C = 'asia-east2-c';
    const ASIA_NORTHEAST_1_A = 'asia-northeast1-a';
    const ASIA_NORTHEAST_1_B = 'asia-northeast1-b';
    const ASIA_NORTHEAST_1_C = 'asia-northeast1-c';
    const ASIA_NORTHEAST_2_A = 'asia-northeast2-a';
    const ASIA_NORTHEAST_2_B = 'asia-northeast2-b';
    const ASIA_NORTHEAST_2_C = 'asia-northeast2-c';
    const ASIA_NORTHEAST_3_A = 'asia-northeast3-a';
    const ASIA_NORTHEAST_3_B = 'asia-northeast3-b';
    const ASIA_NORTHEAST_3_C = 'asia-northeast3-c';
    const ASIA_SOUTH_1_A = 'asia-south1-a';
    const ASIA_SOUTH_1_B = 'asia-south1-b';
    const ASIA_SOUTH_1_C = 'asia-south1-c';
    const ASIA_SOUTH_2_A = 'asia-south2-a';
    const ASIA_SOUTH_2_B = 'asia-south2-b';
    const ASIA_SOUTH_2_C = 'asia-south2-c';
    const ASIA_SOUTHEAST_1_A = 'asia-southeast1-a';
    const ASIA_SOUTHEAST_1_B = 'asia-southeast1-b';
    const ASIA_SOUTHEAST_1_C = 'asia-southeast1-c';
    const ASIA_SOUTHEAST_2_A = 'asia-southeast2-a';
    const ASIA_SOUTHEAST_2_B = 'asia-southeast2-b';
    const ASIA_SOUTHEAST_2_C = 'asia-southeast2-c';
    const AUSTRALIA_SOUTHEAST_1_A = 'australia-southeast1-a';
    const AUSTRALIA_SOUTHEAST_1_B = 'australia-southeast1-b';
    const AUSTRALIA_SOUTHEAST_1_C = 'australia-southeast1-c';
    const AUSTRALIA_SOUTHEAST_2_A = 'australia-southeast2-a';
    const AUSTRALIA_SOUTHEAST_2_B = 'australia-southeast2-b';
    const AUSTRALIA_SOUTHEAST_2_C = 'australia-southeast2-c';
    const EUROPE_CENTRAL_2_A = 'europe-central2-a';
    const EUROPE_CENTRAL_2_B = 'europe-central2-b';
    const EUROPE_CENTRAL_2_C = 'europe-central2-c';
    const EUROPE_NORTH_1_A = 'europe-north1-a';
    const EUROPE_NORTH_1_B = 'europe-north1-b';
    const EUROPE_NORTH_1_C = 'europe-north1-c';
    const EUROPE_SOUTHWEST_1_A = 'europe-southwest1-a';
    const EUROPE_SOUTHWEST_1_B = 'europe-southwest1-b';
    const EUROPE_SOUTHWEST_1_C = 'europe-southwest1-c';
    const EUROPE_WEST_1_B = 'europe-west1-b';
    const EUROPE_WEST_1_C = 'europe-west1-c';
    const EUROPE_WEST_1_D = 'europe-west1-d';
    const EUROPE_WEST_2_A = 'europe-west2-a';
    const EUROPE_WEST_2_B = 'europe-west2-b';
    const EUROPE_WEST_2_C = 'europe-west2-c';
    const EUROPE_WEST_3_A = 'europe-west3-a';
    const EUROPE_WEST_3_B = 'europe-west3-b';
    const EUROPE_WEST_3_C = 'europe-west3-c';
    const EUROPE_WEST_4_A = 'europe-west4-a';
    const EUROPE_WEST_4_B = 'europe-west4-b';
    const EUROPE_WEST_4_C = 'europe-west4-c';
    const EUROPE_WEST_6_A = 'europe-west6-a';
    const EUROPE_WEST_6_B = 'europe-west6-b';
    const EUROPE_WEST_6_C = 'europe-west6-c';
    const EUROPE_WEST_8_A = 'europe-west8-a';
    const EUROPE_WEST_8_B = 'europe-west8-b';
    const EUROPE_WEST_8_C = 'europe-west8-c';
    const EUROPE_WEST_9_A = 'europe-west9-a';
    const EUROPE_WEST_9_B = 'europe-west9-b';
    const EUROPE_WEST_9_C = 'europe-west9-c';
    const ME_WEST_1_A = 'me-west1-a';
    const ME_WEST_1_B = 'me-west1-b';
    const ME_WEST_1_C = 'me-west1-c';
    const NORTHAMERICA_NORTHEAST_1_A = 'northamerica-northeast1-a';
    const NORTHAMERICA_NORTHEAST_1_B = 'northamerica-northeast1-b';
    const NORTHAMERICA_NORTHEAST_1_C = 'northamerica-northeast1-c';
    const NORTHAMERICA_NORTHEAST_2_A = 'northamerica-northeast2-a';
    const NORTHAMERICA_NORTHEAST_2_B = 'northamerica-northeast2-b';
    const NORTHAMERICA_NORTHEAST_2_C = 'northamerica-northeast2-c';
    const SOUTHAMERICA_EAST_1_A = 'southamerica-east1-a';
    const SOUTHAMERICA_EAST_1_B = 'southamerica-east1-b';
    const SOUTHAMERICA_EAST_1_C = 'southamerica-east1-c';
    const SOUTHAMERICA_WEST_1_A = 'southamerica-west1-a';
    const SOUTHAMERICA_WEST_1_B = 'southamerica-west1-b';
    const SOUTHAMERICA_WEST_1_C = 'southamerica-west1-c';
    const US_CENTRAL_1_A = 'us-central1-a';
    const US_CENTRAL_1_B = 'us-central1-b';
    const US_CENTRAL_1_C = 'us-central1-c';
    const US_CENTRAL_1_F = 'us-central1-f';
    const US_EAST_1_B = 'us-east1-b';
    const US_EAST_1_C = 'us-east1-c';
    const US_EAST_1_D = 'us-east1-d';
    const US_EAST_4_A = 'us-east4-a';
    const US_EAST_4_B = 'us-east4-b';
    const US_EAST_4_C = 'us-east4-c';
    const US_EAST_5_A = 'us-east5-a';
    const US_EAST_5_B = 'us-east5-b';
    const US_EAST_5_C = 'us-east5-c';
    const US_SOUTH_1_A = 'us-south1-a';
    const US_SOUTH_1_B = 'us-south1-b';
    const US_SOUTH_1_C = 'us-south1-c';
    const US_WEST_1_A = 'us-west1-a';
    const US_WEST_1_B = 'us-west1-b';
    const US_WEST_1_C = 'us-west1-c';
    const US_WEST_2_A = 'us-west2-a';
    const US_WEST_2_B = 'us-west2-b';
    const US_WEST_2_C = 'us-west2-c';
    const US_WEST_3_A = 'us-west3-a';
    const US_WEST_3_B = 'us-west3-b';
    const US_WEST_3_C = 'us-west3-c';
    const US_WEST_4_A = 'us-west4-a';
    const US_WEST_4_B = 'us-west4-b';
    const US_WEST_4_C = 'us-west4-c';

    /**
     * Object Storage Constructor
     *
     * @param string $root
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $region
     * @param string $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::EUROPE_CENTRAL_2_A, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket . '.storage.googleapis.com';
    }
}

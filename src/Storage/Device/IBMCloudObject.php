<?php

namespace Utopia\Storage\Device;
use Utopia\Storage\Storage;

class IBMCloudObject extends S3{
    /**
     * Regions constants
     */
    const EU_CENTRAL_1 = 'eu-central-1';

    const US_SOUTHEAST_1 = 'us-southeast-1';

    const US_EAST_1 = 'us-east-1';

    const AP_SOUTH_1 = 'ap-south-1';

    /**
     * Object Storage Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $region
     * @param  string  $acl
     */

     public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::EU_CENTRAL_1, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket.'.'.$region.'.'.'cloud.ibm.com';
    }

     /**
     * @return string
     */
    public function getName(): string
    {
        return 'IBM Cloud Object Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'IBM Cloud Object Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_IBM_CLOUD_OBJECT;
    }
}
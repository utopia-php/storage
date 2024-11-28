<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Honeycomb extends S3
{
    /**
     * Regions constants
     */

    //atm there is only one region. new regions will appear soon.
    const EU_CENTRAL_1 = 'eu-de-fsn';

    /**
     * Honeycomb Constructor
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
        //https://s3.honeycomb-cloud.de/bucketname
        $this->headers['host'] = 's3.honeycomb-cloud.de/'.$bucket;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Honeycomb Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Honeycomb Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_HONEYCOMB;
    }
}
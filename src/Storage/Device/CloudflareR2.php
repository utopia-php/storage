<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class CloudflareR2 extends S3
{
    /**
     * Regions constants
     */

    /**
     * Regions constants
     */
    const WNAM = 'us-west-1';

    const ENAM = 'us-east-1';

    const WEUR = 'eu-west-1';

    const EEUR = 'eu-east-1';

    const APAC = 'ap-southeast-1';

    const AUTO = 'auto';

    /**
     * Cloudflare R2 Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $region
     * @param  string  $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::APAC, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket.'.'.'s3'.'.'.$region.'.cloudflarestorage.com';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Cloudflare R2 Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Cloudflare R2 Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_CLOUDFLARER2;
    }
}

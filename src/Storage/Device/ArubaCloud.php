<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

final class ArubaCloud extends S3
{
    /**
     * Region constants
     *
     */
    const R1_IT = 'r1-it';

    const R1_CZ = 'r1-cz';

    const R1_FR = 'r1-fr';

    const R1_DE = 'r1-de';

    const R1_UK = 'r1-uk';

    const R1_PL = 'r1-pl';

    /**
     * ArubaCloud Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $region
     * @param  string  $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::R1_IT, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region);
        $this->headers['host'] = 's3.arubacloud.com/' . $bucket;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Aruba Cloud Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_ARUBA_CLOUD;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Aruba Cloud Storage';
    }
}

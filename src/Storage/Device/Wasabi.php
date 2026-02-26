<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Wasabi extends S3
{
    /**
     * Regions constants
     */
    const US_WEST_1 = 'us-west-1';

    const AP_NORTHEAST_1 = 'ap-northeast-1';

    const AP_NORTHEAST_2 = 'ap-northeast-2';

    const EU_CENTRAL_1 = 'eu-central-1';

    const EU_CENTRAL_2 = 'eu-central-2';

    const EU_WEST_1 = 'eu-west-1';

    const EU_WEST_2 = 'eu-west-2';

    const US_CENTRAL_1 = 'us-central-1';

    const US_EAST_1 = 'us-east-1';

    const US_EAST_2 = 'us-east-2';

    /**
     * Wasabi Constructor
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::EU_CENTRAL_1, string $acl = self::ACL_PRIVATE)
    {
        $host = $bucket.'.'.'s3'.'.'.$region.'.'.'wasabisys'.'.'.'com';
        parent::__construct($root, $accessKey, $secretKey, $host, $region, $acl);
    }

    public function getName(): string
    {
        return 'Wasabi Storage';
    }

    public function getDescription(): string
    {
        return 'Wasabi Storage';
    }

    public function getType(): string
    {
        return Storage::DEVICE_WASABI;
    }
}

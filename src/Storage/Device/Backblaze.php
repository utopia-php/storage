<?php

// namespace Utopia\Storage\Device;

// use Utopia\Storage\Storage;

// class Backblaze extends S3
// {
//     /**
//      * Region constants
//      *
//      * (Technically, these are clusters. There are two Backblaze regions,
//      * US West and EU Central.)
//      */
//     const US_WEST_000 = 'us-west-000';

//     const US_WEST_001 = 'us-west-001';

//     const US_WEST_002 = 'us-west-002';

//     const US_WEST_004 = 'us-west-004';

//     const EU_CENTRAL_003 = 'eu-central-003';

//     /**
//      * Backblaze Constructor
//      *
//      * @param  string  $root
//      * @param  string  $accessKey
//      * @param  string  $secretKey
//      * @param  string  $bucket
//      * @param  string  $region
//      * @param  string  $acl
//      */
//     public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::US_WEST_004, string $acl = self::ACL_PRIVATE)
//     {
//         parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
//         $this->headers['host'] = $bucket.'.'.'s3'.'.'.$region.'.backblazeb2.com';
//     }

//     /**
//      * @return string
//      */
//     public function getName(): string
//     {
//         return 'Backblaze B2 Storage';
//     }

//     /**
//      * @return string
//      */
//     public function getDescription(): string
//     {
//         return 'Backblaze B2 Storage';
//     }

//     /**
//      * @return string
//      */
//     public function getType(): string
//     {
//         return Storage::DEVICE_BACKBLAZE;
//     }
// }

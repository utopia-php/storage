<?php

// namespace Utopia\Tests\Storage\Device;

// use Utopia\Storage\Device\S3;
// use Utopia\Tests\Storage\S3Base;

// class S3Test extends S3Base
// {
//     protected function init(): void
//     {
//         $this->root = '/root';
//         $key = $_SERVER['S3_ACCESS_KEY'] ?? '';
//         $secret = $_SERVER['S3_SECRET'] ?? '';
//         $bucket = 'appwrite-test-bucket';

//         $this->object = new S3($this->root, $key, $secret, $bucket, S3::EU_WEST_1, S3::ACL_PRIVATE);
//     }

//     /**
//      * @return string
//      */
//     protected function getAdapterName(): string
//     {
//         return 'S3 Storage';
//     }

//     protected function getAdapterType(): string
//     {
//         return $this->object->getType();
//     }

//     protected function getAdapterDescription(): string
//     {
//         return 'S3 Bucket Storage drive for AWS or on premise solution';
//     }
// }

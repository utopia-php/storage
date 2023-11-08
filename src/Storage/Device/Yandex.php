<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Storage;

class Yandex extends S3
{
    /**
     * Regions constants
     */

    const RU_CENTRAL_A = 'ru-central1-a';
    const RU_CENTRAL_B = 'ru-central1-b';
    const RU_CENTRAL_C = 'ru-central1-c';

    protected string $bucket;

    protected string $region = 'ru-central1-a';

    /**
     * YandexStorage Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $acl
     */

    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::RU_CENTRAL_A, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket . '.storage.yandexcloud.net';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Yandex Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Yandex Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_YANDEX;
    }
}

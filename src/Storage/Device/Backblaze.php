<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Storage;

class Backblaze extends S3
{
    /**
     * Region constants
     *
     * (Technically, these are clusters. There are two Backblaze regions,
     * US West and EU Central.)
     */
    const US_WEST_000 = 'us-west-000';

    const US_WEST_001 = 'us-west-001';

    const US_WEST_002 = 'us-west-002';

    const US_WEST_004 = 'us-west-004';

    const EU_CENTRAL_003 = 'eu-central-003';

    /**
     * Backblaze Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $region
     * @param  string  $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $bucket, string $region = self::US_WEST_004, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        $this->headers['host'] = $bucket.'.'.'s3'.'.'.$region.'.backblazeb2.com';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Backblaze B2 Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Backblaze B2 Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_BACKBLAZE;
    }

    /**
     * Get list of objects in the given path.
     *
     * @param  string  $prefix
     * @param  int  $maxKeys
     * @param  string  $continuationToken
     * @return array
     *
     * @throws Exception
     */
    protected function listObjects(string $prefix = '', int $maxKeys = self::MAX_PAGE_SIZE, string $continuationToken = ''): array
    {
        if ($maxKeys > S3::MAX_PAGE_SIZE) {
            throw new Exception('Cannot list more than '.S3::MAX_PAGE_SIZE.' objects');
        }

        $uri = '/';
        $prefix = ltrim($prefix, '/'); /** S3 specific requirement that prefix should never contain a leading slash */
        $this->headers['content-type'] = 'text/plain';
        $this->headers['content-md5'] = \base64_encode(md5('', true));

        $parameters = [
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
        ];

        if (! empty($continuationToken)) {
            $parameters['continuation-token'] = $continuationToken;
        }

        $response = $this->call(S3::METHOD_GET, $uri, '', $parameters);

        return $response->body;
    }
}

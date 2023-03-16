<?php

declare(strict_types=1);

namespace Utopia\Storage\Device;

use function preg_replace;
use Utopia\Storage\Storage;

/**
 * Adapter for S3-Compatible storage providers
 *
 * Endpoint URL is explicitly defined by the caller due to providers allowing
 * path-style or vhost-style endpoints as well as extra parameters such as
 * client ID.
 */
class S3Compatible extends S3
{
    /**
     * @var string
     */
    protected string $endpoint;

    /**
     * S3Compatible Constructor
     *
     * @param  string  $endpoint
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  bool  $vhost
     * @param  string  $region
     * @param  string  $acl
     */
    public function __construct(string $endpoint, string $root, string $accessKey, string $secretKey, string $bucket, bool $vhost, string $region = self::US_EAST_1, string $acl = self::ACL_PRIVATE)
    {
        if (! $vhost) {
            $root = $bucket.$root;
        }
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);

        $this->endpoint = $endpoint;
        $this->vhost = $vhost;

        /**
         * Workaround to prevent having to do a refactor of
         * the S3 class along with adapter addition. Class only
         * supports https for the time being.
         *
         * There are multiple endpoint styles dependent upon the provider
         * used. Examples are:
         * <scheme>://<endpoint.url>/<bucket>
         * <scheme>://<clientid.endpoint.url>/<bucket>
         * <scheme>://<bucketvhost.endpoint.url>
         */
        $endpoint = preg_replace('/^https?:\/\//i', '', $endpoint);
        $this->headers['host'] = $endpoint;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'S3-Compatible Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_S3COMPATIBLE;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Generic Connector For S3-Compatible Storage Providers';
    }
}

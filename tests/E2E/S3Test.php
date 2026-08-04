<?php

declare(strict_types=1);

namespace Utopia\Tests\Storage\E2E;

use Throwable;
use Utopia\Psr7\Stream;
use Utopia\Storage\Acl;
use Utopia\Storage\Device\S3;
use Utopia\Storage\DeviceType;
use Utopia\Storage\FileInfo;

final class S3Test extends S3Base
{
    private string $accessKey;

    private string $bucket;

    private string $region;

    private string $secretKey;

    private ?string $pathHost = null;

    private string $pathBucket;

    private function env(string $key, string $default): string
    {
        $value = $_SERVER[$key] ?? null;

        return \is_string($value) ? $value : $default;
    }

    private function configured(string $key): ?string
    {
        $value = $_SERVER[$key] ?? null;

        return \is_string($value) && $value !== '' ? $value : null;
    }

    protected function init(): void
    {
        $this->root = '/root';
        $this->bucket = $this->env('S3_BUCKET', 'utopia-storage-test');
        $this->accessKey = $this->env('S3_ACCESS_KEY', 'minioadmin');
        $this->secretKey = $this->env('S3_SECRET', 'minioadmin');
        $this->region = $this->env('S3_REGION', 'us-east-1');
        $this->pathHost = $this->configured('S3_PATH_HOST');
        $this->pathBucket = $this->env('S3_PATH_BUCKET', $this->bucket);
        $host = $this->env('S3_HOST', "http://{$this->bucket}.localhost:9805");

        $this->object = new S3($this->root, $this->accessKey, $this->secretKey, $host, $this->region, Acl::Private, bucket: $this->bucket);
    }

    protected function getAdapterType(): DeviceType
    {
        return $this->object->getType();
    }

    public function testPathStyleEndpointSharesVirtualHostedBucket(): void
    {
        $pathHost = $this->pathHost;
        $virtualHost = $this->configured('S3_HOST');
        if (($pathHost === null) !== ($virtualHost === null)) {
            self::markTestSkipped('S3_HOST and S3_PATH_HOST must both be configured when overriding the local MinIO endpoints');
        }

        if ($pathHost === null) {
            $pathHost = 'http://localhost:9805';
        }

        $this->assertSame($this->bucket, $this->pathBucket, 'S3_PATH_BUCKET and S3_BUCKET must identify the same bucket for cross-visibility');

        $path = new S3(
            root: '/',
            accessKey: $this->accessKey,
            secretKey: $this->secretKey,
            host: rtrim($pathHost, '/') . '/' . rawurlencode($this->pathBucket) . '/',
            region: $this->region,
            bucket: $this->pathBucket,
        );
        $prefix = 'endpoint-path/' . bin2hex(random_bytes(8));
        $pathObject = $prefix . '/path.txt';
        $virtualObject = $prefix . '/virtual.txt';
        $failure = null;

        try {
            $this->assertTrue($path->write($pathObject, new Stream('path-style'), 'text/plain'));
            $this->assertSame('path-style', (string) $this->object->read($pathObject));

            $this->assertTrue($this->object->write($virtualObject, new Stream('virtual-hosted'), 'text/plain'));
            $this->assertSame('virtual-hosted', (string) $path->read($virtualObject));
            $this->assertSame(
                [$pathObject, $virtualObject],
                array_map(static fn(FileInfo $file): string => $file->path, $path->listFiles($prefix)->files),
            );
        } catch (Throwable $error) {
            $failure = $error;

            throw $error;
        } finally {
            $cleanup = [];
            foreach ([[$path, $pathObject], [$this->object, $virtualObject]] as [$device, $object]) {
                try {
                    if (! $device->delete($object)) {
                        $cleanup[] = "Failed to delete {$object}";
                    }
                } catch (Throwable $error) {
                    $cleanup[] = "Failed to delete {$object}: {$error->getMessage()}";
                }
            }

            if (!$failure instanceof Throwable && $cleanup !== []) {
                self::fail(implode('; ', $cleanup));
            }
        }
    }
}

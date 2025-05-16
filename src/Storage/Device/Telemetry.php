<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device;
use Utopia\Telemetry\Adapter;

class Telemetry extends Device
{
    public function __construct(Adapter $telemetry, private Device $underlying)
    {
        parent::__construct($telemetry);
    }

    public function setTelemetry(Adapter $telemetry): void
    {
        parent::setTelemetry($telemetry);
        $this->underlying->setTelemetry($telemetry);
    }

    private function measure(string $method, &...$args): mixed
    {
        $start = microtime(true);
        try {
            return $this->underlying->{$method}(...$args);
        } finally {
            $this->storageOperationTelemetry->record(
                microtime(true) - $start,
                [
                    'storage' => $this->underlying->getType(),
                    'operation' => "device:$method",
                ]
            );
        }
    }

    public function getName(): string
    {
        return $this->underlying->getName();
    }

    public function getType(): string
    {
        return $this->underlying->getType();
    }

    public function getDescription(): string
    {
        return $this->underlying->getDescription();
    }

    public function getRoot(): string
    {
        return $this->underlying->getRoot();
    }

    public function getPath(string $filename, string $prefix = null): string
    {
        return $this->measure(__FUNCTION__, $filename, $prefix);
    }

    public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        return $this->measure(__FUNCTION__, $source, $path, $chunk, $chunks, $metadata);
    }

    public function uploadData(string $data, string $path, string $contentType, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        return $this->measure(__FUNCTION__, $data, $path, $contentType, $chunk, $chunks, $metadata);
    }

    public function abort(string $path, string $extra = ''): bool
    {
        return $this->measure(__FUNCTION__, $path, $extra);
    }

    public function read(string $path, int $offset = 0, int $length = null): string
    {
        return $this->measure(__FUNCTION__, $path, $offset, $length);
    }

    public function transfer(string $path, string $destination, Device $device): bool
    {
        return $this->measure(__FUNCTION__, $path, $destination, $device);
    }

    public function write(string $path, string $data, string $contentType): bool
    {
        return $this->measure(__FUNCTION__, $path, $data, $contentType);
    }

    public function delete(string $path, bool $recursive = false): bool
    {
        return $this->measure(__FUNCTION__, $path, $recursive);
    }

    public function deletePath(string $path): bool
    {
        return $this->measure(__FUNCTION__, $path);
    }

    public function exists(string $path): bool
    {
        return $this->measure(__FUNCTION__, $path);
    }

    public function getFileSize(string $path): int
    {
        return $this->measure(__FUNCTION__, $path);
    }

    public function getFileMimeType(string $path): string
    {
        return $this->measure(__FUNCTION__, $path);
    }

    public function getFileHash(string $path): string
    {
        return $this->measure(__FUNCTION__, $path);
    }

    public function createDirectory(string $path): bool
    {
        return $this->measure(__FUNCTION__, $path);
    }

    public function getDirectorySize(string $path): int
    {
        return $this->measure(__FUNCTION__, $path);
    }

    public function getPartitionFreeSpace(): float
    {
        return $this->measure(__FUNCTION__);
    }

    public function getPartitionTotalSpace(): float
    {
        return $this->measure(__FUNCTION__);
    }

    public function getFiles(string $dir, int $max = self::MAX_PAGE_SIZE, string $continuationToken = ''): array
    {
        return $this->measure(__FUNCTION__, $dir, $max, $continuationToken);
    }
}

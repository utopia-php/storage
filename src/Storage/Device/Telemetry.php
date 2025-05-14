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

    private function measure(string $method, array $args): mixed
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
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function uploadData(string $data, string $path, string $contentType, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function abort(string $path, string $extra = ''): bool
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function read(string $path, int $offset = 0, int $length = null): string
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function transfer(string $path, string $destination, Device $device): bool
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function write(string $path, string $data, string $contentType): bool
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function delete(string $path, bool $recursive = false): bool
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function deletePath(string $path): bool
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function exists(string $path): bool
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function getFileSize(string $path): int
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function getFileMimeType(string $path): string
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function getFileHash(string $path): string
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function createDirectory(string $path): bool
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function getDirectorySize(string $path): int
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function getPartitionFreeSpace(): float
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function getPartitionTotalSpace(): float
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }

    public function getFiles(string $dir, int $max = self::MAX_PAGE_SIZE, string $continuationToken = ''): array
    {
        return $this->measure(__FUNCTION__, func_get_args());
    }
}

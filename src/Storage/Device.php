<?php

declare(strict_types=1);

namespace Utopia\Storage;

use Utopia\Storage\Exception\StorageException;
use Utopia\Storage\Exception\UploadException;

/**
 * @phpstan-type UploadMetadata array{parts?: array<int, bool|string>, chunks?: int, content_type?: string, uploadId?: string}
 */
abstract class Device
{
    /**
     * Default max chunk size while transferring file from one device to another
     */
    public const TRANSFER_CHUNK_SIZE = 20000000; // 20 MB

    /**
     * Get Type.
     *
     * Get storage device type
     */
    abstract public function getType(): DeviceType;

    /**
     * Get Root.
     *
     * Get storage device root path
     */
    abstract public function getRoot(): string;

    /**
     * Get Path.
     *
     * Each device hold a complex directory structure that is being build in this method.
     */
    abstract public function getPath(string $filename): string;

    /**
     * Prepare Upload.
     *
     * Initialize adapter-specific upload state without transferring a chunk body.
     *
     * @param  UploadMetadata  $metadata
     *
     * @throws StorageException
     */
    abstract public function prepareUpload(string $path, string $contentType, int $chunks = 1, array &$metadata = []): void;

    /**
     * Upload Chunk.
     *
     * Upload exactly one chunk of file contents without finalizing the full upload.
     * Returns the number of chunks received so far.
     *
     * @param  UploadMetadata  $metadata
     *
     * @throws StorageException
     */
    abstract public function uploadChunk(string $data, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int;

    /**
     * Finalize Upload.
     *
     * Complete a prepared upload once all chunks are known to be present.
     *
     * @param  UploadMetadata  $metadata
     *
     * @throws StorageException
     */
    abstract public function finalizeUpload(string $path, int $chunks = 1, array &$metadata = []): bool;

    /**
     * Upload Data.
     *
     * Upload file contents to desired destination in the selected disk.
     * Returns the number of chunks received so far.
     *
     * @param  UploadMetadata  $metadata
     *
     * @throws StorageException
     */
    public function uploadData(string $data, string $path, string $contentType, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        $this->prepareUpload($path, $contentType, $chunks, $metadata);
        $chunksReceived = $this->uploadChunk($data, $path, $chunk, $chunks, $metadata);

        if ($chunks > 1 && $chunks === $chunksReceived && ! $this->finalizeUpload($path, $chunks, $metadata)) {
            throw new UploadException('Failed to finalize upload ' . $path);
        }

        return $chunksReceived;
    }

    /**
     * Abort Chunked Upload
     */
    abstract public function abort(string $path, string $uploadId = ''): bool;

    /**
     * Read file by given path.
     *
     * @param  int<0, max>  $offset
     * @param  int<0, max>|null  $length
     */
    abstract public function read(string $path, int $offset = 0, ?int $length = null): string;

    /**
     * Transfer
     * Transfer a file from current device to destination device.
     *
     */
    abstract public function transfer(string $path, string $destination, Device $device, int $chunkSize = self::TRANSFER_CHUNK_SIZE): bool;

    /**
     * Write file by given path.
     */
    abstract public function write(string $path, string $data, string $contentType): bool;

    /**
     * Move file from given source to given path, return true on success and false on failure.
     */
    public function move(string $source, string $target): bool
    {
        if ($source === $target) {
            return false;
        }

        if ($this->transfer($source, $target, $this)) {
            return $this->delete($source);
        }

        return false;
    }

    /**
     * Delete file in given path return true on success and false on failure.
     */
    abstract public function delete(string $path, bool $recursive = false): bool;

    /**
     * Delete files in given path, path must be a directory. return true on success and false on failure.
     */
    abstract public function deletePath(string $path): bool;

    /**
     * Check if file exists
     */
    abstract public function exists(string $path): bool;

    /**
     * List files under the given prefix, one page at a time.
     *
     * @param  int<1, max>  $max
     */
    abstract public function listFiles(string $prefix = '', int $max = 1000, ?string $cursor = null): FileList;

    /**
     * Returns given file path its size.
     */
    abstract public function getFileSize(string $path): int;

    /**
     * Returns given file path its mime type.
     */
    abstract public function getFileMimeType(string $path): string;

    /**
     * Returns given file path its MD5 hash value.
     */
    abstract public function getFileHash(string $path): string;

    /**
     * Get the absolute path by resolving strings like ../, .., //, /\ and so on.
     *
     * Works like the realpath function but works on files that does not exist
     *
     * Reference https://www.php.net/manual/en/function.realpath.php#84012
     */
    public function getAbsolutePath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), fn(string $part): bool => $part !== '');

        $absolutes = [];
        foreach ($parts as $part) {
            if ($part == '.') {
                continue;
            }
            if ($part == '..') {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}

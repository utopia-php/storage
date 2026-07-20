<?php

declare(strict_types=1);

namespace Utopia\Storage\Device;

use Utopia\Storage\Device;
use Utopia\Storage\DeviceType;
use Utopia\Storage\Exception\NotFoundException;
use Utopia\Storage\Exception\StorageException;
use Utopia\Storage\Exception\UploadException;
use Utopia\Storage\FileInfo;
use Utopia\Storage\FileList;

/**
 * @see \Utopia\Tests\Storage\Device\LocalTest
 *
 * @phpstan-import-type UploadMetadata from Device
 */
class Local extends Device
{
    /**
     * Local constructor.
     */
    public function __construct(protected readonly string $root = '') {}

    public function getType(): DeviceType
    {
        return DeviceType::Local;
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function getPath(string $filename): string
    {
        return $this->getAbsolutePath($this->getRoot() . DIRECTORY_SEPARATOR . $filename);
    }

    /**
     * @param  UploadMetadata  $metadata
     */
    public function prepareUpload(string $path, string $contentType, int $chunks = 1, array &$metadata = []): void
    {
        $this->createDirectory(\dirname($path));
        $metadata['parts'] ??= [];
        $metadata['chunks'] ??= 0;
    }

    public function uploadChunk(string $data, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        $this->createDirectory(\dirname($path));
        $metadata['parts'] ??= [];
        $metadata['chunks'] ??= 0;

        if ($chunks === 1) {
            if (file_put_contents($path, $data) === false) {
                throw new StorageException('Can\'t write file ' . $path);
            }

            $metadata['parts'][$chunk] = true;
            $metadata['chunks'] = 1;

            return 1;
        }

        $tmp = \dirname($path) . DIRECTORY_SEPARATOR . 'tmp_' . basename($path);
        $this->createDirectory($tmp);

        $chunkFilePath = $tmp . DIRECTORY_SEPARATOR . pathinfo($path, PATHINFO_FILENAME) . '.part.' . $chunk;

        // skip writing chunk if the chunk was re-uploaded
        if (!file_exists($chunkFilePath) && file_put_contents($chunkFilePath, $data) === false) {
            throw new StorageException('Failed to write chunk ' . $chunk);
        }

        $chunksReceived = $this->countChunks($tmp, $path);
        $metadata['parts'][$chunk] = true;
        $metadata['chunks'] = $chunksReceived;

        return $chunksReceived;
    }

    public function finalizeUpload(string $path, int $chunks = 1, array &$metadata = []): bool
    {
        if (file_exists($path)) {
            return true;
        }

        if ($chunks === 1) {
            return false;
        }

        $tmp = \dirname($path) . DIRECTORY_SEPARATOR . 'tmp_' . basename($path);
        for ($i = 1; $i <= $chunks; ++$i) {
            $part = $tmp . DIRECTORY_SEPARATOR . pathinfo($path, PATHINFO_FILENAME) . '.part.' . $i;
            if (! file_exists($part)) {
                throw new UploadException('Missing chunk ' . $i);
            }
        }

        $this->joinChunks($path, $chunks);

        return true;
    }

    private function countChunks(string $tmp, string $path): int
    {
        $escaped = (fn(string $literal): string => str_replace(['\\', '*', '?', '[', ']', '{', '}'], ['\\\\', '\\*', '\\?', '\\[', '\\]', '\\{', '\\}'], $literal));
        $pattern = $escaped($tmp) . DIRECTORY_SEPARATOR . $escaped(pathinfo($path, PATHINFO_FILENAME)) . '.part.*';
        $files = glob($pattern);
        if ($files === false) {
            return 0;
        }

        $count = 0;
        foreach ($files as $file) {
            if (preg_match('/\.part\.\d+$/', $file)) {
                ++$count;
            }
        }

        return $count;
    }

    private function joinChunks(string $path, int $chunks): void
    {
        if (file_exists($path)) {
            return;
        }

        $tmp = \dirname($path) . DIRECTORY_SEPARATOR . 'tmp_' . basename($path);
        $tmpAssemble = tempnam(\dirname($path), 'tmp_assemble_' . basename($path) . '_');

        $dest = fopen($tmpAssemble, 'wb');
        if ($dest === false) {
            throw new StorageException('Failed to open temporary assembly file ' . $tmpAssemble);
        }

        $partsToUnlink = [];
        for ($i = 1; $i <= $chunks; ++$i) {
            $part = $tmp . DIRECTORY_SEPARATOR . pathinfo($path, PATHINFO_FILENAME) . '.part.' . $i;
            $src = @fopen($part, 'rb');
            if ($src === false) {
                fclose($dest);
                unlink($tmpAssemble);
                throw new StorageException('Failed to open chunk ' . $part);
            }

            if (stream_copy_to_stream($src, $dest) === false) {
                fclose($src);
                fclose($dest);
                unlink($tmpAssemble);
                throw new StorageException('Failed to copy chunk ' . $part);
            }
            fclose($src);
            $partsToUnlink[] = $part;
        }

        fclose($dest);

        if (! rename($tmpAssemble, $path)) {
            if (file_exists($path)) {
                unlink($tmpAssemble);

                return;
            }
            unlink($tmpAssemble);
            throw new StorageException('Failed to finalize assembled file ' . $path);
        }

        foreach ($partsToUnlink as $part) {
            if (! unlink($part)) {
                trigger_error('Failed to remove chunk part ' . $part, E_USER_WARNING);
            }
        }

        if (! rmdir($tmp)) {
            trigger_error('Failed to remove temporary chunk directory ' . $tmp, E_USER_WARNING);
        }
    }

    /**
     * Transfer
     */
    public function transfer(string $path, string $destination, Device $device, int $chunkSize = self::TRANSFER_CHUNK_SIZE): bool
    {
        if ($chunkSize <= 0) {
            throw new \InvalidArgumentException('Chunk size must be greater than zero');
        }

        if (! $this->exists($path)) {
            throw new NotFoundException('File not found');
        }
        $size = $this->getFileSize($path);
        $contentType = $this->getFileMimeType($path);

        if ($size <= $chunkSize) {
            $source = $this->read($path);

            return $device->write($destination, $source, $contentType);
        }

        $totalChunks = (int) ceil($size / $chunkSize);
        $metadata = ['content_type' => $contentType];
        for ($counter = 0; $counter < $totalChunks; ++$counter) {
            $start = $counter * $chunkSize;
            $data = $this->read($path, $start, $chunkSize);
            $device->uploadData($data, $destination, $contentType, $counter + 1, $totalChunks, $metadata);
        }

        return true;
    }

    /**
     * Abort Chunked Upload
     */
    public function abort(string $path, string $uploadId = ''): bool
    {
        if (file_exists($path)) {
            unlink($path);
        }

        $tmp = \dirname($path) . DIRECTORY_SEPARATOR . 'tmp_' . basename($path) . DIRECTORY_SEPARATOR;

        if (! file_exists(\dirname($tmp))) { // Checks if directory path to file exists
            throw new NotFoundException('File doesn\'t exist: ' . \dirname($path));
        }
        $files = $this->scanDirectory($tmp);

        foreach ($files as $file) {
            $this->delete($file, true);
        }

        return rmdir($tmp);
    }

    /**
     * Read file by given path.
     *
     *
     * @throws StorageException
     */
    public function read(string $path, int $offset = 0, ?int $length = null): string
    {
        if (! $this->exists($path)) {
            throw new NotFoundException('File not found');
        }

        $contents = file_get_contents($path, use_include_path: false, offset: $offset, length: $length);
        if ($contents === false) {
            throw new StorageException('Failed to read file ' . $path);
        }

        return $contents;
    }

    /**
     * Write file by given path.
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        // Checks if directory path to file exists
        if (!file_exists(\dirname($path)) && ! @mkdir(\dirname($path), 0755, true)) {
            throw new StorageException('Can\'t create directory ' . \dirname($path));
        }

        return (bool) file_put_contents($path, $data);
    }

    /**
     * Move file from given source to given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @throws StorageException
     */
    #[\Override]
    public function move(string $source, string $target): bool
    {
        if ($source === $target) {
            return false;
        }

        // Checks if directory path to file exists
        if (!file_exists(\dirname($target)) && ! @mkdir(\dirname($target), 0755, true)) {
            throw new StorageException('Can\'t create directory ' . \dirname($target));
        }
        return rename($source, $target);
    }

    /**
     * Delete file in given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        if (is_dir($path) && $recursive) {
            $entries = scandir($path);

            if ($entries === false) {
                return false;
            }

            foreach ($entries as $entry) {
                if ($entry === '.') {
                    continue;
                }
                if ($entry === '..') {
                    continue;
                }
                if (! $this->delete($path . DIRECTORY_SEPARATOR . $entry, true)) {
                    return false;
                }
            }

            return rmdir($path);
        }

        if (is_file($path) || is_link($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     */
    public function deletePath(string $path): bool
    {
        $path = realpath($this->getRoot() . DIRECTORY_SEPARATOR . $path);

        if ($path === false || ! is_dir($path)) {
            return false;
        }

        $files = $this->scanDirectory($path);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deletePath(substr_replace($file, '', 0, \strlen($this->getRoot() . DIRECTORY_SEPARATOR)));
            } else {
                $this->delete($file, true);
            }
        }

        return rmdir($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Returns given file path its size.
     *
     * @see http://php.net/manual/en/function.filesize.php
     */
    public function getFileSize(string $path): int
    {
        $size = $this->exists($path) ? filesize($path) : false;
        if ($size === false) {
            throw $this->exists($path) ? new StorageException('Failed to get size of file ' . $path) : new NotFoundException('File not found: ' . $path);
        }

        return $size;
    }

    /**
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
     */
    public function getFileMimeType(string $path): string
    {
        $mimeType = $this->exists($path) ? mime_content_type($path) : false;
        if ($mimeType === false) {
            throw $this->exists($path) ? new StorageException('Failed to get mime type of file ' . $path) : new NotFoundException('File not found: ' . $path);
        }

        return $mimeType;
    }

    /**
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
     */
    public function getFileHash(string $path): string
    {
        $hash = $this->exists($path) ? md5_file($path) : false;
        if ($hash === false) {
            throw $this->exists($path) ? new StorageException('Failed to hash file ' . $path) : new NotFoundException('File not found: ' . $path);
        }

        return $hash;
    }

    /**
     * Create a directory at the specified path.
     *
     * Returns true on success or if the directory already exists and false on error
     */
    public function createDirectory(string $path): bool
    {
        if (file_exists($path)) {
            return true;
        }

        return @mkdir($path, 0755, true);
    }

    /**
     * Get directory size in bytes.
     *
     * Return -1 on error
     *
     * Based on http://www.jonasjohn.de/snippets/php/dir-size.htm
     */
    public function getDirectorySize(string $path): int
    {
        $size = 0;

        $directory = opendir($path);

        if (! $directory) {
            return -1;
        }

        while (($file = readdir($directory)) !== false) {
            // Skip file pointers
            if ($file[0] === '.') {
                continue;
            }

            // Go recursive down, or add the file size
            if (is_dir($path . $file)) {
                $size += $this->getDirectorySize($path . $file . DIRECTORY_SEPARATOR);
            } else {
                $size += filesize($path . $file);
            }
        }

        closedir($directory);

        return $size;
    }

    /**
     * Get Partition Free Space.
     *
     * disk_free_space — Returns available space on filesystem or disk partition
     */
    public function getPartitionFreeSpace(): float
    {
        return disk_free_space($this->getRoot()) ?: 0.0;
    }

    /**
     * Get Partition Total Space.
     *
     * disk_total_space — Returns the total size of a filesystem or disk partition
     */
    public function getPartitionTotalSpace(): float
    {
        return disk_total_space($this->getRoot()) ?: 0.0;
    }

    /**
     * List all files under the given directory, recursively, sorted by path.
     *
     * The cursor is a numeric offset into the sorted listing.
     */
    public function listFiles(string $prefix = '', int $max = 1000, ?string $cursor = null): FileList
    {
        $paths = [];
        $pending = [rtrim($prefix, DIRECTORY_SEPARATOR)];
        while ($pending !== []) {
            $directory = array_pop($pending);
            foreach ($this->scanDirectory($directory) as $entry) {
                if (is_dir($entry)) {
                    $pending[] = $entry;
                } else {
                    $paths[] = $entry;
                }
            }
        }
        sort($paths);

        $offset = is_numeric($cursor) ? (int) $cursor : 0;
        $page = \array_slice($paths, $offset, $max);

        $files = [];
        foreach ($page as $path) {
            $modified = filemtime($path);
            $files[] = new FileInfo(
                path: $path,
                size: filesize($path) ?: 0,
                modifiedAt: $modified === false ? null : new \DateTimeImmutable('@' . $modified),
            );
        }

        return new FileList(
            files: $files,
            cursor: $offset + \count($page) < \count($paths) ? (string) ($offset + \count($page)) : null,
        );
    }

    /**
     * Get all files and directories directly inside a directory, hidden entries included.
     *
     * @return string[]
     */
    private function scanDirectory(string $dir): array
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $files = glob($dir . DIRECTORY_SEPARATOR . '*') ?: [];

        /**
         * Hidden files
         */
        foreach (glob($dir . DIRECTORY_SEPARATOR . '.[!.]*') ?: [] as $file) {
            $files[] = $file;
        }

        return $files;
    }
}

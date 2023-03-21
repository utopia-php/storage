<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;
use Utopia\Storage\Storage;

class Local extends Device
{
    /**
     * @var string
     */
    protected string $root = 'temp';

    /**
     * Local constructor.
     *
     * @param  string  $root
     */
    public function __construct(string $root = '')
    {
        $this->root = $root;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Local Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_LOCAL;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adapter for Local storage that is in the physical or virtual machine or mounted to it.';
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @param  string  $filename
     * @param  string|null  $prefix
     * @return string
     */
    public function getPath(string $filename, string $prefix = null): string
    {
        return $this->getAbsolutePath($this->getRoot().DIRECTORY_SEPARATOR.$filename);
    }

    /**
     * Upload.
     *
     * Upload a file to desired destination in the selected disk.
     * return number of chunks uploaded or 0 if it fails.
     *
     * @param  string  $source
     * @param  string  $path
     * @param  int  $chunk
     * @param  int  $chunks
     * @param  array  $metadata
     * @return int
     *
     * @throws \Exception
     */
    public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        if (! \file_exists(\dirname($path))) { // Checks if directory path to file exists
            if (! @\mkdir(\dirname($path), 0755, true)) {
                throw new Exception('Can\'t create directory: '.\dirname($path));
            }
        }

        //move_uploaded_file() verifies the file is not tampered with
        if ($chunks === 1) {
            if (! \move_uploaded_file($source, $path)) {
                throw new Exception('Can\'t upload file '.$path);
            }

            return $chunks;
        }
        $tmp = \dirname($path).DIRECTORY_SEPARATOR.'tmp_'.\basename($path).DIRECTORY_SEPARATOR.\basename($path).'_chunks.log';

        if (! \file_exists(\dirname($tmp))) { // Checks if directory path to file exists
            if (! @\mkdir(\dirname($tmp), 0755, true)) {
                throw new Exception('Can\'t create directory: '.\dirname($tmp));
            }
        }
        if (! file_put_contents($tmp, "$chunk\n", FILE_APPEND)) {
            throw new Exception('Can\'t write chunk log '.$tmp);
        }

        $chunkLogs = file($tmp);
        if (! $chunkLogs) {
            throw new Exception('Unable to read chunk log '.$tmp);
        }

        $chunksReceived = count(file($tmp));

        if (! \rename($source, dirname($tmp).DIRECTORY_SEPARATOR.pathinfo($path, PATHINFO_FILENAME).'.part.'.$chunk)) {
            throw new Exception('Failed to write chunk '.$chunk);
        }

        if ($chunks === $chunksReceived) {
            for ($i = 1; $i <= $chunks; $i++) {
                $part = dirname($tmp).DIRECTORY_SEPARATOR.pathinfo($path, PATHINFO_FILENAME).'.part.'.$i;
                $data = file_get_contents($part);
                if (! $data) {
                    throw new Exception('Failed to read chunk '.$part);
                }

                if (! file_put_contents($path, $data, FILE_APPEND)) {
                    throw new Exception('Failed to append chunk '.$part);
                }
                \unlink($part);
            }
            \unlink($tmp);

            return $chunksReceived;
        }

        return $chunksReceived;
    }

    /**
     * Abort Chunked Upload
     *
     * @param  string  $path
     * @param  string  $extra
     * @return bool
     */
    public function abort(string $path, string $extra = ''): bool
    {
        if (file_exists($path)) {
            \unlink($path);
        }

        $tmp = \dirname($path).DIRECTORY_SEPARATOR.'tmp_'.\basename($path).DIRECTORY_SEPARATOR;

        if (! \file_exists(\dirname($tmp))) { // Checks if directory path to file exists
            throw new Exception('File doesn\'t exist: '.\dirname($path));
        }
        $files = $this->getFiles($tmp);

        foreach ($files as $file) {
            $this->delete($file, true);
        }

        return \rmdir($tmp);
    }

    /**
     * Read file by given path.
     *
     * @param  string  $path
     * @param int offset
     * @param  int|null  $length
     * @return string
     *
     * @throws Exception
     */
    public function read(string $path, int $offset = 0, int $length = null): string
    {
        if (! $this->exists($path)) {
            throw new Exception('File Not Found');
        }

        return \file_get_contents($path, use_include_path: false, context: null, offset: $offset, length: $length);
    }

    /**
     * Write file by given path.
     *
     * @param  string  $path
     * @param  string  $data
     * @param  string  $contentType
     * @return bool
     */
    public function write(string $path, string $data, string $contentType = ''): bool
    {
        if (! \file_exists(\dirname($path))) { // Checks if directory path to file exists
            if (! @\mkdir(\dirname($path), 0755, true)) {
                throw new Exception('Can\'t create directory '.\dirname($path));
            }
        }

        return (bool) \file_put_contents($path, $data);
    }

    /**
     * Move file from given source to given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param  string  $source
     * @param  string  $target
     * @return bool
     */
    public function move(string $source, string $target): bool
    {
        if (! \file_exists(\dirname($target))) { // Checks if directory path to file exists
            if (! @\mkdir(\dirname($target), 0755, true)) {
                throw new Exception('Can\'t create directory '.\dirname($target));
            }
        }

        if (\rename($source, $target)) {
            return true;
        }

        return false;
    }

    /**
     * Delete file in given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param  string  $path
     * @param  bool  $recursive
     * @return bool
     */
    public function delete(string $path, bool $recursive = false): bool
    {
        if (\is_dir($path) && $recursive) {
            $files = $this->getFiles($path);

            foreach ($files as $file) {
                $this->delete($file, true);
            }

            \rmdir($path);
        } elseif (\is_file($path)) {
            return \unlink($path);
        }

        return false;
    }

    /**
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     * @param  string  $path
     * @return bool
     */
    public function deletePath(string $path): bool
    {
        $path = realpath($this->getRoot().DIRECTORY_SEPARATOR.$path);

        if (\is_dir($path)) {
            $files = $this->getFiles($path);

            foreach ($files as $file) {
                $this->delete($file, true);
            }

            \rmdir($path);

            return true;
        }

        return false;
    }

    /**
     * Check if file exists
     *
     * @param  string  $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return \file_exists($path);
    }

    /**
     * Returns given file path its size.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param  string  $path
     * @return int
     */
    public function getFileSize(string $path): int
    {
        return \filesize($path);
    }

    /**
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
     *
     * @param  string  $path
     * @return string
     */
    public function getFileMimeType(string $path): string
    {
        return \mime_content_type($path);
    }

    /**
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
     *
     * @param  string  $path
     * @return string
     */
    public function getFileHash(string $path): string
    {
        return \md5_file($path);
    }

    /**
     * Create a directory at the specified path.
     *
     * Returns true on success or if the directory already exists and false on error
     *
     * @param $path
     * @return bool
     */
    public function createDirectory(string $path): bool
    {
        if (! \file_exists($path)) {
            if (! @\mkdir($path, 0755, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get directory size in bytes.
     *
     * Return -1 on error
     *
     * Based on http://www.jonasjohn.de/snippets/php/dir-size.htm
     *
     * @param  string  $path
     * @return int
     */
    public function getDirectorySize(string $path): int
    {
        $size = 0;

        $directory = \opendir($path);

        if (! $directory) {
            return -1;
        }

        while (($file = \readdir($directory)) !== false) {
            // Skip file pointers
            if ($file[0] === '.') {
                continue;
            }

            // Go recursive down, or add the file size
            if (\is_dir($path.$file)) {
                $size += $this->getDirectorySize($path.$file.DIRECTORY_SEPARATOR);
            } else {
                $size += \filesize($path.$file);
            }
        }

        \closedir($directory);

        return $size;
    }

    /**
     * Get Partition Free Space.
     *
     * disk_free_space — Returns available space on filesystem or disk partition
     *
     * @return float
     */
    public function getPartitionFreeSpace(): float
    {
        return \disk_free_space($this->getRoot());
    }

    /**
     * Get Partition Total Space.
     *
     * disk_total_space — Returns the total size of a filesystem or disk partition
     *
     * @return float
     */
    public function getPartitionTotalSpace(): float
    {
        return \disk_total_space($this->getRoot());
    }

    /**
     * Get all files inside a directory.
     *
     * @param  string  $dir Directory to scan
     * @return string[]
     */
    private function getFiles(string $dir): array
    {
        if (! (\str_ends_with($dir, DIRECTORY_SEPARATOR))) {
            $dir .= DIRECTORY_SEPARATOR;
        }

        $files = [];

        foreach (\scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $files[] = $dir.$file;
        }

        return $files;
    }
}

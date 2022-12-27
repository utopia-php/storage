<?php

namespace Utopia\Storage;

use Exception;

abstract class Device
{
    /**
     * Get Name.
     *
     * Get storage device name
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get Type.
     *
     * Get storage device type
     *
     * @return string
     */
    abstract public function getType(): string;

    /**
     * Get Description.
     *
     * Get storage device description and purpose.
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * Get Root.
     *
     * Get storage device root path
     *
     * @return string
     */
    abstract public function getRoot(): string;

    /**
     * Get Path.
     *
     * Each device hold a complex directory structure that is being build in this method.
     *
     * @param string $filename
     * @param string $prefix
     *
     * @return string
     */
    abstract public function getPath(string $filename, string $prefix = null): string;

    /**
     * Upload.
     *
     * Upload a file to desired destination in the selected disk
     * return number of chunks uploaded or 0 if it fails.
     *
     * @param string $source
     * @param string $path
     * @param int $chunk
     * @param int $chunks
     * @param array $metadata
     * 
     * @throws \Exception
     *
     * @return int
     */
    abstract public function upload(string $source, string $path, int $chunk = 1, int $chunks = 1, array &$metadata = []): int;

    /**
     * Abort Chunked Upload
     * 
     * @param string $path
     * @param string $extra
     * 
     * @return bool
     */
    abstract public function abort(string $path, string $extra = ''): bool;

    /**
     * Read file by given path.
     *
     * @param string $path
     * @param int $offset
     * @param int|null $length
     *
     * @return string
     */
    abstract public function read(string $path, int $offset = 0, int $length = null): string;

    /**
     * Write file by given path.
     *
     * @param string $path
     * @param string $data
     *
     * @return bool
     */
    abstract public function write(string $path, string $data, string $contentType): bool;

    /**
     * Move file from given source to given path, return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $source
     * @param string $target
     *
     * @return bool
     */
    abstract public function move(string $source, string $target): bool;

    /**
     * Delete file in given path return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $path
     * @param bool $recursive
     *
     * @return bool
     */
    abstract public function delete(string $path, bool $recursive = false): bool;
    
    /**
     * Delete files in given path, path must be a directory. return true on success and false on failure.
     *
     *
     * @param string $path
     *
     * @return bool
     */
    abstract public function deletePath(string $path): bool;

    /**
     * Check if file exists
     *
     * @param string $path
     *
     * @return bool
     */
    abstract public function exists(string $path): bool;

    /**
     * Returns given file path its size.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param $path
     *
     * @return int
     */
    abstract public function getFileSize(string $path): int;

    /**
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
     *
     * @param $path
     *
     * @return string
     */
    abstract public function getFileMimeType(string $path): string;

    /**
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
     *
     * @param $path
     *
     * @return string
     */
    abstract public function getFileHash(string $path): string;

    /**
     * Create a directory at the specified path.
     *
     * Returns true on success or if the directory already exists and false on error
     *
     * @param $path
     *
     * @return bool
     */
    abstract public function createDirectory(string $path): bool;

    /**
     * Get directory size in bytes.
     *
     * Return -1 on error
     *
     * Based on http://www.jonasjohn.de/snippets/php/dir-size.htm
     *
     * @param $path
     *
     * @return int
     */
    abstract public function getDirectorySize(string $path): int;

    /**
     * Get Partition Free Space.
     *
     * disk_free_space — Returns available space on filesystem or disk partition
     *
     * @return float
     */
    abstract public function getPartitionFreeSpace(): float;

    /**
     * Get Partition Total Space.
     *
     * disk_total_space — Returns the total size of a filesystem or disk partition
     *
     * @return float
     */
    abstract public function getPartitionTotalSpace(): float;

    /**
     * Get the absolute path by resolving strings like ../, .., //, /\ and so on.
     *
     * Works like the realpath function but works on files that does not exist
     *
     * Reference https://www.php.net/manual/en/function.realpath.php#84012
     *
     * @param string $path
     *
     * @return string
     */
    public function getAbsolutePath(string $path): string
    {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');

        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}

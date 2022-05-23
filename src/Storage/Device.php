<?php

namespace Utopia\Storage;

use Exception;

abstract class Device
{

    /**
     * Max chunk size while transfering file from one device to another
     */
    protected int $transferChunkSize = 20000000; //20 MB

    /**
     * Set Transfer Chunk Size
     * 
     * @param int $chunkSize
     * @return void
     */
    public function setTransferChunkSize(int $chunkSize): void {
        $this->transferChunkSize = $chunkSize;
    }

    /**
     * Get Transfer Chunk Size
     * 
     * @return int
     */
    public function getTransferChunkSize(): int {
        return $this->transferChunkSize;
    }

    /**
     * Get Name.
     *
     * Get storage device name
     *
     * @return string
     */
    abstract public function getName(): string;

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
     * Upload Data.
     *
     * Upload file contents to desired destination in the selected disk.
     * return number of chunks uploaded or 0 if it fails.
     *
     * @param string $data
     * @param string $path
     * @param string $contentType
     * @param int chunk
     * @param int chunks
     * @param array $metadata
     *
     * @throws \Exception
     *
     * @return int
     */
    abstract public function uploadData(string $data, string $path, string $contentType, int $chunk = 1, int $chunks = 1, array &$metadata = []): int;

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
     * @param int $length
     *
     * @return string
     */
    abstract public function read(string $path, int $offset = 0, int $length = null): string;

    /**
     * Transfer
     *
     * @param string $path
     * @param string $destination
     * @param Device $device
     *
     * @return string
     */
    abstract public function transfer(string $path, string $destination, Device $device): bool;

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
    public function move(string $source, string $target): bool
    {
        if($this->transfer($source, $target, $this)) {
            return $this->delete($source);
        }
        return false;
    }

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
}

<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;
use Utopia\Storage\Exception\NotFoundException;
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
        parent::__construct();
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
    public function getPath(string $filename, ?string $prefix = null): string
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
        $this->createDirectory(\dirname($path));

        //move_uploaded_file() verifies the file is not tampered with
        if ($chunks === 1) {
            if (! \move_uploaded_file($source, $path)) {
                throw new Exception('Can\'t upload file '.$path);
            }

            return $chunks;
        }
        $tmp = \dirname($path).DIRECTORY_SEPARATOR.'tmp_'.\basename($path).DIRECTORY_SEPARATOR.\basename($path).'_chunks.log';

        $this->createDirectory(\dirname($tmp));

        $chunkFilePath = dirname($tmp).DIRECTORY_SEPARATOR.pathinfo($path, PATHINFO_FILENAME).'.part.'.$chunk;

        // skip writing chunk if the chunk was re-uploaded
        if (! file_exists($chunkFilePath)) {
            if (! file_put_contents($tmp, "$chunk\n", FILE_APPEND)) {
                throw new Exception('Can\'t write chunk log '.$tmp);
            }
        }

        $chunkLogs = file($tmp);
        if (! $chunkLogs) {
            throw new Exception('Unable to read chunk log '.$tmp);
        }

        $chunksReceived = count(file($tmp));

        if (! \rename($source, $chunkFilePath)) {
            throw new Exception('Failed to write chunk '.$chunk);
        }

        if ($chunks === $chunksReceived) {
            $this->joinChunks($path, $chunks);

            return $chunksReceived;
        }

        return $chunksReceived;
    }

    /**
     * Upload Data.
     *
     * Upload file contents to desired destination in the selected disk.
     * return number of chunks uploaded or 0 if it fails.
     *
     * @param  string  $source
     * @param  string  $path
     * @param  string  $contentType
     * @param int chunk
     * @param int chunks
     * @param  array  $metadata
     * @return int
     *
     * @throws \Exception
     */
    public function uploadData(string $data, string $path, string $contentType, int $chunk = 1, int $chunks = 1, array &$metadata = []): int
    {
        $this->createDirectory(\dirname($path));

        if ($chunks === 1) {
            if (! \file_put_contents($path, $data)) {
                throw new Exception('Can\'t write file '.$path);
            }

            return $chunks;
        }
        $tmp = \dirname($path).DIRECTORY_SEPARATOR.'tmp_'.\basename($path).DIRECTORY_SEPARATOR.\basename($path).'_chunks.log';

        $this->createDirectory(\dirname($tmp));
        if (! file_put_contents($tmp, "$chunk\n", FILE_APPEND)) {
            throw new Exception('Can\'t write chunk log '.$tmp);
        }

        $chunkLogs = file($tmp);
        if (! $chunkLogs) {
            throw new Exception('Unable to read chunk log '.$tmp);
        }

        $chunksReceived = count(file($tmp));

        if (! \file_put_contents(dirname($tmp).DIRECTORY_SEPARATOR.pathinfo($path, PATHINFO_FILENAME).'.part.'.$chunk, $data)) {
            throw new Exception('Failed to write chunk '.$chunk);
        }

        if ($chunks === $chunksReceived) {
            $this->joinChunks($path, $chunks);

            return $chunksReceived;
        }

        return $chunksReceived;
    }

    private function joinChunks(string $path, int $chunks): void
    {
        $tmpDir = \dirname($path).DIRECTORY_SEPARATOR.'tmp_'.\basename($path);
        $tmp = $tmpDir.DIRECTORY_SEPARATOR.\basename($path).'_chunks.log';
        $tmpAssemble = \dirname($path).DIRECTORY_SEPARATOR.'tmp_assemble_'.\basename($path);

        $dest = \fopen($tmpAssemble, 'wb');
        if ($dest === false) {
            throw new Exception('Failed to open temporary assembly file '.$tmpAssemble);
        }

        $partsToUnlink = [];
        for ($i = 1; $i <= $chunks; $i++) {
            $part = $tmpDir.DIRECTORY_SEPARATOR.\pathinfo($path, PATHINFO_FILENAME).'.part.'.$i;
            $src = @\fopen($part, 'rb');
            if ($src === false) {
                \fclose($dest);
                \unlink($tmpAssemble);
                throw new Exception('Failed to open chunk '.$part);
            }

            if (\stream_copy_to_stream($src, $dest) === false) {
                \fclose($src);
                \fclose($dest);
                \unlink($tmpAssemble);
                throw new Exception('Failed to copy chunk '.$part);
            }
            \fclose($src);
            $partsToUnlink[] = $part;
        }

        \fclose($dest);

        if (!\rename($tmpAssemble, $path)) {
            \unlink($tmpAssemble);
            throw new Exception('Failed to finalize assembled file '.$path);
        }

        foreach ($partsToUnlink as $part) {
            if (!\unlink($part)) {
                \trigger_error('Failed to remove chunk part '.$part, E_USER_WARNING);
            }
        }

        if (!\unlink($tmp)) {
            \trigger_error('Failed to remove chunk log '.$tmp, E_USER_WARNING);
        }

        if (!\rmdir($tmpDir)) {
            \trigger_error('Failed to remove temporary chunk directory '.$tmpDir, E_USER_WARNING);
        }
    }

    /**
     * Transfer
     *
     * @param  string  $path
     * @param  string  $destination
     * @param  Device  $device
     * @return string
     */
    public function transfer(string $path, string $destination, Device $device): bool
    {
        if (! $this->exists($path)) {
            throw new Exception('File Not Found');
        }
        $size = $this->getFileSize($path);
        $contentType = $this->getFileMimeType($path);

        if ($size <= $this->transferChunkSize) {
            $source = $this->read($path);

            return $device->write($destination, $source, $contentType);
        }

        $totalChunks = \ceil($size / $this->transferChunkSize);
        $metadata = ['content_type' => $contentType];
        for ($counter = 0; $counter < $totalChunks; $counter++) {
            $start = $counter * $this->transferChunkSize;
            $data = $this->read($path, $start, $this->transferChunkSize);
            $device->uploadData($data, $destination, $contentType, $counter + 1, $totalChunks, $metadata);
        }

        return true;
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
    public function read(string $path, int $offset = 0, ?int $length = null): string
    {
        if (! $this->exists($path)) {
            throw new NotFoundException('File not found');
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
     *
     * @throws Exception
     */
    public function move(string $source, string $target): bool
    {
        if ($source === $target) {
            return false;
        }

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
            $entries = \scandir($path);

            if ($entries === false) {
                return false;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                if (! $this->delete($path.DIRECTORY_SEPARATOR.$entry, true)) {
                    return false;
                }
            }

            return \rmdir($path);
        }

        if (\is_file($path) || \is_link($path)) {
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

        if (! file_exists($path) || ! is_dir($path)) {
            return false;
        }

        $files = $this->getFiles($path);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deletePath(\substr_replace($file, '', 0, \strlen($this->getRoot().DIRECTORY_SEPARATOR)));
            } else {
                $this->delete($file, true);
            }
        }

        return \rmdir($path);
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
     * Get all files and directories inside a directory.
     *
     * @param  string  $dir
     * @param  int  $max
     * @param  string  $continuationToken
     * @return string[]
     */
    public function getFiles(string $dir, int $max = self::MAX_PAGE_SIZE, string $continuationToken = ''): array
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $files = [];

        foreach (\glob($dir.DIRECTORY_SEPARATOR.'*') as $file) {
            $files[] = $file;
        }

        /**
         * Hidden files
         */
        foreach (\glob($dir.DIRECTORY_SEPARATOR.'.[!.]*') as $file) {
            $files[] = $file;
        }

        return $files;
    }
}

<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device;
use Utopia\Storage\Utils\S3Helper;

class S3 extends Device
{
    protected $key;
    protected $secret;
    protected $bucket;
    protected $region;
    protected $host;
    protected $acl='private';
    protected $root = 'temp';
    private $s3Helper;

    public function __construct($root='', $key,$secret, $bucket, $region, $acl) {
        $this->key = $key;
        $this->secret = $secret;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->root = $root;
        $this->acl = $acl;
        $this->host = $bucket . '.s3.'.$region . '.awazonaws.com';
        $this->s3Helper = new S3Helper($this->key,$this->secret, false, 's3.amazonaws.com', $region);
    }

    


    /**
     * @return string
     */
    public function getName():string
    {
        return 'S3 Storage';
    }

    /**
     * @return string
     */
    public function getDescription():string
    {
        return 'S3 Bucket Storage drive for AWS or on premise solution';
    }

    /**
     * @return string
     */
    public function getRoot():string
    {
        return $this->root;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function getPath($filename):string
    {
        $path = '';

        for ($i = 0; $i < 4; ++$i) {
            $path = ($i < \strlen($filename)) ? $path.DIRECTORY_SEPARATOR.$filename[$i] : $path.DIRECTORY_SEPARATOR.'x';
        }

        return $this->getRoot().$path.DIRECTORY_SEPARATOR.$filename;
    }


    /**
     * Upload.
     *
     * Upload a file to desired destination in the selected disk.
     *
     * @param string $source
     * @param string $path
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function upload($source, $path):bool
    {
        $this->write($path,$source);
    }

    /**
     * Read file by given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function read(string $path):string
    {
        $res =  $this->s3Helper->getObject($this->bucket,$path);
        return $res->body;
    }

    /**
     * Write file by given path.
     *
     * @param string $path
     * @param string $data
     *
     * @return bool
     */
    public function write(string $path, string $data, string $contentType = 'text/plain'):bool
    {
        return $this->s3Helper->putObjectString($data, $this->bucket,$path, S3Helper::ACL_PUBLIC_READ);
    }

    private function getInfo(string $path) {
        return $this->s3Helper->getObjectInfo($this->bucket, $path);
    }

    /**
     * Move file from given source to given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $source
     * @param string $target
     *
     * @return bool
     */
    public function move(string $source, string $target):bool
    {
        return false;
    }

    /**
     * Delete file in given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $path
     *
     * @return bool
     */

     /* 
     DELETE /my-second-image.jpg HTTP/1.1
    Host: <bucket>.s3.<Region>.amazonaws.com
    Date: Wed, 12 Oct 2009 17:50:00 GMT
    Authorization: authorization string
    Content-Type: text/plain    
     */

    public function delete(string $path, bool $recursive = false):bool
    {
        return $this->s3Helper->deleteObject($this->bucket, $path);
    }
    
    /**
     * Returns given file path its size.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param $path
     *
     * @return int
     */
    public function getFileSize(string $path):int
    {
        $res = $this->getInfo($path);
        return $res['size'];
    }

    /**
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
     *
     * @param $path
     *
     * @return string
     */
    public function getFileMimeType(string $path):string
    {
        $res = $this->getInfo($path);
        return $res['type'];
    }

    /**
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
     *
     * @param $path
     *
     * @return string
     */
    public function getFileHash(string $path):string
    {
        $res = $this->getInfo($path);
        return $res['hash'];
    }

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
    public function getDirectorySize(string $path):int
    {
        return 0;
    }
    
    /**
     * Get Partition Free Space.
     *
     * disk_free_space — Returns available space on filesystem or disk partition
     *
     * @return float
     */
    public function getPartitionFreeSpace():float
    {
        return 0.0;
    }

    /**
     * Get Partition Total Space.
     *
     * disk_total_space — Returns the total size of a filesystem or disk partition
     *
     * @return float
     */
    public function getPartitionTotalSpace():float
    {
        return 0.0;
    }
}
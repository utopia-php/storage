<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\S3;

class S3Test extends TestCase
{
    /**
     * @var S3
     */
    protected $object = null;
    protected $root = '/root';

    public function setUp(): void
    {
        $this->root = '/root';
        $key = getenv('S3_ACCESS_KEY');
        $secret = getenv('S3_SECRET');
        $bucket = "utopia-storage";
        $this->object = new S3($this->root, $key, $secret, $bucket, S3::AP_SOUTH_1, S3::ACL_PUBLIC_READ);

        $this->uploadTestFiles();
    }

    private function uploadTestFiles()
    {
        $this->object->upload(__DIR__ . '/../../resources/disk-a/kitten-1.jpg', $this->object->getPath('testing/kitten-1.jpg'));
        $this->object->upload(__DIR__ . '/../../resources/disk-a/kitten-2.jpg', $this->object->getPath('testing/kitten-2.jpg'));
        $this->object->upload(__DIR__ . '/../../resources/disk-b/kitten-1.png', $this->object->getPath('testing/kitten-1.png'));
        $this->object->upload(__DIR__ . '/../../resources/disk-b/kitten-2.png', $this->object->getPath('testing/kitten-2.png'));
    }

    private function removeTestFiles()
    {
        $this->object->delete($this->object->getPath('testing/kitten-1.jpg'));
        $this->object->delete($this->object->getPath('testing/kitten-2.jpg'));
        $this->object->delete($this->object->getPath('testing/kitten-1.png'));
        $this->object->delete($this->object->getPath('testing/kitten-2.png'));
    }

    public function tearDown(): void
    {
        $this->removeTestFiles();
    }

    public function testName()
    {
        $this->assertEquals($this->object->getName(), 'S3 Storage');
    }

    public function testDescription()
    {
        $this->assertEquals($this->object->getDescription(), 'S3 Bucket Storage drive for AWS or on premise solution');
    }

    public function testRoot()
    {
        $this->assertEquals($this->object->getRoot(), $this->root);
    }

    public function testPath()
    {
        $this->assertEquals($this->object->getPath('image.png'), $this->root . '/i/m/a/g/image.png');
        $this->assertEquals($this->object->getPath('x.png'), $this->root . '/x/./p/n/x.png');
        $this->assertEquals($this->object->getPath('y'), $this->root . '/y/x/x/x/y');
    }

    public function testWrite()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text.txt'), 'Hello World', 'text/plain'), true);

        $this->object->delete($this->object->getPath('text.txt'));
    }

    public function testRead()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-read.txt'), 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-read.txt')), 'Hello World');

        $this->object->delete($this->object->getPath('text-for-read.txt'));
    }

    public function testMove()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-move.txt'), 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-move.txt')), 'Hello World');
        $this->assertEquals($this->object->move($this->object->getPath('text-for-move.txt'), $this->object->getPath('text-for-move-new.txt')), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-move-new.txt')), 'Hello World');
        $this->assertEquals($this->object->read($this->object->getPath('text-for-move.txt')), '');

        $this->object->delete($this->object->getPath('text-for-move-new.txt'));
    }

    public function testDelete()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-delete.txt'), 'Hello World', 'text/plain'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-delete.txt')), 'Hello World');
        $this->assertEquals($this->object->delete($this->object->getPath('text-for-delete.txt')), true);
    }

    public function testFileSize()
    {
        $this->assertEquals($this->object->getFileSize($this->object->getPath('testing/kitten-1.jpg')), 599639);
        $this->assertEquals($this->object->getFileSize($this->object->getPath('testing/kitten-2.jpg')), 131958);
    }

    public function testFileMimeType()
    {
        $this->assertEquals($this->object->getFileMimeType($this->object->getPath('testing/kitten-1.jpg')), 'image/jpeg');
        $this->assertEquals($this->object->getFileMimeType($this->object->getPath('testing/kitten-2.jpg')), 'image/jpeg');
        $this->assertEquals($this->object->getFileMimeType($this->object->getPath('testing/kitten-1.png')), 'image/png');
        $this->assertEquals($this->object->getFileMimeType($this->object->getPath('testing/kitten-2.png')), 'image/png');
    }

    public function testFileHash()
    {
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-1.jpg')), '7551f343143d2e24ab4aaf4624996b6a');
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-2.jpg')), '81702fdeef2e55b1a22617bce4951cb5');
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-1.png')), '03010f4f02980521a8fd6213b52ec313');
        $this->assertEquals($this->object->getFileHash($this->object->getPath('testing/kitten-2.png')), '8a9ed992b77e4b62b10e3a5c8ed72062');
    }

    public function testDirectorySize()
    {
        $this->assertEquals(-1, $this->object->getDirectorySize('resources/disk-a/'));
    }

    public function testPartitionFreeSpace()
    {
        $this->assertEquals(-1, $this->object->getPartitionFreeSpace());
    }

    public function testPartitionTotalSpace()
    {
        $this->assertEquals(-1, $this->object->getPartitionTotalSpace());
    }
}

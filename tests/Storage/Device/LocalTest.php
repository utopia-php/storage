<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\Local;
use PHPUnit\Framework\TestCase;

class LocalTest extends TestCase
{
    /**
     * @var Local
     */
    protected $object = null;

    public function setUp(): void
    {
        $this->object = new Local(realpath(__DIR__ . '/../../resources/disk-a'));
    }

    public function tearDown(): void
    {
    }

    public function testName()
    {
        $this->assertEquals($this->object->getName(), 'Local Storage');
    }

    public function testType()
    {
        $this->assertEquals($this->object->getType(), 'local');
    }

    public function testDescription()
    {
        $this->assertEquals($this->object->getDescription(), 'Adapter for Local storage that is in the physical or virtual machine or mounted to it.');
    }

    public function testRoot()
    {
        $this->assertEquals($this->object->getRoot(),trim(realpath( __DIR__ . '/../../resources/disk-a'),"/"));
    }

    public function testPath()
    {
        $this->assertEquals($this->object->getPath('image.png'), trim(realpath(__DIR__ . '/../../resources/disk-a').'/image.png',"/"));
    }

    public function testWrite()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text.txt'), 'Hello World'), true);
        $this->assertEquals(file_exists($this->object->getPath('text.txt')), true);
        $this->assertEquals(is_readable($this->object->getPath('text.txt')), true);

        $this->object->delete($this->object->getPath('text.txt'));
    }

    public function testRead()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-read.txt'), 'Hello World'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-read.txt')), 'Hello World');
        
        $this->object->delete($this->object->getPath('text-for-read.txt'));
    }

    public function testFileExists()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-test-exists.txt'), 'Hello World'), true);
        $this->assertEquals($this->object->exists($this->object->getPath('text-for-test-exists.txt')), true);
        $this->assertEquals($this->object->exists($this->object->getPath('text-for-test-doesnt-exist.txt')), false);

        $this->object->delete($this->object->getPath('text-for-test-exists.txt'));
    }

    public function testMove()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-move.txt'), 'Hello World'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-move.txt')), 'Hello World');
        $this->assertEquals($this->object->move($this->object->getPath('text-for-move.txt'), $this->object->getPath('text-for-move-new.txt')), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-move-new.txt')), 'Hello World');
        $this->assertEquals(file_exists($this->object->getPath('text-for-move.txt')), false);
        $this->assertEquals(is_readable($this->object->getPath('text-for-move.txt')), false);
        $this->assertEquals(file_exists($this->object->getPath('text-for-move-new.txt')), true);
        $this->assertEquals(is_readable($this->object->getPath('text-for-move-new.txt')), true);

        $this->object->delete($this->object->getPath('text-for-move-new.txt'));
    }

    public function testDelete()
    {
        $this->assertEquals($this->object->write($this->object->getPath('text-for-delete.txt'), 'Hello World'), true);
        $this->assertEquals($this->object->read($this->object->getPath('text-for-delete.txt')), 'Hello World');
        $this->assertEquals($this->object->delete($this->object->getPath('text-for-delete.txt')), true);
        $this->assertEquals(file_exists($this->object->getPath('text-for-delete.txt')), false);
        $this->assertEquals(is_readable($this->object->getPath('text-for-delete.txt')), false);
    }
    
    public function testFileSize()
    {
        $this->assertEquals($this->object->getFileSize(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'), 599639);
        $this->assertEquals($this->object->getFileSize(__DIR__ . '/../../resources/disk-a/kitten-2.jpg'), 131958);
    }
    
    public function testFileMimeType()
    {
        $this->assertEquals($this->object->getFileMimeType(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'), 'image/jpeg');
        $this->assertEquals($this->object->getFileMimeType(__DIR__ . '/../../resources/disk-a/kitten-2.jpg'), 'image/jpeg');
        $this->assertEquals($this->object->getFileMimeType(__DIR__ . '/../../resources/disk-b/kitten-1.png'), 'image/png');
        $this->assertEquals($this->object->getFileMimeType(__DIR__ . '/../../resources/disk-b/kitten-2.png'), 'image/png');
    }
    
    public function testFileHash()
    {
        $this->assertEquals($this->object->getFileHash(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'), '7551f343143d2e24ab4aaf4624996b6a');
        $this->assertEquals($this->object->getFileHash(__DIR__ . '/../../resources/disk-a/kitten-2.jpg'), '81702fdeef2e55b1a22617bce4951cb5');
        $this->assertEquals($this->object->getFileHash(__DIR__ . '/../../resources/disk-b/kitten-1.png'), '03010f4f02980521a8fd6213b52ec313');
        $this->assertEquals($this->object->getFileHash(__DIR__ . '/../../resources/disk-b/kitten-2.png'), '8a9ed992b77e4b62b10e3a5c8ed72062');
    }

    public function testDirectoryCreate()
    {
        $directory = uniqid();
        $this->assertTrue($this->object->createDirectory(__DIR__ . "/$directory"));
        $this->assertTrue($this->object->exists(__DIR__ . "/$directory"));
    }
    
    public function testDirectorySize()
    {
        $this->assertGreaterThan(0, $this->object->getDirectorySize(__DIR__ . '/../../resources/disk-a/'));
        $this->assertGreaterThan(0, $this->object->getDirectorySize(__DIR__ . '/../../resources/disk-b/'));
    }

    public function testPartUpload() {
        $source = __DIR__ . '/../../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('uploaded.mp4');
        $totalSize = $this->object->getFileSize($source);
        $chunkSize = 2097152;

        $chunks = ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $handle = @fopen($source, 'rb');
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__ . '/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $this->object->upload($op, $dest, $chunk, $chunks);
            $start += strlen($contents);
            $chunk++;
            fseek($handle, $start);
        }
        @fclose($handle);
        $this->assertEquals(\filesize($source), $this->object->getFileSize($dest));
        $this->assertEquals(\md5_file($source), $this->object->getFileHash($dest));
        return $dest;
    }

    public function testAbort() {
        $source = __DIR__ . '/../../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('abcduploaded.mp4');
        $totalSize = $this->object->getFileSize($source);
        $chunkSize = 2097152;
        $chunks = ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $handle = @fopen($source, 'rb');
        while ($chunk < 3) { // only upload two chunks
            $contents = fread($handle, $chunkSize);
            $op = __DIR__ . '/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $this->object->upload($op, $dest, $chunk, $chunks);
            $start += strlen($contents);
            $chunk++;
            fseek($handle, $start);
        }
        @fclose($handle);

        // using file name with same first four chars
        $source = __DIR__ . '/../../resources/disk-a/large_file.mp4';
        $dest1 = $this->object->getPath('abcduploaded2.mp4');
        $totalSize = $this->object->getFileSize($source);
        $chunkSize = 2097152;
        $chunks = ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $handle = @fopen($source, 'rb');
        while ($chunk < 3) { // only upload two chunks
            $contents = fread($handle, $chunkSize);
            $op = __DIR__ . '/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $this->object->upload($op, $dest1, $chunk, $chunks);
            $start += strlen($contents);
            $chunk++;
            fseek($handle, $start);
        }
        @fclose($handle);
        
        $this->assertTrue($this->object->abort($dest));
        $this->assertTrue($this->object->abort($dest1));
    }

    /**
     * @depends testPartUpload
     */
    public function testPartRead($path) {
        $source = __DIR__ . '/../../resources/disk-a/large_file.mp4';
        $chunk = file_get_contents($source, false,null, 0, 500);
        $readChunk = $this->object->read($path, 0, 500);
        $this->assertEquals($chunk, $readChunk);
        $this->object->delete($path);
    }
    
    public function testPartitionFreeSpace()
    {
        $this->assertGreaterThan(0, $this->object->getPartitionFreeSpace());
    }
    
    public function testPartitionTotalSpace()
    {
        $this->assertGreaterThan(0, $this->object->getPartitionTotalSpace());
    }
}

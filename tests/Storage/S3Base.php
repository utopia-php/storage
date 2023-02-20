<?php

namespace Utopia\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\S3;

abstract class S3Base extends TestCase
{
    abstract protected function init(): void;

    /**
     * @return string
     */
    abstract protected function getAdapterName(): string;

    /**
     * @return string
     */
    abstract protected function getAdapterDescription(): string;

    /**
     * @var S3
     */
    protected $object = null;

    /**
     * @var string
     */
    protected $root = '/root';

    public function setUp(): void
    {
        $this->init();
        $this->uploadTestFiles();
    }

    private function uploadTestFiles(): void
    {
        $this->object->upload(__DIR__.'/../resources/disk-a/kitten-1.jpg', $this->object->getPath('testing/kitten-1.jpg'));
        $this->object->upload(__DIR__.'/../resources/disk-a/kitten-2.jpg', $this->object->getPath('testing/kitten-2.jpg'));
        $this->object->upload(__DIR__.'/../resources/disk-b/kitten-1.png', $this->object->getPath('testing/kitten-1.png'));
        $this->object->upload(__DIR__.'/../resources/disk-b/kitten-2.png', $this->object->getPath('testing/kitten-2.png'));
    }

    private function removeTestFiles(): void
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

    public function testName(): void
    {
        $this->assertEquals($this->getAdapterName(), $this->object->getName());
    }

    public function testType()
    {
        $this->assertEquals($this->getAdapterType(), $this->object->getType());
    }

    public function testDescription(): void
    {
        $this->assertEquals($this->getAdapterDescription(), $this->object->getDescription());
    }

    public function testRoot(): void
    {
        $this->assertEquals($this->root, $this->object->getRoot());
    }

    public function testPath(): void
    {
        $this->assertEquals($this->root.'/image.png', $this->object->getPath('image.png'));
    }

    public function testWrite(): void
    {
        $this->assertEquals(true, $this->object->write($this->object->getPath('text.txt'), 'Hello World', 'text/plain'));

        $this->object->delete($this->object->getPath('text.txt'));
    }

    public function testRead(): void
    {
        $this->assertEquals(true, $this->object->write($this->object->getPath('text-for-read.txt'), 'Hello World', 'text/plain'));
        $this->assertEquals('Hello World', $this->object->read($this->object->getPath('text-for-read.txt')));

        $this->object->delete($this->object->getPath('text-for-read.txt'));
    }

    public function testFileExists(): void
    {
        $this->assertEquals(true, $this->object->exists($this->object->getPath('testing/kitten-1.jpg')));
        $this->assertEquals(false, $this->object->exists($this->object->getPath('testing/kitten-5.jpg')));
    }

    public function testMove(): void
    {
        $this->assertEquals(true, $this->object->write($this->object->getPath('text-for-move.txt'), 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($this->object->getPath('text-for-move.txt')));
        $this->assertEquals(true, $this->object->move($this->object->getPath('text-for-move.txt'), $this->object->getPath('text-for-move-new.txt')));
        $this->assertEquals('Hello World', $this->object->read($this->object->getPath('text-for-move-new.txt')));
        $this->assertEquals(false, $this->object->exists($this->object->getPath('text-for-move.txt')));

        $this->object->delete($this->object->getPath('text-for-move-new.txt'));
    }

    public function testDelete(): void
    {
        $this->assertEquals(true, $this->object->write($this->object->getPath('text-for-delete.txt'), 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($this->object->getPath('text-for-delete.txt')));
        $this->assertEquals(true, $this->object->delete($this->object->getPath('text-for-delete.txt')));
    }

    public function testSVGUpload()
    {
        $this->assertEquals(true, $this->object->upload(__DIR__.'/../resources/disk-b/appwrite.svg', $this->object->getPath('testing/appwrite.svg')));
        $this->assertEquals(file_get_contents(__DIR__.'/../resources/disk-b/appwrite.svg'), $this->object->read($this->object->getPath('testing/appwrite.svg')));
        $this->assertEquals(true, $this->object->exists($this->object->getPath('testing/appwrite.svg')));
        $this->assertEquals(true, $this->object->delete($this->object->getPath('testing/appwrite.svg')));
    }

    public function testDeletePath(): void
    {
        // Test Single Object
        $path = $this->object->getPath('text-for-delete-path.txt');
        $path = str_ireplace($this->object->getRoot(), $this->object->getRoot().DIRECTORY_SEPARATOR.'bucket', $path);
        $this->assertEquals(true, $this->object->write($path, 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($path));
        $this->assertEquals(true, $this->object->deletePath('bucket'));
        $this->assertEquals(false, $this->object->exists($path));

        // Test Multiple Objects
        $path = $this->object->getPath('text-for-delete-path1.txt');
        $path = str_ireplace($this->object->getRoot(), $this->object->getRoot().DIRECTORY_SEPARATOR.'bucket', $path);
        $this->assertEquals(true, $this->object->write($path, 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($path));

        $path2 = $this->object->getPath('text-for-delete-path2.txt');
        $path2 = str_ireplace($this->object->getRoot(), $this->object->getRoot().DIRECTORY_SEPARATOR.'bucket', $path2);
        $this->assertEquals(true, $this->object->write($path2, 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($path2));

        $this->assertEquals(true, $this->object->deletePath('bucket'));
        $this->assertEquals(false, $this->object->exists($path));
        $this->assertEquals(false, $this->object->exists($path2));
    }

    public function testFileSize(): void
    {
        $this->assertEquals(599639, $this->object->getFileSize($this->object->getPath('testing/kitten-1.jpg')));
        $this->assertEquals(131958, $this->object->getFileSize($this->object->getPath('testing/kitten-2.jpg')));
    }

    public function testFileMimeType(): void
    {
        $this->assertEquals('image/jpeg', $this->object->getFileMimeType($this->object->getPath('testing/kitten-1.jpg')));
        $this->assertEquals('image/jpeg', $this->object->getFileMimeType($this->object->getPath('testing/kitten-2.jpg')));
        $this->assertEquals('image/png', $this->object->getFileMimeType($this->object->getPath('testing/kitten-1.png')));
        $this->assertEquals('image/png', $this->object->getFileMimeType($this->object->getPath('testing/kitten-2.png')));
    }

    public function testFileHash(): void
    {
        $this->assertEquals('7551f343143d2e24ab4aaf4624996b6a', $this->object->getFileHash($this->object->getPath('testing/kitten-1.jpg')));
        $this->assertEquals('81702fdeef2e55b1a22617bce4951cb5', $this->object->getFileHash($this->object->getPath('testing/kitten-2.jpg')));
        $this->assertEquals('03010f4f02980521a8fd6213b52ec313', $this->object->getFileHash($this->object->getPath('testing/kitten-1.png')));
        $this->assertEquals('8a9ed992b77e4b62b10e3a5c8ed72062', $this->object->getFileHash($this->object->getPath('testing/kitten-2.png')));
    }

    public function testDirectoryCreate()
    {
        $this->assertTrue($this->object->createDirectory('temp'));
    }

    public function testDirectorySize()
    {
        $this->assertEquals(-1, $this->object->getDirectorySize('resources/disk-a/'));
    }

    public function testPartitionFreeSpace(): void
    {
        $this->assertEquals(-1, $this->object->getPartitionFreeSpace());
    }

    public function testPartitionTotalSpace(): void
    {
        $this->assertEquals(-1, $this->object->getPartitionTotalSpace());
    }

    public function testPartUpload()
    {
        $source = __DIR__.'/../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('uploaded.mp4');
        $totalSize = \filesize($source);
        // AWS S3 requires each part to be at least 5MB except for last part
        $chunkSize = 5 * 1024 * 1024;

        $chunks = intval(ceil($totalSize / $chunkSize));

        $chunk = 1;
        $start = 0;

        $metadata = [
            'parts' => [],
            'chunks' => 0,
            'uploadId' => null,
            'content_type' => \mime_content_type($source),
        ];
        $handle = @fopen($source, 'rb');
        $op = __DIR__.'/chunk.part';
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__.'/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $etag = $this->object->upload($op, $dest, $chunk, $chunks, $metadata);
            $parts[] = ['partNumber' => $chunk, 'etag' => $etag];
            $start += strlen($contents);
            $chunk++;
            fseek($handle, $start);
        }
        @fclose($handle);
        unlink($op);

        $this->assertEquals(\filesize($source), $this->object->getFileSize($dest));

        // S3 doesnt provide a method to get a proper MD5-hash of a file created using multipart upload
        // https://stackoverflow.com/questions/8618218/amazon-s3-checksum
        // More info on how AWS calculates ETag for multipart upload here
        // https://savjee.be/2015/10/Verifying-Amazon-S3-multi-part-uploads-with-ETag-hash/
        // TODO
        // $this->assertEquals(\md5_file($source), $this->object->getFileHash($dest));
        // $this->object->delete($dest);
        return $dest;
    }

    /**
     * @depends testPartUpload
     */
    public function testPartRead($path)
    {
        $source = __DIR__.'/../resources/disk-a/large_file.mp4';
        $chunk = file_get_contents($source, false, null, 0, 500);
        $readChunk = $this->object->read($path, 0, 500);
        $this->assertEquals($chunk, $readChunk);
        $this->object->delete($path);
    }
}

<?php

declare(strict_types=1);

namespace Utopia\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Device\S3;
use Utopia\Storage\Exception\NotFoundException;

abstract class S3Base extends TestCase
{
    abstract protected function init(): void;

    abstract protected function getAdapterName(): string;

    abstract protected function getAdapterType(): string;

    abstract protected function getAdapterDescription(): string;

    /**
     * @var S3
     */
    protected $object;

    /**
     * @var string
     */
    protected $root = '/root';

    protected function setUp(): void
    {
        $this->init();
        $this->uploadTestFiles();
    }

    private function uploadTestFiles(): void
    {
        $this->object->upload(__DIR__ . '/../resources/disk-a/kitten-1.jpg', $this->object->getPath('testing/kitten-1.jpg'));
        $this->object->upload(__DIR__ . '/../resources/disk-a/kitten-2.jpg', $this->object->getPath('testing/kitten-2.jpg'));
        $this->object->upload(__DIR__ . '/../resources/disk-b/kitten-1.png', $this->object->getPath('testing/kitten-1.png'));
        $this->object->upload(__DIR__ . '/../resources/disk-b/kitten-2.png', $this->object->getPath('testing/kitten-2.png'));
    }

    private function removeTestFiles(): void
    {
        $this->object->delete($this->object->getPath('testing/kitten-1.jpg'));
        $this->object->delete($this->object->getPath('testing/kitten-2.jpg'));
        $this->object->delete($this->object->getPath('testing/kitten-1.png'));
        $this->object->delete($this->object->getPath('testing/kitten-2.png'));
    }

    protected function tearDown(): void
    {
        $this->removeTestFiles();
    }

    public function testGetFiles(): void
    {
        $path = $this->object->getPath('testing/');
        $files = $this->object->getFiles($path);
        $this->assertEquals(4, $files['KeyCount']);
        $this->assertEquals(false, $files['IsTruncated']);
        $this->assertIsArray($files['Contents']);

        $file = $files['Contents'][0];

        $this->assertArrayHasKey('Key', $file);
        $this->assertArrayHasKey('LastModified', $file);
        $this->assertArrayHasKey('ETag', $file);
        $this->assertArrayHasKey('StorageClass', $file);
        $this->assertArrayHasKey('Size', $file);
    }

    public function testGetFilesPagination(): void
    {
        $path = $this->object->getPath('testing/');

        $files = $this->object->getFiles($path, 3);
        $this->assertEquals(3, $files['KeyCount']);
        $this->assertEquals(3, $files['MaxKeys']);
        $this->assertEquals(true, $files['IsTruncated']);
        $this->assertIsArray($files['Contents']);
        $this->assertArrayHasKey('NextContinuationToken', $files);

        $files = $this->object->getFiles($path, 1000, $files['NextContinuationToken']);
        $this->assertEquals(1, $files['KeyCount']);
        $this->assertEquals(1000, $files['MaxKeys']);
        $this->assertEquals(false, $files['IsTruncated']);
        $this->assertIsArray($files['Contents']);
        $this->assertArrayNotHasKey('NextContinuationToken', $files);
    }

    public function testName(): void
    {
        $this->assertEquals($this->getAdapterName(), $this->object->getName());
    }

    public function testType(): void
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
        $this->assertEquals($this->root . '/image.png', $this->object->getPath('image.png'));
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

    public function testReadNonExistentFile(): void
    {
        $this->expectException(NotFoundException::class);
        $this->object->read($this->object->getPath('non-existent-file.txt'));
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

    public function testMoveIdenticalName(): void
    {
        $file = '/kitten-1.jpg';
        $this->assertFalse($this->object->move($file, $file));
    }

    public function testDelete(): void
    {
        $this->assertEquals(true, $this->object->write($this->object->getPath('text-for-delete.txt'), 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($this->object->getPath('text-for-delete.txt')));
        $this->assertEquals(true, $this->object->delete($this->object->getPath('text-for-delete.txt')));
    }

    public function testSVGUpload(): void
    {
        $this->assertEquals(true, $this->object->upload(__DIR__ . '/../resources/disk-b/appwrite.svg', $this->object->getPath('testing/appwrite.svg')));
        $this->assertEquals(file_get_contents(__DIR__ . '/../resources/disk-b/appwrite.svg'), $this->object->read($this->object->getPath('testing/appwrite.svg')));
        $this->assertEquals(true, $this->object->exists($this->object->getPath('testing/appwrite.svg')));
        $this->assertEquals(true, $this->object->delete($this->object->getPath('testing/appwrite.svg')));
    }

    public function testXMLUpload(): void
    {
        $this->assertEquals(true, $this->object->upload(__DIR__ . '/../resources/disk-a/config.xml', $this->object->getPath('testing/config.xml')));
        $this->assertEquals(file_get_contents(__DIR__ . '/../resources/disk-a/config.xml'), $this->object->read($this->object->getPath('testing/config.xml')));
        $this->assertEquals(true, $this->object->exists($this->object->getPath('testing/config.xml')));
        $this->assertEquals(true, $this->object->delete($this->object->getPath('testing/config.xml')));
    }

    public function testDeletePath(): void
    {
        // Test Single Object
        $path = $this->object->getPath('text-for-delete-path.txt');
        $path = str_ireplace($this->object->getRoot(), $this->object->getRoot() . DIRECTORY_SEPARATOR . 'bucket', $path);
        $this->assertEquals(true, $this->object->write($path, 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($path));
        $this->assertEquals(true, $this->object->deletePath('bucket'));
        $this->assertEquals(false, $this->object->exists($path));

        // Test Multiple Objects
        $path = $this->object->getPath('text-for-delete-path1.txt');
        $path = str_ireplace($this->object->getRoot(), $this->object->getRoot() . DIRECTORY_SEPARATOR . 'bucket', $path);
        $this->assertEquals(true, $this->object->write($path, 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($path));

        $path2 = $this->object->getPath('text-for-delete-path2.txt');
        $path2 = str_ireplace($this->object->getRoot(), $this->object->getRoot() . DIRECTORY_SEPARATOR . 'bucket', $path2);
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

    public function testDirectoryCreate(): void
    {
        $this->assertTrue($this->object->createDirectory('temp'));
    }

    public function testDirectorySize(): void
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
        $source = __DIR__ . '/../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('uploaded.mp4');
        $totalSize = filesize($source);
        // AWS S3 requires each part to be at least 5MB except for last part
        $chunkSize = 5 * 1024 * 1024;

        $chunks = (int) ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $metadata = [
            'parts' => [],
            'chunks' => 0,
            'uploadId' => null,
            'content_type' => mime_content_type($source),
        ];
        $handle = @fopen($source, 'rb');
        $op = __DIR__ . '/chunk.part';
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__ . '/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $this->object->upload($op, $dest, $chunk, $chunks, $metadata);
            $start += \strlen($contents);
            $chunk++;
            fseek($handle, $start);
        }
        @fclose($handle);
        unlink($op);

        $this->assertEquals(filesize($source), $this->object->getFileSize($dest));

        // S3 doesnt provide a method to get a proper MD5-hash of a file created using multipart upload
        // https://stackoverflow.com/questions/8618218/amazon-s3-checksum
        // More info on how AWS calculates ETag for multipart upload here
        // https://savjee.be/2015/10/Verifying-Amazon-S3-multi-part-uploads-with-ETag-hash/
        // TODO
        // $this->assertEquals(\md5_file($source), $this->object->getFileHash($dest));
        // $this->object->delete($dest);
        return $dest;
    }

    public function testPartUploadRetry()
    {
        $source = __DIR__ . '/../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('uploaded.mp4');
        $totalSize = filesize($source);
        // AWS S3 requires each part to be at least 5MB except for last part
        $chunkSize = 5 * 1024 * 1024;

        $chunks = (int) ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $metadata = [
            'parts' => [],
            'chunks' => 0,
            'uploadId' => null,
            'content_type' => mime_content_type($source),
        ];
        $handle = @fopen($source, 'rb');
        $op = __DIR__ . '/chunk.part';
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__ . '/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $this->object->upload($op, $dest, $chunk, $chunks, $metadata);
            $start += \strlen($contents);
            $chunk++;
            break;
        }
        @fclose($handle);
        unlink($op);

        $chunk = 1;
        $start = 0;
        // retry from first to make sure duplicate chunk re-upload works without issue
        $handle = @fopen($source, 'rb');
        $op = __DIR__ . '/chunk.part';
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__ . '/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $this->object->upload($op, $dest, $chunk, $chunks, $metadata);
            $start += \strlen($contents);
            $chunk++;
            fseek($handle, $start);
        }
        @fclose($handle);
        unlink($op);

        $this->assertEquals(filesize($source), $this->object->getFileSize($dest));

        // S3 doesnt provide a method to get a proper MD5-hash of a file created using multipart upload
        // https://stackoverflow.com/questions/8618218/amazon-s3-checksum
        // More info on how AWS calculates ETag for multipart upload here
        // https://savjee.be/2015/10/Verifying-Amazon-S3-multi-part-uploads-with-ETag-hash/
        // TODO
        // $this->assertEquals(\md5_file($source), $this->object->getFileHash($dest));
        // $this->object->delete($dest);
        return $dest;
    }

    public function testOutOfOrderPartUpload()
    {
        $source = __DIR__ . '/../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('uploaded-out-of-order.mp4');
        $totalSize = filesize($source);
        // AWS S3 requires each part to be at least 5MB except for last part
        $chunkSize = 5 * 1024 * 1024;

        $chunks = (int) ceil($totalSize / $chunkSize);

        // Read all chunk contents into memory so we can upload out of order
        $parts = [];
        $handle = @fopen($source, 'rb');
        $chunkNum = 1;
        while ($chunkNum <= $chunks) {
            $contents = fread($handle, $chunkSize);
            $parts[$chunkNum] = $contents;
            $chunkNum++;
        }
        @fclose($handle);

        $metadata = [
            'parts' => [],
            'chunks' => 0,
            'uploadId' => null,
            'content_type' => mime_content_type($source),
        ];

        // Upload chunks in reverse order
        for ($i = $chunks; $i >= 1; $i--) {
            $op = __DIR__ . '/chunk.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $parts[$i]);
            fclose($cc);
            $this->object->upload($op, $dest, $i, $chunks, $metadata);
            unlink($op);
        }

        $this->assertEquals(filesize($source), $this->object->getFileSize($dest));

        // S3 doesnt provide a method to get a proper MD5-hash of a file created using multipart upload
        // TODO
        // $this->assertEquals(\md5_file($source), $this->object->getFileHash($dest));
        // $this->object->delete($dest);
        return $dest;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPartUpload')]
    public function testPartRead(string $path): void
    {
        $source = __DIR__ . '/../resources/disk-a/large_file.mp4';
        $chunk = file_get_contents($source, false, null, 0, 500);
        $readChunk = $this->object->read($path, 0, 500);
        $this->assertEquals($chunk, $readChunk);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPartUpload')]
    public function testTransferLarge(string $path): void
    {
        // chunked file
        $this->object->setTransferChunkSize(10000000); // 10 mb

        $device = new Local(__DIR__ . '/../resources/disk-a');
        $destination = $device->getPath('largefile.mp4');

        $this->assertTrue($this->object->transfer($path, $destination, $device));
        $this->assertTrue($device->exists($destination));
        $this->assertSame('video/mp4', $device->getFileMimeType($destination));

        $device->delete($destination);
        $this->object->delete($path);
    }

    public function testTransferSmall(): void
    {
        $this->object->setTransferChunkSize(10000000); // 10 mb

        $device = new Local(__DIR__ . '/../resources/disk-a');

        $path = $this->object->getPath('text-for-read.txt');
        $this->object->write($path, 'Hello World', 'text/plain');

        $destination = $device->getPath('hello.txt');
        $this->assertTrue($this->object->transfer($path, $destination, $device));
        $this->assertTrue($device->exists($destination));
        $this->assertSame('Hello World', $device->read($destination));

        $this->object->delete($path);
        $device->delete($destination);
    }

    public function testTransferNonExistentFile(): void
    {
        $device = new Local(__DIR__ . '/../resources/disk-a');

        $path = $this->object->getPath('non-existent-file.txt');
        $destination = $device->getPath('hello.txt');

        $this->expectException(NotFoundException::class);
        $this->object->transfer($path, $destination, $device);
    }
}

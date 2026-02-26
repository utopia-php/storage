<?php

namespace Utopia\Tests\Storage\Device;

use Exception;
use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\AWS;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Exception\NotFoundException;

class LocalTest extends TestCase
{
    /**
     * @var Local
     */
    protected $object = null;

    protected function setUp(): void
    {
        $this->object = new Local(realpath(__DIR__.'/../../resources/disk-a'));
    }

    protected function tearDown(): void {}

    public function testPaths()
    {
        $this->assertEquals($this->object->getAbsolutePath('////storage/functions'), '/storage/functions');
        $this->assertEquals($this->object->getAbsolutePath('storage/functions'), '/storage/functions');
        $this->assertEquals($this->object->getAbsolutePath('/storage/functions'), '/storage/functions');
        $this->assertEquals($this->object->getAbsolutePath('//storage///functions//'), '/storage/functions');
        $this->assertEquals($this->object->getAbsolutePath('\\\\\storage\functions'), '/storage/functions');
        $this->assertEquals($this->object->getAbsolutePath('..\\\\\//storage\\//functions'), '/storage/functions');
        $this->assertEquals($this->object->getAbsolutePath('./..\\\\\//storage\\//functions'), '/storage/functions');
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
        $this->assertEquals($this->object->getRoot(), $this->object->getAbsolutePath(__DIR__.'/../../resources/disk-a'));
    }

    public function testPath()
    {
        $this->assertEquals($this->object->getPath('image.png'), $this->object->getAbsolutePath(__DIR__.'/../../resources/disk-a').'/image.png');
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

    public function testReadNonExistentFile()
    {
        $this->expectException(NotFoundException::class);
        $this->object->read($this->object->getPath('non-existent-file.txt'));
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

    public function testRecursiveDeleteRemovesHiddenFiles()
    {
        $directory = $this->object->getPath('delete-hidden');

        $this->assertTrue($this->object->createDirectory($directory));
        $this->assertTrue($this->object->write($directory.DIRECTORY_SEPARATOR.'.hidden', 'secret'));
        $this->assertTrue($this->object->write($directory.DIRECTORY_SEPARATOR.'visible', 'visible'));

        $this->assertTrue($this->object->delete($directory, true));
        $this->assertFalse($this->object->exists($directory));
    }

    public function testFileSize()
    {
        $this->assertEquals($this->object->getFileSize(__DIR__.'/../../resources/disk-a/kitten-1.jpg'), 599639);
        $this->assertEquals($this->object->getFileSize(__DIR__.'/../../resources/disk-a/kitten-2.jpg'), 131958);
    }

    public function testFileMimeType()
    {
        $this->assertEquals($this->object->getFileMimeType(__DIR__.'/../../resources/disk-a/kitten-1.jpg'), 'image/jpeg');
        $this->assertEquals($this->object->getFileMimeType(__DIR__.'/../../resources/disk-a/kitten-2.jpg'), 'image/jpeg');
        $this->assertEquals($this->object->getFileMimeType(__DIR__.'/../../resources/disk-b/kitten-1.png'), 'image/png');
        $this->assertEquals($this->object->getFileMimeType(__DIR__.'/../../resources/disk-b/kitten-2.png'), 'image/png');
    }

    public function testFileHash()
    {
        $this->assertEquals($this->object->getFileHash(__DIR__.'/../../resources/disk-a/kitten-1.jpg'), '7551f343143d2e24ab4aaf4624996b6a');
        $this->assertEquals($this->object->getFileHash(__DIR__.'/../../resources/disk-a/kitten-2.jpg'), '81702fdeef2e55b1a22617bce4951cb5');
        $this->assertEquals($this->object->getFileHash(__DIR__.'/../../resources/disk-b/kitten-1.png'), '03010f4f02980521a8fd6213b52ec313');
        $this->assertEquals($this->object->getFileHash(__DIR__.'/../../resources/disk-b/kitten-2.png'), '8a9ed992b77e4b62b10e3a5c8ed72062');
    }

    public function testDirectoryCreate()
    {
        $directory = uniqid();
        $this->assertTrue($this->object->createDirectory(__DIR__."/$directory"));
        $this->assertTrue($this->object->exists(__DIR__."/$directory"));
    }

    public function testDirectorySize()
    {
        $this->assertGreaterThan(0, $this->object->getDirectorySize(__DIR__.'/../../resources/disk-a/'));
        $this->assertGreaterThan(0, $this->object->getDirectorySize(__DIR__.'/../../resources/disk-b/'));
    }

    public function testPartUpload()
    {
        $source = __DIR__.'/../../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('uploaded.mp4');
        $totalSize = $this->object->getFileSize($source);
        $chunkSize = 2097152;

        $chunks = ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $handle = @fopen($source, 'rb');
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__.'/chunk.part';
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

    public function testPartUploadRetry()
    {
        $source = __DIR__.'/../../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('uploaded2.mp4');
        $totalSize = \filesize($source);
        // AWS S3 requires each part to be at least 5MB except for last part
        $chunkSize = 5 * 1024 * 1024;

        $chunks = ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;
        $handle = @fopen($source, 'rb');
        $op = __DIR__.'/chunkx.part';
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__.'/chunkx.part';
            $cc = fopen($op, 'wb');
            fwrite($cc, $contents);
            fclose($cc);
            $this->object->upload($op, $dest, $chunk, $chunks);
            $start += strlen($contents);
            $chunk++;
            if ($chunk == 2) {
                break;
            }
            fseek($handle, $start);
        }
        @fclose($handle);

        $chunk = 1;
        $start = 0;
        // retry from first to make sure duplicate chunk re-upload works without issue
        $handle = @fopen($source, 'rb');
        $op = __DIR__.'/chunkx.part';
        while ($start < $totalSize) {
            $contents = fread($handle, $chunkSize);
            $op = __DIR__.'/chunkx.part';
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

    public function testAbort()
    {
        $source = __DIR__.'/../../resources/disk-a/large_file.mp4';
        $dest = $this->object->getPath('abcduploaded.mp4');
        $totalSize = $this->object->getFileSize($source);
        $chunkSize = 2097152;
        $chunks = ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $handle = @fopen($source, 'rb');
        while ($chunk < 3) { // only upload two chunks
            $contents = fread($handle, $chunkSize);
            $op = __DIR__.'/chunk.part';
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
        $source = __DIR__.'/../../resources/disk-a/large_file.mp4';
        $dest1 = $this->object->getPath('abcduploaded2.mp4');
        $totalSize = $this->object->getFileSize($source);
        $chunkSize = 2097152;
        $chunks = ceil($totalSize / $chunkSize);

        $chunk = 1;
        $start = 0;

        $handle = @fopen($source, 'rb');
        while ($chunk < 3) { // only upload two chunks
            $contents = fread($handle, $chunkSize);
            $op = __DIR__.'/chunk.part';
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
    public function testPartRead($path)
    {
        $source = __DIR__.'/../../resources/disk-a/large_file.mp4';
        $chunk = file_get_contents($source, false, null, 0, 500);
        $readChunk = $this->object->read($path, 0, 500);
        $this->assertEquals($chunk, $readChunk);
    }

    public function testPartitionFreeSpace()
    {
        $this->assertGreaterThan(0, $this->object->getPartitionFreeSpace());
    }

    public function testPartitionTotalSpace()
    {
        $this->assertGreaterThan(0, $this->object->getPartitionTotalSpace());
    }

    /**
     * @depends testPartUpload
     */
    public function testTransferLarge($path)
    {
        // chunked file
        $this->object->setTransferChunkSize(10000000); // 10 mb

        $key = $_SERVER['S3_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['S3_SECRET'] ?? '';
        $bucket = 'utopia-storage-test';

        $device = new AWS('/root', $key, $secret, $bucket, AWS::EU_CENTRAL_1, AWS::ACL_PRIVATE);
        $destination = $device->getPath('largefile.mp4');

        $this->assertTrue($this->object->transfer($path, $destination, $device));
        $this->assertTrue($device->exists($destination));
        $this->assertEquals($device->getFileMimeType($destination), 'video/mp4');

        $device->delete($destination);
        $this->object->delete($path);
    }

    public function testTransferSmall()
    {
        $this->object->setTransferChunkSize(10000000); // 10 mb

        $key = $_SERVER['S3_ACCESS_KEY'] ?? '';
        $secret = $_SERVER['S3_SECRET'] ?? '';
        $bucket = 'utopia-storage-test';

        $device = new AWS('/root', $key, $secret, $bucket, AWS::EU_CENTRAL_1, AWS::ACL_PRIVATE);

        $path = $this->object->getPath('text-for-read.txt');
        $this->object->write($path, 'Hello World');

        $destination = $device->getPath('hello.txt');
        $this->assertTrue($this->object->transfer($path, $destination, $device));
        $this->assertTrue($device->exists($destination));
        $this->assertEquals($device->read($destination), 'Hello World');

        $this->object->delete($path);
        $device->delete($destination);
    }

    public function testDeletePath()
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

        $path3 = $this->object->getPath('.hidden.txt');
        $path3 = str_ireplace($this->object->getRoot(), $this->object->getRoot().DIRECTORY_SEPARATOR.'bucket', $path3);
        $this->assertEquals(true, $this->object->write($path3, 'Hello World', 'text/plain'));
        $this->assertEquals(true, $this->object->exists($path3));

        $this->assertEquals(true, $this->object->deletePath('bucket/'));
        $this->assertEquals(false, $this->object->exists($path));
        $this->assertEquals(false, $this->object->exists($path2));
        $this->assertEquals(false, $this->object->exists($path3));
    }

    public function testGetFiles()
    {
        $dir = DIRECTORY_SEPARATOR.'get-files-test';

        $this->assertTrue($this->object->createDirectory($dir));

        $files = $this->object->getFiles($dir);
        $this->assertEquals(0, \count($files));

        $this->object->write($dir.DIRECTORY_SEPARATOR.'new-file.txt', 'Hello World');
        $this->object->write($dir.DIRECTORY_SEPARATOR.'new-file-two.txt', 'Hello World');

        $files = $this->object->getFiles($dir);
        $this->assertEquals(2, \count($files));
    }

    public function testNestedDeletePath()
    {
        $dir = $this->object->getPath('nested-delete-path-test');
        $dir2 = $dir.DIRECTORY_SEPARATOR.'dir2';
        $dir3 = $dir2.DIRECTORY_SEPARATOR.'dir3';

        $this->assertTrue($this->object->createDirectory($dir));
        $this->object->write($dir.DIRECTORY_SEPARATOR.'new-file.txt', 'Hello World');
        $this->assertTrue($this->object->createDirectory($dir2));
        $this->object->write($dir2.DIRECTORY_SEPARATOR.'new-file-2.txt', 'Hello World');
        $this->assertTrue($this->object->createDirectory($dir3));
        $this->object->write($dir3.DIRECTORY_SEPARATOR.'new-file-3.txt', 'Hello World');

        $this->assertTrue($this->object->deletePath('nested-delete-path-test'));
        $this->assertFalse($this->object->exists($dir));
    }

    // -------------------------------------------------------------------------
    // joinChunks tests
    // -------------------------------------------------------------------------

    /**
     * Create a self-contained Local storage instance in a fresh temp directory.
     * The caller is responsible for deleting the root after the test.
     */
    private function makeJoinTestStorage(): Local
    {
        $dir = \sys_get_temp_dir().DIRECTORY_SEPARATOR.'utopia-join-test-'.uniqid();
        \mkdir($dir, 0755, true);

        return new Local($dir);
    }

    public function testJoinChunksAssemblesContentCorrectly(): void
    {
        $storage = $this->makeJoinTestStorage();
        $dest = $storage->getRoot().DIRECTORY_SEPARATOR.'test.dat';

        $storage->uploadData('AAAA', $dest, 'application/octet-stream', 1, 3);
        $storage->uploadData('BBBB', $dest, 'application/octet-stream', 2, 3);
        $storage->uploadData('CCCC', $dest, 'application/octet-stream', 3, 3);

        $this->assertTrue(\file_exists($dest));
        $this->assertSame('AAAABBBBCCCC', \file_get_contents($dest));

        $storage->delete($storage->getRoot(), true);
    }

    public function testJoinChunksCleansUpTempFilesOnSuccess(): void
    {
        $storage = $this->makeJoinTestStorage();
        $dest = $storage->getRoot().DIRECTORY_SEPARATOR.'test.dat';
        $tmpDir = $storage->getRoot().DIRECTORY_SEPARATOR.'tmp_test.dat';
        $tmpAssemble = $storage->getRoot().DIRECTORY_SEPARATOR.'tmp_assemble_test.dat';

        $storage->uploadData('AAAA', $dest, 'application/octet-stream', 1, 3);
        $storage->uploadData('BBBB', $dest, 'application/octet-stream', 2, 3);
        $storage->uploadData('CCCC', $dest, 'application/octet-stream', 3, 3);

        $this->assertFalse(\is_dir($tmpDir), 'Temp chunk directory should be removed after assembly');
        $this->assertFalse(\file_exists($tmpAssemble), 'Temp assembly file should be removed after rename');
        for ($i = 1; $i <= 3; $i++) {
            $this->assertFalse(
                \file_exists($tmpDir.DIRECTORY_SEPARATOR.'test.part.'.$i),
                "Part file $i should be removed after assembly"
            );
        }

        $storage->delete($storage->getRoot(), true);
    }

    public function testJoinChunksMissingPartThrowsAndPreservesState(): void
    {
        $storage = $this->makeJoinTestStorage();
        $dest = $storage->getRoot().DIRECTORY_SEPARATOR.'test.dat';
        $tmpDir = $storage->getRoot().DIRECTORY_SEPARATOR.'tmp_test.dat';
        $tmpAssemble = $storage->getRoot().DIRECTORY_SEPARATOR.'tmp_assemble_test.dat';

        $storage->uploadData('AAAA', $dest, 'application/octet-stream', 1, 3);
        $storage->uploadData('BBBB', $dest, 'application/octet-stream', 2, 3);

        // Simulate a missing/corrupted chunk by deleting part 1 before the
        // final upload triggers assembly.
        \unlink($tmpDir.DIRECTORY_SEPARATOR.'test.part.1');

        $exceptionThrown = false;
        try {
            $storage->uploadData('CCCC', $dest, 'application/octet-stream', 3, 3);
        } catch (Exception $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('Failed to open chunk', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Exception should be thrown when a chunk is missing');
        $this->assertFalse(\file_exists($dest), 'Final file must not be created on assembly failure');
        $this->assertFalse(\file_exists($tmpAssemble), 'Temp assembly file must be cleaned up on failure');
        // Surviving parts must remain so the upload can be retried.
        $this->assertTrue(
            \file_exists($tmpDir.DIRECTORY_SEPARATOR.'test.part.2'),
            'Part 2 must be preserved for retry'
        );
        $this->assertTrue(
            \file_exists($tmpDir.DIRECTORY_SEPARATOR.'test.part.3'),
            'Part 3 must be preserved for retry'
        );

        $storage->delete($storage->getRoot(), true);
    }

    public function testJoinChunksStaleAssemblyFileIsOverwritten(): void
    {
        $storage = $this->makeJoinTestStorage();
        $dest = $storage->getRoot().DIRECTORY_SEPARATOR.'test.dat';
        $tmpAssemble = $storage->getRoot().DIRECTORY_SEPARATOR.'tmp_assemble_test.dat';

        $storage->uploadData('AAAA', $dest, 'application/octet-stream', 1, 3);
        $storage->uploadData('BBBB', $dest, 'application/octet-stream', 2, 3);

        // Simulate a stale assembly file left by a previously crashed attempt.
        \file_put_contents($tmpAssemble, 'STALE_GARBAGE_DATA');

        $storage->uploadData('CCCC', $dest, 'application/octet-stream', 3, 3);

        $this->assertTrue(\file_exists($dest));
        $this->assertSame('AAAABBBBCCCC', \file_get_contents($dest), 'Stale assembly file must not corrupt output');
        $this->assertFalse(\file_exists($tmpAssemble), 'Temp assembly file should be removed after successful rename');

        $storage->delete($storage->getRoot(), true);
    }
}

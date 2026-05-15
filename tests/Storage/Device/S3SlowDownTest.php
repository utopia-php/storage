<?php

namespace Utopia\Tests\Storage\Device;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\S3;

/**
 * Testable S3 subclass that exposes protected helpers.
 */
class TestableS3 extends S3
{
    public array $calls = [];

    public string $completedBody = '';

    public array $headersByOperation = [];

    private bool $objectExists = false;

    public function exposedIsTransientError(int $statusCode, string $body): bool
    {
        return $this->isTransientError($statusCode, $body);
    }

    protected function call(string $operation, string $method, string $uri, string $data = '', array $parameters = [], bool $decode = true)
    {
        $this->calls[] = $operation;
        $this->headersByOperation[$operation] = $this->headers;

        if ($operation === 's3:info') {
            if (! $this->objectExists) {
                throw new \Exception('Not found');
            }

            return (object) ['headers' => ['content-length' => 1], 'body' => ''];
        }

        if ($operation === 's3:createMultipartUpload') {
            return (object) ['headers' => [], 'body' => ['UploadId' => 'upload-123']];
        }

        if ($operation === 's3:uploadPart') {
            return (object) ['headers' => ['etag' => 'etag-'.$parameters['partNumber']], 'body' => ''];
        }

        if ($operation === 's3:completeMultipartUpload') {
            $this->completedBody = $data;
            $this->objectExists = true;

            return (object) ['headers' => [], 'body' => ''];
        }

        return (object) ['headers' => [], 'body' => ''];
    }
}

class S3SlowDownTest extends TestCase
{
    private TestableS3 $s3;

    protected function setUp(): void
    {
        $this->s3 = new TestableS3(
            root: '/root',
            accessKey: 'test-key',
            secretKey: 'test-secret',
            host: 'https://s3.example.com',
            region: 'us-east-1',
        );
    }

    protected function tearDown(): void
    {
        S3::setRetryAttempts(3);
        S3::setRetryDelay(500);
    }

    public function testTransientXmlErrorIsRetried(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>SlowDown</Code><Message>Please reduce your request rate.</Message></Error>';
        $this->assertTrue($this->s3->exposedIsTransientError(503, $body));
    }

    public function testNonTransientXmlErrorIsNotRetried(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>NoSuchKey</Code><Message>The specified key does not exist.</Message></Error>';
        $this->assertFalse($this->s3->exposedIsTransientError(404, $body));
    }

    public function testStatusFallbackIsTransient(): void
    {
        $this->assertTrue($this->s3->exposedIsTransientError(429, ''));
        $this->assertTrue($this->s3->exposedIsTransientError(503, ''));
    }

    /** XML error code takes precedence over HTTP status — 503 with non-transient XML must not be retried. */
    public function test503WithNonTransientXmlIsNotRetried(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>InternalError</Code><Message>Internal server error.</Message></Error>';
        $this->assertFalse($this->s3->exposedIsTransientError(503, $body));
    }

    public function testDefaultRetrySettings(): void
    {
        $prop = fn (string $name) => (new \ReflectionProperty(S3::class, $name))->getValue();
        $this->assertSame(3, $prop('retryAttempts'));
        $this->assertSame(500, $prop('retryDelay'));
    }

    public function testPrepareUploadCreatesMultipartMetadata(): void
    {
        $metadata = [];

        $this->s3->prepareUpload('/root/file.txt', 'text/plain', 2, $metadata);

        $this->assertSame('upload-123', $metadata['uploadId']);
        $this->assertSame([], $metadata['parts']);
        $this->assertSame(0, $metadata['chunks']);
        $this->assertSame(['s3:createMultipartUpload'], $this->s3->calls);
    }

    public function testUploadChunkRecordsPartWithoutCompleting(): void
    {
        $metadata = [];
        $source = __DIR__.'/s3-chunk.part';
        file_put_contents($source, 'aaa');

        $this->s3->prepareUpload('/root/file.txt', 'text/plain', 2, $metadata);
        $chunks = $this->s3->uploadChunk($source, '/root/file.txt', 1, 2, $metadata);

        $this->assertSame(1, $chunks);
        $this->assertSame('etag-1', $metadata['parts'][1]);
        $this->assertNotContains('s3:completeMultipartUpload', $this->s3->calls);

        unlink($source);
    }

    public function testSingleChunkUploadDataDoesNotFinalizeOrCheckExists(): void
    {
        $metadata = [];

        $this->assertSame(1, $this->s3->uploadData('aaa', '/root/file.txt', 'text/plain', 1, 1, $metadata));
        $this->assertSame(['s3:write'], $this->s3->calls);
        $this->assertSame([1 => true], $metadata['parts']);
        $this->assertSame(1, $metadata['chunks']);
    }

    public function testFinalizeUploadRequiresAllS3Parts(): void
    {
        $metadata = [
            'uploadId' => 'upload-123',
            'parts' => [1 => 'etag-1'],
            'chunks' => 1,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing chunk 2');
        $this->s3->finalizeUpload('/root/file.txt', 2, $metadata);
    }

    public function testFinalizeUploadCompletesS3PartsInNumericOrder(): void
    {
        $metadata = [
            'uploadId' => 'upload-123',
            'parts' => [
                10 => 'etag-10',
                9 => 'etag-9',
                8 => 'etag-8',
                7 => 'etag-7',
                6 => 'etag-6',
                5 => 'etag-5',
                4 => 'etag-4',
                3 => 'etag-3',
                2 => 'etag-2',
                1 => 'etag-1',
            ],
            'chunks' => 10,
        ];

        $this->assertTrue($this->s3->finalizeUpload('/root/file.txt', 10, $metadata));

        $part1 = strpos($this->s3->completedBody, '<PartNumber>1</PartNumber>');
        $part2 = strpos($this->s3->completedBody, '<PartNumber>2</PartNumber>');
        $part10 = strpos($this->s3->completedBody, '<PartNumber>10</PartNumber>');

        $this->assertNotFalse($part1);
        $this->assertNotFalse($part2);
        $this->assertNotFalse($part10);
        $this->assertLessThan($part2, $part1);
        $this->assertLessThan($part10, $part2);
    }

    public function testFinalizeUploadSendsCompleteBodyAsXml(): void
    {
        $metadata = [
            'uploadId' => 'upload-123',
            'parts' => [1 => 'etag-1', 2 => 'etag-2'],
            'chunks' => 2,
        ];

        $this->assertTrue($this->s3->finalizeUpload('/root/file.txt', 2, $metadata));
        $this->assertSame('application/xml', $this->s3->headersByOperation['s3:completeMultipartUpload']['content-type']);
    }
}

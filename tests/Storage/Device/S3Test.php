<?php

declare(strict_types=1);

namespace Utopia\Tests\Storage\Device;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Utopia\Client\Adapter;
use Utopia\Client\Decorator\Retry;
use Utopia\Client\Exception\NetworkException;
use Utopia\Client\Tls;
use Utopia\Psr7\Request;
use Utopia\Psr7\Response;
use Utopia\Psr7\Stream;
use Utopia\Psr7\Uri;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Device\S3;
use Utopia\Storage\Device\S3\Response as S3Response;
use Utopia\Storage\Device\S3\RetryStrategy;
use Utopia\Storage\Exception\NotFoundException;
use Utopia\Storage\Exception\RemoteException;
use Utopia\Storage\Exception\StorageException;
use Utopia\Storage\Exception\TransportException;
use Utopia\Storage\Exception\UploadException;

/**
 * Testable S3 subclass that exposes protected helpers.
 */
class TestableS3 extends S3
{
    /**
     * @var array<string>
     */
    public array $calls = [];

    public string $completedBody = '';

    /**
     * @var array<string, array<string, string>>
     */
    public array $headersByOperation = [];

    public ?int $failPart = null;

    private bool $objectExists = false;

    #[\Override]
    protected function call(string $method, string $uri, string $data = '', array $parameters = [], array $headers = [], array $amzHeaders = [], bool $decode = true): S3Response
    {
        $operation = match (true) {
            $method === 'HEAD' => 's3:info',
            $method === 'POST' && \array_key_exists('uploads', $parameters) => 's3:createMultipartUpload',
            $method === 'PUT' && isset($parameters['partNumber']) => 's3:uploadPart',
            $method === 'POST' && isset($parameters['uploadId']) => 's3:completeMultipartUpload',
            $method === 'DELETE' && isset($parameters['uploadId']) => 's3:abort',
            $method === 'PUT' => 's3:write',
            default => 's3:' . strtolower($method),
        };
        $this->calls[] = $operation;
        $this->headersByOperation[$operation] = $headers;

        if ($operation === 's3:info') {
            if (! $this->objectExists) {
                throw new NotFoundException('Not found');
            }

            return new S3Response(code: 200, headers: ['content-length' => '1'], body: '');
        }

        if ($operation === 's3:createMultipartUpload') {
            return new S3Response(code: 200, headers: [], body: ['UploadId' => 'upload-123']);
        }

        if ($operation === 's3:uploadPart') {
            if ($this->failPart === $parameters['partNumber']) {
                throw new RemoteException('Injected part failure');
            }

            return new S3Response(code: 200, headers: ['etag' => 'etag-' . $parameters['partNumber']], body: '');
        }

        if ($operation === 's3:completeMultipartUpload') {
            $this->completedBody = $data;
            $this->objectExists = true;

            return new S3Response(code: 200, headers: [], body: '');
        }

        return new S3Response(code: 200, headers: [], body: '');
    }
}

/**
 * Client stub that replays scripted responses and records every request.
 * Implements the full utopia-php/client Adapter so the Retry decorator can wrap it.
 */
final class ScriptedClient implements Adapter
{
    /**
     * @var array<RequestInterface>
     */
    public array $requests = [];

    /**
     * @param  array<ResponseInterface|\Psr\Http\Client\ClientExceptionInterface>  $responses
     */
    public function __construct(private array $responses) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $response = array_shift($this->responses);
        if ($response instanceof \Psr\Http\Client\ClientExceptionInterface) {
            throw $response;
        }
        if (! $response instanceof ResponseInterface) {
            throw new \RuntimeException('No scripted response left for ' . $request->getMethod() . ' ' . $request->getUri());
        }

        return $response;
    }

    public function stream(RequestInterface $request, callable $sink): ResponseInterface
    {
        $response = $this->sendRequest($request);
        $sink((string) $response->getBody());

        return $response;
    }

    public function withTimeout(float $seconds): static
    {
        return $this;
    }

    public function withConnectTimeout(float $seconds): static
    {
        return $this;
    }

    public function withSslVerification(bool $enabled = true): static
    {
        return $this;
    }

    public function withCustomCA(string $path): static
    {
        return $this;
    }

    public function withCertificate(string $certPath, string $keyPath, ?string $passphrase = null): static
    {
        return $this;
    }

    public function withMinTlsVersion(Tls $version): static
    {
        return $this;
    }

    public function withConnectionReuse(bool $enabled = true): static
    {
        return $this;
    }
}

final class S3Test extends TestCase
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

    public function testPrepareUploadCreatesMultipartMetadata(): void
    {
        $metadata = [];

        $this->s3->prepareUpload('/root/file.txt', 'text/plain', 2, $metadata);

        $this->assertSame('upload-123', $metadata['uploadId'] ?? null);
        $this->assertSame([], $metadata['parts'] ?? null);
        $this->assertSame(0, $metadata['chunks'] ?? null);
        $this->assertSame(['s3:createMultipartUpload'], $this->s3->calls);
    }

    public function testUploadChunkRecordsPartWithoutCompleting(): void
    {
        $metadata = [];

        $this->s3->prepareUpload('/root/file.txt', 'text/plain', 2, $metadata);
        $chunks = $this->s3->uploadChunk('aaa', '/root/file.txt', 1, 2, $metadata);

        $this->assertSame(1, $chunks);
        $this->assertSame('etag-1', ($metadata['parts'] ?? [])[1] ?? null);
        $this->assertNotContains('s3:completeMultipartUpload', $this->s3->calls);
    }

    public function testSingleChunkUploadDataDoesNotFinalizeOrCheckExists(): void
    {
        $metadata = [];

        $this->assertSame(1, $this->s3->uploadData('aaa', '/root/file.txt', 'text/plain', 1, 1, $metadata));
        $this->assertSame(['s3:write'], $this->s3->calls);
        $this->assertSame([1 => true], $metadata['parts'] ?? null);
        $this->assertSame(1, $metadata['chunks'] ?? null);
    }

    public function testFinalizeUploadRequiresAllS3Parts(): void
    {
        $metadata = [
            'uploadId' => 'upload-123',
            'parts' => [1 => 'etag-1'],
            'chunks' => 1,
        ];

        $this->expectException(UploadException::class);
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

    private function device(ScriptedClient $client): S3
    {
        return new S3(
            root: '/root',
            accessKey: 'test-key',
            secretKey: 'test-secret',
            host: 'https://s3.example.com',
            region: 'us-east-1',
            client: new Retry($client, new RetryStrategy(delay: 0.0)),
        );
    }

    private function slowDown(): Response
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>SlowDown</Code><Message>Please reduce your request rate.</Message></Error>';

        return new Response(503, body: new Stream($body))->withHeader('content-type', 'application/xml');
    }

    public function testWriteSendsSignedRequest(): void
    {
        $client = new ScriptedClient([new Response(200)]);

        $this->assertTrue($this->device($client)->write('/root/file.txt', 'Hello World', 'text/plain'));
        $this->assertCount(1, $client->requests);

        $request = $client->requests[0];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('s3.example.com', $request->getUri()->getHost());
        $this->assertSame('/root/file.txt', $request->getUri()->getPath());
        $this->assertSame('Hello World', (string) $request->getBody());
        $this->assertSame('text/plain', $request->getHeaderLine('content-type'));
        $this->assertSame('private', $request->getHeaderLine('x-amz-acl'));
        $this->assertSame(hash('sha256', 'Hello World'), $request->getHeaderLine('x-amz-content-sha256'));
        $this->assertSame(base64_encode(md5('Hello World', true)), $request->getHeaderLine('content-md5'));
        $this->assertStringStartsWith('AWS4-HMAC-SHA256 Credential=test-key/', $request->getHeaderLine('authorization'));
        $this->assertSame('utopia-php/storage', $request->getHeaderLine('user-agent'));
    }

    public function testTransientErrorIsRetriedUntilSuccess(): void
    {
        $client = new ScriptedClient([$this->slowDown(), $this->slowDown(), new Response(200)]);

        $this->assertTrue($this->device($client)->write('/root/file.txt', 'Hello World', 'text/plain'));
        $this->assertCount(3, $client->requests);
    }

    public function testTransientErrorRetriesAreExhausted(): void
    {
        $client = new ScriptedClient([$this->slowDown(), $this->slowDown(), $this->slowDown(), $this->slowDown()]);

        try {
            $this->device($client)->write('/root/file.txt', 'Hello World', 'text/plain');
            self::fail('Expected exception after exhausting retries');
        } catch (RemoteException $e) {
            $this->assertSame(503, $e->getCode());
            $this->assertSame('SlowDown', $e->errorCode);
        }

        // Initial attempt plus the default three retries.
        $this->assertCount(4, $client->requests);
    }

    public function testNoSuchKeyBecomesNotFoundException(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>NoSuchKey</Code><Message>The specified key does not exist.</Message></Error>';
        $client = new ScriptedClient([new Response(404, body: new Stream($body))]);

        $this->expectException(NotFoundException::class);
        $this->device($client)->read('/root/missing.txt');
    }

    public function testHeadNotFoundHasNoBodyButThrowsNotFound(): void
    {
        // HEAD error responses carry no body, so the 404 status is the only signal.
        $client = new ScriptedClient([new Response(404)]);

        $this->expectException(NotFoundException::class);
        $this->device($client)->getFileSize('/root/missing.txt');
    }

    public function testTransportFailureIsWrapped(): void
    {
        $psrError = new NetworkException(new Request('PUT', Uri::parse('https://s3.example.com/root')), 'Connection reset');
        $client = new ScriptedClient([$psrError]);

        try {
            $this->device($client)->write('/root/file.txt', 'Hello World', 'text/plain');
            self::fail('Expected transport exception');
        } catch (TransportException $e) {
            $this->assertSame($psrError, $e->getPrevious());
        }
    }

    public function testEveryFailureIsCatchableAsStorageException(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>AccessDenied</Code><Message>Access denied.</Message></Error>';
        $client = new ScriptedClient([new Response(403, body: new Stream($body))]);

        try {
            $this->device($client)->read('/root/file.txt');
            self::fail('Expected storage exception');
        } catch (StorageException $e) {
            $this->assertInstanceOf(RemoteException::class, $e);
            $this->assertSame('Access denied.', $e->getMessage());
            $this->assertSame(403, $e->getCode());
        }
    }

    public function testExistsReturnsFalseOnlyForNotFound(): void
    {
        $client = new ScriptedClient([new Response(404)]);

        $this->assertFalse($this->device($client)->exists('/root/missing.txt'));
    }

    public function testExistsPropagatesTransientErrors(): void
    {
        // One initial attempt plus the default three retries, all throttled.
        $client = new ScriptedClient([$this->slowDown(), $this->slowDown(), $this->slowDown(), $this->slowDown()]);

        $this->expectException(RemoteException::class);
        $this->device($client)->exists('/root/file.txt');
    }

    public function testErrorMessageIncludesAmzRequestIds(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><Error><Code>AccessDenied</Code><Message>Access denied.</Message></Error>';
        $response = new Response(403, body: new Stream($body))
            ->withHeader('x-amz-request-id', 'REQ123')
            ->withHeader('x-amz-id-2', 'HOST456');
        $client = new ScriptedClient([$response]);

        try {
            $this->device($client)->read('/root/file.txt');
            self::fail('Expected remote exception');
        } catch (RemoteException $e) {
            $this->assertStringContainsString('Access denied.', $e->getMessage());
            $this->assertStringContainsString('request-id: REQ123', $e->getMessage());
            $this->assertStringContainsString('id-2: HOST456', $e->getMessage());
        }
    }

    public function testTransferAbortsMultipartUploadOnFailure(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'utopia-storage-' . uniqid();
        mkdir($dir);
        $sourcePath = $dir . DIRECTORY_SEPARATOR . 'src.bin';
        file_put_contents($sourcePath, str_repeat('a', 30));

        $this->s3->failPart = 2;

        try {
            new Local($dir)->transfer($sourcePath, '/root/dest.bin', $this->s3, 10);
            self::fail('Expected the injected part failure to surface');
        } catch (RemoteException $e) {
            $this->assertSame('Injected part failure', $e->getMessage());
        } finally {
            unlink($sourcePath);
            rmdir($dir);
        }

        $this->assertContains('s3:createMultipartUpload', $this->s3->calls);
        $this->assertContains('s3:abort', $this->s3->calls);
        $this->assertNotContains('s3:completeMultipartUpload', $this->s3->calls);
    }

    public function testXmlListingIsDecodedIntoTypedFiles(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><ListBucketResult><KeyCount>2</KeyCount><IsTruncated>true</IsTruncated><MaxKeys>1000</MaxKeys><NextContinuationToken>next-token</NextContinuationToken>'
            . '<Contents><Key>root/a.txt</Key><Size>11</Size><LastModified>2026-01-02T03:04:05.000Z</LastModified><ETag>&quot;abc123&quot;</ETag></Contents>'
            . '<Contents><Key>root/b.txt</Key><Size>22</Size><LastModified>2026-01-02T03:04:06.000Z</LastModified><ETag>&quot;def456&quot;</ETag></Contents>'
            . '</ListBucketResult>';
        $client = new ScriptedClient([new Response(200, body: new Stream($body))->withHeader('content-type', 'application/xml')]);

        $list = $this->device($client)->listFiles('/root/testing');

        $this->assertCount(2, $list->files);
        $this->assertSame('root/a.txt', $list->files[0]->path);
        $this->assertSame(11, $list->files[0]->size);
        $this->assertSame('abc123', $list->files[0]->etag);
        $this->assertSame('2026-01-02', $list->files[0]->modifiedAt?->format('Y-m-d'));
        $this->assertSame('next-token', $list->cursor);
    }

    /** A lone element decodes as one associative entry rather than a list — the consumer must handle both. */
    public function testXmlListingWithSingleObjectIsDecoded(): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><ListBucketResult><KeyCount>1</KeyCount><IsTruncated>false</IsTruncated>'
            . '<Contents><Key>root/a.txt</Key><Size>11</Size><LastModified>2026-01-02T03:04:05.000Z</LastModified><ETag>&quot;abc123&quot;</ETag></Contents>'
            . '</ListBucketResult>';
        $client = new ScriptedClient([new Response(200, body: new Stream($body))->withHeader('content-type', 'application/xml')]);

        $list = $this->device($client)->listFiles('/root/testing');

        $this->assertCount(1, $list->files);
        $this->assertSame('root/a.txt', $list->files[0]->path);
        $this->assertSame(11, $list->files[0]->size);
        $this->assertNull($list->cursor);
    }
}

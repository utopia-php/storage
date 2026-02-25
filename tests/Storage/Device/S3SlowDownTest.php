<?php

namespace Utopia\Tests\Storage\Device;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\S3;

/**
 * Testable S3 subclass that exposes protected helpers.
 */
class TestableS3 extends S3
{
    public function exposedIsTransientError(int $statusCode, string $body): bool
    {
        return $this->isTransientError($statusCode, $body);
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
}

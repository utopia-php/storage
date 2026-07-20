<?php

declare(strict_types=1);

namespace Utopia\Storage\Device\S3;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Utopia\Client\Decorator\Retry\Strategy;

/**
 * Retry strategy for transient S3 rate-limiting errors (e.g. SlowDown,
 * ServiceUnavailable), for use with the `utopia-php/client` Retry decorator.
 *
 * The XML body is parsed first so that specific S3 error codes are detected
 * regardless of HTTP status: a 503/429 carrying a parseable but non-transient
 * error code is not retried, while unparseable 429/503 responses fall back to
 * status-code detection.
 * @see \Utopia\Tests\Storage\Device\S3\RetryStrategyTest
 */
final readonly class RetryStrategy implements Strategy
{
    /**
     * @var array<int, string>
     */
    private const array TRANSIENT_ERROR_CODES = ['SlowDown', 'ServiceUnavailable', 'Throttling', 'RequestThrottled'];

    /**
     * @var array<int, int>
     */
    private const array TRANSIENT_STATUS_CODES = [429, 503];

    /**
     * @param  int  $retries  Retries after the initial attempt
     * @param  float  $delay  Delay between attempts in seconds
     */
    public function __construct(
        private int $retries = 3,
        private float $delay = 0.5,
    ) {}

    public function delay(RequestInterface $request, int $attempt, ?ResponseInterface $response, ?ClientExceptionInterface $error): ?float
    {
        if ($attempt > $this->retries || ! $response instanceof ResponseInterface) {
            return null;
        }

        return $this->isTransient($response) ? $this->delay : null;
    }

    private function isTransient(ResponseInterface $response): bool
    {
        $body = (string) $response->getBody();

        $trimmed = ltrim($body);
        if (str_starts_with($trimmed, '<?xml') || str_starts_with($trimmed, '<Error')) {
            $xml = @simplexml_load_string($body);
            if ($xml !== false) {
                $code = (string) ($xml->Code ?? '');
                if (\in_array($code, self::TRANSIENT_ERROR_CODES, true)) {
                    return true;
                }
                // Successfully parsed XML with a non-transient error code — do not retry.
                if ($code !== '') {
                    return false;
                }
            }
        }

        // Fall back to HTTP status code for responses that cannot be parsed as XML.
        return \in_array($response->getStatusCode(), self::TRANSIENT_STATUS_CODES, true);
    }
}

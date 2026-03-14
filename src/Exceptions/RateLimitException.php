<?php

declare(strict_types=1);

namespace SnapAPI\Exceptions;

/**
 * Thrown when the API returns HTTP 429 Too Many Requests.
 *
 * Check {@see getRetryAfter()} for the number of seconds to wait.
 *
 * ```php
 * try {
 *     $img = $client->screenshot(['url' => 'https://example.com']);
 * } catch (\SnapAPI\Exceptions\RateLimitException $e) {
 *     sleep($e->getRetryAfter());
 *     $img = $client->screenshot(['url' => 'https://example.com']);
 * }
 * ```
 */
class RateLimitException extends SnapAPIException
{
    /**
     * @param array<mixed>|null $details
     */
    public function __construct(
        string $message = 'Rate limit exceeded.',
        private readonly int $retryAfter = 0,
        ?array $details = null,
    ) {
        parent::__construct($message, 'RATE_LIMITED', 429, $details);
    }

    /**
     * Number of seconds to wait before retrying (from the Retry-After header).
     * Returns 0 if the server did not provide a hint.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

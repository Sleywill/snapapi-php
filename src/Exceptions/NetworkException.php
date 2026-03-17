<?php

declare(strict_types=1);

namespace SnapAPI\Exceptions;

/**
 * Thrown when a network-level error prevents the request from completing.
 *
 * This covers cURL errors such as DNS failures, connection timeouts, and
 * SSL certificate problems.  The HTTP status code is 0 for these errors.
 *
 * ```php
 * try {
 *     $img = $client->screenshot(['url' => 'https://example.com']);
 * } catch (\SnapAPI\Exceptions\NetworkException $e) {
 *     echo 'Network error: ' . $e->getMessage();
 * }
 * ```
 */
class NetworkException extends SnapAPIException
{
    /**
     * @param array<mixed>|null $details
     */
    public function __construct(
        string $message = 'A network error occurred.',
        ?array $details = null,
    ) {
        parent::__construct($message, 'CONNECTION_ERROR', 0, $details);
    }
}

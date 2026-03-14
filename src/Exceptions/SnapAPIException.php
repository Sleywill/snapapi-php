<?php

declare(strict_types=1);

namespace SnapAPI\Exceptions;

/**
 * Base exception for all SnapAPI errors.
 *
 * Catch this class to handle any SnapAPI failure:
 *
 * ```php
 * try {
 *     $img = $client->screenshot(['url' => 'https://example.com']);
 * } catch (\SnapAPI\Exceptions\SnapAPIException $e) {
 *     echo $e->getErrorCode() . ': ' . $e->getMessage();
 * }
 * ```
 */
class SnapAPIException extends \RuntimeException
{
    /**
     * @param string       $message    Human-readable error description.
     * @param string       $errorCode  Machine-readable error code (e.g. "UNAUTHORIZED").
     * @param int          $statusCode HTTP status code (0 for network errors).
     * @param array<mixed>|null $details    Raw detail array returned by the API, if any.
     */
    public function __construct(
        string $message,
        private readonly string $errorCode = 'UNKNOWN_ERROR',
        private readonly int $statusCode = 0,
        private readonly ?array $details = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    /**
     * Machine-readable error code (e.g. INVALID_PARAMS, SERVER_ERROR).
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * HTTP status code from the server (0 for network-level errors).
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Additional structured details returned by the API, if any.
     *
     * @return array<mixed>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s', $this->errorCode, $this->getMessage());
    }
}

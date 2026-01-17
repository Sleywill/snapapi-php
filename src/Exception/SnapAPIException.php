<?php

declare(strict_types=1);

namespace SnapAPI\Exception;

/**
 * Exception thrown for SnapAPI errors.
 */
class SnapAPIException extends \Exception
{
    private string $errorCode;
    private int $statusCode;
    private ?array $details;

    /**
     * @param string $message Error message
     * @param string $code Error code
     * @param int $statusCode HTTP status code
     * @param array|null $details Additional error details
     */
    public function __construct(
        string $message,
        string $code = 'UNKNOWN_ERROR',
        int $statusCode = 500,
        ?array $details = null
    ) {
        parent::__construct($message);
        $this->errorCode = $code;
        $this->statusCode = $statusCode;
        $this->details = $details;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get additional error details.
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * String representation of the exception.
     */
    public function __toString(): string
    {
        return "[{$this->errorCode}] {$this->message}";
    }
}

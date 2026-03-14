<?php

declare(strict_types=1);

namespace SnapAPI\Exceptions;

/**
 * Thrown when the account's monthly API quota is exhausted.
 *
 * Corresponds to HTTP 402 or a QUOTA_EXCEEDED error code.
 */
class QuotaException extends SnapAPIException
{
    /**
     * @param array<mixed>|null $details
     */
    public function __construct(
        string $message = 'API quota exceeded.',
        int $statusCode = 402,
        ?array $details = null,
    ) {
        parent::__construct($message, 'QUOTA_EXCEEDED', $statusCode, $details);
    }
}

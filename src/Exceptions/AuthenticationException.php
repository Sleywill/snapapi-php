<?php

declare(strict_types=1);

namespace SnapAPI\Exceptions;

/**
 * Thrown when the API key is missing, invalid, or lacks permissions.
 *
 * Corresponds to HTTP 401 (Unauthorized) and 403 (Forbidden).
 */
class AuthenticationException extends SnapAPIException
{
    /**
     * @param array<mixed>|null $details
     */
    public function __construct(
        string $message = 'Authentication failed.',
        string $errorCode = 'UNAUTHORIZED',
        int $statusCode = 401,
        ?array $details = null,
    ) {
        parent::__construct($message, $errorCode, $statusCode, $details);
    }
}

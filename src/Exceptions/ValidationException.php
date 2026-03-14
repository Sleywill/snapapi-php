<?php

declare(strict_types=1);

namespace SnapAPI\Exceptions;

/**
 * Thrown when the server rejects a request due to invalid parameters (HTTP 400).
 */
class ValidationException extends SnapAPIException
{
    /**
     * @param array<mixed>|null $details
     */
    public function __construct(
        string $message = 'Validation failed.',
        ?array $details = null,
    ) {
        parent::__construct($message, 'INVALID_PARAMS', 400, $details);
    }
}

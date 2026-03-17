<?php

declare(strict_types=1);

namespace SnapAPI\Exceptions;

/**
 * Alias for {@see QuotaException}.
 *
 * Provided for naming consistency with the rest of the exception hierarchy
 * (all other exceptions end in "Exception").
 *
 * Corresponds to HTTP 402 or a QUOTA_EXCEEDED error code.
 */
class QuotaExceededException extends QuotaException
{
}

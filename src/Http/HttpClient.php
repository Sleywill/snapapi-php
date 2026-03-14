<?php

declare(strict_types=1);

namespace SnapAPI\Http;

use SnapAPI\Exceptions\AuthenticationException;
use SnapAPI\Exceptions\QuotaException;
use SnapAPI\Exceptions\RateLimitException;
use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;

/**
 * Low-level HTTP transport for the SnapAPI client.
 *
 * Handles request building, error parsing, and exponential-backoff retry.
 *
 * @internal Not part of the public API. Use {@see \SnapAPI\Client} instead.
 */
final class HttpClient
{
    private const USER_AGENT = 'snapapi-php/3.0.0';

    /**
     * @param string $baseUrl  API base URL (no trailing slash).
     * @param string $apiKey   Bearer token.
     * @param int    $timeout  cURL timeout in seconds.
     * @param int    $retries  Maximum number of retries on transient errors.
     * @param int    $retryDelayMs  Base delay in milliseconds for exponential back-off.
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 30,
        private readonly int $retries = 3,
        private readonly int $retryDelayMs = 500,
    ) {
    }

    /**
     * Perform a GET request and return the response body as a string.
     *
     * @throws SnapAPIException
     */
    public function get(string $path): string
    {
        return $this->send('GET', $path, null);
    }

    /**
     * Perform a POST request with a JSON body and return the response body.
     *
     * @param array<string, mixed> $data
     * @throws SnapAPIException
     */
    public function post(string $path, array $data = []): string
    {
        return $this->send('POST', $path, $data);
    }

    /**
     * Perform a DELETE request.
     *
     * @throws SnapAPIException
     */
    public function delete(string $path): string
    {
        return $this->send('DELETE', $path, null);
    }

    /**
     * Execute the request with automatic retry on transient errors.
     *
     * @param array<string, mixed>|null $data
     * @throws SnapAPIException
     */
    private function send(string $method, string $path, ?array $data): string
    {
        $lastException = null;
        $delay = $this->retryDelayMs;

        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            if ($attempt > 0) {
                usleep($delay * 1000);
                $delay = (int) ($delay * 2);
            }

            try {
                return $this->roundTrip($method, $path, $data);
            } catch (RateLimitException $e) {
                $lastException = $e;
                // Honour the Retry-After header if provided.
                if ($e->getRetryAfter() > 0 && $attempt < $this->retries) {
                    sleep($e->getRetryAfter());
                }
                // RateLimitException is retryable.
                continue;
            } catch (SnapAPIException $e) {
                $lastException = $e;
                if (!$this->isRetryable($e) || $attempt >= $this->retries) {
                    throw $e;
                }
                // Retryable server error — loop continues.
            }
        }

        // Should only be reached when all retries are exhausted.
        if ($lastException !== null) {
            throw $lastException;
        }

        throw new SnapAPIException('Unknown error after retries.', 'UNKNOWN_ERROR', 0);
    }

    /**
     * Execute a single HTTP round-trip using cURL.
     *
     * @param array<string, mixed>|null $data
     * @throws SnapAPIException
     */
    private function roundTrip(string $method, string $path, ?array $data): string
    {
        $ch = curl_init($this->baseUrl . $path);
        if ($ch === false) {
            throw new SnapAPIException('Failed to initialise cURL.', 'CONNECTION_ERROR', 0);
        }

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: ' . self::USER_AGENT,
            'Accept: */*',
        ];

        $curlOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => true, // include response headers in output
        ];

        if ($method === 'POST') {
            $curlOpts[CURLOPT_POST]       = true;
            $curlOpts[CURLOPT_POSTFIELDS] = $data !== null ? (string) json_encode($data) : '{}';
        } elseif ($method === 'DELETE') {
            $curlOpts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        curl_setopt_array($ch, $curlOpts);

        $raw       = (string) curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($curlError !== '') {
            throw new SnapAPIException('cURL error: ' . $curlError, 'CONNECTION_ERROR', 0);
        }

        $responseHeaders = substr($raw, 0, $headerSize);
        $body            = substr($raw, $headerSize);

        if ($httpCode >= 400) {
            $this->throwFromResponse($body, $httpCode, $responseHeaders);
        }

        return $body;
    }

    /**
     * Reports whether an exception should trigger a retry.
     */
    private function isRetryable(SnapAPIException $e): bool
    {
        return $e instanceof RateLimitException || $e->getStatusCode() >= 500;
    }

    /**
     * Parse an error response body and throw the appropriate exception type.
     *
     * @throws SnapAPIException
     */
    private function throwFromResponse(string $body, int $httpCode, string $headers): never
    {
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($body, true);
        $message = is_array($decoded) ? (string) ($decoded['message'] ?? "HTTP {$httpCode}") : "HTTP {$httpCode}";
        $rawCode = is_array($decoded) ? (string) ($decoded['error'] ?? '') : '';
        $details = is_array($decoded) && isset($decoded['details']) && is_array($decoded['details'])
            ? $decoded['details']
            : null;

        // Parse Retry-After header (seconds only).
        $retryAfter = 0;
        if (preg_match('/^Retry-After:\s*(\d+)/im', $headers, $m)) {
            $retryAfter = (int) $m[1];
        }

        $errorCode = $this->mapErrorCode($rawCode, $httpCode);

        match (true) {
            $httpCode === 429                    => throw new RateLimitException($message, $retryAfter, $details),
            in_array($httpCode, [401, 403], true) => throw new AuthenticationException($message, $errorCode, $httpCode, $details),
            $httpCode === 402 || $errorCode === 'QUOTA_EXCEEDED' => throw new QuotaException($message, $httpCode, $details),
            $httpCode === 400                    => throw new ValidationException($message, $details),
            default                              => throw new SnapAPIException($message, $errorCode, $httpCode, $details),
        };
    }

    /**
     * Map a raw error string and HTTP status code to an error code constant.
     */
    private function mapErrorCode(string $rawCode, int $httpCode): string
    {
        return match ($httpCode) {
            401     => 'UNAUTHORIZED',
            403     => 'FORBIDDEN',
            429     => 'RATE_LIMITED',
            400     => 'INVALID_PARAMS',
            default => match (strtoupper(str_replace(' ', '_', $rawCode))) {
                'VALIDATION_ERROR', 'INVALID_PARAMS' => 'INVALID_PARAMS',
                'UNAUTHORIZED'                       => 'UNAUTHORIZED',
                'FORBIDDEN'                          => 'FORBIDDEN',
                'RATE_LIMITED'                       => 'RATE_LIMITED',
                'QUOTA_EXCEEDED'                     => 'QUOTA_EXCEEDED',
                'TIMEOUT'                            => 'TIMEOUT',
                'CAPTURE_FAILED'                     => 'CAPTURE_FAILED',
                default                              => $httpCode >= 500 ? 'SERVER_ERROR' : ($rawCode ?: 'HTTP_ERROR'),
            },
        };
    }
}

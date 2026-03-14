<?php

declare(strict_types=1);

namespace SnapAPI;

use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;
use SnapAPI\Http\HttpClient;

/**
 * SnapAPI PHP SDK v3 — Official client.
 *
 * Supports: Screenshot, PDF, Scrape, Extract, Video, Quota.
 *
 * ```php
 * $client = new \SnapAPI\Client('sk_...');
 *
 * $png = $client->screenshot(['url' => 'https://example.com', 'format' => 'png']);
 * file_put_contents('screenshot.png', $png);
 *
 * $quota = $client->quota();
 * echo "Used: {$quota['used']}/{$quota['total']}";
 * ```
 */
class Client
{
    private readonly HttpClient $http;

    /**
     * Create a new SnapAPI client.
     *
     * @param string $apiKey Your SnapAPI key.
     * @param array{
     *     baseUrl?: string,
     *     timeout?: int,
     *     retries?: int,
     *     retryDelayMs?: int,
     * } $options
     *
     * @throws \InvalidArgumentException if $apiKey is empty.
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('API key must not be empty.');
        }

        $this->http = new HttpClient(
            baseUrl: rtrim($options['baseUrl'] ?? 'https://snapapi.pics', '/'),
            apiKey: $apiKey,
            timeout: $options['timeout'] ?? 30,
            retries: $options['retries'] ?? 3,
            retryDelayMs: $options['retryDelayMs'] ?? 500,
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Screenshot  POST /v1/screenshot
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Capture a screenshot of a URL.
     *
     * Returns raw binary image bytes (PNG or JPEG).
     *
     * @param array{
     *   url: string,
     *   format?: string,
     *   width?: int,
     *   height?: int,
     *   full_page?: bool,
     *   wait?: int,
     *   delay?: int,
     *   quality?: int,
     *   selector?: string,
     * } $options
     *
     * @return string Raw image bytes.
     * @throws SnapAPIException
     */
    public function screenshot(array $options): string
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->http->post('/v1/screenshot', $options);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Scrape  POST /v1/scrape
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Scrape content from a URL.
     *
     * @param array{
     *   url: string,
     *   selector?: string,
     *   wait?: int,
     * } $options
     *
     * @return array{success: bool, url: string, html?: string, text?: string}
     * @throws SnapAPIException
     */
    public function scrape(array $options): array
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->decodeJson($this->http->post('/v1/scrape', $options));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Extract  POST /v1/extract
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Extract LLM-ready content from a URL.
     *
     * @param array{
     *   url: string,
     *   format?: string,
     *   wait?: int,
     * } $options  format: "markdown" (default), "text", or "json"
     *
     * @return array{success: bool, url: string, format: string, content: string, responseTime: int}
     * @throws SnapAPIException
     */
    public function extract(array $options): array
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->decodeJson($this->http->post('/v1/extract', $options));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PDF  POST /v1/pdf
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generate a PDF of a URL.
     *
     * @param array{
     *   url: string,
     *   format?: string,
     *   margin?: string,
     * } $options  format: "a4" (default) or "letter"
     *
     * @return string Raw PDF bytes.
     * @throws SnapAPIException
     */
    public function pdf(array $options): string
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->http->post('/v1/pdf', $options);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Video  POST /v1/video
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Record a short video of a URL.
     *
     * @param array{
     *   url: string,
     *   duration?: int,
     *   format?: string,
     *   width?: int,
     *   height?: int,
     * } $options  format: "webm" (default), "mp4", or "gif"
     *
     * @return string Raw video bytes.
     * @throws SnapAPIException
     */
    public function video(array $options): string
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->http->post('/v1/video', $options);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Quota  GET /v1/quota
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return the caller's current API quota usage.
     *
     * @return array{used: int, total: int, remaining: int, resetAt?: string}
     * @throws SnapAPIException
     */
    public function quota(): array
    {
        return $this->decodeJson($this->http->get('/v1/quota'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Decode a JSON response body into an array.
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    private function decodeJson(string $body): array
    {
        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new SnapAPIException('Unexpected non-JSON response.', 'PARSE_ERROR', 0);
        }
        return $decoded;
    }
}

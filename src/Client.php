<?php

declare(strict_types=1);

namespace SnapAPI;

use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;
use SnapAPI\Http\HttpClient;

/**
 * SnapAPI PHP SDK v2.1.0 -- Official client.
 *
 * Supports: Screenshot, PDF, Scrape, Extract, Analyze, Video, Usage.
 *
 * ```php
 * $client = new \SnapAPI\Client('sk_live_...');
 *
 * $png = $client->screenshot(['url' => 'https://example.com', 'format' => 'png']);
 * file_put_contents('screenshot.png', $png);
 *
 * $usage = $client->getUsage();
 * echo "Used: {$usage['used']}/{$usage['total']}";
 * ```
 */
class Client
{
    private readonly HttpClient $http;

    /**
     * Create a new SnapAPI client.
     *
     * @param string $apiKey Your SnapAPI key (e.g. "sk_live_...").
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
            baseUrl: rtrim($options['baseUrl'] ?? 'https://api.snapapi.pics', '/'),
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
     * Returns raw binary image bytes (PNG, JPEG, WebP, or PDF).
     *
     * @param array{
     *   url: string,
     *   format?: string,
     *   width?: int,
     *   height?: int,
     *   full_page?: bool,
     *   delay?: int,
     *   quality?: int,
     *   scale?: float,
     *   block_ads?: bool,
     *   wait_for_selector?: string,
     *   clip?: array{x: int, y: int, w: int, h: int},
     *   scroll_y?: int,
     *   custom_css?: string,
     *   custom_js?: string,
     *   headers?: array<string, string>,
     *   user_agent?: string,
     *   proxy?: string,
     *   access_key?: string,
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

    /**
     * Capture a screenshot and save it directly to a file.
     *
     * @param string $filename Path to write the file.
     * @param array<string, mixed> $options Same as screenshot().
     *
     * @return int Number of bytes written.
     * @throws SnapAPIException
     */
    public function screenshotToFile(string $filename, array $options): int
    {
        $data = $this->screenshot($options);
        $bytes = file_put_contents($filename, $data);
        if ($bytes === false) {
            throw new SnapAPIException("Failed to write file: {$filename}", 'FILE_ERROR', 0);
        }
        return $bytes;
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
     *   format?: string,
     *   wait_for_selector?: string,
     *   headers?: array<string, string>,
     *   proxy?: string,
     *   access_key?: string,
     * } $options  format: "html" (default), "text", or "json"
     *
     * @return array{data: string, url: string, status: int}
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
     *   include_links?: bool,
     *   include_images?: bool,
     *   selector?: string,
     *   wait_for_selector?: string,
     *   headers?: array<string, string>,
     *   proxy?: string,
     *   access_key?: string,
     * } $options  format: "markdown" (default), "text", or "json"
     *
     * @return array{content: string, url: string, word_count: int}
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
    // Analyze  POST /v1/analyze
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Analyze a web page using an LLM provider.
     *
     * Note: This endpoint may return HTTP 503 if LLM credits are exhausted.
     *
     * @param array{
     *   url: string,
     *   prompt?: string,
     *   provider?: string,
     *   apiKey?: string,
     *   jsonSchema?: array<string, mixed>,
     * } $options  provider: "openai", "anthropic", or "google"
     *
     * @return array{result: string, url: string}
     * @throws SnapAPIException
     */
    public function analyze(array $options): array
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->decodeJson($this->http->post('/v1/analyze', $options));
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
    // Usage  GET /v1/usage
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return the caller's current API usage statistics.
     *
     * @return array{used: int, total: int, remaining: int, resetAt?: string}
     * @throws SnapAPIException
     */
    public function getUsage(): array
    {
        return $this->decodeJson($this->http->get('/v1/usage'));
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

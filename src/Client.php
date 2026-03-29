<?php

declare(strict_types=1);

namespace SnapAPI;

use SnapAPI\ApiKeys\ApiKeysClient;
use SnapAPI\Exceptions\NetworkException;
use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;
use SnapAPI\Http\HttpClient;
use SnapAPI\Scheduled\ScheduledClient;
use SnapAPI\Storage\StorageClient;
use SnapAPI\Webhooks\WebhooksClient;

/**
 * SnapAPI PHP SDK v3.2.0 -- Official client.
 *
 * Supports: Screenshot, PDF, Scrape, Extract, Analyze, Video, OG Image, Usage.
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
     * @param string $apiKey  Your SnapAPI key (e.g. "sk_live_...").
     * @param array{
     *     baseUrl?: string,
     *     timeout?: int,
     *     retries?: int,
     *     retryDelayMs?: int,
     *     transport?: callable,
     * } $options
     *
     * @throws \InvalidArgumentException if $apiKey is empty.
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('API key must not be empty.');
        }

        /** @var (callable(string, string, ?string, array<int, string>): array{int, string, string})|null $transport */
        $transport = $options['transport'] ?? null;

        $this->http = new HttpClient(
            baseUrl: rtrim($options['baseUrl'] ?? 'https://api.snapapi.pics', '/'),
            apiKey: $apiKey,
            timeout: $options['timeout'] ?? 30,
            retries: $options['retries'] ?? 3,
            retryDelayMs: $options['retryDelayMs'] ?? 500,
            transport: $transport,
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Screenshot  POST /v1/screenshot
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Capture a screenshot of a URL.
     *
     * Returns raw binary image bytes (PNG, JPEG, or WebP).
     *
     * @param array<string, mixed> $options {
     *   url: string,             -- required
     *   format?: string,         -- "png" | "jpeg" | "webp"  (default "png")
     *   width?: int,             -- viewport width in pixels
     *   height?: int,            -- viewport height in pixels
     *   full_page?: bool,        -- capture entire scrollable page
     *   delay?: int,             -- ms to wait after page load
     *   quality?: int,           -- JPEG/WebP quality 1-100
     *   scale?: float,           -- device scale factor (retina)
     *   block_ads?: bool,        -- enable ad blocking
     *   block_cookies?: bool,    -- block cookie consent banners
     *   dark_mode?: bool,        -- enable prefers-color-scheme: dark
     *   wait_for_selector?: string,
     *   clip?: array{x: int, y: int, w: int, h: int},
     *   scroll_y?: int,
     *   custom_css?: string,
     *   custom_js?: string,
     *   headers?: array<string, string>,
     *   user_agent?: string,
     *   proxy?: string,
     *   selector?: string,
     * }
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
     * @param string               $filename Path to write the file.
     * @param array<string, mixed> $options  Same options as screenshot().
     *
     * @return int Number of bytes written.
     * @throws SnapAPIException
     */
    public function screenshotToFile(string $filename, array $options): int
    {
        $data  = $this->screenshot($options);
        $bytes = file_put_contents($filename, $data);
        if ($bytes === false) {
            throw new NetworkException("Failed to write file: {$filename}");
        }
        return $bytes;
    }

    /**
     * Capture a screenshot and store it in SnapAPI-managed cloud storage.
     *
     * Returns metadata including the public URL and storage key.
     *
     * @param array<string, mixed> $options {
     *   url: string,                -- required
     *   format?: string,            -- "png" | "jpeg" | "webp"
     *   storage_key?: string,       -- custom key/path in storage
     *   storage_bucket?: string,    -- override default bucket
     *   ...                         -- all screenshot() options accepted
     * }
     *
     * @return array<string, mixed> { url: string, key: string, bucket: string, size: int, content_type: string, created_at: string }
     * @throws SnapAPIException
     */
    public function screenshotToStorage(array $options): array
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->decodeJson($this->http->post('/v1/screenshot/storage', $options));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Scrape  POST /v1/scrape
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Scrape content from a URL.
     *
     * Response shape: { data: string, url: string, status: int }
     *
     * @param array<string, mixed> $options {
     *   url: string,                -- required
     *   selector?: string,
     *   selectors?: array<string, string>, -- named multi-element selectors
     *   format?: string,            -- "html" (default) | "text" | "json"
     *   waitFor?: string,           -- CSS selector or timeout to wait for
     *   wait_for_selector?: string,
     *   headers?: array<string, string>,
     *   proxy?: string,
     * }
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function scrape(array $options): array
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->decodeJson($this->http->post('/v1/scrape', $options));
    }

    /**
     * Convenience wrapper: scrape a URL and return only the plain-text content.
     *
     * @param string $url The URL to scrape.
     * @return string Plain-text content.
     * @throws SnapAPIException
     */
    public function scrapeText(string $url): string
    {
        $result = $this->scrape(['url' => $url, 'format' => 'text']);
        return (string) ($result['data'] ?? '');
    }

    /**
     * Convenience wrapper: scrape a URL and return only the raw HTML.
     *
     * @param string $url The URL to scrape.
     * @return string Raw HTML content.
     * @throws SnapAPIException
     */
    public function scrapeHtml(string $url): string
    {
        $result = $this->scrape(['url' => $url, 'format' => 'html']);
        return (string) ($result['data'] ?? '');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Extract  POST /v1/extract
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Extract LLM-ready content from a URL.
     *
     * Response shape: { content: string, url: string, word_count: int }
     *
     * @param array<string, mixed> $options {
     *   url: string,                -- required
     *   format?: string,            -- "markdown" (default) | "text" | "json"
     *   include_links?: bool,
     *   include_images?: bool,
     *   selector?: string,
     *   wait_for_selector?: string,
     *   headers?: array<string, string>,
     *   proxy?: string,
     * }
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function extract(array $options): array
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        return $this->decodeJson($this->http->post('/v1/extract', $options));
    }

    /**
     * Convenience wrapper: extract content as Markdown and return just the string.
     *
     * @param string $url The URL to extract from.
     * @return string Markdown content.
     * @throws SnapAPIException
     */
    public function extractMarkdown(string $url): string
    {
        $result = $this->extract(['url' => $url, 'format' => 'markdown']);
        return (string) ($result['content'] ?? '');
    }

    /**
     * Convenience wrapper: extract content as plain text and return just the string.
     *
     * @param string $url The URL to extract from.
     * @return string Plain-text content.
     * @throws SnapAPIException
     */
    public function extractText(string $url): string
    {
        $result = $this->extract(['url' => $url, 'format' => 'text']);
        return (string) ($result['content'] ?? '');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Analyze  POST /v1/analyze
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Analyze a web page using an LLM provider.
     *
     * Note: This endpoint may return HTTP 503 if LLM credits are exhausted.
     *
     * Response shape: { result: string, url: string }
     *
     * @param array<string, mixed> $options {
     *   url: string,                        -- required
     *   prompt?: string,
     *   provider?: string,                  -- "openai" | "anthropic" | "google"
     *   apiKey?: string,                    -- your LLM provider key
     *   jsonSchema?: array<string, mixed>,
     * }
     *
     * @return array<string, mixed>
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
     * @param array<string, mixed> $options {
     *   url: string,       -- required
     *   format?: string,   -- "a4" (default) | "letter"
     *   margin?: string,   -- e.g. "10mm"
     * }
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

    /**
     * Generate a PDF and save it directly to a file.
     *
     * @param string               $filename Path to write the file.
     * @param array<string, mixed> $options  Same options as pdf().
     *
     * @return int Number of bytes written.
     * @throws SnapAPIException
     */
    public function pdfToFile(string $filename, array $options): int
    {
        $data  = $this->pdf($options);
        $bytes = file_put_contents($filename, $data);
        if ($bytes === false) {
            throw new NetworkException("Failed to write file: {$filename}");
        }
        return $bytes;
    }

    /**
     * Alias for pdf().
     *
     * @param array<string, mixed> $options Same options as pdf().
     * @return string Raw PDF bytes.
     * @throws SnapAPIException
     */
    public function generatePdf(array $options): string
    {
        return $this->pdf($options);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Video  POST /v1/video
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Record a short video of a URL.
     *
     * @param array<string, mixed> $options {
     *   url: string,           -- required
     *   duration?: int,        -- seconds (default 5)
     *   format?: string,       -- "webm" (default) | "mp4" | "gif"
     *   width?: int,
     *   height?: int,
     *   scrollVideo?: bool,    -- scroll-based video recording
     * }
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
    // OG Image  POST /v1/screenshot  (1200×630 preset)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Generate an Open Graph social image for a URL.
     *
     * Calls the screenshot endpoint with standard OG dimensions (1200×630)
     * unless overridden by $options.
     *
     * @param array<string, mixed> $options Same options as screenshot().
     *                                       url is required.
     *
     * @return string Raw image bytes.
     * @throws SnapAPIException
     */
    public function ogImage(array $options): string
    {
        if (empty($options['url'])) {
            throw new ValidationException('url is required.');
        }
        $params = array_merge(['width' => 1200, 'height' => 630], $options);
        return $this->http->post('/v1/screenshot', $params);
    }

    /**
     * Alias for ogImage().
     *
     * @param array<string, mixed> $options Same options as ogImage().
     * @return string Raw image bytes.
     * @throws SnapAPIException
     */
    public function generateOgImage(array $options): string
    {
        return $this->ogImage($options);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Ping  GET /v1/ping
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Check API health.
     *
     * Response shape: { status: string, timestamp: int }
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function ping(): array
    {
        return $this->decodeJson($this->http->get('/v1/ping'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Usage  GET /v1/usage
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Return the caller's current API usage statistics.
     *
     * Response shape: { used: int, total: int, remaining: int, resetAt?: string }
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function getUsage(): array
    {
        return $this->decodeJson($this->http->get('/v1/usage'));
    }

    /**
     * Alias for getUsage().
     *
     * @return array<string, mixed>
     * @throws SnapAPIException
     */
    public function quota(): array
    {
        return $this->getUsage();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sub-clients
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Access the Storage sub-client for managing stored captures.
     *
     * ```php
     * $files = $client->storage()->list();
     * ```
     */
    public function storage(): StorageClient
    {
        return new StorageClient($this->http);
    }

    /**
     * Access the Scheduled sub-client for recurring capture jobs.
     *
     * ```php
     * $client->scheduled()->create([
     *     'url'      => 'https://example.com',
     *     'type'     => 'screenshot',
     *     'schedule' => '0 9 * * *',
     * ]);
     * ```
     */
    public function scheduled(): ScheduledClient
    {
        return new ScheduledClient($this->http);
    }

    /**
     * Access the Webhooks sub-client for managing event delivery endpoints.
     *
     * ```php
     * $client->webhooks()->create([
     *     'url'    => 'https://myapp.com/hooks/snapapi',
     *     'events' => ['screenshot.completed'],
     * ]);
     * ```
     */
    public function webhooks(): WebhooksClient
    {
        return new WebhooksClient($this->http);
    }

    /**
     * Access the API Keys sub-client for managing additional keys.
     *
     * ```php
     * $key = $client->apiKeys()->create(['name' => 'ci-key']);
     * ```
     */
    public function apiKeys(): ApiKeysClient
    {
        return new ApiKeysClient($this->http);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal helpers
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

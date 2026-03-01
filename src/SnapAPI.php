<?php

declare(strict_types=1);

namespace SnapAPI;

use SnapAPI\Exception\SnapAPIException;

/**
 * SnapAPI v2 — Official PHP SDK
 *
 * Supports: Screenshot, PDF, Scrape, Extract, Analyze,
 *           Storage, Scheduled jobs, Webhooks, API Keys.
 *
 * @example
 * $api = new \SnapAPI\SnapAPI('your-api-key');
 * $png = $api->screenshot(['url' => 'https://example.com', 'format' => 'png']);
 * file_put_contents('screenshot.png', $png);
 */
class SnapAPI
{
    private const BASE_URL   = 'https://api.snapapi.pics';
    private const USER_AGENT = 'snapapi-php/2.0.0';
    private const TIMEOUT    = 90;

    private string $apiKey;
    private string $baseUrl;
    private int    $timeout;

    /**
     * Create a SnapAPI client.
     *
     * @param string $apiKey   Your SnapAPI key (x-api-key header).
     * @param array{baseUrl?: string, timeout?: int} $options
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('API key must not be empty.');
        }
        $this->apiKey  = $apiKey;
        $this->baseUrl = rtrim($options['baseUrl'] ?? self::BASE_URL, '/');
        $this->timeout = $options['timeout'] ?? self::TIMEOUT;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Screenshot  POST /v1/screenshot
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Capture a screenshot of a URL or HTML/Markdown source.
     *
     * Returns raw binary image bytes (PNG/JPEG/WEBP/AVIF) or PDF bytes.
     * When `storage` is set in $options the response will be a JSON array
     * containing `id` and `url`.
     *
     * @param array{
     *   url?: string,
     *   html?: string,
     *   markdown?: string,
     *   format?: string,
     *   quality?: int,
     *   width?: int,
     *   height?: int,
     *   device?: string,
     *   fullPage?: bool,
     *   selector?: string,
     *   delay?: int,
     *   timeout?: int,
     *   waitUntil?: string,
     *   waitForSelector?: string,
     *   darkMode?: bool,
     *   css?: string,
     *   javascript?: string,
     *   hideSelectors?: array<string>,
     *   clickSelector?: string,
     *   blockAds?: bool,
     *   blockTrackers?: bool,
     *   blockCookieBanners?: bool,
     *   userAgent?: string,
     *   extraHeaders?: array<string,string>,
     *   cookies?: array,
     *   httpAuth?: array{username: string, password: string},
     *   proxy?: string,
     *   premiumProxy?: bool,
     *   geolocation?: array{latitude: float, longitude: float, accuracy?: float},
     *   timezone?: string,
     *   pdf?: array{pageSize?: string, landscape?: bool, marginTop?: string, marginRight?: string, marginBottom?: string, marginLeft?: string},
     *   storage?: array{destination?: string, format?: string},
     *   webhookUrl?: string
     * } $options
     * @return string Binary image/PDF bytes, or JSON string when storage is used.
     * @throws SnapAPIException
     */
    public function screenshot(array $options): string
    {
        if (empty($options['url']) && empty($options['html']) && empty($options['markdown'])) {
            throw new \InvalidArgumentException('One of url, html, or markdown is required.');
        }
        return $this->request('POST', '/v1/screenshot', $options);
    }

    /**
     * Generate a PDF.  Shorthand for screenshot() with format=pdf.
     *
     * @param array $options Same options as screenshot(); format is forced to "pdf".
     * @return string Binary PDF bytes.
     * @throws SnapAPIException
     */
    public function pdf(array $options): string
    {
        if (empty($options['url']) && empty($options['html']) && empty($options['markdown'])) {
            throw new \InvalidArgumentException('One of url, html, or markdown is required.');
        }
        $options['format'] = 'pdf';
        return $this->request('POST', '/v1/screenshot', $options);
    }

    /**
     * Convenience: capture a screenshot from an HTML string.
     *
     * @param string $html    HTML content.
     * @param array  $options Additional screenshot options.
     * @return string Binary image bytes.
     * @throws SnapAPIException
     */
    public function screenshotFromHtml(string $html, array $options = []): string
    {
        $options['html'] = $html;
        return $this->screenshot($options);
    }

    /**
     * Convenience: capture a screenshot from a Markdown string.
     *
     * @param string $markdown Markdown content.
     * @param array  $options  Additional screenshot options.
     * @return string Binary image bytes.
     * @throws SnapAPIException
     */
    public function screenshotFromMarkdown(string $markdown, array $options = []): string
    {
        $options['markdown'] = $markdown;
        return $this->screenshot($options);
    }

    /**
     * Capture a screenshot and store it (returns storage metadata as array).
     *
     * @param array $options Screenshot options including storage configuration.
     * @return array{id: string, url: string}
     * @throws SnapAPIException
     */
    public function screenshotToStorage(array $options): array
    {
        $raw = $this->screenshot($options);
        return json_decode($raw, true);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Scrape  POST /v1/scrape
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Scrape text, HTML, or links from a URL (up to 10 pages).
     *
     * @param array{
     *   url: string,
     *   type?: string,
     *   pages?: int,
     *   waitMs?: int,
     *   proxy?: string,
     *   premiumProxy?: bool,
     *   blockResources?: bool,
     *   locale?: string
     * } $options
     * @return array{success: bool, results: list<array{page: int, url: string, data: string}>}
     * @throws SnapAPIException
     */
    public function scrape(array $options): array
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required.');
        }
        return $this->requestJson('POST', '/v1/scrape', $options);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Extract  POST /v1/extract
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Extract content from a webpage.
     *
     * @param array{
     *   url: string,
     *   type?: string,
     *   selector?: string,
     *   waitFor?: string,
     *   timeout?: int,
     *   darkMode?: bool,
     *   blockAds?: bool,
     *   blockCookieBanners?: bool,
     *   includeImages?: bool,
     *   maxLength?: int
     * } $options  type: html|text|markdown|article|links|images|metadata|structured
     * @return array{success: bool, type: string, url: string, data: mixed, responseTime: int}
     * @throws SnapAPIException
     */
    public function extract(array $options): array
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required.');
        }
        return $this->requestJson('POST', '/v1/extract', $options);
    }

    /** @throws SnapAPIException */
    public function extractMarkdown(string $url): array
    {
        return $this->extract(['url' => $url, 'type' => 'markdown']);
    }

    /** @throws SnapAPIException */
    public function extractArticle(string $url): array
    {
        return $this->extract(['url' => $url, 'type' => 'article']);
    }

    /** @throws SnapAPIException */
    public function extractText(string $url): array
    {
        return $this->extract(['url' => $url, 'type' => 'text']);
    }

    /** @throws SnapAPIException */
    public function extractLinks(string $url): array
    {
        return $this->extract(['url' => $url, 'type' => 'links']);
    }

    /** @throws SnapAPIException */
    public function extractImages(string $url): array
    {
        return $this->extract(['url' => $url, 'type' => 'images']);
    }

    /** @throws SnapAPIException */
    public function extractMetadata(string $url): array
    {
        return $this->extract(['url' => $url, 'type' => 'metadata']);
    }

    /** @throws SnapAPIException */
    public function extractStructured(string $url): array
    {
        return $this->extract(['url' => $url, 'type' => 'structured']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Analyze  POST /v1/analyze
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Perform AI-powered analysis of a webpage.
     *
     * @param array{
     *   url: string,
     *   prompt?: string,
     *   provider?: string,
     *   apiKey?: string,
     *   model?: string,
     *   jsonSchema?: string,
     *   includeScreenshot?: bool,
     *   includeMetadata?: bool,
     *   maxContentLength?: int
     * } $options  provider: openai|anthropic
     * @return array{success: bool, url: string, metadata: array, analysis: mixed, provider: string, model: string, responseTime: int}
     * @throws SnapAPIException
     */
    public function analyze(array $options): array
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required.');
        }
        return $this->requestJson('POST', '/v1/analyze', $options);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Storage  /v1/storage/*
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * List all stored files.
     *
     * @return array{success: bool, files: list<array>, total: int}
     * @throws SnapAPIException
     */
    public function listStorageFiles(): array
    {
        return $this->requestJson('GET', '/v1/storage/files');
    }

    /**
     * Delete a stored file by ID.
     *
     * @throws SnapAPIException
     */
    public function deleteStorageFile(string $id): void
    {
        $this->request('DELETE', "/v1/storage/files/{$id}");
    }

    /**
     * Get storage usage statistics.
     *
     * @return array{success: bool, used: int, limit: int}
     * @throws SnapAPIException
     */
    public function getStorageUsage(): array
    {
        return $this->requestJson('GET', '/v1/storage/usage');
    }

    /**
     * Configure an S3-compatible storage backend.
     *
     * @param array{
     *   bucket: string,
     *   region: string,
     *   accessKeyId: string,
     *   secretAccessKey: string,
     *   endpoint?: string,
     *   publicUrl?: string
     * } $config
     * @throws SnapAPIException
     */
    public function configureS3(array $config): void
    {
        $this->request('POST', '/v1/storage/s3', $config);
    }

    /**
     * Test the configured S3 connection.
     *
     * @throws SnapAPIException
     */
    public function testS3(): void
    {
        $this->request('POST', '/v1/storage/s3/test');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Scheduled  /v1/scheduled/*
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Create a scheduled screenshot job.
     *
     * @param array{
     *   url: string,
     *   cronExpression: string,
     *   format?: string,
     *   width?: int,
     *   height?: int,
     *   fullPage?: bool,
     *   webhookUrl?: string
     * } $options
     * @return array Scheduled job info (id, url, cronExpression, …)
     * @throws SnapAPIException
     */
    public function createScheduled(array $options): array
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required.');
        }
        if (empty($options['cronExpression'])) {
            throw new \InvalidArgumentException('cronExpression is required.');
        }
        return $this->requestJson('POST', '/v1/scheduled', $options);
    }

    /**
     * List all scheduled jobs.
     *
     * @return array{success: bool, jobs: list<array>}
     * @throws SnapAPIException
     */
    public function listScheduled(): array
    {
        return $this->requestJson('GET', '/v1/scheduled');
    }

    /**
     * Delete a scheduled job by ID.
     *
     * @throws SnapAPIException
     */
    public function deleteScheduled(string $id): void
    {
        $this->request('DELETE', "/v1/scheduled/{$id}");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Webhooks  /v1/webhooks/*
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Register a new webhook.
     *
     * @param array{
     *   url: string,
     *   events: list<string>,
     *   secret?: string
     * } $options
     * @return array Webhook info (id, url, events, active, createdAt)
     * @throws SnapAPIException
     */
    public function createWebhook(array $options): array
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('url is required.');
        }
        return $this->requestJson('POST', '/v1/webhooks', $options);
    }

    /**
     * List all registered webhooks.
     *
     * @return array{success: bool, webhooks: list<array>}
     * @throws SnapAPIException
     */
    public function listWebhooks(): array
    {
        return $this->requestJson('GET', '/v1/webhooks');
    }

    /**
     * Delete a webhook by ID.
     *
     * @throws SnapAPIException
     */
    public function deleteWebhook(string $id): void
    {
        $this->request('DELETE', "/v1/webhooks/{$id}");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // API Keys  /v1/keys/*
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * List all API keys.
     *
     * @return array{success: bool, keys: list<array>}
     * @throws SnapAPIException
     */
    public function listKeys(): array
    {
        return $this->requestJson('GET', '/v1/keys');
    }

    /**
     * Create a new API key.
     *
     * @param string $name A descriptive name for the key.
     * @return array Key info including the secret `key` value (only returned once).
     * @throws SnapAPIException
     */
    public function createKey(string $name): array
    {
        return $this->requestJson('POST', '/v1/keys', ['name' => $name]);
    }

    /**
     * Revoke an API key by ID.
     *
     * @throws SnapAPIException
     */
    public function deleteKey(string $id): void
    {
        $this->request('DELETE', "/v1/keys/{$id}");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HTTP internals
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Send an HTTP request and return the raw response body.
     *
     * @param string     $method GET|POST|DELETE
     * @param string     $path   API path (e.g. "/v1/screenshot")
     * @param array|null $data   Request body (JSON-encoded for POST)
     * @return string Raw response body
     * @throws SnapAPIException
     */
    private function request(string $method, string $path, ?array $data = null): string
    {
        $ch = curl_init($this->baseUrl . $path);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
                'User-Agent: ' . self::USER_AGENT,
                'Accept: */*',
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data !== null ? json_encode($data) : '{}');
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $body      = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new SnapAPIException("cURL error: {$curlError}", 'CONNECTION_ERROR', 0);
        }

        if ($httpCode >= 400) {
            $this->throwFromResponse((string) $body, $httpCode);
        }

        return (string) $body;
    }

    /**
     * Like request() but JSON-decodes the response.
     *
     * @throws SnapAPIException
     */
    private function requestJson(string $method, string $path, ?array $data = null): array
    {
        $raw    = $this->request($method, $path, $data);
        $result = json_decode($raw, true);
        if (!is_array($result)) {
            throw new SnapAPIException('Unexpected non-JSON response.', 'PARSE_ERROR', 0);
        }
        return $result;
    }

    /**
     * Parse and throw a SnapAPIException from an error response body.
     *
     * @throws SnapAPIException
     */
    private function throwFromResponse(string $body, int $httpCode): void
    {
        $decoded = json_decode($body, true);
        $message = $decoded['message'] ?? "HTTP {$httpCode}";
        $code    = $decoded['error']   ?? 'HTTP_ERROR';

        if (is_string($code)) {
            $code = strtoupper(str_replace(' ', '_', $code));
        }

        throw new SnapAPIException($message, $code, $httpCode, $decoded['details'] ?? null);
    }
}

<?php

declare(strict_types=1);

namespace SnapAPI;

use SnapAPI\Exception\SnapAPIException;

/**
 * SnapAPI Client for PHP
 *
 * @example
 * $client = new \SnapAPI\Client('sk_live_xxx');
 * $screenshot = $client->screenshot(['url' => 'https://example.com']);
 * file_put_contents('screenshot.png', $screenshot);
 */
class Client
{
    private const DEFAULT_BASE_URL = 'https://api.snapapi.pics';
    private const DEFAULT_TIMEOUT = 60;
    private const USER_AGENT = 'snapapi-php/1.2.0';

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    /**
     * Create a new SnapAPI client.
     *
     * @param string $apiKey Your SnapAPI API key
     * @param array{baseUrl?: string, timeout?: int} $options Client options
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($options['baseUrl'] ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
    }

    /**
     * Capture a screenshot of the specified URL.
     *
     * @param array{
     *     url?: string,
     *     html?: string,
     *     markdown?: string,
     *     format?: string,
     *     quality?: int,
     *     device?: string,
     *     width?: int,
     *     height?: int,
     *     deviceScaleFactor?: float,
     *     isMobile?: bool,
     *     hasTouch?: bool,
     *     isLandscape?: bool,
     *     fullPage?: bool,
     *     fullPageScrollDelay?: int,
     *     fullPageMaxHeight?: int,
     *     selector?: string,
     *     selectorScrollIntoView?: bool,
     *     clipX?: int,
     *     clipY?: int,
     *     clipWidth?: int,
     *     clipHeight?: int,
     *     delay?: int,
     *     timeout?: int,
     *     waitUntil?: string,
     *     waitForSelector?: string,
     *     waitForSelectorTimeout?: int,
     *     darkMode?: bool,
     *     reducedMotion?: bool,
     *     css?: string,
     *     javascript?: string,
     *     hideSelectors?: array,
     *     clickSelector?: string,
     *     clickDelay?: int,
     *     blockAds?: bool,
     *     blockTrackers?: bool,
     *     blockCookieBanners?: bool,
     *     blockChatWidgets?: bool,
     *     blockResources?: array,
     *     userAgent?: string,
     *     extraHeaders?: array,
     *     cookies?: array,
     *     httpAuth?: array,
     *     proxy?: array,
     *     geolocation?: array,
     *     timezone?: string,
     *     locale?: string,
     *     pdfOptions?: array,
     *     thumbnail?: array,
     *     failOnHttpError?: bool,
     *     cache?: bool,
     *     cacheTtl?: int,
     *     responseType?: string,
     *     includeMetadata?: bool,
     *     extractMetadata?: array,
     *     failIfContentMissing?: array<string>,
     *     failIfContentContains?: array<string>
     * } $options Screenshot options
     * @return string|array Binary image data or array if responseType is 'json'
     * @throws SnapAPIException
     */
    public function screenshot(array $options)
    {
        if (empty($options['url']) && empty($options['html']) && empty($options['markdown'])) {
            throw new \InvalidArgumentException('Either URL, HTML, or Markdown is required');
        }

        $responseType = $options['responseType'] ?? 'binary';
        $response = $this->request('POST', '/v1/screenshot', $options);

        if ($responseType === 'json' || $responseType === 'base64') {
            return json_decode($response, true);
        }

        return $response;
    }

    /**
     * Capture a screenshot from HTML content.
     *
     * @param string $html HTML content to render
     * @param array $options Additional screenshot options
     * @return string|array Binary image data or array if responseType is 'json'
     * @throws SnapAPIException
     */
    public function screenshotFromHtml(string $html, array $options = [])
    {
        $options['html'] = $html;
        return $this->screenshot($options);
    }

    /**
     * Capture a screenshot using a device preset.
     *
     * @param string $url URL to capture
     * @param string $device Device preset name (e.g., 'iphone-15-pro', 'ipad-pro-12.9')
     * @param array $options Additional screenshot options
     * @return string|array Binary image data or array if responseType is 'json'
     * @throws SnapAPIException
     */
    public function screenshotDevice(string $url, string $device, array $options = [])
    {
        $options['url'] = $url;
        $options['device'] = $device;
        return $this->screenshot($options);
    }

    /**
     * Generate a PDF from a URL.
     *
     * @param array{
     *     url?: string,
     *     html?: string,
     *     pdfOptions?: array{
     *         pageSize?: string,
     *         width?: string,
     *         height?: string,
     *         landscape?: bool,
     *         marginTop?: string,
     *         marginRight?: string,
     *         marginBottom?: string,
     *         marginLeft?: string,
     *         printBackground?: bool,
     *         headerTemplate?: string,
     *         footerTemplate?: string,
     *         displayHeaderFooter?: bool,
     *         scale?: float,
     *         pageRanges?: string,
     *         preferCSSPageSize?: bool
     *     },
     *     timeout?: int,
     *     waitUntil?: string,
     *     cookies?: array,
     *     headers?: array,
     *     httpAuth?: array
     * } $options PDF options
     * @return string Binary PDF data
     * @throws SnapAPIException
     */
    public function pdf(array $options): string
    {
        if (empty($options['url']) && empty($options['html'])) {
            throw new \InvalidArgumentException('Either URL or HTML is required');
        }

        $options['format'] = 'pdf';
        $options['responseType'] = $options['responseType'] ?? 'binary';

        return $this->request('POST', '/v1/pdf', $options);
    }

    /**
     * Capture a video of a webpage with optional scroll animation.
     *
     * @param array{
     *     url: string,
     *     format?: string,
     *     quality?: int,
     *     width?: int,
     *     height?: int,
     *     device?: string,
     *     duration?: int,
     *     fps?: int,
     *     delay?: int,
     *     timeout?: int,
     *     waitUntil?: string,
     *     waitForSelector?: string,
     *     darkMode?: bool,
     *     blockAds?: bool,
     *     blockCookieBanners?: bool,
     *     css?: string,
     *     javascript?: string,
     *     hideSelectors?: array,
     *     userAgent?: string,
     *     cookies?: array,
     *     responseType?: string,
     *     scroll?: bool,
     *     scrollDelay?: int,
     *     scrollDuration?: int,
     *     scrollBy?: int,
     *     scrollEasing?: string,
     *     scrollBack?: bool,
     *     scrollComplete?: bool
     * } $options Video options
     * @return string|array Binary video data or array if responseType is 'json'
     * @throws SnapAPIException
     */
    public function video(array $options)
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('URL is required');
        }

        $responseType = $options['responseType'] ?? 'binary';
        $response = $this->request('POST', '/v1/video', $options);

        if ($responseType === 'json' || $responseType === 'base64') {
            return json_decode($response, true);
        }

        return $response;
    }

    /**
     * Capture screenshots of multiple URLs.
     *
     * @param array{
     *     urls: array<string>,
     *     format?: string,
     *     quality?: int,
     *     width?: int,
     *     height?: int,
     *     fullPage?: bool,
     *     webhookUrl?: string,
     *     darkMode?: bool,
     *     blockAds?: bool,
     *     blockCookieBanners?: bool
     * } $options Batch options
     * @return array Batch job result
     * @throws SnapAPIException
     */
    public function batch(array $options): array
    {
        if (empty($options['urls']) || !is_array($options['urls'])) {
            throw new \InvalidArgumentException('URLs array is required');
        }

        $response = $this->request('POST', '/v1/screenshot/batch', $options);
        return json_decode($response, true);
    }

    /**
     * Check the status of a batch job.
     *
     * @param string $jobId The batch job ID
     * @return array Batch job status and results
     * @throws SnapAPIException
     */
    public function getBatchStatus(string $jobId): array
    {
        $response = $this->request('GET', "/v1/screenshot/batch/{$jobId}");
        return json_decode($response, true);
    }

    /**
     * Get available device presets.
     *
     * @return array Device presets grouped by category
     * @throws SnapAPIException
     */
    public function getDevices(): array
    {
        $response = $this->request('GET', '/v1/devices');
        return json_decode($response, true);
    }

    /**
     * Get API capabilities and features.
     *
     * @return array API capabilities
     * @throws SnapAPIException
     */
    public function getCapabilities(): array
    {
        $response = $this->request('GET', '/v1/capabilities');
        return json_decode($response, true);
    }

    /**
     * Get your API usage statistics.
     *
     * @return array Usage info (used, limit, remaining, resetAt)
     * @throws SnapAPIException
     */
    public function getUsage(): array
    {
        $response = $this->request('GET', '/v1/usage');
        return json_decode($response, true);
    }

    /**
     * Extract content from a webpage.
     *
     * @param array{
     *     url: string,
     *     format?: string,
     *     selector?: string,
     *     timeout?: int,
     *     waitUntil?: string,
     *     waitForSelector?: string,
     *     blockAds?: bool,
     *     blockTrackers?: bool,
     *     blockCookieBanners?: bool,
     *     cookies?: array,
     *     httpAuth?: array,
     *     proxy?: array,
     *     userAgent?: string,
     *     extraHeaders?: array
     * } $options Extract options
     * @return array Extracted content
     * @throws SnapAPIException
     */
    public function extract(array $options): array
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('URL is required');
        }

        $response = $this->request('POST', '/v1/extract', $options);
        return json_decode($response, true);
    }

    /**
     * Extract content from a URL as Markdown.
     *
     * @param string $url URL to extract from
     * @return array Extracted content
     * @throws SnapAPIException
     */
    public function extractMarkdown(string $url): array
    {
        return $this->extract(['url' => $url, 'format' => 'markdown']);
    }

    /**
     * Extract article content from a URL.
     *
     * @param string $url URL to extract from
     * @return array Extracted content
     * @throws SnapAPIException
     */
    public function extractArticle(string $url): array
    {
        return $this->extract(['url' => $url, 'format' => 'article']);
    }

    /**
     * Extract structured data from a URL.
     *
     * @param string $url URL to extract from
     * @return array Extracted content
     * @throws SnapAPIException
     */
    public function extractStructured(string $url): array
    {
        return $this->extract(['url' => $url, 'format' => 'structured']);
    }

    /**
     * Extract plain text from a URL.
     *
     * @param string $url URL to extract from
     * @return array Extracted content
     * @throws SnapAPIException
     */
    public function extractText(string $url): array
    {
        return $this->extract(['url' => $url, 'format' => 'text']);
    }

    /**
     * Extract all links from a URL.
     *
     * @param string $url URL to extract from
     * @return array Extracted links
     * @throws SnapAPIException
     */
    public function extractLinks(string $url): array
    {
        return $this->extract(['url' => $url, 'format' => 'links']);
    }

    /**
     * Extract all images from a URL.
     *
     * @param string $url URL to extract from
     * @return array Extracted images
     * @throws SnapAPIException
     */
    public function extractImages(string $url): array
    {
        return $this->extract(['url' => $url, 'format' => 'images']);
    }

    /**
     * Extract metadata from a URL.
     *
     * @param string $url URL to extract from
     * @return array Extracted metadata
     * @throws SnapAPIException
     */
    public function extractMetadata(string $url): array
    {
        return $this->extract(['url' => $url, 'format' => 'metadata']);
    }

    /**
     * Analyze a webpage using AI vision.
     *
     * @param array{
     *     url: string,
     *     prompt?: string,
     *     format?: string,
     *     width?: int,
     *     height?: int,
     *     device?: string,
     *     fullPage?: bool,
     *     delay?: int,
     *     timeout?: int,
     *     waitUntil?: string,
     *     waitForSelector?: string,
     *     darkMode?: bool,
     *     blockAds?: bool,
     *     blockCookieBanners?: bool,
     *     blockChatWidgets?: bool,
     *     css?: string,
     *     javascript?: string,
     *     hideSelectors?: array,
     *     cookies?: array,
     *     httpAuth?: array,
     *     proxy?: array,
     *     userAgent?: string,
     *     extraHeaders?: array
     * } $options Analyze options
     * @return array Analysis result
     * @throws SnapAPIException
     */
    public function analyze(array $options): array
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('URL is required');
        }

        $response = $this->request('POST', '/v1/analyze', $options);
        return json_decode($response, true);
    }

    /**
     * Capture a screenshot from Markdown content.
     *
     * @param string $markdown Markdown content to render
     * @param array $options Additional screenshot options
     * @return string|array Binary image data or array if responseType is 'json'
     * @throws SnapAPIException
     */
    public function screenshotFromMarkdown(string $markdown, array $options = [])
    {
        $options['markdown'] = $markdown;
        return $this->screenshot($options);
    }

    /**
     * Make an HTTP request to the API.
     *
     * @param string $method HTTP method
     * @param string $path API path
     * @param array|null $data Request body data
     * @return string Response body
     * @throws SnapAPIException
     */
    private function request(string $method, string $path, ?array $data = null): string
    {
        $url = $this->baseUrl . $path;

        $ch = curl_init();

        $headers = [
            'X-Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: ' . self::USER_AGENT,
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new SnapAPIException("Connection error: {$error}", 'CONNECTION_ERROR', 0);
        }

        if ($httpCode >= 400) {
            $this->handleError($response, $httpCode);
        }

        return $response;
    }

    /**
     * Handle error response from API.
     *
     * @param string $response Response body
     * @param int $httpCode HTTP status code
     * @throws SnapAPIException
     */
    private function handleError(string $response, int $httpCode): void
    {
        $body = json_decode($response, true);
        $error = $body['error'] ?? [];

        throw new SnapAPIException(
            $error['message'] ?? "HTTP {$httpCode}",
            $error['code'] ?? 'HTTP_ERROR',
            $httpCode,
            $error['details'] ?? null
        );
    }
}

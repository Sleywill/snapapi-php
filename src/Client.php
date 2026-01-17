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
    private const DEFAULT_BASE_URL = 'https://api.snapapi.dev';
    private const DEFAULT_TIMEOUT = 60;
    private const USER_AGENT = 'snapapi-php/1.0.0';

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
     *     url: string,
     *     format?: string,
     *     width?: int,
     *     height?: int,
     *     fullPage?: bool,
     *     quality?: int,
     *     scale?: float,
     *     delay?: int,
     *     timeout?: int,
     *     darkMode?: bool,
     *     mobile?: bool,
     *     selector?: string,
     *     waitForSelector?: string,
     *     javascript?: string,
     *     blockAds?: bool,
     *     hideCookieBanners?: bool,
     *     cookies?: array,
     *     headers?: array,
     *     responseType?: string
     * } $options Screenshot options
     * @return string|array Binary image data or array if responseType is 'json'
     * @throws SnapAPIException
     */
    public function screenshot(array $options)
    {
        if (empty($options['url'])) {
            throw new \InvalidArgumentException('URL is required');
        }

        $responseType = $options['responseType'] ?? 'binary';
        $response = $this->request('POST', '/v1/screenshot', $options);

        if ($responseType === 'json') {
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
     *     width?: int,
     *     height?: int,
     *     fullPage?: bool,
     *     webhookUrl?: string,
     *     darkMode?: bool,
     *     blockAds?: bool
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

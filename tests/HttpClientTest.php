<?php

declare(strict_types=1);

namespace SnapAPI\Tests;

use PHPUnit\Framework\TestCase;
use SnapAPI\Client;
use SnapAPI\Exceptions\AuthenticationException;
use SnapAPI\Exceptions\QuotaException;
use SnapAPI\Exceptions\RateLimitException;
use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;

/**
 * HTTP-level tests for the SnapAPI PHP SDK.
 *
 * These tests use an injected transport callback to simulate API responses
 * without any network access.
 */
class HttpClientTest extends TestCase
{
    /**
     * Build a Client with a fake transport that returns the given response.
     *
     * @param int    $statusCode HTTP status code.
     * @param string $body       Response body (JSON string or raw bytes).
     * @param string $headers    Response headers string.
     * @param callable|null $interceptor Optional callback to inspect the request.
     */
    private function makeClient(
        int $statusCode,
        string $body,
        string $headers = '',
        ?callable $interceptor = null,
    ): Client {
        $transport = function (
            string $method,
            string $url,
            ?string $requestBody,
            array $requestHeaders,
        ) use ($statusCode, $body, $headers, $interceptor): array {
            if ($interceptor !== null) {
                $interceptor($method, $url, $requestBody, $requestHeaders);
            }
            return [$statusCode, $headers, $body];
        };

        return new Client('sk_test_key', [
            'transport'    => $transport,
            'retries'      => 0,
            'retryDelayMs' => 1,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Screenshot
    // ──────────────────────────────────────────────────────────────────────────

    public function testScreenshotReturnsRawBytes(): void
    {
        $fakeImage = "\x89PNG fake image bytes";
        $client = $this->makeClient(200, $fakeImage);

        $result = $client->screenshot(['url' => 'https://example.com', 'format' => 'png']);
        $this->assertSame($fakeImage, $result);
    }

    public function testScreenshotSendsCorrectHeaders(): void
    {
        $receivedHeaders = [];
        $client = $this->makeClient(200, 'ok', '', function (
            string $method,
            string $url,
            ?string $body,
            array $headers,
        ) use (&$receivedHeaders): void {
            $receivedHeaders = $headers;
        });

        $client->screenshot(['url' => 'https://example.com']);

        $headerString = implode("\n", $receivedHeaders);
        $this->assertStringContainsString('X-Api-Key: sk_test_key', $headerString);
        $this->assertStringContainsString('Authorization: Bearer sk_test_key', $headerString);
        $this->assertStringContainsString('Content-Type: application/json', $headerString);
    }

    public function testScreenshotSendsCorrectBody(): void
    {
        $receivedBody = '';
        $client = $this->makeClient(200, 'ok', '', function (
            string $method,
            string $url,
            ?string $body,
        ) use (&$receivedBody): void {
            $receivedBody = $body ?? '';
        });

        $client->screenshot([
            'url'       => 'https://example.com',
            'format'    => 'png',
            'full_page' => true,
            'width'     => 1920,
        ]);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($receivedBody, true);
        $this->assertSame('https://example.com', $decoded['url']);
        $this->assertSame('png', $decoded['format']);
        $this->assertTrue($decoded['full_page']);
        $this->assertSame(1920, $decoded['width']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Scrape
    // ──────────────────────────────────────────────────────────────────────────

    public function testScrapeReturnsDecodedJson(): void
    {
        $json = json_encode(['data' => '<html>Hello</html>', 'url' => 'https://example.com', 'status' => 200]);
        $client = $this->makeClient(200, (string) $json);

        $result = $client->scrape(['url' => 'https://example.com']);
        $this->assertSame('<html>Hello</html>', $result['data']);
        $this->assertSame(200, $result['status']);
    }

    public function testScrapeTextConvenience(): void
    {
        $json = json_encode(['data' => 'Hello world', 'url' => 'https://example.com', 'status' => 200]);
        $client = $this->makeClient(200, (string) $json);

        $text = $client->scrapeText('https://example.com');
        $this->assertSame('Hello world', $text);
    }

    public function testScrapeHtmlConvenience(): void
    {
        $json = json_encode(['data' => '<html><body>Hello</body></html>', 'url' => 'https://example.com', 'status' => 200]);
        $client = $this->makeClient(200, (string) $json);

        $html = $client->scrapeHtml('https://example.com');
        $this->assertStringContainsString('<html>', $html);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Extract
    // ──────────────────────────────────────────────────────────────────────────

    public function testExtractReturnsDecodedJson(): void
    {
        $json = json_encode(['content' => '# Hello', 'url' => 'https://example.com', 'word_count' => 1]);
        $client = $this->makeClient(200, (string) $json);

        $result = $client->extract(['url' => 'https://example.com', 'format' => 'markdown']);
        $this->assertSame('# Hello', $result['content']);
        $this->assertSame(1, $result['word_count']);
    }

    public function testExtractMarkdownConvenience(): void
    {
        $json = json_encode(['content' => '# Title', 'url' => 'https://example.com', 'word_count' => 1]);
        $client = $this->makeClient(200, (string) $json);

        $md = $client->extractMarkdown('https://example.com');
        $this->assertSame('# Title', $md);
    }

    public function testExtractTextConvenience(): void
    {
        $json = json_encode(['content' => 'Title Body', 'url' => 'https://example.com', 'word_count' => 2]);
        $client = $this->makeClient(200, (string) $json);

        $text = $client->extractText('https://example.com');
        $this->assertSame('Title Body', $text);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Analyze
    // ──────────────────────────────────────────────────────────────────────────

    public function testAnalyzeReturnsDecodedJson(): void
    {
        $json = json_encode(['result' => 'This is a test page.', 'url' => 'https://example.com']);
        $client = $this->makeClient(200, (string) $json);

        $result = $client->analyze(['url' => 'https://example.com', 'prompt' => 'Summarize']);
        $this->assertSame('This is a test page.', $result['result']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PDF
    // ──────────────────────────────────────────────────────────────────────────

    public function testPdfReturnsRawBytes(): void
    {
        $fakePdf = '%PDF-1.4 fake content';
        $client = $this->makeClient(200, $fakePdf);

        $result = $client->pdf(['url' => 'https://example.com']);
        $this->assertStringStartsWith('%PDF', $result);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Video
    // ──────────────────────────────────────────────────────────────────────────

    public function testVideoReturnsRawBytes(): void
    {
        $fakeVideo = 'fake-video-data';
        $client = $this->makeClient(200, $fakeVideo);

        $result = $client->video(['url' => 'https://example.com', 'duration' => 5]);
        $this->assertSame('fake-video-data', $result);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // OG Image
    // ──────────────────────────────────────────────────────────────────────────

    public function testOgImageSendsCorrectDimensions(): void
    {
        $receivedBody = '';
        $client = $this->makeClient(200, 'fake-og', '', function (
            string $method,
            string $url,
            ?string $body,
        ) use (&$receivedBody): void {
            $receivedBody = $body ?? '';
        });

        $client->ogImage(['url' => 'https://example.com']);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($receivedBody, true);
        $this->assertSame(1200, $decoded['width']);
        $this->assertSame(630, $decoded['height']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Screenshot to Storage
    // ──────────────────────────────────────────────────────────────────────────

    public function testScreenshotToStorageReturnsMetadata(): void
    {
        $json = json_encode([
            'url'          => 'https://storage.snapapi.pics/reports/home.png',
            'key'          => 'reports/home.png',
            'bucket'       => 'snapapi-captures',
            'size'         => 45678,
            'content_type' => 'image/png',
            'created_at'   => '2026-03-17T10:00:00Z',
        ]);
        $client = $this->makeClient(200, (string) $json);

        $result = $client->screenshotToStorage(['url' => 'https://example.com', 'format' => 'png']);
        $this->assertSame('reports/home.png', $result['key']);
        $this->assertSame(45678, $result['size']);
    }

    public function testScreenshotToStorageThrowsWhenUrlMissing(): void
    {
        $client = $this->makeClient(200, '{}');
        $this->expectException(ValidationException::class);
        $client->screenshotToStorage([]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Ping / Usage
    // ──────────────────────────────────────────────────────────────────────────

    public function testPingReturnsDecodedJson(): void
    {
        $json = json_encode(['status' => 'ok', 'timestamp' => 1710540000000]);
        $receivedMethod = '';
        $client = $this->makeClient(200, (string) $json, '', function (string $method) use (&$receivedMethod): void {
            $receivedMethod = $method;
        });

        $result = $client->ping();
        $this->assertSame('ok', $result['status']);
        $this->assertSame('GET', $receivedMethod);
    }

    public function testGetUsageReturnsDecodedJson(): void
    {
        $json = json_encode(['used' => 42, 'total' => 1000, 'remaining' => 958]);
        $client = $this->makeClient(200, (string) $json);

        $result = $client->getUsage();
        $this->assertSame(42, $result['used']);
        $this->assertSame(1000, $result['total']);
    }

    public function testQuotaIsAliasForGetUsage(): void
    {
        $json = json_encode(['used' => 5, 'total' => 100, 'remaining' => 95]);
        $client = $this->makeClient(200, (string) $json);

        $result = $client->quota();
        $this->assertSame(5, $result['used']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Error handling
    // ──────────────────────────────────────────────────────────────────────────

    public function testUnauthorizedThrowsAuthenticationException(): void
    {
        $json = json_encode(['statusCode' => 401, 'error' => 'Unauthorized', 'message' => 'Invalid API key']);
        $client = $this->makeClient(401, (string) $json);

        $this->expectException(AuthenticationException::class);
        $client->screenshot(['url' => 'https://example.com']);
    }

    public function testForbiddenThrowsAuthenticationException(): void
    {
        $json = json_encode(['statusCode' => 403, 'error' => 'Forbidden', 'message' => 'No access']);
        $client = $this->makeClient(403, (string) $json);

        $this->expectException(AuthenticationException::class);
        $client->screenshot(['url' => 'https://example.com']);
    }

    public function testRateLimitThrowsRateLimitException(): void
    {
        $json = json_encode(['statusCode' => 429, 'error' => 'Rate Limited', 'message' => 'Too many requests']);
        $client = $this->makeClient(429, (string) $json, "Retry-After: 30\r\n");

        try {
            $client->screenshot(['url' => 'https://example.com']);
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(30, $e->getRetryAfter());
            $this->assertSame(429, $e->getStatusCode());
        }
    }

    public function testQuotaExceededThrowsQuotaException(): void
    {
        $json = json_encode(['statusCode' => 402, 'error' => 'Payment Required', 'message' => 'Quota exceeded']);
        $client = $this->makeClient(402, (string) $json);

        $this->expectException(QuotaException::class);
        $client->screenshot(['url' => 'https://example.com']);
    }

    public function testBadRequestThrowsValidationException(): void
    {
        $json = json_encode(['statusCode' => 400, 'error' => 'Bad Request', 'message' => 'Invalid params']);
        $client = $this->makeClient(400, (string) $json);

        $this->expectException(ValidationException::class);
        $client->screenshot(['url' => 'https://example.com']);
    }

    public function testServerErrorThrowsSnapAPIException(): void
    {
        $json = json_encode(['statusCode' => 500, 'error' => 'Internal Server Error', 'message' => 'Something broke']);
        $client = $this->makeClient(500, (string) $json);

        try {
            $client->screenshot(['url' => 'https://example.com']);
            $this->fail('Expected SnapAPIException');
        } catch (SnapAPIException $e) {
            $this->assertSame(500, $e->getStatusCode());
            $this->assertSame('SERVER_ERROR', $e->getErrorCode());
        }
    }

    public function testServiceUnavailableThrowsSnapAPIException(): void
    {
        $json = json_encode(['statusCode' => 503, 'error' => 'Service Unavailable', 'message' => 'LLM credits exhausted']);
        $client = $this->makeClient(503, (string) $json);

        try {
            $client->analyze(['url' => 'https://example.com']);
            $this->fail('Expected SnapAPIException');
        } catch (SnapAPIException $e) {
            $this->assertSame(503, $e->getStatusCode());
            $this->assertSame('SERVICE_UNAVAILABLE', $e->getErrorCode());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Retry behavior
    // ──────────────────────────────────────────────────────────────────────────

    public function testRetryEventualSuccess(): void
    {
        $calls = 0;
        $transport = function () use (&$calls): array {
            $calls++;
            if ($calls < 3) {
                $body = json_encode(['statusCode' => 500, 'error' => 'Server Error', 'message' => 'temporary']);
                return [500, '', (string) $body];
            }
            return [200, '', "\x89PNG fake"];
        };

        $client = new Client('sk_test_key', [
            'transport'    => $transport,
            'retries'      => 3,
            'retryDelayMs' => 1,
        ]);

        $result = $client->screenshot(['url' => 'https://example.com']);
        $this->assertSame("\x89PNG fake", $result);
        $this->assertSame(3, $calls);
    }

    public function testRetryExhausted(): void
    {
        $calls = 0;
        $transport = function () use (&$calls): array {
            $calls++;
            $body = json_encode(['statusCode' => 500, 'error' => 'Server Error', 'message' => 'always failing']);
            return [500, '', (string) $body];
        };

        $client = new Client('sk_test_key', [
            'transport'    => $transport,
            'retries'      => 2,
            'retryDelayMs' => 1,
        ]);

        try {
            $client->screenshot(['url' => 'https://example.com']);
            $this->fail('Expected SnapAPIException');
        } catch (SnapAPIException $e) {
            $this->assertSame(500, $e->getStatusCode());
        }
        // 1 initial + 2 retries = 3 calls
        $this->assertSame(3, $calls);
    }

    public function testNoRetryOn4xx(): void
    {
        $calls = 0;
        $transport = function () use (&$calls): array {
            $calls++;
            $body = json_encode(['statusCode' => 400, 'error' => 'Bad Request', 'message' => 'invalid params']);
            return [400, '', (string) $body];
        };

        $client = new Client('sk_test_key', [
            'transport'    => $transport,
            'retries'      => 3,
            'retryDelayMs' => 1,
        ]);

        try {
            $client->screenshot(['url' => 'https://example.com']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            // expected
        }
        // 4xx should NOT be retried
        $this->assertSame(1, $calls);
    }

    public function testRetryOnRateLimit(): void
    {
        $calls = 0;
        $transport = function () use (&$calls): array {
            $calls++;
            if ($calls < 2) {
                $body = json_encode(['statusCode' => 429, 'error' => 'Rate Limited', 'message' => 'Too many requests']);
                return [429, "Retry-After: 0\r\n", (string) $body];
            }
            return [200, '', "\x89PNG ok"];
        };

        $client = new Client('sk_test_key', [
            'transport'    => $transport,
            'retries'      => 3,
            'retryDelayMs' => 1,
        ]);

        $result = $client->screenshot(['url' => 'https://example.com']);
        $this->assertSame("\x89PNG ok", $result);
        $this->assertSame(2, $calls);
    }
}

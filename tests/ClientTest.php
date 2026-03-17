<?php

declare(strict_types=1);

namespace SnapAPI\Tests;

use PHPUnit\Framework\TestCase;
use SnapAPI\Client;
use SnapAPI\Exceptions\AuthenticationException;
use SnapAPI\Exceptions\NetworkException;
use SnapAPI\Exceptions\QuotaException;
use SnapAPI\Exceptions\QuotaExceededException;
use SnapAPI\Exceptions\RateLimitException;
use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;

/**
 * Unit tests for the SnapAPI PHP SDK.
 *
 * These tests verify input validation, exception hierarchy, and client
 * behaviour without requiring network access to the actual SnapAPI service.
 */
class ClientTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────────
    // Constructor
    // ──────────────────────────────────────────────────────────────────────────

    public function testConstructorThrowsOnEmptyKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('');
    }

    public function testConstructorAcceptsValidKey(): void
    {
        $client = new Client('sk_test_key');
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testClientAcceptsAllOptions(): void
    {
        $client = new Client('sk_test_key', [
            'timeout'      => 60,
            'retries'      => 5,
            'retryDelayMs' => 1000,
            'baseUrl'      => 'https://api.snapapi.pics',
        ]);
        $this->assertInstanceOf(Client::class, $client);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ValidationException thrown when url is missing
    // ──────────────────────────────────────────────────────────────────────────

    public function testScreenshotThrowsWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('url is required.');
        $client->screenshot([]);
    }

    public function testScrapeThrowsWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->scrape([]);
    }

    public function testExtractThrowsWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->extract([]);
    }

    public function testAnalyzeThrowsWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->analyze([]);
    }

    public function testPdfThrowsWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->pdf([]);
    }

    public function testVideoThrowsWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->video([]);
    }

    public function testOgImageThrowsWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->ogImage([]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sub-client factories
    // ──────────────────────────────────────────────────────────────────────────

    public function testStorageReturnsStorageClient(): void
    {
        $client = new Client('test-key');
        $this->assertInstanceOf(\SnapAPI\Storage\StorageClient::class, $client->storage());
    }

    public function testScheduledReturnsScheduledClient(): void
    {
        $client = new Client('test-key');
        $this->assertInstanceOf(\SnapAPI\Scheduled\ScheduledClient::class, $client->scheduled());
    }

    public function testWebhooksReturnsWebhooksClient(): void
    {
        $client = new Client('test-key');
        $this->assertInstanceOf(\SnapAPI\Webhooks\WebhooksClient::class, $client->webhooks());
    }

    public function testApiKeysReturnsApiKeysClient(): void
    {
        $client = new Client('test-key');
        $this->assertInstanceOf(\SnapAPI\ApiKeys\ApiKeysClient::class, $client->apiKeys());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // quota() is an alias for getUsage()
    // ──────────────────────────────────────────────────────────────────────────

    // (quota and getUsage make real HTTP calls; we only test that they exist
    //  via the sub-client validation tests below)

    // ──────────────────────────────────────────────────────────────────────────
    // Exception hierarchy
    // ──────────────────────────────────────────────────────────────────────────

    public function testRateLimitExceptionIsSnapAPIException(): void
    {
        $e = new RateLimitException('Too many requests', 30);
        $this->assertInstanceOf(SnapAPIException::class, $e);
        $this->assertSame(30, $e->getRetryAfter());
        $this->assertSame(429, $e->getStatusCode());
        $this->assertSame('RATE_LIMITED', $e->getErrorCode());
    }

    public function testRateLimitExceptionDefaultRetryAfter(): void
    {
        $e = new RateLimitException();
        $this->assertSame(0, $e->getRetryAfter());
        $this->assertSame('Rate limit exceeded.', $e->getMessage());
    }

    public function testAuthenticationExceptionIsSnapAPIException(): void
    {
        $e = new AuthenticationException('Unauthorized');
        $this->assertInstanceOf(SnapAPIException::class, $e);
        $this->assertSame(401, $e->getStatusCode());
        $this->assertSame('UNAUTHORIZED', $e->getErrorCode());
    }

    public function testAuthenticationExceptionDefaultMessage(): void
    {
        $e = new AuthenticationException();
        $this->assertSame('Authentication failed.', $e->getMessage());
    }

    public function testQuotaExceptionIsSnapAPIException(): void
    {
        $e = new QuotaException('Quota exceeded');
        $this->assertInstanceOf(SnapAPIException::class, $e);
        $this->assertSame('QUOTA_EXCEEDED', $e->getErrorCode());
        $this->assertSame(402, $e->getStatusCode());
    }

    public function testQuotaExceededExceptionExtendsQuotaException(): void
    {
        $e = new QuotaExceededException();
        $this->assertInstanceOf(QuotaException::class, $e);
        $this->assertInstanceOf(SnapAPIException::class, $e);
        $this->assertSame('QUOTA_EXCEEDED', $e->getErrorCode());
    }

    public function testValidationExceptionIsSnapAPIException(): void
    {
        $e = new ValidationException('Bad params', ['field' => 'url']);
        $this->assertInstanceOf(SnapAPIException::class, $e);
        $this->assertSame(400, $e->getStatusCode());
        $this->assertSame('INVALID_PARAMS', $e->getErrorCode());
        $this->assertSame(['field' => 'url'], $e->getDetails());
    }

    public function testValidationExceptionDefaultMessage(): void
    {
        $e = new ValidationException();
        $this->assertSame('Validation failed.', $e->getMessage());
        $this->assertNull($e->getDetails());
    }

    public function testNetworkExceptionIsSnapAPIException(): void
    {
        $e = new NetworkException('DNS lookup failed.');
        $this->assertInstanceOf(SnapAPIException::class, $e);
        $this->assertSame('CONNECTION_ERROR', $e->getErrorCode());
        $this->assertSame(0, $e->getStatusCode());
    }

    public function testNetworkExceptionDefaultMessage(): void
    {
        $e = new NetworkException();
        $this->assertSame('A network error occurred.', $e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SnapAPIException getters and __toString
    // ──────────────────────────────────────────────────────────────────────────

    public function testSnapAPIExceptionToString(): void
    {
        $e = new SnapAPIException('Something went wrong', 'SERVER_ERROR', 500);
        $str = (string) $e;
        $this->assertStringContainsString('SERVER_ERROR', $str);
        $this->assertStringContainsString('Something went wrong', $str);
    }

    public function testSnapAPIExceptionGetters(): void
    {
        $details = [['field' => 'url', 'message' => 'required']];
        $e       = new SnapAPIException('Test error', 'TEST_CODE', 422, $details);

        $this->assertSame('Test error', $e->getMessage());
        $this->assertSame('TEST_CODE', $e->getErrorCode());
        $this->assertSame(422, $e->getStatusCode());
        $this->assertSame($details, $e->getDetails());
    }

    public function testSnapAPIExceptionNullDetails(): void
    {
        $e = new SnapAPIException('Oops');
        $this->assertNull($e->getDetails());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Enums
    // ──────────────────────────────────────────────────────────────────────────

    public function testImageFormatEnumValues(): void
    {
        $this->assertSame('png', \SnapAPI\Enums\ImageFormat::Png->value);
        $this->assertSame('jpeg', \SnapAPI\Enums\ImageFormat::Jpeg->value);
        $this->assertSame('webp', \SnapAPI\Enums\ImageFormat::Webp->value);
    }

    public function testImageFormatEnumFromValue(): void
    {
        $this->assertSame(\SnapAPI\Enums\ImageFormat::Png, \SnapAPI\Enums\ImageFormat::from('png'));
        $this->assertSame(\SnapAPI\Enums\ImageFormat::Jpeg, \SnapAPI\Enums\ImageFormat::from('jpeg'));
    }

    public function testVideoFormatEnumValues(): void
    {
        $this->assertSame('webm', \SnapAPI\Enums\VideoFormat::Webm->value);
        $this->assertSame('mp4', \SnapAPI\Enums\VideoFormat::Mp4->value);
        $this->assertSame('gif', \SnapAPI\Enums\VideoFormat::Gif->value);
    }

    public function testScrapeFormatEnumValues(): void
    {
        $this->assertSame('html', \SnapAPI\Enums\ScrapeFormat::Html->value);
        $this->assertSame('text', \SnapAPI\Enums\ScrapeFormat::Text->value);
        $this->assertSame('json', \SnapAPI\Enums\ScrapeFormat::Json->value);
    }

    public function testExtractFormatEnumValues(): void
    {
        $this->assertSame('markdown', \SnapAPI\Enums\ExtractFormat::Markdown->value);
        $this->assertSame('text', \SnapAPI\Enums\ExtractFormat::Text->value);
        $this->assertSame('json', \SnapAPI\Enums\ExtractFormat::Json->value);
    }

    public function testPdfPageFormatEnumValues(): void
    {
        $this->assertSame('a4', \SnapAPI\Enums\PdfPageFormat::A4->value);
        $this->assertSame('letter', \SnapAPI\Enums\PdfPageFormat::Letter->value);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sub-client input validation (no HTTP)
    // ──────────────────────────────────────────────────────────────────────────

    public function testStorageGetThrowsOnEmptyId(): void
    {
        $storage = (new Client('test-key'))->storage();
        $this->expectException(ValidationException::class);
        $storage->get('');
    }

    public function testStorageDeleteThrowsOnEmptyId(): void
    {
        $storage = (new Client('test-key'))->storage();
        $this->expectException(ValidationException::class);
        $storage->delete('');
    }

    public function testStorageDownloadThrowsOnEmptyId(): void
    {
        $storage = (new Client('test-key'))->storage();
        $this->expectException(ValidationException::class);
        $storage->download('');
    }

    public function testScheduledCreateThrowsWhenUrlMissing(): void
    {
        $scheduled = (new Client('test-key'))->scheduled();
        $this->expectException(ValidationException::class);
        $scheduled->create(['type' => 'screenshot', 'schedule' => '0 9 * * *']);
    }

    public function testScheduledCreateThrowsWhenTypeMissing(): void
    {
        $scheduled = (new Client('test-key'))->scheduled();
        $this->expectException(ValidationException::class);
        $scheduled->create(['url' => 'https://example.com', 'schedule' => '0 9 * * *']);
    }

    public function testScheduledCreateThrowsWhenScheduleMissing(): void
    {
        $scheduled = (new Client('test-key'))->scheduled();
        $this->expectException(ValidationException::class);
        $scheduled->create(['url' => 'https://example.com', 'type' => 'screenshot']);
    }

    public function testScheduledGetThrowsOnEmptyId(): void
    {
        $scheduled = (new Client('test-key'))->scheduled();
        $this->expectException(ValidationException::class);
        $scheduled->get('');
    }

    public function testScheduledDeleteThrowsOnEmptyId(): void
    {
        $scheduled = (new Client('test-key'))->scheduled();
        $this->expectException(ValidationException::class);
        $scheduled->delete('');
    }

    public function testScheduledPauseThrowsOnEmptyId(): void
    {
        $scheduled = (new Client('test-key'))->scheduled();
        $this->expectException(ValidationException::class);
        $scheduled->pause('');
    }

    public function testScheduledResumeThrowsOnEmptyId(): void
    {
        $scheduled = (new Client('test-key'))->scheduled();
        $this->expectException(ValidationException::class);
        $scheduled->resume('');
    }

    public function testWebhooksCreateThrowsWhenUrlMissing(): void
    {
        $webhooks = (new Client('test-key'))->webhooks();
        $this->expectException(ValidationException::class);
        $webhooks->create([]);
    }

    public function testWebhooksGetThrowsOnEmptyId(): void
    {
        $webhooks = (new Client('test-key'))->webhooks();
        $this->expectException(ValidationException::class);
        $webhooks->get('');
    }

    public function testWebhooksDeleteThrowsOnEmptyId(): void
    {
        $webhooks = (new Client('test-key'))->webhooks();
        $this->expectException(ValidationException::class);
        $webhooks->delete('');
    }

    public function testApiKeysGetThrowsOnEmptyId(): void
    {
        $apiKeys = (new Client('test-key'))->apiKeys();
        $this->expectException(ValidationException::class);
        $apiKeys->get('');
    }

    public function testApiKeysRevokeThrowsOnEmptyId(): void
    {
        $apiKeys = (new Client('test-key'))->apiKeys();
        $this->expectException(ValidationException::class);
        $apiKeys->revoke('');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Webhook signature verification
    // ──────────────────────────────────────────────────────────────────────────

    public function testVerifySignatureReturnsTrueForValidSignature(): void
    {
        $secret    = 'my-webhook-secret';
        $body      = '{"event":"screenshot.completed","id":"job_123"}';
        $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $webhooks = (new Client('test-key'))->webhooks();
        $this->assertTrue($webhooks->verifySignature($body, $signature, $secret));
    }

    public function testVerifySignatureReturnsFalseForInvalidSignature(): void
    {
        $secret    = 'my-webhook-secret';
        $body      = '{"event":"screenshot.completed","id":"job_123"}';
        $signature = 'sha256=invalidsignature';

        $webhooks = (new Client('test-key'))->webhooks();
        $this->assertFalse($webhooks->verifySignature($body, $signature, $secret));
    }

    public function testVerifySignatureReturnsFalseForWrongSecret(): void
    {
        $body      = '{"event":"screenshot.completed"}';
        $signature = 'sha256=' . hash_hmac('sha256', $body, 'correct-secret');

        $webhooks = (new Client('test-key'))->webhooks();
        $this->assertFalse($webhooks->verifySignature($body, $signature, 'wrong-secret'));
    }
}

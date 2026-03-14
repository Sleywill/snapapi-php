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
 * Unit tests for the SnapAPI PHP SDK.
 *
 * These tests use a real lightweight PHP built-in server to mock responses,
 * avoiding any dependency on the actual SnapAPI service.
 */
class ClientTest extends TestCase
{
    // ── Constructor ────────────────────────────────────────────────────────────

    public function testConstructorThrowsOnEmptyKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Client('');
    }

    // ── ValidationException on missing URL ────────────────────────────────────

    public function testScreenshotThrowsValidationExceptionWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->screenshot([]);
    }

    public function testScrapeThrowsValidationExceptionWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->scrape([]);
    }

    public function testExtractThrowsValidationExceptionWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->extract([]);
    }

    public function testPdfThrowsValidationExceptionWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->pdf([]);
    }

    public function testVideoThrowsValidationExceptionWhenUrlMissing(): void
    {
        $client = new Client('test-key');
        $this->expectException(ValidationException::class);
        $client->video([]);
    }

    // ── Exception hierarchy ────────────────────────────────────────────────────

    public function testRateLimitExceptionIsSnapAPIException(): void
    {
        $e = new RateLimitException('Too many requests', 30);
        $this->assertInstanceOf(SnapAPIException::class, $e);
        $this->assertSame(30, $e->getRetryAfter());
        $this->assertSame(429, $e->getStatusCode());
        $this->assertSame('RATE_LIMITED', $e->getErrorCode());
    }

    public function testAuthenticationExceptionIsSnapAPIException(): void
    {
        $e = new AuthenticationException('Unauthorized');
        $this->assertInstanceOf(SnapAPIException::class, $e);
        $this->assertSame(401, $e->getStatusCode());
        $this->assertSame('UNAUTHORIZED', $e->getErrorCode());
    }

    public function testQuotaExceptionIsSnapAPIException(): void
    {
        $e = new QuotaException('Quota exceeded');
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

    // ── SnapAPIException string representation ─────────────────────────────────

    public function testSnapAPIExceptionToString(): void
    {
        $e = new SnapAPIException('Something went wrong', 'SERVER_ERROR', 500);
        $this->assertStringContainsString('SERVER_ERROR', (string) $e);
        $this->assertStringContainsString('Something went wrong', (string) $e);
    }

    // ── Getters ────────────────────────────────────────────────────────────────

    public function testSnapAPIExceptionGetters(): void
    {
        $details = [['field' => 'url', 'message' => 'required']];
        $e = new SnapAPIException('Test error', 'TEST_CODE', 422, $details);

        $this->assertSame('Test error', $e->getMessage());
        $this->assertSame('TEST_CODE', $e->getErrorCode());
        $this->assertSame(422, $e->getStatusCode());
        $this->assertSame($details, $e->getDetails());
    }

    public function testRateLimitExceptionDefaultRetryAfter(): void
    {
        $e = new RateLimitException('Rate limited');
        $this->assertSame(0, $e->getRetryAfter());
    }
}

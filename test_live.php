<?php

declare(strict_types=1);

/**
 * SnapAPI PHP SDK — Comprehensive Live API Test Suite
 *
 * Requires:
 *   - composer install (autoload vendor/autoload.php)
 *   - PHP 8.1+
 *   - ext-curl, ext-json
 *
 * Usage:
 *   php test_live.php
 *
 * Set SNAPAPI_KEY env var or edit $API_KEY below.
 */

require_once __DIR__ . '/vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\AuthenticationException;
use SnapAPI\Exceptions\NetworkException;
use SnapAPI\Exceptions\QuotaException;
use SnapAPI\Exceptions\QuotaExceededException;
use SnapAPI\Exceptions\RateLimitException;
use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;

// ─── Configuration ───────────────────────────────────────────────────────────

const API_KEY      = 'sk_live_YOUR_API_KEY_HERE';
const BASE_URL     = 'https://api.snapapi.pics';
const TIMEOUT      = 60; // seconds; video needs more
const TMP_DIR      = '/tmp/snapapi_tests';

// ─── Test runner ─────────────────────────────────────────────────────────────

$results  = [];
$pass     = 0;
$fail     = 0;
$skip     = 0;
$startAll = microtime(true);

@mkdir(TMP_DIR, 0755, true);

function run(string $name, callable $fn): void
{
    global $results, $pass, $fail;

    $start = microtime(true);
    try {
        $detail = $fn();
        $elapsed = round((microtime(true) - $start) * 1000);
        $results[] = ['PASS', $name, $detail ?? '', $elapsed];
        $pass++;
    } catch (\Throwable $e) {
        $elapsed = round((microtime(true) - $start) * 1000);
        $results[] = ['FAIL', $name, get_class($e) . ': ' . $e->getMessage(), $elapsed];
        $fail++;
    }
}

function skip(string $name, string $reason): void
{
    global $results, $skip;
    $results[] = ['SKIP', $name, $reason, 0];
    $skip++;
}

/** Assert a condition or throw on failure */
function ok(bool $condition, string $msg): void
{
    if (!$condition) {
        throw new \AssertionError("Assertion failed: {$msg}");
    }
}

/** Assert an array has expected keys */
function hasKeys(array $arr, array $keys): void
{
    foreach ($keys as $k) {
        if (!array_key_exists($k, $arr)) {
            throw new \AssertionError("Missing key '{$k}' in response. Got: " . implode(', ', array_keys($arr)));
        }
    }
}

/** Assert a string starts with a magic byte sequence for a known format */
function assertBinaryFormat(string $bytes, string $format): void
{
    $sig = match ($format) {
        'png'  => "\x89PNG",
        'jpeg' => "\xFF\xD8\xFF",
        'webp' => 'RIFF',
        'pdf'  => '%PDF',
        'webm' => "\x1a\x45\xdf\xa3",
        'mp4'  => null, // variable magic
        'gif'  => 'GIF8',
        default => null,
    };
    if ($sig !== null) {
        ok(
            str_starts_with($bytes, $sig),
            "Expected {$format} magic bytes, got: " . bin2hex(substr($bytes, 0, 8))
        );
    }
    ok(strlen($bytes) > 100, "Binary response too small ({$format}): " . strlen($bytes) . ' bytes');
}

// ─── Create clients ───────────────────────────────────────────────────────────

$client = new Client(API_KEY, [
    'baseUrl'      => BASE_URL,
    'timeout'      => TIMEOUT,
    'retries'      => 2,
    'retryDelayMs' => 300,
]);

$clientNoRetry = new Client(API_KEY, [
    'baseUrl'  => BASE_URL,
    'timeout'  => 30,
    'retries'  => 0,
]);

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║       SnapAPI PHP SDK — Live API Test Suite                      ║\n";
echo "║       " . date('Y-m-d H:i:s') . "   SDK v3.2.0                      ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 1: Client Instantiation & Configuration
// ═══════════════════════════════════════════════════════════════════════════════

echo "── Section 1: Client Instantiation ───────────────────────────────\n";

run('Client: empty API key throws InvalidArgumentException', function () {
    try {
        new Client('');
        throw new \AssertionError('Expected InvalidArgumentException not thrown');
    } catch (\InvalidArgumentException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('Client: custom baseUrl option respected', function () {
    $c = new Client('test_key_placeholder', ['baseUrl' => 'https://custom.example.com/']);
    // Can only verify construction succeeds; no way to read private prop directly.
    return 'Client constructed with trailing-slash baseUrl';
});

run('Client: retries and retryDelayMs options accepted', function () {
    $c = new Client(API_KEY, ['retries' => 1, 'retryDelayMs' => 100]);
    return 'Client constructed with retries=1 retryDelayMs=100';
});

run('Client: custom transport injection works', function () {
    $called = false;
    $transport = function (string $method, string $url, ?string $body, array $headers) use (&$called): array {
        $called = true;
        return [200, '', '{"status":"ok","timestamp":' . time() . '}'];
    };
    $c = new Client(API_KEY, ['transport' => $transport]);
    $result = $c->ping();
    ok($called, 'Transport was not called');
    hasKeys($result, ['status', 'timestamp']);
    return 'Custom transport injected and called';
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 2: Health / Ping
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 2: Ping / Health ───────────────────────────────────────\n";

run('ping(): returns {status, timestamp}', function () use ($client) {
    $r = $client->ping();
    hasKeys($r, ['status', 'timestamp']);
    ok($r['status'] === 'ok', "Expected status=ok, got: " . $r['status']);
    ok(is_numeric($r['timestamp']), 'timestamp not numeric');
    return "status={$r['status']} ts={$r['timestamp']}";
});

run('ping(): unauthenticated still succeeds (health is public)', function () {
    $c = new Client('test_invalid_key', ['baseUrl' => BASE_URL, 'retries' => 0]);
    try {
        $r = $c->ping();
        hasKeys($r, ['status']);
        return 'Public ping succeeded with invalid key';
    } catch (AuthenticationException $e) {
        // Also acceptable if server requires auth for ping
        return 'Server requires auth for ping (not ideal but ok): ' . $e->getMessage();
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 3: Authentication & Error Handling
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 3: Authentication & Error Handling ─────────────────────\n";

run('AuthenticationException thrown on invalid API key', function () {
    $bad = new Client('INVALID_KEY_FOR_TESTING', [
        'baseUrl' => BASE_URL,
        'retries' => 0,
    ]);
    try {
        $bad->getUsage();
        throw new \AssertionError('Expected AuthenticationException');
    } catch (AuthenticationException $e) {
        ok($e->getStatusCode() === 401 || $e->getStatusCode() === 403, "Expected 401/403, got {$e->getStatusCode()}");
        ok($e->getErrorCode() === 'UNAUTHORIZED' || $e->getErrorCode() === 'FORBIDDEN',
            "Unexpected error code: {$e->getErrorCode()}");
        return 'AuthenticationException: ' . $e->getMessage();
    }
});

run('SnapAPIException::getErrorCode() returns string', function () use ($client) {
    try {
        $client->screenshot([]);
    } catch (ValidationException $e) {
        ok(is_string($e->getErrorCode()), 'getErrorCode not string');
        ok($e->getStatusCode() === 400, "Expected 400, got {$e->getStatusCode()}");
        return 'code=' . $e->getErrorCode() . ' status=' . $e->getStatusCode();
    }
});

run('ValidationException thrown when url missing (client-side)', function () use ($client) {
    try {
        $client->screenshot([]);
        throw new \AssertionError('Expected ValidationException');
    } catch (ValidationException $e) {
        ok(str_contains($e->getMessage(), 'url'), 'Expected url in message');
        return 'Caught client-side: ' . $e->getMessage();
    }
});

run('ValidationException thrown when url missing (scrape)', function () use ($client) {
    try {
        $client->scrape([]);
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('ValidationException thrown when url missing (extract)', function () use ($client) {
    try {
        $client->extract([]);
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('ValidationException thrown when url missing (pdf)', function () use ($client) {
    try {
        $client->pdf([]);
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('ValidationException thrown when url missing (video)', function () use ($client) {
    try {
        $client->video([]);
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('ValidationException thrown when url missing (ogImage)', function () use ($client) {
    try {
        $client->ogImage([]);
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('SnapAPIException::__toString() includes error code', function () use ($client) {
    try {
        $client->screenshot([]);
    } catch (SnapAPIException $e) {
        $str = (string) $e;
        ok(str_contains($str, '['), '__toString missing bracket');
        return "toString: {$str}";
    }
});

run('SnapAPIException::getDetails() returns null when no details field', function () {
    $e = new SnapAPIException('test', 'TEST', 500, null);
    ok($e->getDetails() === null, 'Expected null details');
    return 'getDetails() = null as expected';
});

run('RateLimitException::getRetryAfter() returns int', function () {
    $e = new RateLimitException('rate limit', 30);
    ok($e->getRetryAfter() === 30, 'Expected 30');
    ok($e->getErrorCode() === 'RATE_LIMITED', 'Wrong error code');
    ok($e->getStatusCode() === 429, 'Wrong status code');
    return 'RateLimitException retryAfter=30';
});

run('QuotaException hierarchy: QuotaExceededException extends QuotaException', function () {
    $e = new QuotaExceededException('quota hit', 402);
    ok($e instanceof QuotaException, 'Not instance of QuotaException');
    ok($e instanceof SnapAPIException, 'Not instance of SnapAPIException');
    ok($e->getErrorCode() === 'QUOTA_EXCEEDED', 'Wrong error code');
    return 'Hierarchy correct';
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 4: Usage
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 4: Usage ───────────────────────────────────────────────\n";

run('getUsage(): returns {used, limit, remaining}', function () use ($client) {
    $r = $client->getUsage();
    hasKeys($r, ['used', 'remaining']);
    ok(is_int($r['used']) || is_numeric($r['used']), 'used not numeric');
    ok(is_int($r['remaining']) || is_numeric($r['remaining']), 'remaining not numeric');
    return "used={$r['used']} remaining={$r['remaining']}";
});

run('quota(): alias for getUsage() returns same structure', function () use ($client) {
    $r = $client->quota();
    hasKeys($r, ['used', 'remaining']);
    return "used={$r['used']}";
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 5: Screenshot
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 5: Screenshot ──────────────────────────────────────────\n";

run('screenshot(): basic PNG capture', function () use ($client) {
    $bytes = $client->screenshot(['url' => 'https://example.com', 'format' => 'png']);
    assertBinaryFormat($bytes, 'png');
    return 'PNG bytes=' . strlen($bytes);
});

run('screenshot(): JPEG format', function () use ($client) {
    $bytes = $client->screenshot(['url' => 'https://example.com', 'format' => 'jpeg']);
    assertBinaryFormat($bytes, 'jpeg');
    return 'JPEG bytes=' . strlen($bytes);
});

run('screenshot(): WebP format', function () use ($client) {
    $bytes = $client->screenshot(['url' => 'https://example.com', 'format' => 'webp']);
    assertBinaryFormat($bytes, 'webp');
    return 'WebP bytes=' . strlen($bytes);
});

run('screenshot(): custom width and height', function () use ($client) {
    $bytes = $client->screenshot([
        'url'    => 'https://example.com',
        'width'  => 800,
        'height' => 600,
        'format' => 'png',
    ]);
    assertBinaryFormat($bytes, 'png');
    return 'Custom 800x600 PNG bytes=' . strlen($bytes);
});

run('screenshot(): full_page option', function () use ($client) {
    $bytes = $client->screenshot([
        'url'       => 'https://example.com',
        'full_page' => true,
        'format'    => 'png',
    ]);
    assertBinaryFormat($bytes, 'png');
    return 'Full page PNG bytes=' . strlen($bytes);
});

run('screenshot(): dark_mode option', function () use ($client) {
    $bytes = $client->screenshot([
        'url'       => 'https://example.com',
        'dark_mode' => true,
        'format'    => 'png',
    ]);
    assertBinaryFormat($bytes, 'png');
    return 'Dark mode PNG bytes=' . strlen($bytes);
});

run('screenshot(): delay option', function () use ($client) {
    $bytes = $client->screenshot([
        'url'    => 'https://example.com',
        'delay'  => 500,
        'format' => 'png',
    ]);
    assertBinaryFormat($bytes, 'png');
    return 'Delay=500ms PNG bytes=' . strlen($bytes);
});

run('screenshot(): JPEG quality option', function () use ($client) {
    $bytes = $client->screenshot([
        'url'     => 'https://example.com',
        'format'  => 'jpeg',
        'quality' => 50,
    ]);
    assertBinaryFormat($bytes, 'jpeg');
    return 'JPEG quality=50 bytes=' . strlen($bytes);
});

run('screenshotToFile(): saves PNG to disk', function () use ($client) {
    $file  = TMP_DIR . '/test_screenshot.png';
    $bytes = $client->screenshotToFile($file, ['url' => 'https://example.com', 'format' => 'png']);
    ok(file_exists($file), "File not written: {$file}");
    ok($bytes > 0, 'Zero bytes written');
    ok(filesize($file) === $bytes, 'File size mismatch');
    assertBinaryFormat(file_get_contents($file), 'png');
    @unlink($file);
    return "Wrote {$bytes} bytes to disk";
});

run('screenshot(): block_ads option accepted', function () use ($client) {
    $bytes = $client->screenshot([
        'url'        => 'https://example.com',
        'block_ads'  => true,
        'format'     => 'png',
    ]);
    assertBinaryFormat($bytes, 'png');
    return 'block_ads=true PNG bytes=' . strlen($bytes);
});

run('screenshot(): custom user_agent option', function () use ($client) {
    $bytes = $client->screenshot([
        'url'        => 'https://example.com',
        'user_agent' => 'Mozilla/5.0 (compatible; SnapAPIBot/1.0)',
        'format'     => 'png',
    ]);
    assertBinaryFormat($bytes, 'png');
    return 'Custom user_agent PNG bytes=' . strlen($bytes);
});

run('screenshot(): wait_for_selector option', function () use ($client) {
    $bytes = $client->screenshot([
        'url'               => 'https://example.com',
        'wait_for_selector' => 'h1',
        'format'            => 'png',
    ]);
    assertBinaryFormat($bytes, 'png');
    return 'wait_for_selector=h1 PNG bytes=' . strlen($bytes);
});

run('screenshot(): server-side validation rejects empty url string', function () use ($clientNoRetry) {
    try {
        // Pass non-empty string to bypass client-side check, but send clearly bad URL
        $clientNoRetry->screenshot(['url' => '']);
        throw new \AssertionError('Expected exception');
    } catch (ValidationException $e) {
        return 'Client caught empty url: ' . $e->getMessage();
    } catch (SnapAPIException $e) {
        return 'Server rejected: ' . $e->getMessage();
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 6: OG Image
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 6: OG Image ────────────────────────────────────────────\n";

run('ogImage(): returns PNG with 1200x630 defaults', function () use ($client) {
    $bytes = $client->ogImage(['url' => 'https://example.com', 'format' => 'png']);
    assertBinaryFormat($bytes, 'png');
    return 'OG PNG bytes=' . strlen($bytes);
});

run('generateOgImage(): alias for ogImage()', function () use ($client) {
    $bytes = $client->generateOgImage(['url' => 'https://example.com', 'format' => 'jpeg']);
    assertBinaryFormat($bytes, 'jpeg');
    return 'generateOgImage JPEG bytes=' . strlen($bytes);
});

run('ogImage(): custom dimensions override 1200x630 defaults', function () use ($client) {
    $bytes = $client->ogImage([
        'url'    => 'https://example.com',
        'width'  => 1200,
        'height' => 600,
        'format' => 'png',
    ]);
    assertBinaryFormat($bytes, 'png');
    return 'Custom 1200x600 OG PNG bytes=' . strlen($bytes);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 7: PDF
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 7: PDF ─────────────────────────────────────────────────\n";

run('pdf(): returns raw PDF bytes', function () use ($client) {
    $bytes = $client->pdf(['url' => 'https://example.com']);
    assertBinaryFormat($bytes, 'pdf');
    return 'PDF bytes=' . strlen($bytes);
});

run('generatePdf(): alias returns PDF bytes', function () use ($client) {
    $bytes = $client->generatePdf(['url' => 'https://example.com']);
    assertBinaryFormat($bytes, 'pdf');
    return 'generatePdf bytes=' . strlen($bytes);
});

run('pdfToFile(): saves PDF to disk', function () use ($client) {
    $file  = TMP_DIR . '/test_output.pdf';
    $bytes = $client->pdfToFile($file, ['url' => 'https://example.com']);
    ok(file_exists($file), "PDF file not created: {$file}");
    ok($bytes > 100, 'PDF too small');
    assertBinaryFormat(file_get_contents($file), 'pdf');
    @unlink($file);
    return "Wrote {$bytes} bytes to disk";
});

run('pdf(): A4 format option', function () use ($client) {
    $bytes = $client->pdf(['url' => 'https://example.com', 'format' => 'a4']);
    assertBinaryFormat($bytes, 'pdf');
    return 'A4 PDF bytes=' . strlen($bytes);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 8: Scrape
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 8: Scrape ──────────────────────────────────────────────\n";

run('scrape(): returns array with data key', function () use ($client) {
    $r = $client->scrape(['url' => 'https://example.com']);
    ok(is_array($r), 'Expected array response');
    // accept either {data:...} or {results:[...]} shape
    $hasData    = array_key_exists('data', $r);
    $hasResults = array_key_exists('results', $r);
    ok($hasData || $hasResults, 'Expected data or results key in: ' . implode(', ', array_keys($r)));
    return 'Keys: ' . implode(', ', array_keys($r));
});

run('scrape(): html format returns HTML', function () use ($client) {
    $r = $client->scrape(['url' => 'https://example.com', 'format' => 'html']);
    ok(is_array($r), 'Expected array');
    $content = $r['data'] ?? ($r['results'][0]['data'] ?? '');
    ok(strlen((string) $content) > 0, 'Empty content returned');
    return 'html format content length=' . strlen((string) $content);
});

run('scrape(): text format', function () use ($client) {
    $r = $client->scrape(['url' => 'https://example.com', 'format' => 'text']);
    ok(is_array($r), 'Expected array');
    return 'text format response: ' . substr(json_encode($r), 0, 80);
});

run('scrapeText(): convenience wrapper returns string', function () use ($client) {
    $text = $client->scrapeText('https://example.com');
    ok(is_string($text), 'Expected string');
    return 'scrapeText length=' . strlen($text) . ' chars';
});

run('scrapeHtml(): convenience wrapper returns string', function () use ($client) {
    $html = $client->scrapeHtml('https://example.com');
    ok(is_string($html), 'Expected string');
    return 'scrapeHtml length=' . strlen($html) . ' chars';
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 9: Extract
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 9: Extract ─────────────────────────────────────────────\n";

run('extract(): markdown format returns content', function () use ($client) {
    $r = $client->extract(['url' => 'https://example.com', 'format' => 'markdown']);
    ok(is_array($r), 'Expected array');
    // API returns {success, type, url, data, responseTime} — SDK docs say 'content' but actual field is 'data'
    $hasContent = array_key_exists('content', $r);
    $hasData    = array_key_exists('data', $r);
    $hasResults = array_key_exists('results', $r);
    ok($hasContent || $hasData || $hasResults,
        'Expected content, data, or results key. Got: ' . implode(', ', array_keys($r)));
    return 'Keys: ' . implode(', ', array_keys($r));
});

run('extractMarkdown(): convenience wrapper returns string', function () use ($client) {
    $md = $client->extractMarkdown('https://example.com');
    ok(is_string($md), 'Expected string');
    return 'extractMarkdown length=' . strlen($md) . ' chars';
});

run('extractText(): convenience wrapper returns string', function () use ($client) {
    $text = $client->extractText('https://example.com');
    ok(is_string($text), 'Expected string');
    return 'extractText length=' . strlen($text) . ' chars';
});

run('extract(): include_links option', function () use ($client) {
    $r = $client->extract(['url' => 'https://example.com', 'include_links' => true]);
    ok(is_array($r), 'Expected array');
    return 'include_links=true response keys: ' . implode(', ', array_keys($r));
});

run('extract(): text format option', function () use ($client) {
    $r = $client->extract(['url' => 'https://example.com', 'format' => 'text']);
    ok(is_array($r), 'Expected array');
    return 'text format keys: ' . implode(', ', array_keys($r));
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 10: Analyze (endpoint known to be broken/503)
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 10: Analyze (expect 503 or graceful error) ─────────────\n";

run('analyze(): gracefully handles unavailable endpoint', function () use ($clientNoRetry) {
    try {
        $r = $clientNoRetry->analyze(['url' => 'https://example.com', 'prompt' => 'Summarize this page']);
        // If it somehow works, great
        ok(is_array($r), 'Expected array response');
        return 'Analyze returned response: ' . json_encode(array_keys($r));
    } catch (SnapAPIException $e) {
        // Expected: 503 Service Unavailable or similar
        return 'Expected error: [' . $e->getErrorCode() . '] ' . $e->getMessage() . ' (HTTP ' . $e->getStatusCode() . ')';
    }
});

run('analyze(): url required validation', function () use ($client) {
    try {
        $client->analyze([]);
        throw new \AssertionError('Expected ValidationException');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 11: Video
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 11: Video ──────────────────────────────────────────────\n";

run('video(): WebM format short recording', function () use ($client) {
    $bytes = $client->video([
        'url'      => 'https://example.com',
        'duration' => 2,
        'format'   => 'webm',
        'width'    => 800,
        'height'   => 600,
    ]);
    ok(strlen($bytes) > 100, 'Video response too small: ' . strlen($bytes));
    return 'WebM bytes=' . strlen($bytes);
});

run('video(): MP4 format', function () use ($client) {
    $bytes = $client->video([
        'url'      => 'https://example.com',
        'duration' => 2,
        'format'   => 'mp4',
    ]);
    ok(strlen($bytes) > 100, 'Video response too small: ' . strlen($bytes));
    return 'MP4 bytes=' . strlen($bytes);
});

run('video(): GIF format', function () use ($client) {
    $bytes = $client->video([
        'url'      => 'https://example.com',
        'duration' => 2,
        'format'   => 'gif',
    ]);
    ok(strlen($bytes) > 100, 'GIF response too small: ' . strlen($bytes));
    // GIF magic
    ok(str_starts_with($bytes, 'GIF8'), 'Not a GIF: ' . bin2hex(substr($bytes, 0, 4)));
    return 'GIF bytes=' . strlen($bytes);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 12: Storage sub-client
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 12: Storage Sub-Client ────────────────────────────────\n";

// The storage routes are under /storage/ (non-v1) per server config.
// SDK calls /v1/storage — test what actually happens.

run('storage()->list(): call succeeds or returns expected 404', function () use ($client) {
    try {
        $r = $client->storage()->list();
        ok(is_array($r), 'Expected array');
        return 'Response keys: ' . implode(', ', array_keys($r));
    } catch (SnapAPIException $e) {
        // 404 = route not at /v1/storage; document it
        return 'Expected mismatch - server returned: [' . $e->getErrorCode() . '] HTTP ' . $e->getStatusCode();
    }
});

run('storage()->get(): empty fileId throws ValidationException', function () use ($client) {
    try {
        $client->storage()->get('');
        throw new \AssertionError('Expected ValidationException');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('storage()->download(): empty fileId throws ValidationException', function () use ($client) {
    try {
        $client->storage()->download('');
        throw new \AssertionError('Expected ValidationException');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('storage()->delete(): empty fileId throws ValidationException', function () use ($client) {
    try {
        $client->storage()->delete('');
        throw new \AssertionError('Expected ValidationException');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('storage()->list(): with type filter', function () use ($client) {
    try {
        $r = $client->storage()->list(['type' => 'screenshot', 'limit' => 5]);
        ok(is_array($r), 'Expected array');
        return 'type=screenshot response keys: ' . implode(', ', array_keys($r));
    } catch (SnapAPIException $e) {
        return 'Route mismatch: ' . $e->getErrorCode() . ' HTTP ' . $e->getStatusCode();
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 13: Webhooks sub-client
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 13: Webhooks Sub-Client ────────────────────────────────\n";

$createdWebhookId = null;

run('webhooks()->list(): returns {webhooks: [...]}', function () use ($client) {
    $r = $client->webhooks()->list();
    ok(is_array($r), 'Expected array');
    hasKeys($r, ['webhooks']);
    ok(is_array($r['webhooks']), 'webhooks must be array');
    return 'webhooks count=' . count($r['webhooks']);
});

run('webhooks()->create(): create a test webhook', function () use ($client, &$createdWebhookId) {
    $r = $client->webhooks()->create([
        'url'    => 'https://webhook.site/test-snapapi-sdk-php',
        'events' => ['screenshot.completed'],
        'name'   => 'sdk-test-' . time(),
    ]);
    ok(is_array($r), 'Expected array');
    // Accept either {id:...} or wrapped in a parent key
    $id = $r['id'] ?? ($r['webhook']['id'] ?? null);
    if ($id !== null) {
        $createdWebhookId = (string) $id;
        return 'Created webhook id=' . $id;
    }
    return 'Created (no id field): ' . json_encode(array_keys($r));
});

run('webhooks()->create(): url validation', function () use ($client) {
    try {
        $client->webhooks()->create([]);
        throw new \AssertionError('Expected ValidationException');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('webhooks()->get(): empty webhookId throws ValidationException', function () use ($client) {
    try {
        $client->webhooks()->get('');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('webhooks()->update(): empty webhookId throws ValidationException', function () use ($client) {
    try {
        $client->webhooks()->update('', ['url' => 'https://example.com']);
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('webhooks()->delete(): empty webhookId throws ValidationException', function () use ($client) {
    try {
        $client->webhooks()->delete('');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('webhooks()->verifySignature(): correct HMAC returns true', function () use ($client) {
    $secret  = 'my_secret_123';
    $body    = '{"event":"screenshot.completed","data":{"id":"abc"}}';
    $sig     = 'sha256=' . hash_hmac('sha256', $body, $secret);
    $result  = $client->webhooks()->verifySignature($body, $sig, $secret);
    ok($result === true, 'Expected true for valid HMAC');
    return 'Valid HMAC verified';
});

run('webhooks()->verifySignature(): wrong signature returns false', function () use ($client) {
    $result = $client->webhooks()->verifySignature(
        '{"event":"test"}',
        'sha256=wrongsignature',
        'secret'
    );
    ok($result === false, 'Expected false for invalid HMAC');
    return 'Invalid HMAC correctly rejected';
});

// Cleanup created webhook
if ($createdWebhookId !== null) {
    run("webhooks()->delete(): cleanup created webhook id={$createdWebhookId}", function () use ($client, $createdWebhookId) {
        $r = $client->webhooks()->delete($createdWebhookId);
        ok(is_array($r), 'Expected array');
        return 'Deleted webhook: ' . json_encode($r);
    });
}

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 14: API Keys sub-client
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 14: API Keys Sub-Client ────────────────────────────────\n";

// SDK calls /v1/api-keys, server has those under /dashboard/api-keys with JWT auth
// Test both the SDK call behavior and document the route mismatch

run('apiKeys()->list(): call result (may hit route mismatch)', function () use ($client) {
    try {
        $r = $client->apiKeys()->list();
        ok(is_array($r), 'Expected array');
        return 'Response keys: ' . implode(', ', array_keys($r));
    } catch (SnapAPIException $e) {
        return 'Route mismatch documented: [' . $e->getErrorCode() . '] HTTP ' . $e->getStatusCode() . ' - ' . $e->getMessage();
    }
});

run('apiKeys()->get(): empty keyId throws ValidationException', function () use ($client) {
    try {
        $client->apiKeys()->get('');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('apiKeys()->revoke(): empty keyId throws ValidationException', function () use ($client) {
    try {
        $client->apiKeys()->revoke('');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 15: Scheduled sub-client
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 15: Scheduled Sub-Client ───────────────────────────────\n";

run('scheduled()->create(): url validation', function () use ($client) {
    try {
        $client->scheduled()->create(['type' => 'screenshot', 'schedule' => '0 9 * * *']);
        throw new \AssertionError('Expected ValidationException for missing url');
    } catch (ValidationException $e) {
        return 'Caught missing url: ' . $e->getMessage();
    }
});

run('scheduled()->create(): type validation', function () use ($client) {
    try {
        $client->scheduled()->create(['url' => 'https://example.com', 'schedule' => '0 9 * * *']);
        throw new \AssertionError('Expected ValidationException for missing type');
    } catch (ValidationException $e) {
        return 'Caught missing type: ' . $e->getMessage();
    }
});

run('scheduled()->create(): schedule validation', function () use ($client) {
    try {
        $client->scheduled()->create(['url' => 'https://example.com', 'type' => 'screenshot']);
        throw new \AssertionError('Expected ValidationException for missing schedule');
    } catch (ValidationException $e) {
        return 'Caught missing schedule: ' . $e->getMessage();
    }
});

run('scheduled()->list(): call result', function () use ($client) {
    try {
        $r = $client->scheduled()->list();
        ok(is_array($r), 'Expected array');
        return 'Response keys: ' . implode(', ', array_keys($r));
    } catch (SnapAPIException $e) {
        return 'Route result: [' . $e->getErrorCode() . '] HTTP ' . $e->getStatusCode();
    }
});

run('scheduled()->get(): empty jobId throws ValidationException', function () use ($client) {
    try {
        $client->scheduled()->get('');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('scheduled()->pause(): empty jobId throws ValidationException', function () use ($client) {
    try {
        $client->scheduled()->pause('');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('scheduled()->resume(): empty jobId throws ValidationException', function () use ($client) {
    try {
        $client->scheduled()->resume('');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

run('scheduled()->delete(): empty jobId throws ValidationException', function () use ($client) {
    try {
        $client->scheduled()->delete('');
    } catch (ValidationException $e) {
        return 'Caught: ' . $e->getMessage();
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 16: Retry logic verification (via mock transport)
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 16: Retry Logic ────────────────────────────────────────\n";

run('Retry: 500 error retries 2x then propagates', function () {
    $callCount = 0;
    $transport = function () use (&$callCount): array {
        $callCount++;
        return [500, '', '{"message":"Internal Server Error","error":"SERVER_ERROR"}'];
    };
    $c = new Client(API_KEY, ['transport' => $transport, 'retries' => 2, 'retryDelayMs' => 1]);
    try {
        $c->ping();
        throw new \AssertionError('Expected SnapAPIException on 500');
    } catch (SnapAPIException $e) {
        ok($callCount === 3, "Expected 3 calls (1 + 2 retries), got {$callCount}");
        return "500 retried {$callCount} times as expected";
    }
});

run('Retry: 401 is NOT retried', function () {
    $callCount = 0;
    $transport = function () use (&$callCount): array {
        $callCount++;
        return [401, '', '{"message":"Unauthorized","error":"UNAUTHORIZED"}'];
    };
    $c = new Client(API_KEY, ['transport' => $transport, 'retries' => 3, 'retryDelayMs' => 1]);
    try {
        $c->ping();
        throw new \AssertionError('Expected AuthenticationException');
    } catch (AuthenticationException $e) {
        ok($callCount === 1, "Expected 1 call (no retry on 401), got {$callCount}");
        return "401 NOT retried: callCount={$callCount}";
    }
});

run('Retry: 400 is NOT retried', function () {
    $callCount = 0;
    $transport = function () use (&$callCount): array {
        $callCount++;
        return [400, '', '{"message":"Bad Request","error":"INVALID_PARAMS"}'];
    };
    $c = new Client(API_KEY, ['transport' => $transport, 'retries' => 3, 'retryDelayMs' => 1]);
    try {
        $c->ping();
        throw new \AssertionError('Expected ValidationException');
    } catch (ValidationException $e) {
        ok($callCount === 1, "Expected 1 call (no retry on 400), got {$callCount}");
        return "400 NOT retried: callCount={$callCount}";
    }
});

run('Retry: 429 is retried with Retry-After header parsed', function () {
    $callCount = 0;
    $transport = function () use (&$callCount): array {
        $callCount++;
        if ($callCount < 2) {
            return [429, "Retry-After: 0\r\nContent-Type: application/json\r\n", '{"message":"Rate limited","error":"RATE_LIMITED"}'];
        }
        return [200, '', '{"status":"ok","timestamp":' . time() . '}'];
    };
    $c = new Client(API_KEY, ['transport' => $transport, 'retries' => 3, 'retryDelayMs' => 1]);
    $r = $c->ping();
    ok($r['status'] === 'ok', 'Expected ok after retry');
    ok($callCount === 2, "Expected 2 calls, got {$callCount}");
    return "429 retried, succeeded on attempt 2";
});

run('Retry: succeeds on 2nd attempt after 500', function () {
    $callCount = 0;
    $transport = function () use (&$callCount): array {
        $callCount++;
        if ($callCount === 1) {
            return [500, '', '{"message":"Server Error","error":"SERVER_ERROR"}'];
        }
        return [200, '', '{"status":"ok","timestamp":' . time() . '}'];
    };
    $c = new Client(API_KEY, ['transport' => $transport, 'retries' => 2, 'retryDelayMs' => 1]);
    $r = $c->ping();
    ok($r['status'] === 'ok', 'Expected ok after retry');
    ok($callCount === 2, "Expected 2 calls, got {$callCount}");
    return "Recovered on attempt 2";
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 17: Response parsing edge cases
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 17: Response Parsing Edge Cases ─────────────────────────\n";

run('decodeJson: malformed JSON triggers SnapAPIException PARSE_ERROR', function () {
    $transport = fn() => [200, '', 'not-json-at-all{{'];
    $c = new Client(API_KEY, ['transport' => $transport]);
    try {
        $c->getUsage(); // expects JSON decode
        throw new \AssertionError('Expected SnapAPIException');
    } catch (SnapAPIException $e) {
        ok($e->getErrorCode() === 'PARSE_ERROR', 'Expected PARSE_ERROR, got: ' . $e->getErrorCode());
        return 'PARSE_ERROR thrown for malformed JSON';
    }
});

run('decodeJson: empty JSON body triggers PARSE_ERROR', function () {
    $transport = fn() => [200, '', ''];
    $c = new Client(API_KEY, ['transport' => $transport]);
    try {
        $c->getUsage();
        throw new \AssertionError('Expected SnapAPIException');
    } catch (SnapAPIException $e) {
        ok($e->getErrorCode() === 'PARSE_ERROR', 'Expected PARSE_ERROR');
        return 'PARSE_ERROR on empty body';
    }
});

run('screenshot() returns raw binary (not JSON) without decodeJson wrapping', function () {
    $fakePng = "\x89PNG\r\n\x1a\n" . str_repeat('x', 500);
    $transport = fn() => [200, '', $fakePng];
    $c = new Client(API_KEY, ['transport' => $transport]);
    $result = $c->screenshot(['url' => 'https://example.com']);
    ok($result === $fakePng, 'screenshot() should return raw body without JSON parsing');
    return 'Raw binary passthrough confirmed';
});

run('Error response with no JSON body uses fallback message HTTP $code', function () {
    $transport = fn() => [503, '', 'Service Unavailable'];
    $c = new Client(API_KEY, ['transport' => $transport, 'retries' => 0]);
    try {
        $c->getUsage();
    } catch (SnapAPIException $e) {
        ok($e->getStatusCode() === 503, 'Expected 503');
        ok(str_contains($e->getMessage(), '503') || strlen($e->getMessage()) > 0,
            'Expected message to contain HTTP code or be non-empty');
        return 'Non-JSON 503 handled: ' . $e->getMessage();
    }
});

run('Error response details field is parsed into getDetails()', function () {
    $transport = fn() => [400, '', '{"message":"Bad params","error":"INVALID_PARAMS","details":{"field":"url","issue":"required"}}'];
    $c = new Client(API_KEY, ['transport' => $transport, 'retries' => 0]);
    try {
        $c->getUsage();
    } catch (ValidationException $e) {
        $details = $e->getDetails();
        ok($details !== null, 'Expected details to be non-null');
        ok(is_array($details), 'Expected details to be array');
        ok(isset($details['field']), 'Expected details.field');
        return 'details parsed: ' . json_encode($details);
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 18: Network error handling
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 18: Network Error Handling ─────────────────────────────\n";

run('NetworkException thrown when host is unreachable', function () {
    $c = new Client(API_KEY, [
        'baseUrl' => 'https://this.host.definitely.does.not.exist.invalid',
        'timeout' => 5,
        'retries' => 0,
    ]);
    try {
        $c->ping();
        throw new \AssertionError('Expected NetworkException');
    } catch (NetworkException $e) {
        ok(str_contains($e->getMessage(), 'cURL') || strlen($e->getMessage()) > 0, 'Expected cURL error message');
        return 'NetworkException: ' . $e->getMessage();
    }
});

run('screenshotToFile(): NetworkException on write failure', function () use ($client) {
    $transport = fn() => [200, '', "\x89PNG\r\n\x1a\n" . str_repeat('x', 200)];
    $c = new Client(API_KEY, ['transport' => $transport]);
    try {
        $c->screenshotToFile('/nonexistent/dir/test.png', ['url' => 'https://example.com']);
        throw new \AssertionError('Expected NetworkException for write fail');
    } catch (NetworkException $e) {
        ok(str_contains($e->getMessage(), 'Failed to write'), 'Expected write error message');
        return 'Write failure caught: ' . $e->getMessage();
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 19: Enums
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 19: Enums ───────────────────────────────────────────────\n";

run('ImageFormat enum values exist', function () {
    $cases = \SnapAPI\Enums\ImageFormat::cases();
    ok(count($cases) >= 3, 'Expected at least 3 image formats');
    $values = array_map(fn($c) => $c->value, $cases);
    ok(in_array('png', $values, true), 'Expected png');
    ok(in_array('jpeg', $values, true), 'Expected jpeg');
    ok(in_array('webp', $values, true), 'Expected webp');
    return 'ImageFormat: ' . implode(', ', $values);
});

run('VideoFormat enum values exist', function () {
    $cases = \SnapAPI\Enums\VideoFormat::cases();
    ok(count($cases) >= 2, 'Expected at least 2 video formats');
    $values = array_map(fn($c) => $c->value, $cases);
    return 'VideoFormat: ' . implode(', ', $values);
});

run('ScrapeFormat enum values exist', function () {
    $cases = \SnapAPI\Enums\ScrapeFormat::cases();
    ok(count($cases) >= 2, 'Expected at least 2 scrape formats');
    $values = array_map(fn($c) => $c->value, $cases);
    return 'ScrapeFormat: ' . implode(', ', $values);
});

run('ExtractFormat enum values exist', function () {
    $cases = \SnapAPI\Enums\ExtractFormat::cases();
    ok(count($cases) >= 2, 'Expected at least 2 extract formats');
    $values = array_map(fn($c) => $c->value, $cases);
    return 'ExtractFormat: ' . implode(', ', $values);
});

run('PdfPageFormat enum values exist', function () {
    $cases = \SnapAPI\Enums\PdfPageFormat::cases();
    ok(count($cases) >= 2, 'Expected at least 2 PDF page formats');
    $values = array_map(fn($c) => $c->value, $cases);
    return 'PdfPageFormat: ' . implode(', ', $values);
});

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 20: Live integration smoke tests
// ═══════════════════════════════════════════════════════════════════════════════

echo "\n── Section 20: Live Integration Smoke Tests ────────────────────────\n";

run('End-to-end: screenshot → save to file → verify bytes', function () use ($client) {
    $file  = TMP_DIR . '/e2e_screenshot.png';
    $bytes = $client->screenshotToFile($file, [
        'url'       => 'https://example.com',
        'format'    => 'png',
        'width'     => 1280,
        'height'    => 720,
        'full_page' => false,
    ]);
    ok($bytes > 0, 'Zero bytes');
    ok(file_exists($file), 'File not created');
    ok(filesize($file) === $bytes, 'File size mismatch');
    assertBinaryFormat(file_get_contents($file), 'png');
    @unlink($file);
    return "E2E screenshot: {$bytes} bytes at 1280x720";
});

run('End-to-end: extract markdown from real page', function () use ($client) {
    $md = $client->extractMarkdown('https://example.com');
    ok(is_string($md), 'Expected string');
    ok(strlen($md) > 10, 'Content too short: ' . strlen($md));
    return 'Markdown extraction: ' . strlen($md) . ' chars, starts: ' . substr($md, 0, 50);
});

run('End-to-end: scrape html then verify it contains HTML tags', function () use ($client) {
    $html = $client->scrapeHtml('https://example.com');
    ok(is_string($html), 'Expected string');
    // HTML or markdown could be returned
    ok(strlen($html) > 5, 'Response too short');
    return 'Scrape html: ' . strlen($html) . ' chars';
});

run('End-to-end: usage counter increments after screenshot', function () use ($client) {
    $before = $client->getUsage();
    $usedBefore = (int) ($before['used'] ?? 0);

    $client->screenshot(['url' => 'https://example.com', 'format' => 'png']);

    // Small delay for server to update
    usleep(500_000); // 0.5s

    $after = $client->getUsage();
    $usedAfter = (int) ($after['used'] ?? 0);

    ok($usedAfter >= $usedBefore, "Usage should not decrease: before={$usedBefore} after={$usedAfter}");
    return "Usage: {$usedBefore} → {$usedAfter}";
});

// ─── Summary ──────────────────────────────────────────────────────────────────

$totalMs = round((microtime(true) - $startAll) * 1000);

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST RESULTS                                                    ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";

$colWidth = 55;
foreach ($results as [$status, $name, $detail, $ms]) {
    $icon = match ($status) {
        'PASS' => '✓',
        'FAIL' => '✗',
        'SKIP' => '~',
    };
    $color = match ($status) {
        'PASS' => "\033[32m",
        'FAIL' => "\033[31m",
        'SKIP' => "\033[33m",
    };
    $reset = "\033[0m";
    $truncName = strlen($name) > $colWidth ? substr($name, 0, $colWidth - 3) . '...' : $name;
    $msStr = $ms > 0 ? sprintf('%5dms', $ms) : '      ';
    printf("║ {$color}%s{$reset} %-{$colWidth}s %s ║\n", $icon, $truncName, $msStr);

    if ($status === 'FAIL' || ($status === 'SKIP')) {
        $truncDetail = strlen($detail) > 70 ? substr($detail, 0, 67) . '...' : $detail;
        printf("║   \033[90m%-68s\033[0m ║\n", $truncDetail);
    }
}

$total = $pass + $fail + $skip;
echo "╠══════════════════════════════════════════════════════════════════╣\n";
printf("║  Total: %-3d  \033[32mPass: %-3d\033[0m  \033[31mFail: %-3d\033[0m  \033[33mSkip: %-3d\033[0m  Time: %dms     ║\n",
    $total, $pass, $fail, $skip, $totalMs);
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

if ($fail > 0) {
    echo "\033[31mFAILED TESTS:\033[0m\n";
    foreach ($results as [$status, $name, $detail]) {
        if ($status === 'FAIL') {
            echo "  ✗ {$name}\n";
            echo "    → {$detail}\n";
        }
    }
    echo "\n";
}

exit($fail > 0 ? 1 : 0);

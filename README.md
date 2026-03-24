# snapapi-php

Official PHP SDK for [SnapAPI.pics](https://snapapi.pics) -- capture screenshots, generate PDFs, scrape pages, extract LLM-ready content, record videos, manage storage, schedule recurring captures, and handle webhooks.

[![CI](https://github.com/Sleywill/snapapi-php/actions/workflows/ci.yml/badge.svg)](https://github.com/Sleywill/snapapi-php/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)

## Features

- **Screenshot** -- Capture full-page or viewport screenshots in PNG, JPEG, WebP
- **PDF** -- Generate PDFs from any URL with configurable paper size and margins
- **Scrape** -- Extract HTML, text, or JSON from any web page
- **Extract** -- Get clean, LLM-ready content in Markdown, text, or JSON
- **Analyze** -- Send page content to OpenAI, Anthropic, or Google LLMs
- **Video** -- Record short browser session videos (WebM, MP4, GIF)
- **OG Image** -- One-call Open Graph image generation (1200x630 preset)
- **Storage** -- List, download, and delete stored capture files
- **Scheduled** -- Create recurring capture jobs on a cron schedule
- **Webhooks** -- Register endpoints for event delivery with HMAC-SHA256 verification
- **API Keys** -- Create and revoke additional API keys
- **PHP 8.1 Enums** -- Type-safe format constants (`ImageFormat`, `VideoFormat`, etc.)
- **Retry with backoff** -- Automatic exponential backoff on 5xx and rate-limit errors
- **Typed exceptions** -- Rich exception hierarchy for precise error handling
- **Zero mandatory dependencies** -- Uses cURL (standard PHP extension)

## Requirements

- PHP 8.1+
- `ext-curl`
- `ext-json`

## Installation

```bash
composer require snapapi/snapapi-php
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\SnapAPIException;

$client = new Client($_ENV['SNAPAPI_KEY']);

try {
    $png = $client->screenshot([
        'url'       => 'https://example.com',
        'format'    => 'png',
        'full_page' => true,
    ]);
    file_put_contents('screenshot.png', $png);
} catch (SnapAPIException $e) {
    echo "[{$e->getErrorCode()}] {$e->getMessage()}\n";
}
```

## Configuration

```php
$client = new Client('sk_live_...', [
    'timeout'      => 45,   // HTTP timeout in seconds (default: 30)
    'retries'      => 3,    // Retries on 5xx / rate-limit errors (default: 3)
    'retryDelayMs' => 500,  // Base delay in ms for exponential back-off (default: 500)
    'baseUrl'      => 'https://api.snapapi.pics',  // Override for testing
]);
```

## PHP 8.1 Enums

Use enums for type-safe format selection:

```php
use SnapAPI\Enums\ImageFormat;
use SnapAPI\Enums\VideoFormat;
use SnapAPI\Enums\ScrapeFormat;
use SnapAPI\Enums\ExtractFormat;
use SnapAPI\Enums\PdfPageFormat;

$client->screenshot(['url' => 'https://example.com', 'format' => ImageFormat::Png->value]);
$client->video(['url' => 'https://example.com', 'format' => VideoFormat::Mp4->value]);
$client->scrape(['url' => 'https://example.com', 'format' => ScrapeFormat::Html->value]);
$client->extract(['url' => 'https://example.com', 'format' => ExtractFormat::Markdown->value]);
$client->pdf(['url' => 'https://example.com', 'format' => PdfPageFormat::A4->value]);
```

## API Reference

### screenshot() -- `POST /v1/screenshot`

```php
$image = $client->screenshot([
    'url'               => 'https://example.com',  // required
    'format'            => 'png',      // "png" | "jpeg" | "webp"
    'width'             => 1280,
    'height'            => 720,
    'full_page'         => true,
    'delay'             => 500,        // ms to wait after page load
    'quality'           => 85,         // JPEG/WebP quality (1-100)
    'scale'             => 2.0,        // device scale factor (retina)
    'block_ads'         => true,
    'block_cookies'     => true,       // block cookie consent banners
    'dark_mode'         => true,       // enable prefers-color-scheme: dark
    'wait_for_selector' => '.loaded',
    'selector'          => '#hero',    // capture only this element
    'scroll_y'          => 500,
    'custom_css'        => 'body { background: white; }',
    'custom_js'         => "document.querySelector('.popup')?.remove();",
    'user_agent'        => 'MyBot/1.0',
    'proxy'             => 'http://proxy:8080',
    'headers'           => ['Cookie' => 'session=abc'],
    'clip'              => ['x' => 0, 'y' => 0, 'w' => 800, 'h' => 600],
]);
file_put_contents('screenshot.png', $image);
```

### screenshotToFile()

```php
$bytes = $client->screenshotToFile('output.png', [
    'url'       => 'https://example.com',
    'format'    => 'png',
    'full_page' => true,
]);
echo "Wrote {$bytes} bytes\n";
```

### scrape() -- `POST /v1/scrape`

```php
$result = $client->scrape([
    'url'               => 'https://example.com',  // required
    'selector'          => 'article',
    'selectors'         => ['title' => 'h1', 'body' => 'article'], // named multi-element
    'format'            => 'html',   // "html" | "text" | "json"
    'waitFor'           => '.content', // wait for selector/timeout before scraping
    'wait_for_selector' => '.content',
    'headers'           => ['Accept-Language' => 'en-US'],
    'proxy'             => 'http://proxy:8080',
]);
echo $result['data'];    // scraped content
echo $result['url'];     // final URL after redirects
echo $result['status'];  // HTTP status of the scraped page
```

### extract() -- `POST /v1/extract`

```php
$result = $client->extract([
    'url'               => 'https://example.com/blog/post',  // required
    'format'            => 'markdown',  // "markdown" | "text" | "json"
    'include_links'     => true,
    'include_images'    => false,
    'selector'          => 'main',
    'wait_for_selector' => '.loaded',
    'headers'           => ['Authorization' => 'Bearer token'],
]);
echo $result['content'];     // clean markdown/text
echo $result['word_count'];  // approximate word count
echo $result['url'];         // final URL
```

### analyze() -- `POST /v1/analyze`

```php
$result = $client->analyze([
    'url'        => 'https://example.com',  // required
    'prompt'     => 'Summarize this page in 3 bullet points.',
    'provider'   => 'openai',   // "openai" | "anthropic" | "google"
    'apiKey'     => 'sk-...',
    'jsonSchema' => [
        'type'       => 'object',
        'properties' => ['summary' => ['type' => 'string']],
    ],
]);
echo $result['result'];
```

Note: The analyze endpoint may return HTTP 503 if LLM credits are exhausted.

### pdf() -- `POST /v1/pdf`

```php
$pdf = $client->pdf([
    'url'    => 'https://example.com',  // required
    'format' => 'a4',    // "a4" | "letter"
    'margin' => '10mm',
]);
file_put_contents('output.pdf', $pdf);
```

### pdfToFile()

```php
$bytes = $client->pdfToFile('output.pdf', [
    'url'    => 'https://example.com',
    'format' => PdfPageFormat::A4->value,
]);
```

### video() -- `POST /v1/video`

```php
$video = $client->video([
    'url'         => 'https://example.com',  // required
    'duration'    => 5,
    'format'      => 'mp4',  // "webm" | "mp4" | "gif"
    'width'       => 1280,
    'height'      => 720,
    'scrollVideo' => true,   // scroll-based video recording
]);
file_put_contents('capture.mp4', $video);
```

### ogImage()

Calls the screenshot endpoint with a 1200x630 preset (overridable):

```php
$image = $client->ogImage([
    'url'    => 'https://mysite.com/blog/post',
    'format' => 'png',
]);
file_put_contents('og.png', $image);
```

### ping() -- `GET /v1/ping`

```php
$status = $client->ping();
echo $status['status'];  // "ok"
```

### getUsage() / quota() -- `GET /v1/usage`

```php
$usage = $client->getUsage();  // or $client->quota()
echo "Used: {$usage['used']} / {$usage['total']} ({$usage['remaining']} remaining)\n";
```

## Storage Sub-Client

```php
$storage = $client->storage();

// List stored files
$result = $storage->list(['limit' => 20, 'type' => 'screenshot']);

// Get file metadata
$meta = $storage->get('file_abc123');

// Download raw bytes
$bytes = $storage->download('file_abc123');

// Download directly to disk
$storage->downloadToFile('file_abc123', 'local_copy.png');

// Delete
$storage->delete('file_abc123');
```

## Scheduled Sub-Client

```php
$scheduled = $client->scheduled();

// Create a recurring job
$job = $scheduled->create([
    'url'      => 'https://example.com',
    'type'     => 'screenshot',       // "screenshot"|"pdf"|"scrape"|"extract"
    'schedule' => '0 9 * * *',        // cron expression
    'options'  => ['format' => 'png', 'full_page' => true],
    'webhook'  => 'https://myapp.com/hooks/snapapi',
    'name'     => 'Daily homepage screenshot',
]);

// List jobs
$jobs = $scheduled->list();

// Pause / resume
$scheduled->pause($job['id']);
$scheduled->resume($job['id']);

// Update
$scheduled->update($job['id'], ['schedule' => '0 8 * * *']);

// Delete
$scheduled->delete($job['id']);
```

## Webhooks Sub-Client

```php
$webhooks = $client->webhooks();

// Register an endpoint
$hook = $webhooks->create([
    'url'    => 'https://myapp.com/hooks/snapapi',
    'events' => ['screenshot.completed', 'pdf.completed'],
    'secret' => 'my-secret',
]);

// List registered webhooks
$webhooks->list();

// Update
$webhooks->update($hook['id'], ['events' => ['screenshot.completed']]);

// Delete
$webhooks->delete($hook['id']);
```

### Verifying Webhook Signatures

SnapAPI signs the raw request body with HMAC-SHA256 and sends the digest in the `X-SnapAPI-Signature` header:

```php
$rawBody  = file_get_contents('php://input');
$sig      = $_SERVER['HTTP_X_SNAPAPI_SIGNATURE'] ?? '';
$isValid  = $client->webhooks()->verifySignature($rawBody, $sig, 'my-secret');

if (!$isValid) {
    http_response_code(401);
    exit;
}

$event = json_decode($rawBody, true);
echo $event['event'];  // e.g. "screenshot.completed"
```

## API Keys Sub-Client

```php
$keys = $client->apiKeys();

// Create a new key
$key = $keys->create(['name' => 'ci-key', 'scopes' => ['screenshot']]);
echo $key['key'];  // save this -- only shown once

// List keys
$keys->list();

// Revoke
$keys->revoke($key['id']);
```

## Error Handling

All methods throw typed subclasses of `SnapAPIException`:

```php
use SnapAPI\Exceptions\AuthenticationException;
use SnapAPI\Exceptions\NetworkException;
use SnapAPI\Exceptions\QuotaException;
use SnapAPI\Exceptions\RateLimitException;
use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;

try {
    $image = $client->screenshot(['url' => 'https://example.com']);

} catch (RateLimitException $e) {
    // HTTP 429 -- server asked us to back off
    sleep($e->getRetryAfter() ?: 5);

} catch (AuthenticationException $e) {
    // HTTP 401/403 -- invalid or missing API key
    die("Auth failed: {$e->getMessage()}\n");

} catch (QuotaException $e) {
    // HTTP 402 -- monthly quota exhausted
    die("Quota exceeded. Upgrade your plan.\n");

} catch (ValidationException $e) {
    // HTTP 400 -- bad request parameters
    var_dump($e->getDetails());

} catch (NetworkException $e) {
    // cURL / transport error (no HTTP response)
    echo "Network error: {$e->getMessage()}\n";

} catch (SnapAPIException $e) {
    // All other API errors (5xx, etc.)
    echo "[{$e->getErrorCode()}] {$e->getMessage()} (HTTP {$e->getStatusCode()})\n";
}
```

### Exception Hierarchy

```
SnapAPIException          (base -- catch-all)
  NetworkException        Transport/cURL errors; statusCode = 0
  RateLimitException      HTTP 429; getRetryAfter(): int
  AuthenticationException HTTP 401/403
  QuotaException          HTTP 402
    QuotaExceededException  Alias (same behaviour)
  ValidationException     HTTP 400
```

### SnapAPIException Methods

| Method | Return | Description |
|---|---|---|
| `getMessage()` | `string` | Human-readable error description |
| `getErrorCode()` | `string` | Machine-readable code (e.g. `RATE_LIMITED`) |
| `getStatusCode()` | `int` | HTTP status code (0 for network errors) |
| `getDetails()` | `array\|null` | Structured detail array from the API |

### Error Codes

| Code | HTTP Status | Description |
|---|---|---|
| `INVALID_PARAMS` | 400 | Bad request parameters |
| `UNAUTHORIZED` | 401 | Invalid or missing API key |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `QUOTA_EXCEEDED` | 402 | Monthly quota exhausted |
| `RATE_LIMITED` | 429 | Rate limit exceeded |
| `TIMEOUT` | -- | Request timed out server-side |
| `CAPTURE_FAILED` | -- | Browser capture failed |
| `CONNECTION_ERROR` | -- | Network-level failure (cURL error) |
| `SERVER_ERROR` | 5xx | Unexpected server error |
| `SERVICE_UNAVAILABLE` | 503 | Service temporarily unavailable |
| `PARSE_ERROR` | -- | Non-JSON response received |

## Retry Behaviour

The SDK automatically retries on transient failures:

- **Retried:** 5xx errors, 429 rate limits
- **Not retried:** 4xx client errors (400, 401, 402, 403)
- **Backoff:** Exponential with configurable base delay (default 500ms, doubles each attempt)
- **Retry-After:** Honored when the server provides this header

Disable retries:

```bash
$client = new Client('sk_...', ['retries' => 0]);
```

## Running the Examples

```bash
export SNAPAPI_KEY=sk_live_your_key_here

php examples/screenshot.php
php examples/scrape.php
php examples/extract.php
php examples/analyze.php
php examples/pdf.php
php examples/video.php
php examples/webhooks.php
php examples/storage.php
php examples/advanced.php
```

## Running Tests

```bash
composer install
composer test
```

## Static Analysis

```bash
composer analyse
```

PHPStan level 8, zero errors.

## Links

- [SnapAPI Website](https://snapapi.pics)
- [API Documentation](https://snapapi.pics/docs)
- [GitHub Repository](https://github.com/Sleywill/snapapi-php)
- [Packagist](https://packagist.org/packages/snapapi/snapapi-php)
- [Changelog](./CHANGELOG.md)
- [Report Issues](https://github.com/Sleywill/snapapi-php/issues)

## License

MIT — see [LICENSE](LICENSE).

# snapapi-php

Official PHP SDK for [SnapAPI.pics](https://snapapi.pics) -- capture screenshots, generate PDFs, scrape pages, extract structured content, and analyze web pages with LLMs.

[![CI](https://github.com/Sleywill/snapapi-php/actions/workflows/ci.yml/badge.svg)](https://github.com/Sleywill/snapapi-php/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)

## Features

- **Screenshot** -- Capture full-page or viewport screenshots in PNG, JPEG, WebP
- **PDF** -- Generate PDFs from any URL with configurable paper size and margins
- **Scrape** -- Extract HTML, text, or JSON from any web page
- **Extract** -- Get clean, LLM-ready content in Markdown, text, or JSON
- **Analyze** -- Send page content to OpenAI, Anthropic, or Google LLMs
- **Video** -- Record short browser session videos
- **Usage** -- Monitor your API quota in real time
- **Retry with backoff** -- Automatic exponential backoff on 5xx and rate-limit errors
- **Typed exceptions** -- Rich exception hierarchy for precise error handling

## Requirements

- PHP 8.1+
- ext-curl
- ext-json

## Installation

```bash
composer require snapapi/sdk
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\SnapAPIException;

$client = new Client($_ENV['SNAPAPI_KEY']);

try {
    // Capture a screenshot
    $png = $client->screenshot([
        'url'       => 'https://example.com',
        'format'    => 'png',
        'full_page' => true,
    ]);
    file_put_contents('screenshot.png', $png);

    // Or save directly to a file
    $client->screenshotToFile('screenshot.png', [
        'url'    => 'https://example.com',
        'format' => 'png',
    ]);
} catch (SnapAPIException $e) {
    echo "[{$e->getErrorCode()}] {$e->getMessage()}\n";
}
```

## Configuration

```php
$client = new Client('sk_live_...', [
    // HTTP timeout in seconds (default: 30)
    'timeout'      => 45,
    // Number of retries on 5xx / rate-limit errors (default: 3)
    'retries'      => 3,
    // Base delay in milliseconds for exponential back-off (default: 500)
    'retryDelayMs' => 500,
    // Override the base URL (useful for testing)
    'baseUrl'      => 'https://api.snapapi.pics',
]);
```

## Complete API Reference

### Screenshot -- `POST /v1/screenshot`

Capture a screenshot of any URL. Returns raw image bytes.

```php
$image = $client->screenshot([
    'url'               => 'https://example.com',   // required
    'format'            => 'png',      // "png", "jpeg", "webp", or "pdf"
    'width'             => 1280,       // viewport width in pixels
    'height'            => 720,        // viewport height in pixels
    'full_page'         => true,       // capture entire scrollable page
    'delay'             => 500,        // ms to wait after page load
    'quality'           => 85,         // JPEG/WebP quality (1-100)
    'scale'             => 2.0,        // device scale factor (retina)
    'block_ads'         => true,       // enable ad blocking
    'wait_for_selector' => '.loaded',  // wait for CSS selector
    'selector'          => '#hero',    // capture only this element
    'scroll_y'          => 500,        // scroll down before capturing
    'custom_css'        => 'body { background: white; }',
    'custom_js'         => "document.querySelector('.popup')?.remove();",
    'user_agent'        => 'MyBot/1.0',
    'proxy'             => 'http://proxy:8080',
    'headers'           => ['Cookie' => 'session=abc'],
    'clip'              => ['x' => 0, 'y' => 0, 'w' => 800, 'h' => 600],
]);
file_put_contents('screenshot.png', $image);
```

### screenshotToFile

Convenience method that captures and writes directly to disk:

```php
$bytes = $client->screenshotToFile('output.png', [
    'url'       => 'https://example.com',
    'format'    => 'png',
    'full_page' => true,
]);
echo "Wrote {$bytes} bytes\n";
```

### Scrape -- `POST /v1/scrape`

Fetch HTML, text, or structured data from a URL:

```php
$result = $client->scrape([
    'url'               => 'https://example.com',  // required
    'selector'          => 'article',     // scope to CSS selector
    'format'            => 'html',        // "html", "text", or "json"
    'wait_for_selector' => '.content',    // wait for dynamic content
    'headers'           => ['Accept-Language' => 'en-US'],
    'proxy'             => 'http://proxy:8080',
]);
echo $result['data'];       // scraped content
echo $result['url'];        // final URL after redirects
echo $result['status'];     // HTTP status of the scraped page
```

### Extract -- `POST /v1/extract`

Extract clean, readable content optimized for LLM consumption:

```php
$result = $client->extract([
    'url'               => 'https://example.com/blog/post',  // required
    'format'            => 'markdown',    // "markdown", "text", or "json"
    'include_links'     => true,          // include hyperlinks (default: true)
    'include_images'    => false,         // include image refs (default: false)
    'selector'          => 'main',        // scope extraction
    'wait_for_selector' => '.loaded',
    'headers'           => ['Authorization' => 'Bearer token'],
]);
echo $result['content'];      // clean markdown/text
echo $result['word_count'];   // approximate word count
echo $result['url'];          // final URL
```

### Analyze -- `POST /v1/analyze`

Send a page to an LLM for analysis:

```php
$result = $client->analyze([
    'url'        => 'https://example.com',
    'prompt'     => 'Summarize this page in 3 bullet points.',
    'provider'   => 'openai',       // "openai", "anthropic", or "google"
    'apiKey'     => 'sk-...',       // your LLM provider API key
    'jsonSchema' => [
        'type' => 'object',
        'properties' => [
            'summary' => ['type' => 'string'],
        ],
    ],
]);
echo $result['result'];
```

> **Note:** The analyze endpoint may return HTTP 503 if LLM credits are exhausted.

### PDF -- `POST /v1/pdf`

Generate a PDF from any URL:

```php
$pdf = $client->pdf([
    'url'    => 'https://example.com',   // required
    'format' => 'a4',      // "a4" or "letter"
    'margin' => '10mm',    // page margins
]);
file_put_contents('output.pdf', $pdf);
```

### Video -- `POST /v1/video`

Record a short browser session video:

```php
$video = $client->video([
    'url'      => 'https://example.com',   // required
    'duration' => 5,       // seconds
    'format'   => 'mp4',   // "webm", "mp4", or "gif"
    'width'    => 1280,
    'height'   => 720,
]);
file_put_contents('capture.mp4', $video);
```

### getUsage -- `GET /v1/usage`

Check your current API usage:

```php
$usage = $client->getUsage();
echo "Used: {$usage['used']} / {$usage['total']} ({$usage['remaining']} remaining)\n";
```

## Error Handling

All methods throw typed subclasses of `SnapAPIException`:

```php
use SnapAPI\Exceptions\AuthenticationException;
use SnapAPI\Exceptions\QuotaException;
use SnapAPI\Exceptions\RateLimitException;
use SnapAPI\Exceptions\SnapAPIException;
use SnapAPI\Exceptions\ValidationException;

try {
    $image = $client->screenshot(['url' => 'https://example.com']);
} catch (RateLimitException $e) {
    // HTTP 429 -- back off and retry
    sleep($e->getRetryAfter() ?: 5);
} catch (AuthenticationException $e) {
    // HTTP 401/403 -- check your API key
    die("Auth failed: {$e->getMessage()}\n");
} catch (QuotaException $e) {
    // HTTP 402 -- monthly quota exhausted
    die("Quota exceeded.\n");
} catch (ValidationException $e) {
    // HTTP 400 -- bad request parameters
    var_dump($e->getDetails());
} catch (SnapAPIException $e) {
    // All other API errors (5xx, network, etc.)
    echo "[{$e->getErrorCode()}] {$e->getMessage()} (HTTP {$e->getStatusCode()})\n";
}
```

### Exception Hierarchy

```
SnapAPIException          (base -- catch-all)
  RateLimitException      HTTP 429; getRetryAfter(): int
  AuthenticationException HTTP 401/403
  QuotaException          HTTP 402
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
| `RATE_LIMITED` | 429 | Rate limit exceeded |
| `QUOTA_EXCEEDED` | 402 | Monthly quota exhausted |
| `TIMEOUT` | -- | Request timed out server-side |
| `CAPTURE_FAILED` | -- | Browser capture failed |
| `CONNECTION_ERROR` | -- | Network-level failure (cURL error) |
| `SERVER_ERROR` | 5xx | Unexpected server error |
| `SERVICE_UNAVAILABLE` | 503 | Service temporarily unavailable |

## Retry Behavior

The SDK automatically retries on transient failures:

- **Retried:** 5xx errors, 429 rate limits
- **Not retried:** 4xx client errors (400, 401, 403)
- **Backoff:** Exponential with configurable base delay (default 500ms)
- **Retry-After:** Honored when the server provides this header

Disable retries:

```php
$client = new Client('sk_...', ['retries' => 0]);
```

## Real-World Use Cases

### Website Monitoring

```php
// Capture screenshots of your sites on a schedule (cron job)
$urls = ['https://mysite.com', 'https://mysite.com/pricing'];
foreach ($urls as $url) {
    $filename = 'monitor_' . md5($url) . '_' . date('Y-m-d_His') . '.png';
    try {
        $client->screenshotToFile($filename, [
            'url'       => $url,
            'format'    => 'png',
            'full_page' => true,
        ]);
    } catch (SnapAPIException $e) {
        error_log("Monitor failed for {$url}: {$e->getMessage()}");
    }
}
```

### SEO Audit Tool

```php
// Extract content and analyze for SEO quality
$content = $client->extract([
    'url'    => 'https://example.com',
    'format' => 'text',
]);
echo "Page has {$content['word_count']} words\n";

// Use the analyze endpoint for deeper insights
$analysis = $client->analyze([
    'url'      => 'https://example.com',
    'prompt'   => 'Analyze this page for SEO issues. List missing meta tags and content problems.',
    'provider' => 'openai',
    'apiKey'   => $_ENV['OPENAI_API_KEY'],
]);
echo $analysis['result'];
```

### LLM Content Pipeline

```php
// Extract content from multiple pages and feed to your LLM pipeline
$urls = [
    'https://blog.example.com/post-1',
    'https://blog.example.com/post-2',
];
foreach ($urls as $url) {
    try {
        $result = $client->extract([
            'url'    => $url,
            'format' => 'markdown',
        ]);
        // Feed $result['content'] to your LLM, RAG pipeline, or embedding model
        echo "Extracted {$result['word_count']} words from {$result['url']}\n";
    } catch (SnapAPIException $e) {
        error_log("Failed: {$e->getMessage()}");
    }
}
```

### Competitor Price Tracking

```php
// Scrape competitor pricing pages
$result = $client->scrape([
    'url'               => 'https://competitor.com/pricing',
    'selector'          => '.pricing-table',
    'format'            => 'html',
    'wait_for_selector' => '.price',
]);
// Parse $result['data'] for price information
echo "Scraped " . strlen($result['data']) . " chars from pricing page\n";
```

### Social Media Thumbnail Generation

```php
// Generate OG images for social sharing
$client->screenshotToFile('og_twitter.png', [
    'url'    => 'https://mysite.com/blog/my-post',
    'format' => 'png',
    'width'  => 1200,
    'height' => 628,
    'clip'   => ['x' => 0, 'y' => 0, 'w' => 1200, 'h' => 628],
]);
```

### PDF Report Generation

```php
// Generate PDF invoices or reports
$pdf = $client->pdf([
    'url'    => 'https://myapp.com/invoice/12345',
    'format' => 'a4',
    'margin' => '15mm',
]);
file_put_contents('invoice_12345.pdf', $pdf);
```

## Running the Examples

```bash
export SNAPAPI_KEY=sk_live_your_key_here

php examples/screenshot.php
php examples/scrape.php
php examples/extract.php
php examples/analyze.php
php examples/advanced.php
```

## Running Tests

```bash
composer install
composer test
```

## Running Static Analysis

```bash
composer analyse
```

## License

MIT -- see [LICENSE](LICENSE).

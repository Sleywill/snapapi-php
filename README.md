# snapapi-php

Official PHP SDK for [SnapAPI.pics](https://snapapi.pics) — capture screenshots, generate PDFs, scrape pages, and extract structured content from any URL.

## Requirements

- PHP 8.1+
- ext-curl
- ext-json

## Installation

```bash
composer require snapapi/sdk
```

## Quick start

```php
<?php
require 'vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\RateLimitException;
use SnapAPI\Exceptions\SnapAPIException;

$client = new Client($_ENV['SNAPAPI_KEY']);

try {
    $png = $client->screenshot([
        'url'       => 'https://example.com',
        'format'    => 'png',
        'full_page' => true,
    ]);
    file_put_contents('screenshot.png', $png);
} catch (RateLimitException $e) {
    echo "Rate limited. Retry after {$e->getRetryAfter()}s\n";
} catch (SnapAPIException $e) {
    echo "[{$e->getErrorCode()}] {$e->getMessage()}\n";
}
```

## Configuration

```php
$client = new Client('sk_...', [
    // HTTP timeout in seconds (default: 30)
    'timeout'      => 45,
    // Number of retries on 5xx / rate-limit errors (default: 3)
    'retries'      => 3,
    // Base delay in milliseconds for exponential back-off (default: 500)
    'retryDelayMs' => 500,
    // Override the base URL (useful for testing)
    'baseUrl'      => 'https://snapapi.pics',
]);
```

## Endpoints

### Screenshot — `POST /v1/screenshot`

```php
$image = $client->screenshot([
    'url'       => 'https://example.com',
    'format'    => 'png',      // "png" or "jpeg"
    'full_page' => true,
    'width'     => 1280,
    'height'    => 720,
    'wait'      => 500,        // ms to wait after page load
    'quality'   => 85,         // JPEG quality (1-100)
    'selector'  => 'article',  // capture only this CSS element
]);
file_put_contents('screenshot.png', $image);
```

### Scrape — `POST /v1/scrape`

```php
$result = $client->scrape([
    'url'      => 'https://example.com',
    'selector' => 'main',   // optional CSS selector
    'wait'     => 1000,
]);
echo $result['text'];
```

### Extract — `POST /v1/extract`

```php
$result = $client->extract([
    'url'    => 'https://example.com',
    'format' => 'markdown',   // "markdown", "text", or "json"
]);
echo $result['content'];
```

### PDF — `POST /v1/pdf`

```php
$pdf = $client->pdf([
    'url'    => 'https://example.com',
    'format' => 'a4',     // "a4" or "letter"
    'margin' => '10mm',
]);
file_put_contents('output.pdf', $pdf);
```

### Video — `POST /v1/video`

```php
$video = $client->video([
    'url'      => 'https://example.com',
    'duration' => 5,
    'format'   => 'mp4',   // "webm", "mp4", or "gif"
    'width'    => 1280,
    'height'   => 720,
]);
file_put_contents('capture.mp4', $video);
```

### Quota — `GET /v1/quota`

```php
$quota = $client->quota();
echo "Used: {$quota['used']} / {$quota['total']} ({$quota['remaining']} remaining)\n";
```

## Error handling

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
    // HTTP 429 — back off and retry
    sleep($e->getRetryAfter() ?: 5);
} catch (AuthenticationException $e) {
    // HTTP 401/403 — check your API key
    die("Auth failed: {$e->getMessage()}\n");
} catch (QuotaException $e) {
    // HTTP 402 — monthly quota exhausted
    die("Quota exceeded.\n");
} catch (ValidationException $e) {
    // HTTP 400 — bad request parameters
    var_dump($e->getDetails());
} catch (SnapAPIException $e) {
    // All other API errors
    echo "[{$e->getErrorCode()}] {$e->getMessage()} (HTTP {$e->getStatusCode()})\n";
}
```

### Exception hierarchy

```
SnapAPIException          (base — catch-all)
├── RateLimitException    HTTP 429; getRetryAfter(): int
├── AuthenticationException HTTP 401/403
├── QuotaException        HTTP 402
└── ValidationException   HTTP 400
```

### SnapAPIException methods

| Method | Return | Description |
|---|---|---|
| `getMessage()` | `string` | Human-readable error description |
| `getErrorCode()` | `string` | Machine-readable code (e.g. `RATE_LIMITED`) |
| `getStatusCode()` | `int` | HTTP status code (0 for network errors) |
| `getDetails()` | `array\|null` | Structured detail array from the API |

### Error codes

| Code | Meaning |
|---|---|
| `INVALID_PARAMS` | Bad request parameters |
| `UNAUTHORIZED` | Invalid or missing API key |
| `FORBIDDEN` | Insufficient permissions |
| `RATE_LIMITED` | Rate limit exceeded |
| `QUOTA_EXCEEDED` | Monthly quota exhausted |
| `TIMEOUT` | Request timed out server-side |
| `CAPTURE_FAILED` | Browser capture failed |
| `CONNECTION_ERROR` | Network-level failure |
| `SERVER_ERROR` | Unexpected server error (5xx) |

## Running the examples

```bash
export SNAPAPI_KEY=sk_your_key

php examples/screenshot.php
php examples/scrape.php
php examples/extract.php
```

## Running tests

```bash
composer install
composer test
```

## Running static analysis

```bash
composer analyse
```

## License

MIT — see [LICENSE](LICENSE).

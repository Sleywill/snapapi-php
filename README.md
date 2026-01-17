# SnapAPI PHP SDK

Official PHP SDK for [SnapAPI](https://snapapi.dev) - Lightning-fast screenshot API for developers.

## Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension

## Installation

```bash
composer require snapapi/sdk
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use SnapAPI\Client;

$client = new Client('sk_live_xxx');

// Capture a screenshot
$screenshot = $client->screenshot(['url' => 'https://example.com']);

// Save to file
file_put_contents('screenshot.png', $screenshot);
```

## Usage Examples

### Basic Screenshot

```php
$screenshot = $client->screenshot(['url' => 'https://example.com']);
```

### Full Page Screenshot

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'fullPage' => true,
    'format' => 'png'
]);
```

### Mobile Screenshot

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'width' => 375,
    'height' => 812,
    'mobile' => true,
    'scale' => 3 // Retina
]);
```

### Dark Mode

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'darkMode' => true
]);
```

### PDF Export

```php
$pdf = $client->screenshot([
    'url' => 'https://example.com',
    'format' => 'pdf',
    'fullPage' => true
]);

file_put_contents('document.pdf', $pdf);
```

### Block Ads & Cookies

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'blockAds' => true,
    'hideCookieBanners' => true
]);
```

### Custom JavaScript Execution

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'javascript' => "document.querySelector('.popup')?.remove();",
    'delay' => 1000
]);
```

### With Cookies (Authenticated Pages)

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com/dashboard',
    'cookies' => [
        [
            'name' => 'session',
            'value' => 'abc123',
            'domain' => 'example.com'
        ]
    ]
]);
```

### Get JSON Response with Metadata

```php
$result = $client->screenshot([
    'url' => 'https://example.com',
    'responseType' => 'json'
]);

echo $result['width'];     // 1920
echo $result['height'];    // 1080
echo $result['fileSize'];  // 45321
echo $result['duration'];  // 523
echo $result['data'];      // base64 encoded image
```

### Batch Screenshots

```php
$batch = $client->batch([
    'urls' => [
        'https://example.com',
        'https://example.org',
        'https://example.net'
    ],
    'format' => 'png',
    'webhookUrl' => 'https://your-server.com/webhook'
]);

echo $batch['jobId']; // 'job_abc123'

// Check status later
$status = $client->getBatchStatus($batch['jobId']);
if ($status['status'] === 'completed') {
    print_r($status['results']);
}
```

## Configuration Options

### Client Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `apiKey` | string | *required* | Your API key |
| `baseUrl` | string | `https://api.snapapi.dev` | API base URL |
| `timeout` | int | `60` | Request timeout in seconds |

### Screenshot Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `url` | string | *required* | URL to capture |
| `format` | string | `'png'` | `'png'`, `'jpeg'`, `'webp'`, `'pdf'` |
| `width` | int | `1920` | Viewport width (100-3840) |
| `height` | int | `1080` | Viewport height (100-2160) |
| `fullPage` | bool | `false` | Capture full scrollable page |
| `quality` | int | `80` | Image quality 1-100 (JPEG/WebP) |
| `scale` | float | `1.0` | Device scale factor 0.5-3 |
| `delay` | int | `0` | Delay before capture (0-10000ms) |
| `timeout` | int | `30000` | Max wait time (1000-60000ms) |
| `darkMode` | bool | `false` | Emulate dark mode |
| `mobile` | bool | `false` | Emulate mobile device |
| `selector` | string | `null` | CSS selector for element capture |
| `waitForSelector` | string | `null` | Wait for element before capture |
| `javascript` | string | `null` | JS to execute before capture |
| `blockAds` | bool | `false` | Block ads and trackers |
| `hideCookieBanners` | bool | `false` | Hide cookie banners |
| `cookies` | array | `null` | Cookies to set |
| `headers` | array | `null` | Custom HTTP headers |
| `responseType` | string | `'binary'` | `'binary'`, `'base64'`, `'json'` |

## Error Handling

```php
use SnapAPI\Client;
use SnapAPI\Exception\SnapAPIException;

try {
    $client->screenshot(['url' => 'invalid-url']);
} catch (SnapAPIException $e) {
    echo $e->getErrorCode();  // 'INVALID_URL'
    echo $e->getStatusCode(); // 400
    echo $e->getMessage();    // 'The provided URL is not valid'
    print_r($e->getDetails()); // ['url' => 'invalid-url']
}
```

### Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `INVALID_URL` | 400 | URL is malformed or not accessible |
| `INVALID_PARAMS` | 400 | One or more parameters are invalid |
| `UNAUTHORIZED` | 401 | Missing or invalid API key |
| `FORBIDDEN` | 403 | API key doesn't have permission |
| `QUOTA_EXCEEDED` | 429 | Monthly quota exceeded |
| `RATE_LIMITED` | 429 | Too many requests |
| `TIMEOUT` | 504 | Page took too long to load |
| `CAPTURE_FAILED` | 500 | Screenshot capture failed |

## License

MIT

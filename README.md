# SnapAPI PHP SDK

Official PHP SDK for [SnapAPI](https://snapapi.pics) - Lightning-fast screenshot API for developers.

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

### Device Presets

Capture screenshots using pre-configured device settings:

```php
// Using device preset
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'device' => 'iphone-15-pro'
]);

// Or use the convenience method
$screenshot = $client->screenshotDevice('https://example.com', 'ipad-pro-12.9');

// Get all available device presets
$devices = $client->getDevices();
print_r($devices['devices']); // Grouped by: desktop, mac, iphone, ipad, android
```

Available device presets:
- **Desktop**: `desktop-1080p`, `desktop-1440p`, `desktop-4k`
- **Mac**: `macbook-pro-13`, `macbook-pro-16`, `imac-24`
- **iPhone**: `iphone-se`, `iphone-12`, `iphone-13`, `iphone-14`, `iphone-14-pro`, `iphone-15`, `iphone-15-pro`, `iphone-15-pro-max`
- **iPad**: `ipad`, `ipad-mini`, `ipad-air`, `ipad-pro-11`, `ipad-pro-12.9`
- **Android**: `pixel-7`, `pixel-8`, `pixel-8-pro`, `samsung-galaxy-s23`, `samsung-galaxy-s24`, `samsung-galaxy-tab-s9`

### Dark Mode

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'darkMode' => true
]);
```

### Screenshot from HTML

```php
$html = '<html><body><h1>Hello World</h1></body></html>';
$screenshot = $client->screenshotFromHtml($html, [
    'width' => 800,
    'height' => 600
]);
```

### PDF Export

```php
$pdf = $client->pdf([
    'url' => 'https://example.com',
    'pdfOptions' => [
        'pageSize' => 'a4',
        'landscape' => false,
        'marginTop' => '20mm',
        'marginBottom' => '20mm',
        'marginLeft' => '15mm',
        'marginRight' => '15mm',
        'printBackground' => true,
        'displayHeaderFooter' => true,
        'headerTemplate' => '<div style="font-size:10px;text-align:center;width:100%;">Header</div>',
        'footerTemplate' => '<div style="font-size:10px;text-align:center;width:100%;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>'
    ]
]);

file_put_contents('document.pdf', $pdf);
```

### Geolocation Emulation

```php
$screenshot = $client->screenshot([
    'url' => 'https://maps.google.com',
    'geolocation' => [
        'latitude' => 48.8566,
        'longitude' => 2.3522,
        'accuracy' => 100
    ]
]);
```

### Timezone & Locale

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'timezone' => 'America/New_York',
    'locale' => 'en-US'
]);
```

### Proxy Support

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'proxy' => [
        'server' => 'http://proxy.example.com:8080',
        'username' => 'user',
        'password' => 'pass'
    ]
]);
```

### Hide Elements

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'hideSelectors' => [
        '.cookie-banner',
        '#popup-modal',
        '.advertisement'
    ]
]);
```

### Click Before Screenshot

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'clickSelector' => '.accept-cookies-button',
    'clickDelay' => 500, // Wait 500ms after clicking
    'delay' => 1000 // Then wait another 1s before screenshot
]);
```

### Block Ads, Trackers, Chat Widgets

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'blockAds' => true,
    'blockTrackers' => true,
    'blockCookieBanners' => true,
    'blockChatWidgets' => true // Blocks Intercom, Drift, Zendesk, etc.
]);
```

### Thumbnail Generation

```php
$result = $client->screenshot([
    'url' => 'https://example.com',
    'thumbnail' => [
        'enabled' => true,
        'width' => 300,
        'height' => 200,
        'fit' => 'cover' // 'cover', 'contain', or 'fill'
    ],
    'responseType' => 'json'
]);

// Access both full image and thumbnail
$fullImage = base64_decode($result['data']);
$thumbnail = base64_decode($result['thumbnail']);
```

### Fail on HTTP Errors

```php
try {
    $screenshot = $client->screenshot([
        'url' => 'https://example.com/404-page',
        'failOnHttpError' => true // Will throw if page returns 4xx or 5xx
    ]);
} catch (SnapAPIException $e) {
    echo "Page returned HTTP error";
}
```

### Custom JavaScript Execution

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'javascript' => "document.querySelector('.popup')?.remove();",
    'delay' => 1000
]);
```

### Custom CSS

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'css' => '
        body { background: #f0f0f0 !important; }
        .ads, .banner { display: none !important; }
    '
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

### HTTP Basic Authentication

```php
$screenshot = $client->screenshot([
    'url' => 'https://example.com/protected',
    'httpAuth' => [
        'username' => 'user',
        'password' => 'pass'
    ]
]);
```

### Element Screenshot with Clipping

```php
// Capture specific element
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'selector' => '.hero-section'
]);

// Or use manual clipping
$screenshot = $client->screenshot([
    'url' => 'https://example.com',
    'clipX' => 100,
    'clipY' => 100,
    'clipWidth' => 500,
    'clipHeight' => 300
]);
```

### Extract Metadata

```php
$result = $client->screenshot([
    'url' => 'https://example.com',
    'responseType' => 'json',
    'includeMetadata' => true,
    'extractMetadata' => [
        'fonts' => true,
        'colors' => true,
        'links' => true,
        'httpStatusCode' => true
    ]
]);

echo "Title: " . $result['metadata']['title'];
echo "HTTP Status: " . $result['metadata']['httpStatusCode'];
print_r($result['metadata']['fonts']); // List of fonts used
print_r($result['metadata']['colors']); // Dominant colors
print_r($result['metadata']['links']); // All links on page
```

### Get JSON Response with Metadata

```php
$result = $client->screenshot([
    'url' => 'https://example.com',
    'responseType' => 'json',
    'includeMetadata' => true
]);

echo $result['width'];     // 1920
echo $result['height'];    // 1080
echo $result['fileSize'];  // 45321
echo $result['took'];      // 523 (milliseconds)
echo $result['data'];      // base64 encoded image
print_r($result['metadata']); // Page metadata
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

echo $batch['jobId']; // 'batch_abc123'

// Check status later
$status = $client->getBatchStatus($batch['jobId']);
if ($status['status'] === 'completed') {
    foreach ($status['results'] as $result) {
        if ($result['status'] === 'completed') {
            file_put_contents(
                basename(parse_url($result['url'], PHP_URL_HOST)) . '.png',
                base64_decode($result['data'])
            );
        }
    }
}
```

### Screenshot from Markdown

```php
$markdown = "# Hello World\n\nThis is **bold** and this is *italic*.";
$screenshot = $client->screenshotFromMarkdown($markdown, [
    'width' => 800,
    'height' => 600
]);

file_put_contents('markdown.png', $screenshot);
```

### Extract Content

Extract structured content from any webpage:

```php
// Full control with extract()
$result = $client->extract([
    'url' => 'https://example.com',
    'format' => 'markdown'
]);

echo $result['content'];
```

#### Convenience Methods

```php
// Extract as Markdown
$result = $client->extractMarkdown('https://example.com/blog-post');
echo $result['content'];

// Extract article content (removes nav, ads, etc.)
$result = $client->extractArticle('https://example.com/blog-post');
echo $result['content'];

// Extract structured data
$result = $client->extractStructured('https://example.com');
print_r($result['content']);

// Extract plain text
$result = $client->extractText('https://example.com');
echo $result['content'];

// Extract all links
$result = $client->extractLinks('https://example.com');
print_r($result['content']); // Array of links

// Extract all images
$result = $client->extractImages('https://example.com');
print_r($result['content']); // Array of image URLs

// Extract page metadata (title, description, Open Graph, etc.)
$result = $client->extractMetadata('https://example.com');
print_r($result['content']);
```

#### Extract with Options

```php
$result = $client->extract([
    'url' => 'https://example.com',
    'format' => 'markdown',
    'selector' => '.main-content',
    'blockAds' => true,
    'blockCookieBanners' => true,
    'cookies' => [
        ['name' => 'session', 'value' => 'abc123', 'domain' => 'example.com']
    ]
]);
```

### Analyze with AI

Analyze a webpage using AI vision:

```php
$result = $client->analyze([
    'url' => 'https://example.com',
    'prompt' => 'Describe the layout and design of this page'
]);

echo $result['analysis'];
```

```php
// Analyze with custom viewport
$result = $client->analyze([
    'url' => 'https://example.com',
    'prompt' => 'List all call-to-action buttons on this page',
    'device' => 'iphone-15-pro',
    'darkMode' => true,
    'blockAds' => true
]);

echo $result['analysis'];
```

### Get API Capabilities

```php
$capabilities = $client->getCapabilities();
print_r($capabilities['capabilities']['features']);
```

## Configuration Options

### Client Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `apiKey` | string | *required* | Your API key |
| `baseUrl` | string | `https://api.snapapi.pics` | API base URL |
| `timeout` | int | `60` | Request timeout in seconds |

### Screenshot Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `url` | string | - | URL to capture (required if no html/markdown) |
| `html` | string | - | HTML content to render (required if no url/markdown) |
| `markdown` | string | - | Markdown content to render (required if no url/html) |
| `format` | string | `'png'` | `'png'`, `'jpeg'`, `'webp'`, `'avif'`, `'pdf'` |
| `quality` | int | `80` | Image quality 1-100 (JPEG/WebP) |
| `device` | string | - | Device preset name |
| `width` | int | `1280` | Viewport width (100-3840) |
| `height` | int | `800` | Viewport height (100-2160) |
| `deviceScaleFactor` | float | `1` | Device pixel ratio (1-3) |
| `isMobile` | bool | `false` | Emulate mobile device |
| `hasTouch` | bool | `false` | Enable touch events |
| `isLandscape` | bool | `false` | Landscape orientation |
| `fullPage` | bool | `false` | Capture full scrollable page |
| `fullPageScrollDelay` | int | `400` | Delay between scroll steps (ms) |
| `fullPageMaxHeight` | int | - | Max height for full page (px) |
| `selector` | string | - | CSS selector for element capture |
| `clipX`, `clipY` | int | - | Clip region position |
| `clipWidth`, `clipHeight` | int | - | Clip region size |
| `delay` | int | `0` | Delay before capture (0-30000ms) |
| `timeout` | int | `30000` | Max wait time (1000-60000ms) |
| `waitUntil` | string | `'load'` | `'load'`, `'domcontentloaded'`, `'networkidle'` |
| `waitForSelector` | string | - | Wait for element before capture |
| `darkMode` | bool | `false` | Emulate dark mode |
| `reducedMotion` | bool | `false` | Reduce animations |
| `css` | string | - | Custom CSS to inject |
| `javascript` | string | - | JS to execute before capture |
| `hideSelectors` | array | - | CSS selectors to hide |
| `clickSelector` | string | - | Element to click before capture |
| `clickDelay` | int | - | Delay after click (ms) |
| `blockAds` | bool | `false` | Block ads |
| `blockTrackers` | bool | `false` | Block trackers |
| `blockCookieBanners` | bool | `false` | Hide cookie banners |
| `blockChatWidgets` | bool | `false` | Block chat widgets |
| `blockResources` | array | - | Resource types to block |
| `userAgent` | string | - | Custom User-Agent |
| `extraHeaders` | array | - | Custom HTTP headers |
| `cookies` | array | - | Cookies to set |
| `httpAuth` | array | - | HTTP basic auth credentials |
| `proxy` | array | - | Proxy configuration |
| `geolocation` | array | - | Geolocation coordinates |
| `timezone` | string | - | Timezone (e.g., 'America/New_York') |
| `locale` | string | - | Locale (e.g., 'en-US') |
| `pdfOptions` | array | - | PDF generation options |
| `thumbnail` | array | - | Thumbnail generation options |
| `failOnHttpError` | bool | `false` | Fail on 4xx/5xx responses |
| `cache` | bool | `false` | Enable caching |
| `cacheTtl` | int | `86400` | Cache TTL in seconds |
| `responseType` | string | `'binary'` | `'binary'`, `'base64'`, `'json'` |
| `includeMetadata` | bool | `false` | Include page metadata |
| `extractMetadata` | array | - | Additional metadata to extract |

### PDF Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `pageSize` | string | `'a4'` | `'a4'`, `'a3'`, `'a5'`, `'letter'`, `'legal'`, `'tabloid'`, `'custom'` |
| `width` | string | - | Custom width (e.g., '210mm') |
| `height` | string | - | Custom height (e.g., '297mm') |
| `landscape` | bool | `false` | Landscape orientation |
| `marginTop` | string | - | Top margin (e.g., '20mm') |
| `marginRight` | string | - | Right margin |
| `marginBottom` | string | - | Bottom margin |
| `marginLeft` | string | - | Left margin |
| `printBackground` | bool | `true` | Print background graphics |
| `headerTemplate` | string | - | HTML template for header |
| `footerTemplate` | string | - | HTML template for footer |
| `displayHeaderFooter` | bool | `false` | Show header/footer |
| `scale` | float | `1` | Scale (0.1-2) |
| `pageRanges` | string | - | Page ranges (e.g., '1-5') |
| `preferCSSPageSize` | bool | `false` | Use CSS page size |

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
| `HTTP_ERROR` | varies | Page returned HTTP error (with failOnHttpError) |

## License

MIT

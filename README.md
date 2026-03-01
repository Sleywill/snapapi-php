# snapapi-php

Official PHP SDK (v2.0.0) for [SnapAPI](https://snapapi.pics) — lightning-fast screenshot, PDF, scrape, extract, and AI web analysis API.

## Requirements

- PHP 8.0+
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

$api = new \SnapAPI\SnapAPI('your-api-key');

// Take a screenshot
$png = $api->screenshot([
    'url'    => 'https://example.com',
    'format' => 'png',
]);
file_put_contents('screenshot.png', $png);
```

## Authentication

Pass your API key to the constructor:

```php
$api = new \SnapAPI\SnapAPI($_ENV['SNAPAPI_KEY']);
```

## Endpoints

### Screenshot — `POST /v1/screenshot`

```php
// Basic PNG screenshot
$png = $api->screenshot([
    'url'    => 'https://example.com',
    'format' => 'png',
    'width'  => 1440,
    'height' => 900,
]);

// Full-page dark mode screenshot
$img = $api->screenshot([
    'url'                => 'https://example.com',
    'fullPage'           => true,
    'darkMode'           => true,
    'blockAds'           => true,
    'blockCookieBanners' => true,
]);

// Screenshot from HTML
$img = $api->screenshotFromHtml('<h1 style="color:blue">Hello!</h1>');

// Screenshot from Markdown
$img = $api->screenshotFromMarkdown('# Title\n\nContent here.');

// With device emulation
$mobile = $api->screenshot([
    'url'    => 'https://example.com',
    'device' => 'iphone-15-pro',
]);
```

### PDF — `POST /v1/screenshot` (format=pdf)

```php
$pdf = $api->pdf([
    'url' => 'https://example.com',
    'pdf' => [
        'pageSize'  => 'A4',
        'landscape' => false,
        'marginTop' => '20px',
    ],
]);
file_put_contents('page.pdf', $pdf);

// PDF from HTML
$pdf = $api->pdf([
    'html' => '<h1>Report</h1><p>Content</p>',
    'pdf'  => ['pageSize' => 'Letter'],
]);
```

### Screenshot to Storage

```php
$result = $api->screenshotToStorage([
    'url'     => 'https://example.com',
    'format'  => 'png',
    'storage' => ['destination' => 's3'],
]);
echo $result['url']; // Public S3 URL
```

### Scrape — `POST /v1/scrape`

```php
$result = $api->scrape([
    'url'   => 'https://example.com',
    'type'  => 'text',   // text|html|links
    'pages' => 3,
]);

foreach ($result['results'] as $page) {
    echo "Page {$page['page']}: {$page['url']}\n";
    echo substr($page['data'], 0, 100) . "...\n";
}
```

### Extract — `POST /v1/extract`

```php
// Article extraction
$article = $api->extractArticle('https://example.com/post');

// Markdown
$md = $api->extractMarkdown('https://example.com');

// Links
$links = $api->extractLinks('https://example.com');

// Images
$images = $api->extractImages('https://example.com');

// Metadata (title, description, OG tags…)
$meta = $api->extractMetadata('https://example.com');

// Structured data
$structured = $api->extractStructured('https://example.com');

// Full control
$result = $api->extract([
    'url'           => 'https://example.com',
    'type'          => 'article',
    'includeImages' => true,
    'maxLength'     => 5000,
]);
```

### Analyze — `POST /v1/analyze`

```php
$result = $api->analyze([
    'url'               => 'https://example.com',
    'prompt'            => 'What is the main purpose of this page?',
    'provider'          => 'openai',   // or 'anthropic'
    'apiKey'            => 'sk-...',   // your LLM API key
    'includeScreenshot' => true,
    'includeMetadata'   => true,
]);
echo $result['analysis'];
```

### Storage — `/v1/storage/*`

```php
// List files
$files = $api->listStorageFiles();

// Usage
$usage = $api->getStorageUsage();
echo "Used: {$usage['used']} / {$usage['limit']} bytes\n";

// Configure S3
$api->configureS3([
    'bucket'          => 'my-bucket',
    'region'          => 'us-east-1',
    'accessKeyId'     => 'AKIA...',
    'secretAccessKey' => '...',
]);

// Delete a file
$api->deleteStorageFile('file-id');
```

### Scheduled — `/v1/scheduled/*`

```php
// Create hourly screenshot job
$job = $api->createScheduled([
    'url'            => 'https://example.com',
    'cronExpression' => '0 * * * *',
    'format'         => 'png',
    'fullPage'       => true,
    'webhookUrl'     => 'https://myapp.com/webhook',
]);
echo $job['id'];

// List all jobs
$jobs = $api->listScheduled();

// Delete a job
$api->deleteScheduled($job['id']);
```

### Webhooks — `/v1/webhooks/*`

```php
// Create webhook
$hook = $api->createWebhook([
    'url'    => 'https://myapp.com/snapapi',
    'events' => ['screenshot.completed', 'scheduled.run'],
    'secret' => 'my-secret',
]);

// List
$hooks = $api->listWebhooks();

// Delete
$api->deleteWebhook($hook['id']);
```

### API Keys — `/v1/keys/*`

```php
// List
$keys = $api->listKeys();

// Create
$key = $api->createKey('production');
echo $key['key']; // Only shown once

// Revoke
$api->deleteKey($key['id']);
```

## Error Handling

```php
use SnapAPI\Exception\SnapAPIException;

try {
    $img = $api->screenshot(['url' => 'https://example.com']);
} catch (SnapAPIException $e) {
    echo $e->getErrorCode();   // e.g. "RATE_LIMITED"
    echo $e->getStatusCode();  // e.g. 429
    echo $e->getMessage();     // human-readable message
}
```

## Running the Example

```bash
SNAPAPI_KEY=your-key php examples/basic.php
```

## License

MIT

<?php

declare(strict_types=1);

/**
 * Advanced use cases demonstrating real-world SnapAPI patterns.
 *
 * - Website monitoring with automated screenshots
 * - SEO content auditing
 * - PDF report generation
 * - Social media thumbnail generation
 * - Competitor price tracking
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\SnapAPIException;

$apiKey = getenv('SNAPAPI_KEY');
if (!$apiKey) {
    fwrite(STDERR, "SNAPAPI_KEY environment variable is required.\n");
    exit(1);
}

$client = new Client($apiKey, [
    'timeout' => 60,
    'retries' => 3,
]);

// --- Use case 1: Website monitoring ---
echo "=== Website Monitoring ===\n";
$urls = ['https://example.com', 'https://example.org'];
foreach ($urls as $url) {
    try {
        $filename = 'monitor_' . preg_replace('/[^a-z0-9]/', '_', strtolower($url)) . '.png';
        $bytes = $client->screenshotToFile($filename, [
            'url'       => $url,
            'format'    => 'png',
            'full_page' => true,
            'width'     => 1280,
        ]);
        echo "Captured {$url} -> {$filename} ({$bytes} bytes)\n";
    } catch (SnapAPIException $e) {
        fwrite(STDERR, "Failed {$url}: [{$e->getErrorCode()}] {$e->getMessage()}\n");
    }
}

// --- Use case 2: SEO content extraction ---
echo "\n=== SEO Content Extraction ===\n";
try {
    $content = $client->extract([
        'url'    => 'https://example.com',
        'format' => 'text',
    ]);
    echo "Word count: {$content['word_count']}\n";
    $preview = substr($content['content'], 0, 200);
    echo "Preview: {$preview}...\n";
} catch (SnapAPIException $e) {
    fwrite(STDERR, "Extract failed: {$e->getMessage()}\n");
}

// --- Use case 3: PDF report generation ---
echo "\n=== PDF Report Generation ===\n";
try {
    $pdf = $client->pdf([
        'url'    => 'https://example.com',
        'format' => 'a4',
        'margin' => '15mm',
    ]);
    file_put_contents('report.pdf', $pdf);
    echo 'Saved report.pdf (' . strlen($pdf) . " bytes)\n";
} catch (SnapAPIException $e) {
    fwrite(STDERR, "PDF failed: {$e->getMessage()}\n");
}

// --- Use case 4: Social media thumbnail ---
echo "\n=== Social Media Thumbnail ===\n";
try {
    $bytes = $client->screenshotToFile('og_image.png', [
        'url'    => 'https://example.com',
        'format' => 'png',
        'width'  => 1200,
        'height' => 630,
        'clip'   => ['x' => 0, 'y' => 0, 'w' => 1200, 'h' => 630],
    ]);
    echo "Saved og_image.png (1200x630, {$bytes} bytes)\n";
} catch (SnapAPIException $e) {
    fwrite(STDERR, "Thumbnail failed: {$e->getMessage()}\n");
}

// --- Use case 5: Competitor scraping ---
echo "\n=== Competitor Scraping ===\n";
try {
    $result = $client->scrape([
        'url'      => 'https://example.com',
        'selector' => 'body',
        'format'   => 'text',
    ]);
    echo "Scraped {$result['url']} (status {$result['status']}, " . strlen($result['data']) . " chars)\n";
} catch (SnapAPIException $e) {
    fwrite(STDERR, "Scrape failed: [{$e->getErrorCode()}] {$e->getMessage()}\n");
}

// --- Check usage ---
echo "\n=== Usage ===\n";
try {
    $usage = $client->getUsage();
    echo "API Usage: {$usage['used']} / {$usage['total']} ({$usage['remaining']} remaining)\n";
} catch (SnapAPIException $e) {
    fwrite(STDERR, "Could not fetch usage: {$e->getMessage()}\n");
}

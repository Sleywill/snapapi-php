<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SnapAPI\SnapAPI;

$apiKey = getenv('SNAPAPI_KEY') ?: 'your-api-key';
$api    = new SnapAPI($apiKey);

// ── Screenshot ─────────────────────────────────────────────────────────────
echo "Taking screenshot...\n";
$png = $api->screenshot([
    'url'      => 'https://example.com',
    'format'   => 'png',
    'fullPage' => true,
    'width'    => 1280,
    'height'   => 720,
]);
file_put_contents(__DIR__ . '/screenshot.png', $png);
echo 'Saved screenshot.png (' . strlen($png) . " bytes)\n";

// ── PDF ────────────────────────────────────────────────────────────────────
echo "Generating PDF...\n";
$pdf = $api->pdf([
    'url' => 'https://example.com',
    'pdf' => ['pageSize' => 'A4', 'landscape' => false],
]);
file_put_contents(__DIR__ . '/page.pdf', $pdf);
echo 'Saved page.pdf (' . strlen($pdf) . " bytes)\n";

// ── Scrape ─────────────────────────────────────────────────────────────────
echo "Scraping...\n";
$scrape = $api->scrape([
    'url'  => 'https://example.com',
    'type' => 'text',
]);
foreach ($scrape['results'] as $item) {
    echo "Page {$item['page']}: " . strlen($item['data']) . " chars\n";
}

// ── Extract ────────────────────────────────────────────────────────────────
echo "Extracting article...\n";
$article = $api->extractArticle('https://example.com');
echo "Type: {$article['type']}, response time: {$article['responseTime']}ms\n";

// ── Analyze ────────────────────────────────────────────────────────────────
// echo "Analyzing page...\n";
// $analysis = $api->analyze([
//     'url'      => 'https://example.com',
//     'prompt'   => 'What is the main purpose of this page?',
//     'provider' => 'openai',
//     'apiKey'   => 'sk-...',
// ]);
// echo "Analysis: {$analysis['analysis']}\n";

// ── List API Keys ──────────────────────────────────────────────────────────
echo "Listing keys...\n";
$keys = $api->listKeys();
foreach ($keys['keys'] as $key) {
    echo "  {$key['name']} (id={$key['id']})\n";
}

// ── Scheduled ──────────────────────────────────────────────────────────────
echo "Creating scheduled job...\n";
$job = $api->createScheduled([
    'url'            => 'https://example.com',
    'cronExpression' => '0 * * * *',
    'format'         => 'png',
    'fullPage'       => true,
]);
echo "Created job: {$job['id']}\n";
$api->deleteScheduled($job['id']);
echo "Deleted job.\n";

echo "\nDone!\n";

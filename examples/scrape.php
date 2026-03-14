<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\SnapAPIException;

$apiKey = getenv('SNAPAPI_KEY');
if (!$apiKey) {
    fwrite(STDERR, "SNAPAPI_KEY environment variable is required.\n");
    exit(1);
}

$client = new Client($apiKey);

try {
    $result = $client->scrape([
        'url' => 'https://example.com',
    ]);

    echo "URL: {$result['url']}\n";
    echo 'Text (' . strlen($result['text'] ?? '') . " chars):\n";
    echo ($result['text'] ?? '') . "\n";

} catch (SnapAPIException $e) {
    fwrite(STDERR, "[{$e->getErrorCode()}] {$e->getMessage()}\n");
    exit(1);
}

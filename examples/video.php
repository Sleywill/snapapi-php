<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Enums\VideoFormat;
use SnapAPI\Exceptions\SnapAPIException;

$apiKey = getenv('SNAPAPI_KEY');
if (!$apiKey) {
    fwrite(STDERR, "SNAPAPI_KEY environment variable is required.\n");
    exit(1);
}

$client = new Client($apiKey, ['timeout' => 120]);

try {
    $videoBytes = $client->video([
        'url'      => 'https://example.com',
        'duration' => 5,
        'format'   => VideoFormat::Mp4->value,
        'width'    => 1280,
        'height'   => 720,
    ]);

    file_put_contents('capture.mp4', $videoBytes);
    echo 'Saved capture.mp4 (' . strlen($videoBytes) . " bytes)\n";
} catch (SnapAPIException $e) {
    fwrite(STDERR, "[{$e->getErrorCode()}] {$e->getMessage()}\n");
    exit(1);
}

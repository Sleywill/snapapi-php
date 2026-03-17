<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Enums\PdfPageFormat;
use SnapAPI\Exceptions\SnapAPIException;

$apiKey = getenv('SNAPAPI_KEY');
if (!$apiKey) {
    fwrite(STDERR, "SNAPAPI_KEY environment variable is required.\n");
    exit(1);
}

$client = new Client($apiKey, ['timeout' => 45]);

try {
    $bytes = $client->pdfToFile('output.pdf', [
        'url'    => 'https://example.com',
        'format' => PdfPageFormat::A4->value,
        'margin' => '15mm',
    ]);
    echo "Saved output.pdf ({$bytes} bytes)\n";
} catch (SnapAPIException $e) {
    fwrite(STDERR, "[{$e->getErrorCode()}] {$e->getMessage()}\n");
    exit(1);
}

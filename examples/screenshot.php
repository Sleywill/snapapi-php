<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\AuthenticationException;
use SnapAPI\Exceptions\RateLimitException;
use SnapAPI\Exceptions\SnapAPIException;

$apiKey = getenv('SNAPAPI_KEY');
if (!$apiKey) {
    fwrite(STDERR, "SNAPAPI_KEY environment variable is required.\n");
    exit(1);
}

$client = new Client($apiKey, [
    'timeout' => 30,
    'retries' => 3,
]);

try {
    // Take a full-page screenshot
    $image = $client->screenshot([
        'url'       => 'https://example.com',
        'format'    => 'png',
        'full_page' => true,
        'width'     => 1280,
        'height'    => 720,
    ]);

    file_put_contents('screenshot.png', $image);
    echo 'Saved screenshot.png (' . strlen($image) . " bytes)\n";

    // Or use the convenience method
    $bytes = $client->screenshotToFile('screenshot2.png', [
        'url'       => 'https://example.com',
        'format'    => 'png',
        'block_ads' => true,
    ]);
    echo "Saved screenshot2.png ({$bytes} bytes)\n";

    // Show usage
    $usage = $client->getUsage();
    echo "Usage: {$usage['used']} used / {$usage['total']} total ({$usage['remaining']} remaining)\n";

} catch (RateLimitException $e) {
    fwrite(STDERR, "Rate limited. Retry after {$e->getRetryAfter()} seconds.\n");
    exit(1);
} catch (AuthenticationException $e) {
    fwrite(STDERR, "Authentication failed: {$e->getMessage()}\n");
    exit(1);
} catch (SnapAPIException $e) {
    fwrite(STDERR, "[{$e->getErrorCode()}] {$e->getMessage()} (HTTP {$e->getStatusCode()})\n");
    exit(1);
}

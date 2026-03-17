<?php

declare(strict_types=1);

/**
 * Webhooks example.
 *
 * Demonstrates registering a webhook endpoint, listing webhooks,
 * and verifying incoming webhook signatures.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\SnapAPIException;

$apiKey = getenv('SNAPAPI_KEY');
if (!$apiKey) {
    fwrite(STDERR, "SNAPAPI_KEY environment variable is required.\n");
    exit(1);
}

$client   = new Client($apiKey);
$webhooks = $client->webhooks();

try {
    // Register a new webhook
    $hook = $webhooks->create([
        'url'    => 'https://myapp.com/hooks/snapapi',
        'events' => ['screenshot.completed', 'pdf.completed'],
        'secret' => 'my-webhook-secret',
        'name'   => 'My App Webhook',
    ]);
    echo "Created webhook: {$hook['id']}\n";

    // List all webhooks
    $list = $webhooks->list();
    echo "Total webhooks: {$list['total']}\n";

    // Verify a signature (in your actual webhook handler)
    $rawBody  = '{"event":"screenshot.completed","id":"job_abc"}';
    $sig      = 'sha256=' . hash_hmac('sha256', $rawBody, 'my-webhook-secret');
    $isValid  = $webhooks->verifySignature($rawBody, $sig, 'my-webhook-secret');
    echo 'Signature valid: ' . ($isValid ? 'yes' : 'no') . "\n";

    // Delete the webhook
    $result = $webhooks->delete($hook['id']);
    echo 'Webhook deleted: ' . ($result['deleted'] ? 'yes' : 'no') . "\n";

} catch (SnapAPIException $e) {
    fwrite(STDERR, "[{$e->getErrorCode()}] {$e->getMessage()}\n");
    exit(1);
}

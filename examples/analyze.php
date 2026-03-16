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

$client = new Client($apiKey, ['timeout' => 60]);

try {
    $result = $client->analyze([
        'url'      => 'https://example.com',
        'prompt'   => 'Summarize the main purpose of this website in 2-3 sentences.',
        'provider' => 'openai',
        'apiKey'   => getenv('OPENAI_API_KEY') ?: '',
    ]);

    echo "Analysis of {$result['url']}:\n\n";
    echo $result['result'] . "\n";

} catch (SnapAPIException $e) {
    if ($e->getStatusCode() === 503) {
        fwrite(STDERR, "Analyze endpoint unavailable (LLM credits may be exhausted).\n");
    } else {
        fwrite(STDERR, "[{$e->getErrorCode()}] {$e->getMessage()}\n");
    }
    exit(1);
}

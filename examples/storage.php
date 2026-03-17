<?php

declare(strict_types=1);

/**
 * Storage example.
 *
 * List, download, and delete stored captures.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SnapAPI\Client;
use SnapAPI\Exceptions\SnapAPIException;

$apiKey = getenv('SNAPAPI_KEY');
if (!$apiKey) {
    fwrite(STDERR, "SNAPAPI_KEY environment variable is required.\n");
    exit(1);
}

$client  = new Client($apiKey);
$storage = $client->storage();

try {
    // List stored files
    $result = $storage->list(['limit' => 10]);
    echo "Stored files: {$result['total']}\n";

    if (!empty($result['files'])) {
        $file   = $result['files'][0];
        $fileId = (string) $file['id'];

        // Get metadata
        $meta = $storage->get($fileId);
        echo "File: {$meta['id']} ({$meta['type']}, {$meta['size']} bytes)\n";

        // Download to disk
        $bytes = $storage->downloadToFile($fileId, 'downloaded_file');
        echo "Downloaded {$bytes} bytes\n";

        // Delete
        $del = $storage->delete($fileId);
        echo 'Deleted: ' . ($del['deleted'] ? 'yes' : 'no') . "\n";
    }

} catch (SnapAPIException $e) {
    fwrite(STDERR, "[{$e->getErrorCode()}] {$e->getMessage()}\n");
    exit(1);
}

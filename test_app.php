<?php
/**
 * SnapAPI PHP SDK - Comprehensive Test Suite
 * Tests ALL endpoints against the LIVE API.
 */

require_once __DIR__ . '/src/Exception/SnapAPIException.php';
require_once __DIR__ . '/src/Client.php';

use SnapAPI\Client;
use SnapAPI\Exception\SnapAPIException;

$apiKey = getenv('SNAPAPI_KEY') ?: (isset($argv[1]) ? $argv[1] : '');
if (empty($apiKey)) {
    echo "Usage: SNAPAPI_KEY=sk_live_xxx php test_app.php\n";
    echo "   or: php test_app.php sk_live_xxx\n";
    exit(1);
}
$client = new Client($apiKey);

$passed = 0;
$failed = 0;
$errors = [];

function test(string $name, callable $fn): void {
    global $passed, $failed, $errors;
    echo "  Testing: {$name}... ";
    try {
        $fn();
        echo "✅ PASS\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "❌ FAIL: " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "{$name}: {$e->getMessage()}";
    }
}

function assert_true(bool $condition, string $msg = 'Assertion failed'): void {
    if (!$condition) throw new \RuntimeException($msg);
}

// ═══════════════════════════════════════════
echo "\n🔵 1. PING\n";
// ═══════════════════════════════════════════
test('GET /v1/ping', function() use ($client) {
    $r = $client->ping();
    assert_true(isset($r['status']) || isset($r['pong']) || !empty($r), 'Empty ping response');
    echo json_encode($r) . ' ';
});

// ═══════════════════════════════════════════
echo "\n🔵 2. USAGE\n";
// ═══════════════════════════════════════════
test('GET /v1/usage', function() use ($client) {
    $r = $client->getUsage();
    assert_true(isset($r['used']) || isset($r['usage']) || isset($r['limit']) || !empty($r), 'Bad usage response');
    echo json_encode(array_slice($r, 0, 5, true)) . ' ';
});

// ═══════════════════════════════════════════
echo "\n🔵 3. DEVICES\n";
// ═══════════════════════════════════════════
test('GET /v1/devices', function() use ($client) {
    $r = $client->getDevices();
    assert_true(!empty($r), 'Empty devices response');
    $count = 0;
    if (isset($r['devices'])) {
        foreach ($r['devices'] as $cat => $devs) $count += count($devs);
    }
    echo "({$count} devices) ";
});

// ═══════════════════════════════════════════
echo "\n🔵 4. CAPABILITIES\n";
// ═══════════════════════════════════════════
test('GET /v1/capabilities', function() use ($client) {
    $r = $client->getCapabilities();
    assert_true(!empty($r), 'Empty capabilities');
    echo array_keys($r)[0] . ' ';
});

// ═══════════════════════════════════════════
echo "\n🔵 5. SCREENSHOT - URL (binary)\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - basic URL png', function() use ($client) {
    $r = $client->screenshot(['url' => 'https://example.com', 'format' => 'png']);
    assert_true(is_string($r) && strlen($r) > 1000, 'Screenshot too small: ' . strlen($r));
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

test('POST /v1/screenshot - jpeg format', function() use ($client) {
    $r = $client->screenshot(['url' => 'https://example.com', 'format' => 'jpeg', 'quality' => 50]);
    assert_true(strlen($r) > 500, 'JPEG too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

test('POST /v1/screenshot - webp format', function() use ($client) {
    $r = $client->screenshot(['url' => 'https://example.com', 'format' => 'webp']);
    assert_true(strlen($r) > 500, 'WebP too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 6. SCREENSHOT - JSON response + metadata\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - responseType json + includeMetadata', function() use ($client) {
    $r = $client->screenshot([
        'url' => 'https://example.com',
        'responseType' => 'json',
        'includeMetadata' => true,
    ]);
    assert_true(is_array($r), 'Expected array response');
    assert_true(isset($r['data']) || isset($r['image']), 'No image data in JSON response');
    echo 'keys: ' . implode(',', array_keys($r)) . ' ';
});

test('POST /v1/screenshot - responseType base64', function() use ($client) {
    $r = $client->screenshot([
        'url' => 'https://example.com',
        'responseType' => 'base64',
    ]);
    assert_true(is_array($r), 'Expected array for base64');
    echo 'keys: ' . implode(',', array_keys($r)) . ' ';
});

// ═══════════════════════════════════════════
echo "\n🔵 7. SCREENSHOT - HTML input\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - HTML input', function() use ($client) {
    $r = $client->screenshotFromHtml('<html><body style="background:blue"><h1 style="color:white">Test</h1></body></html>');
    assert_true(strlen($r) > 500, 'HTML screenshot too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 8. SCREENSHOT - Markdown input\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - Markdown input', function() use ($client) {
    $r = $client->screenshotFromMarkdown("# Hello\n\n**Bold text** and *italic*.\n\n- Item 1\n- Item 2");
    assert_true(strlen($r) > 500, 'Markdown screenshot too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 9. SCREENSHOT - Device preset\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - device iphone-15-pro', function() use ($client) {
    $r = $client->screenshotDevice('https://example.com', 'iphone-15-pro');
    assert_true(strlen($r) > 500, 'Device screenshot too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 10. SCREENSHOT - fullPage\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - fullPage', function() use ($client) {
    $r = $client->screenshot(['url' => 'https://example.com', 'fullPage' => true]);
    assert_true(strlen($r) > 500, 'Full page screenshot too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 11. SCREENSHOT - selector\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - selector h1', function() use ($client) {
    $r = $client->screenshot(['url' => 'https://example.com', 'selector' => 'h1']);
    assert_true(strlen($r) > 100, 'Selector screenshot too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 12. SCREENSHOT - dark mode\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - darkMode', function() use ($client) {
    $r = $client->screenshot(['url' => 'https://example.com', 'darkMode' => true]);
    assert_true(strlen($r) > 500, 'Dark mode screenshot too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 13. SCREENSHOT - custom CSS/JS\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - custom CSS + JS (needs Starter plan)', function() use ($client) {
    try {
        $r = $client->screenshot([
            'url' => 'https://example.com',
            'css' => 'body { background: red !important; }',
            'javascript' => 'document.title = "Modified";',
        ]);
        assert_true(strlen($r) > 500, 'CSS/JS screenshot too small');
        echo '(' . number_format(strlen($r)) . ' bytes) ';
    } catch (SnapAPIException $e) {
        if (stripos($e->getMessage(), 'plan') !== false) {
            echo '⚠️ SKIPPED (plan limitation) ';
        } else {
            throw $e;
        }
    }
});

// ═══════════════════════════════════════════
echo "\n🔵 14. SCREENSHOT - blocking options\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - blockAds/cookies/trackers (needs Starter plan)', function() use ($client) {
    try {
        $r = $client->screenshot([
            'url' => 'https://example.com',
            'blockAds' => true,
            'blockTrackers' => true,
            'blockCookieBanners' => true,
            'blockChatWidgets' => true,
        ]);
        assert_true(strlen($r) > 500, 'Blocking screenshot too small');
        echo '(' . number_format(strlen($r)) . ' bytes) ';
    } catch (SnapAPIException $e) {
        if (stripos($e->getMessage(), 'plan') !== false) {
            echo '⚠️ SKIPPED (plan limitation) ';
        } else {
            throw $e;
        }
    }
});

// ═══════════════════════════════════════════
echo "\n🔵 15. SCREENSHOT - thumbnail\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - thumbnail', function() use ($client) {
    $r = $client->screenshot([
        'url' => 'https://example.com',
        'thumbnail' => ['enabled' => true, 'width' => 200, 'height' => 150],
        'responseType' => 'json',
    ]);
    assert_true(is_array($r), 'Expected array for thumbnail');
    echo 'keys: ' . implode(',', array_keys($r)) . ' ';
});

// ═══════════════════════════════════════════
echo "\n🔵 16. SCREENSHOT - delay + waitForSelector\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot - delay + waitForSelector', function() use ($client) {
    $r = $client->screenshot([
        'url' => 'https://example.com',
        'delay' => 500,
        'waitForSelector' => 'h1',
    ]);
    assert_true(strlen($r) > 500, 'Delay screenshot too small');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 17. PDF\n";
// ═══════════════════════════════════════════
test('POST /v1/pdf - basic', function() use ($client) {
    $r = $client->pdf(['url' => 'https://example.com']);
    assert_true(strlen($r) > 1000, 'PDF too small');
    assert_true(substr($r, 0, 4) === '%PDF', 'Not a PDF file');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

test('POST /v1/pdf - with options', function() use ($client) {
    $r = $client->pdf([
        'url' => 'https://example.com',
        'pdfOptions' => [
            'pageSize' => 'a4',
            'landscape' => true,
            'printBackground' => true,
            'marginTop' => '20mm',
            'marginBottom' => '20mm',
        ]
    ]);
    assert_true(strlen($r) > 1000 && substr($r, 0, 4) === '%PDF', 'Bad PDF');
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 18. BATCH SCREENSHOTS\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot/batch', function() use ($client) {
    $r = $client->batch([
        'urls' => ['https://example.com', 'https://example.org'],
        'format' => 'png',
    ]);
    assert_true(is_array($r), 'Expected array');
    assert_true(isset($r['jobId']) || isset($r['results']) || isset($r['id']), 'No jobId or results');
    echo json_encode(array_intersect_key($r, array_flip(['jobId', 'id', 'status']))) . ' ';
    
    // Poll batch status if jobId present
    $jobId = $r['jobId'] ?? $r['id'] ?? null;
    if ($jobId) {
        sleep(3);
        $status = $client->getBatchStatus($jobId);
        echo "\n    Batch status: " . ($status['status'] ?? 'unknown') . ' ';
    }
});

// ═══════════════════════════════════════════
echo "\n🔵 19. VIDEO\n";
// ═══════════════════════════════════════════
test('POST /v1/video', function() use ($client) {
    $r = $client->video([
        'url' => 'https://example.com',
        'duration' => 3,
        'width' => 800,
        'height' => 600,
    ]);
    assert_true(strlen($r) > 1000, 'Video too small: ' . strlen($r));
    echo '(' . number_format(strlen($r)) . ' bytes) ';
});

// ═══════════════════════════════════════════
echo "\n🔵 20. EXTRACT\n";
// ═══════════════════════════════════════════
$extractFormats = ['html', 'text', 'markdown', 'article', 'links', 'images', 'metadata', 'structured'];
foreach ($extractFormats as $fmt) {
    test("POST /v1/extract - format={$fmt}", function() use ($client, $fmt) {
        $r = $client->extract(['url' => 'https://example.com', 'format' => $fmt]);
        assert_true(is_array($r) && !empty($r), "Empty {$fmt} response");
        echo 'keys: ' . implode(',', array_slice(array_keys($r), 0, 5)) . ' ';
    });
}

// ═══════════════════════════════════════════
echo "\n🔵 21. ANALYZE\n";
// ═══════════════════════════════════════════
test('POST /v1/analyze', function() use ($client) {
    $r = $client->analyze([
        'url' => 'https://example.com',
        'prompt' => 'What color is the background?',
    ]);
    assert_true(is_array($r), 'Expected array');
    assert_true(isset($r['analysis']) || isset($r['result']), 'No analysis in response');
    $text = $r['analysis'] ?? $r['result'] ?? '';
    echo substr($text, 0, 80) . '... ';
});

// ═══════════════════════════════════════════
echo "\n🔵 22. ASYNC SCREENSHOT + POLLING\n";
// ═══════════════════════════════════════════
test('POST /v1/screenshot (async) + GET status', function() use ($client) {
    $r = $client->screenshotAsync(['url' => 'https://example.com']);
    assert_true(is_array($r), 'Expected array');
    $jobId = $r['jobId'] ?? $r['id'] ?? null;
    assert_true($jobId !== null, 'No jobId returned: ' . json_encode($r));
    echo "jobId={$jobId} ";
    
    // Poll
    for ($i = 0; $i < 10; $i++) {
        sleep(2);
        $status = $client->getScreenshotStatus($jobId);
        $st = $status['status'] ?? 'unknown';
        echo "[{$st}] ";
        if ($st === 'completed' || $st === 'failed') break;
    }
});

// ═══════════════════════════════════════════
echo "\n🔵 23. ERROR HANDLING\n";
// ═══════════════════════════════════════════
test('Invalid URL error', function() use ($client) {
    try {
        $client->screenshot(['url' => 'not-a-url']);
        throw new \RuntimeException('Should have thrown');
    } catch (SnapAPIException $e) {
        assert_true($e->getStatusCode() >= 400, 'Expected 4xx status');
        echo "code={$e->getErrorCode()} status={$e->getStatusCode()} ";
    }
});

test('Missing params error', function() use ($client) {
    try {
        $client->screenshot([]);
        throw new \RuntimeException('Should have thrown');
    } catch (\InvalidArgumentException $e) {
        echo "caught: {$e->getMessage()} ";
    }
});

// ═══════════════════════════════════════════
echo "\n" . str_repeat('═', 50) . "\n";
echo "Results: ✅ {$passed} passed, ❌ {$failed} failed\n";
if ($errors) {
    echo "\nFailed tests:\n";
    foreach ($errors as $e) echo "  - {$e}\n";
}
echo str_repeat('═', 50) . "\n";

exit($failed > 0 ? 1 : 0);

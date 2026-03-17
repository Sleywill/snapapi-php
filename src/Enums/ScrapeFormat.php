<?php

declare(strict_types=1);

namespace SnapAPI\Enums;

/**
 * Supported output formats for scrape().
 *
 * ```php
 * use SnapAPI\Enums\ScrapeFormat;
 *
 * $client->scrape([
 *     'url'    => 'https://example.com',
 *     'format' => ScrapeFormat::Html->value,
 * ]);
 * ```
 */
enum ScrapeFormat: string
{
    case Html = 'html';
    case Text = 'text';
    case Json = 'json';
}

<?php

declare(strict_types=1);

namespace SnapAPI\Enums;

/**
 * Supported output formats for extract().
 *
 * ```php
 * use SnapAPI\Enums\ExtractFormat;
 *
 * $client->extract([
 *     'url'    => 'https://example.com',
 *     'format' => ExtractFormat::Markdown->value,
 * ]);
 * ```
 */
enum ExtractFormat: string
{
    case Markdown = 'markdown';
    case Text     = 'text';
    case Json     = 'json';
}

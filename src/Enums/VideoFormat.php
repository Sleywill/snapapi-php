<?php

declare(strict_types=1);

namespace SnapAPI\Enums;

/**
 * Supported video output formats for video().
 *
 * ```php
 * use SnapAPI\Enums\VideoFormat;
 *
 * $client->video([
 *     'url'    => 'https://example.com',
 *     'format' => VideoFormat::Mp4->value,
 * ]);
 * ```
 */
enum VideoFormat: string
{
    case Webm = 'webm';
    case Mp4  = 'mp4';
    case Gif  = 'gif';
}

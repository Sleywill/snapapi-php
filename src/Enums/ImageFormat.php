<?php

declare(strict_types=1);

namespace SnapAPI\Enums;

/**
 * Supported image output formats for screenshot() and ogImage().
 *
 * ```php
 * use SnapAPI\Enums\ImageFormat;
 *
 * $client->screenshot([
 *     'url'    => 'https://example.com',
 *     'format' => ImageFormat::Png->value,
 * ]);
 * ```
 */
enum ImageFormat: string
{
    case Png  = 'png';
    case Jpeg = 'jpeg';
    case Webp = 'webp';
}

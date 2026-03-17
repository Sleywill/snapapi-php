<?php

declare(strict_types=1);

namespace SnapAPI\Enums;

/**
 * Supported paper formats for pdf().
 *
 * ```php
 * use SnapAPI\Enums\PdfPageFormat;
 *
 * $client->pdf([
 *     'url'    => 'https://example.com',
 *     'format' => PdfPageFormat::A4->value,
 * ]);
 * ```
 */
enum PdfPageFormat: string
{
    case A4     = 'a4';
    case Letter = 'letter';
}

<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Support;

final class Utf8Text
{
    public static function sanitize(string $text): string
    {
        $text = str_replace("\0", '', $text);

        if (function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8') ?: $text;
        }

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'UTF-8//IGNORE', $text);

            if (is_string($converted)) {
                $text = $converted;
            }
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;
    }
}

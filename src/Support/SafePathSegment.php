<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Support;

use InvalidArgumentException;

class SafePathSegment
{
    public static function assert(string $segment, string $label = 'path segment'): string
    {
        if ($segment === '') {
            throw new InvalidArgumentException("Invalid {$label}: empty value.");
        }

        if (str_contains($segment, "\0")) {
            throw new InvalidArgumentException("Invalid {$label}: null bytes are not allowed.");
        }

        if (str_contains($segment, '..')) {
            throw new InvalidArgumentException("Invalid {$label}: path traversal is not allowed.");
        }

        if (str_starts_with($segment, '/') || str_starts_with($segment, '\\')) {
            throw new InvalidArgumentException("Invalid {$label}: absolute paths are not allowed.");
        }

        $normalized = basename(str_replace('\\', '/', $segment));

        if ($normalized === '' || $normalized === '.' || $normalized === '..') {
            throw new InvalidArgumentException("Invalid {$label}: resolved to an unsafe path.");
        }

        return $normalized;
    }

    public static function assertCollectionKey(string $collection): string
    {
        if ($collection === '') {
            throw new InvalidArgumentException('Invalid collection: empty value.');
        }

        if (str_contains($collection, "\0") || str_contains($collection, '..')) {
            throw new InvalidArgumentException('Invalid collection: unsafe characters.');
        }

        if (str_contains($collection, '/') || str_contains($collection, '\\')) {
            throw new InvalidArgumentException('Invalid collection: slashes are not allowed.');
        }

        return $collection;
    }

    public static function assertBasename(string $basename): string
    {
        self::assert($basename, 'canonical basename');

        if (str_contains($basename, '/')) {
            throw new InvalidArgumentException('Invalid canonical basename: slashes are not allowed.');
        }

        return $basename;
    }
}

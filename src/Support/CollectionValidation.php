<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Support;

class CollectionValidation
{
    /**
     * @return list<string>
     */
    public static function collections(): array
    {
        $cases = config('document-archive.cases', []);

        return is_array($cases) ? array_keys($cases) : [];
    }

    public static function requiredRule(): string
    {
        $collections = self::collections();

        if ($collections === []) {
            return 'required|string';
        }

        return 'required|string|in:'.implode(',', $collections);
    }

    public static function optionalRule(): string
    {
        $collections = self::collections();

        if ($collections === []) {
            return 'nullable|string';
        }

        return 'nullable|string|in:'.implode(',', $collections);
    }

    /**
     * @return list<string>
     */
    public static function schemaEnum(): array
    {
        return self::collections();
    }
}

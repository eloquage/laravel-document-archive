<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Contracts;

interface TranscriptService
{
    public function read(string $collection, string $basename, string $language): string;

    public function write(
        string $collection,
        string $canonicalBasename,
        string $language,
        string $content,
    ): void;
}

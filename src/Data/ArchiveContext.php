<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Data;

readonly class ArchiveContext
{
    public function __construct(
        public string $collection,
        public ?string $canonicalBasename = null,
        public ?string $sourceFile = null,
        public ?string $language = null,
    ) {}

    public static function forCollection(string $collection): self
    {
        return new self(collection: $collection);
    }

    public function withBasename(string $canonicalBasename): self
    {
        return new self(
            collection: $this->collection,
            canonicalBasename: $canonicalBasename,
            sourceFile: $this->sourceFile,
            language: $this->language,
        );
    }

    public function withSourceFile(string $sourceFile): self
    {
        return new self(
            collection: $this->collection,
            canonicalBasename: $this->canonicalBasename,
            sourceFile: $sourceFile,
            language: $this->language,
        );
    }

    public function withLanguage(?string $language): self
    {
        return new self(
            collection: $this->collection,
            canonicalBasename: $this->canonicalBasename,
            sourceFile: $this->sourceFile,
            language: $language,
        );
    }
}

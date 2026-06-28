<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Events;

use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;
use Illuminate\Foundation\Events\Dispatchable;

class MissingTranscriptDetected
{
    use Dispatchable;

    public readonly ArchiveContext $context;

    public function __construct(
        public string $collection,
        public string $canonicalBasename,
        public ?string $language,
        public string $sourceFile,
        public string $expectedPath,
        ?ArchiveContext $context = null,
        public ?string $tier = null,
    ) {
        $this->context = $context ?? new ArchiveContext(
            collection: $collection,
            canonicalBasename: $canonicalBasename,
            sourceFile: $sourceFile,
            language: $language,
            tier: $tier,
        );
    }
}

<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Events;

use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;
use Illuminate\Foundation\Events\Dispatchable;

class ManifestEntryRemoved
{
    use Dispatchable;

    public readonly ArchiveContext $context;

    public function __construct(
        public string $collection,
        public string $canonicalBasename,
        public string $sourceFile,
        ?ArchiveContext $context = null,
    ) {
        $this->context = $context ?? new ArchiveContext(
            collection: $collection,
            canonicalBasename: $canonicalBasename,
            sourceFile: $sourceFile,
        );
    }
}

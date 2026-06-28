<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Events;

use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;
use Illuminate\Foundation\Events\Dispatchable;

class ManifestEntryUpdated
{
    use Dispatchable;

    public readonly ArchiveContext $context;

    /**
     * @param  array<string, mixed>  $entry
     */
    public function __construct(
        public string $collection,
        public string $canonicalBasename,
        public array $entry,
        ?ArchiveContext $context = null,
    ) {
        $sourceFile = is_string($entry['source_file'] ?? null) ? $entry['source_file'] : null;

        $this->context = $context ?? new ArchiveContext(
            collection: $collection,
            canonicalBasename: $canonicalBasename,
            sourceFile: $sourceFile,
        );
    }
}

<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ManifestEntryRemoved
{
    use Dispatchable;

    public function __construct(
        public string $collection,
        public string $canonicalBasename,
        public string $sourceFile,
    ) {}
}

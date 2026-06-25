<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ManifestEntryUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $entry
     */
    public function __construct(
        public string $collection,
        public string $canonicalBasename,
        public array $entry,
    ) {}
}

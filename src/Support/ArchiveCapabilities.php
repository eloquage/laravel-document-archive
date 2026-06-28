<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Support;

use Eloquage\LaravelDocumentArchive\Contracts\DocumentArchiveSearchContract;
use Eloquage\LaravelDocumentArchive\Services\NullDocumentArchiveSearchService;

class ArchiveCapabilities
{
    public static function searchEnabled(): bool
    {
        $search = app(DocumentArchiveSearchContract::class);

        return ! $search instanceof NullDocumentArchiveSearchService;
    }
}

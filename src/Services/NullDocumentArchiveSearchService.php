<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Contracts\DocumentArchiveSearchContract;

class NullDocumentArchiveSearchService implements DocumentArchiveSearchContract
{
    /**
     * @return array<string, mixed>
     */
    public function semanticSearch(
        string $query,
        ?string $collection = null,
        int $limit = 10,
        ?float $minScore = null,
        ?string $chunkModel = null,
    ): array {
        return [
            'success' => true,
            'status' => 'skipped',
            'results' => [],
            'error' => 'Vector search bridge not installed. Require eloquage/laravel-document-archive-vector.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fulltextSearch(string $query, ?string $collection = null, int $limit = 20): array
    {
        return [
            'success' => true,
            'status' => 'skipped',
            'results' => [],
            'error' => 'Vector search bridge not installed. Require eloquage/laravel-document-archive-vector.',
        ];
    }
}

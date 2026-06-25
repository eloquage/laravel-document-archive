<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Contracts;

interface DocumentArchiveSearchContract
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
    ): array;

    /**
     * @return array<string, mixed>
     */
    public function fulltextSearch(string $query, ?string $collection = null, int $limit = 20): array;
}

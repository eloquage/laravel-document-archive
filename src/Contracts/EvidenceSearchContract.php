<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Contracts;

use Eloquage\LaravelDocumentArchive\Data\Requests\SemanticSearchRequestData;

interface EvidenceSearchContract
{
    /**
     * @return array<string, mixed>
     */
    public function semanticSearch(
        SemanticSearchRequestData $request,
        ?string $chunkModel = null,
        ?float $defaultMinScore = null,
    ): array;
}

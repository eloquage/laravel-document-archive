<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Contracts\DocumentArchiveSearchContract;
use Eloquage\LaravelDocumentArchive\Contracts\EvidenceSearchContract;
use Eloquage\LaravelDocumentArchive\Data\Requests\SemanticSearchRequestData;

class DocumentArchiveEvidenceSearchService implements EvidenceSearchContract
{
    public function __construct(
        private DocumentArchiveSearchContract $search,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function semanticSearch(
        SemanticSearchRequestData $request,
        ?string $chunkModel = null,
        ?float $defaultMinScore = null,
    ): array {
        $minScore = $request->min_score ?? $defaultMinScore;

        $result = $this->search->semanticSearch(
            $request->query,
            $request->case !== '' ? $request->case : null,
            $request->limit,
            $minScore,
            $chunkModel,
        );

        return $this->enrichEmptyResults($result);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function enrichEmptyResults(array $result): array
    {
        if (! ($result['success'] ?? false)) {
            return $result;
        }

        $results = $result['results'] ?? [];

        if ($results !== []) {
            $result['count'] = count($results);

            return $result;
        }

        $result['count'] = 0;

        if (! isset($result['message']) && ! isset($result['status'])) {
            $result['message'] = 'No matching evidence chunks found for this query.';
        }

        return $result;
    }
}

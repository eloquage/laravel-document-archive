<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Support\ChunkResultMapper;
use Eloquage\LaravelVectorSearch\Enums\IndexDriver;
use Eloquage\LaravelVectorSearch\Facades\LaravelVectorSearch;
use Eloquage\LaravelVectorSearch\Managers\EmbeddingProviderManager;
use Eloquage\LaravelVectorSearch\Models\VectorChunk;
use Eloquage\LaravelVectorSearch\Models\VectorIndex;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class DocumentArchiveSearchService
{
    public function __construct(
        private EmbeddingProviderManager $embeddings,
    ) {}

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
        $chunkModel ??= VectorChunk::class;
        $this->validateChunkModel($chunkModel);

        if ($chunkModel === VectorChunk::class || is_subclass_of($chunkModel, VectorChunk::class)) {
            return $this->semanticSearchViaVectorIndex($query, $collection, $limit, $minScore);
        }

        return $this->semanticSearchViaChunkModel($query, $collection, $limit, $minScore, $chunkModel);
    }

    /**
     * @return array<string, mixed>
     */
    public function fulltextSearch(string $query, ?string $collection = null, int $limit = 20): array
    {
        $indexName = $this->vectorIndexName();
        $index = VectorIndex::query()->where('name', $indexName)->first();

        $driver = $index?->getAttribute('driver');

        if ($index === null || ! $driver instanceof IndexDriver || $driver !== IndexDriver::MEILISEARCH) {
            return [
                'success' => true,
                'status' => 'skipped',
                'results' => [],
                'error' => 'Vector index driver is not meilisearch',
            ];
        }

        $filters = $collection !== null ? ['collection' => $collection] : [];

        $chunks = LaravelVectorSearch::forIndex($index)->keywordSearch($query, $limit, $filters);

        $results = $chunks
            ->map(fn (VectorChunk $chunk): array => ChunkResultMapper::toSearchResult($chunk))
            ->values()
            ->all();

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function semanticSearchViaVectorIndex(
        string $query,
        ?string $collection,
        int $limit,
        ?float $minScore,
    ): array {
        $indexName = $this->vectorIndexName();

        $chunkCount = VectorChunk::query()
            ->whereHas('vectorDocument.vectorIndex', fn ($builder) => $builder->where('name', $indexName))
            ->count();

        if ($chunkCount === 0) {
            return [
                'success' => true,
                'status' => 'empty_index',
                'hint' => 'Run evidence_index to populate embeddings.',
                'results' => [],
            ];
        }

        if (! $this->embeddingConfigured()) {
            return [
                'success' => false,
                'error' => 'Embedding provider not configured. Set vector-search embedding provider credentials.',
            ];
        }

        $results = LaravelVectorSearch::forIndex($indexName)->search($query, $limit * 3);

        if ($collection !== null) {
            $results = $results->filter(
                fn (VectorChunk $chunk): bool => ChunkResultMapper::collection($chunk) === $collection
            );
        }

        $queryVector = $this->embedQuery($query);

        $mapped = [];

        foreach ($results->take($limit) as $chunk) {
            $embedding = $chunk->getAttribute('embedding');
            $embedding = is_array($embedding) ? $embedding : [];
            $score = $this->cosineSimilarity($queryVector, $embedding);

            if ($minScore !== null && $score < $minScore) {
                continue;
            }

            $mapped[] = ChunkResultMapper::toSearchResult($chunk, $score);
        }

        return [
            'success' => true,
            'results' => $mapped,
        ];
    }

    /**
     * @param  class-string<Model>  $chunkModel
     * @return array<string, mixed>
     */
    private function semanticSearchViaChunkModel(
        string $query,
        ?string $collection,
        int $limit,
        ?float $minScore,
        string $chunkModel,
    ): array {
        /** @var Model $prototype */
        $prototype = new $chunkModel;
        $chunkCount = $prototype->newQuery()->count();

        if ($chunkCount === 0) {
            return [
                'success' => true,
                'status' => 'empty_index',
                'hint' => 'No chunks found for the configured chunk model.',
                'results' => [],
            ];
        }

        if (! $this->embeddingConfigured()) {
            return [
                'success' => false,
                'error' => 'Embedding provider not configured. Set vector-search embedding provider credentials.',
            ];
        }

        $queryVector = $this->embedQuery($query);

        $chunks = $prototype->newQuery()->get();

        if ($collection !== null) {
            $chunks = $chunks->filter(
                fn (Model $chunk): bool => ChunkResultMapper::collectionFromModel($chunk) === $collection
            );
        }

        $scored = [];

        foreach ($chunks as $chunk) {
            $embedding = $chunk->getAttribute('embedding');
            $embedding = is_array($embedding) ? $embedding : [];
            $score = $this->cosineSimilarity($queryVector, $embedding);

            if ($minScore !== null && $score < $minScore) {
                continue;
            }

            $scored[] = [
                'score' => $score,
                'result' => ChunkResultMapper::toSearchResultFromModel($chunk, $score),
            ];
        }

        usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $mapped = array_map(
            fn (array $item): array => $item['result'],
            array_slice($scored, 0, $limit),
        );

        return [
            'success' => true,
            'results' => $mapped,
        ];
    }

    /**
     * @param  class-string<Model>  $chunkModel
     */
    private function validateChunkModel(string $chunkModel): void
    {
        if (! class_exists($chunkModel)) {
            throw new InvalidArgumentException("Chunk model class does not exist: {$chunkModel}");
        }

        if (! is_subclass_of($chunkModel, Model::class)) {
            throw new InvalidArgumentException('Chunk model must extend '.Model::class.": {$chunkModel}");
        }

        /** @var Model $prototype */
        $prototype = new $chunkModel;

        if (! $prototype->hasCast('embedding', 'array') && ! $prototype->hasCast('embedding', 'json')) {
            throw new InvalidArgumentException("Chunk model must cast embedding as array: {$chunkModel}");
        }
    }

    private function vectorIndexName(): string
    {
        return (string) config('document-archive.vector_index', 'document-archive');
    }

    /**
     * @return list<float>
     */
    private function embedQuery(string $query): array
    {
        return $this->embeddings
            ->driver((string) config('vector-search.default_embedding_provider', 'null'))
            ->embed($query);
    }

    private function embeddingConfigured(): bool
    {
        $provider = (string) config('vector-search.default_embedding_provider', 'openai');

        return match ($provider) {
            'null', 'null_provider' => true,
            'openai' => filled(config('vector-search.embedding_providers.openai.api_key')),
            'ollama' => filled(config('vector-search.embedding_providers.ollama.host')),
            'cohere' => filled(config('vector-search.embedding_providers.cohere.api_key')),
            default => false,
        };
    }

    /**
     * @param  array<float>  $a
     * @param  array<float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $length = min(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}

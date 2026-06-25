<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Support;

use Eloquage\LaravelVectorSearch\Models\VectorChunk;
use Eloquage\LaravelVectorSearch\Models\VectorDocument;
use Illuminate\Database\Eloquent\Model;

class ChunkResultMapper
{
    /**
     * @return array{case_number: ?string, canonical_basename: ?string, chunk_index: int, content: string, score?: float}
     */
    public static function toSearchResult(VectorChunk $chunk, ?float $score = null): array
    {
        return self::toSearchResultFromModel($chunk, $score, self::metadata($chunk));
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array{case_number: ?string, canonical_basename: ?string, chunk_index: int, content: string, score?: float}
     */
    public static function toSearchResultFromModel(Model $chunk, ?float $score = null, ?array $metadata = null): array
    {
        $metadata ??= self::metadataFromModel($chunk);

        $result = [
            'case_number' => $metadata['case_number'] ?? $metadata['collection'] ?? null,
            'canonical_basename' => $metadata['canonical_basename'] ?? null,
            'chunk_index' => (int) ($chunk->getAttribute('chunk_index') ?? 0),
            'content' => (string) ($chunk->getAttribute('content') ?? ''),
        ];

        if ($score !== null) {
            $result['score'] = round($score, 4);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function metadata(VectorChunk $chunk): array
    {
        $document = $chunk->vectorDocument;

        if (! $document instanceof VectorDocument) {
            return [];
        }

        $metadata = $document->getAttribute('metadata');

        return is_array($metadata) ? $metadata : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function metadataFromModel(Model $chunk): array
    {
        if ($chunk instanceof VectorChunk) {
            return self::metadata($chunk);
        }

        $metadata = $chunk->getAttribute('metadata');

        if (is_array($metadata)) {
            return $metadata;
        }

        $collection = $chunk->getAttribute('collection');

        return is_string($collection) ? ['collection' => $collection] : [];
    }

    public static function collection(VectorChunk $chunk): ?string
    {
        return self::collectionFromModel($chunk);
    }

    public static function collectionFromModel(Model $chunk): ?string
    {
        $metadata = self::metadataFromModel($chunk);
        $collection = $metadata['collection'] ?? $chunk->getAttribute('collection') ?? null;

        return is_string($collection) ? $collection : null;
    }
}

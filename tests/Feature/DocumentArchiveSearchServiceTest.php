<?php

use Eloquage\LaravelDocumentArchive\Services\DocumentArchiveSearchService;
use Eloquage\LaravelVectorSearch\Enums\EmbeddingProvider;
use Eloquage\LaravelVectorSearch\Enums\IndexDriver;
use Eloquage\LaravelVectorSearch\Models\VectorChunk;
use Eloquage\LaravelVectorSearch\Models\VectorDocument;
use Eloquage\LaravelVectorSearch\Models\VectorIndex;

it('returns empty_index when no chunks exist', function () {
    $result = app(DocumentArchiveSearchService::class)->semanticSearch('witness statement');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('empty_index');
});

it('returns semantic search results for vector chunks', function () {
    $index = VectorIndex::factory()->create([
        'name' => 'document-archive',
        'driver' => IndexDriver::POSTGRES_VECTOR,
        'embedding_provider' => EmbeddingProvider::NULL_PROVIDER,
    ]);

    $document = VectorDocument::factory()->for($index, 'vectorIndex')->create([
        'metadata' => [
            'collection' => '479-26-0T9MTS',
            'canonical_basename' => 'chunk-a',
        ],
    ]);

    VectorChunk::factory()->for($document, 'vectorDocument')->create([
        'chunk_index' => 0,
        'content' => 'alpha content',
        'embedding' => [1, 1, 1, 1, 1, 1, 1, 1],
    ]);

    $result = app(DocumentArchiveSearchService::class)->semanticSearch('alpha', limit: 5);

    expect($result['success'])->toBeTrue()
        ->and($result['results'])->not->toBeEmpty()
        ->and($result['results'][0]['canonical_basename'])->toBe('chunk-a');
});

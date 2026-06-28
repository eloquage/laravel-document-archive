<?php

use Eloquage\LaravelDocumentArchive\Data\Requests\ManifestLookupRequestData;
use Eloquage\LaravelDocumentArchive\Data\Requests\ReadTranscriptRequestData;
use Eloquage\LaravelDocumentArchive\Data\Requests\SemanticSearchRequestData;

it('builds read transcript request data from array', function () {
    $data = ReadTranscriptRequestData::fromArray([
        'canonical_basename' => 'doc-1',
        'language' => 'pt',
        'start_line' => 2,
        'max_chars' => 500,
    ]);

    expect($data->canonical_basename)->toBe('doc-1')
        ->and($data->language)->toBe('pt')
        ->and($data->start_line)->toBe(2)
        ->and($data->max_chars)->toBe(500);
});

it('requires canonical basename for read transcript request', function () {
    ReadTranscriptRequestData::fromArray([]);
})->throws(InvalidArgumentException::class);

it('resolves collection alias for manifest lookup', function () {
    config()->set('document-archive.cases', [
        '479-26-0T9MTS' => ['nuipc' => '479/26.0T9MTS'],
    ]);

    $data = ManifestLookupRequestData::fromArray([
        'collection' => '479-26-0T9MTS',
        'canonical_basename' => 'doc-1',
    ]);

    expect($data->case)->toBe('479-26-0T9MTS');
});

it('builds semantic search request data with defaults', function () {
    $data = SemanticSearchRequestData::fromArray(['query' => 'witness'], defaultLimit: 15);

    expect($data->query)->toBe('witness')
        ->and($data->limit)->toBe(15)
        ->and($data->min_score)->toBeNull();
});

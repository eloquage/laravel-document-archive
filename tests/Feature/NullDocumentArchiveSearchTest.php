<?php

use Eloquage\LaravelDocumentArchive\Contracts\DocumentArchiveSearchContract;
use Eloquage\LaravelDocumentArchive\Services\NullDocumentArchiveSearchService;
use Eloquage\LaravelDocumentArchive\Support\ArchiveCapabilities;

it('returns skipped when vector bridge is not installed', function () {
    $result = app(DocumentArchiveSearchContract::class)->semanticSearch('witness statement');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('skipped')
        ->and(app(DocumentArchiveSearchContract::class))->toBeInstanceOf(NullDocumentArchiveSearchService::class);
    expect(ArchiveCapabilities::searchEnabled())->toBeFalse();
});

it('reports search enabled when the application binds a search adapter', function () {
    app()->instance(DocumentArchiveSearchContract::class, Mockery::mock(DocumentArchiveSearchContract::class));

    expect(ArchiveCapabilities::searchEnabled())->toBeTrue();
});

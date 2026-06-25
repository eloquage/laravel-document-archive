<?php

use Eloquage\LaravelDocumentArchive\Contracts\DocumentArchiveSearchContract;
use Eloquage\LaravelDocumentArchive\Services\NullDocumentArchiveSearchService;

it('returns skipped when vector bridge is not installed', function () {
    $result = app(DocumentArchiveSearchContract::class)->semanticSearch('witness statement');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('skipped')
        ->and(app(DocumentArchiveSearchContract::class))->toBeInstanceOf(NullDocumentArchiveSearchService::class);
});

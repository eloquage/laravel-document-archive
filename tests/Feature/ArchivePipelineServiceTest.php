<?php

use Eloquage\LaravelDocumentArchive\Services\ArchivePipelineService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('intake');
    Storage::fake('legacy');

    $manifest = file_get_contents(__DIR__.'/../Fixtures/evidence_manifest_sample.json');
    Storage::disk('legacy')->put('denuncia-v1/479-26-0T9MTS/evidence_manifest.json', $manifest);
});

it('returns ingest dry run results with capabilities', function () {
    Storage::disk('intake')->put('479-26-0T9MTS/new-file.pdf', 'content');

    $result = app(ArchivePipelineService::class)->ingest('479-26-0T9MTS', dryRun: true);

    expect($result->success)->toBeTrue()
        ->and($result->count)->toBe(1)
        ->and($result->dryRun)->toBeTrue()
        ->and($result->capabilities['ingest_manifest_write'])->toBeTrue()
        ->and($result->capabilities['validate_fix'])->toBeTrue();
});

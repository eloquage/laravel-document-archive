<?php

use Eloquage\LaravelDocumentArchive\Exceptions\CollectionNotConfiguredException;
use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('legacy');
    Storage::fake('collections');
    Storage::fake('manifests');
});

it('resolves legacy manifest path for known collections', function () {
    $resolver = new DocumentPathResolver;

    expect($resolver->manifestRelativePath('479-26-0T9MTS'))
        ->toBe('denuncia-v1/479-26-0T9MTS/evidence_manifest.json')
        ->and($resolver->manifestDiskName('479-26-0T9MTS'))->toBe('legacy')
        ->and($resolver->manifestRelativePath('384-22-0PIPRT'))
        ->toBe('denuncia-v1/384-22-0PIPRT/evidence_manifest.json');
});

it('prefers manifests disk when manifest exists there', function () {
    Storage::disk('manifests')->put('479-26-0T9MTS.json', '{}');

    $resolver = new DocumentPathResolver;

    expect($resolver->manifestRelativePath('479-26-0T9MTS'))
        ->toBe('479-26-0T9MTS.json')
        ->and($resolver->manifestDiskName('479-26-0T9MTS'))->toBe('manifests');
});

it('resolves transcript paths on legacy disk by default', function () {
    $resolver = new DocumentPathResolver;

    expect($resolver->transcriptRelativePath('479-26-0T9MTS', '2025-11-18-479-26-0T9MTS-DEN-NCIA', 'en'))
        ->toBe('denuncia-v1/479-26-0T9MTS/2025-11-18-479-26-0T9MTS-DEN-NCIA-EN.md')
        ->and($resolver->transcriptDiskName('479-26-0T9MTS', '2025-11-18-479-26-0T9MTS-DEN-NCIA', 'en'))
        ->toBe('legacy');
});

it('prefers collections disk when transcript exists there', function () {
    Storage::disk('collections')->put('479-26-0T9MTS/doc-EN.md', '# content');

    $resolver = new DocumentPathResolver;

    expect($resolver->transcriptRelativePath('479-26-0T9MTS', 'doc', 'en'))
        ->toBe('479-26-0T9MTS/doc-EN.md')
        ->and($resolver->transcriptDiskName('479-26-0T9MTS', 'doc', 'en'))
        ->toBe('collections');
});

it('rejects unknown collection', function () {
    (new DocumentPathResolver)->validateCollection('unknown-collection');
})->throws(CollectionNotConfiguredException::class);

it('exposes cases as deprecated alias for collections', function () {
    $resolver = new DocumentPathResolver;

    expect($resolver->cases())->toBe($resolver->collections());
});

<?php

use Eloquage\LaravelDocumentArchive\Support\ManifestSchemaValidator;

it('accepts valid manifest entries', function () {
    $validator = new ManifestSchemaValidator;

    $result = $validator->validateFiles([
        'scan.pdf' => [
            'source_file' => 'scan.pdf',
            'canonical_basename' => '2023-03-13-479-26-0T9MTS-SCAN',
            'status' => 'success',
            'document_date' => '2023-03-13',
        ],
    ]);

    expect($result['invalid'])->toBe([])
        ->and($result['valid'])->toHaveKey('scan.pdf');
});

it('rejects entries with path traversal in source file', function () {
    $validator = new ManifestSchemaValidator;

    $result = $validator->validateFiles([
        '../evil.pdf' => [
            'source_file' => '../evil.pdf',
            'canonical_basename' => '2023-03-13-479-26-0T9MTS-SCAN',
            'status' => 'pending',
        ],
    ]);

    expect($result['valid'])->toBe([])
        ->and($result['invalid'])->not->toBeEmpty();
});

it('rejects entries with invalid status', function () {
    $validator = new ManifestSchemaValidator;

    $errors = $validator->validateEntry('scan.pdf', [
        'source_file' => 'scan.pdf',
        'canonical_basename' => '2023-03-13-479-26-0T9MTS-SCAN',
        'status' => 'unknown',
    ]);

    expect($errors)->not->toBeEmpty();
});

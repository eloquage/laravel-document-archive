<?php

use Eloquage\LaravelDocumentArchive\Support\SafePathSegment;

it('rejects path traversal in path segments', function () {
    expect(fn () => SafePathSegment::assert('../etc/passwd', 'source file'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects null bytes in path segments', function () {
    expect(fn () => SafePathSegment::assert("file\0name.pdf", 'source file'))
        ->toThrow(InvalidArgumentException::class);
});

it('normalizes safe filenames with basename', function () {
    expect(SafePathSegment::assert('subdir/scan.pdf', 'source file'))->toBe('scan.pdf');
});

it('rejects slashes in collection keys', function () {
    expect(fn () => SafePathSegment::assertCollectionKey('bad/collection'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects slashes in canonical basenames', function () {
    expect(fn () => SafePathSegment::assertBasename('bad/basename'))
        ->toThrow(InvalidArgumentException::class);
});

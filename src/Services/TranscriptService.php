<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Contracts\TranscriptService as TranscriptServiceContract;
use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;
use Eloquage\LaravelDocumentArchive\Events\TranscriptWritten;
use Eloquage\LaravelDocumentArchive\Exceptions\TranscriptNotFoundException;
use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
use Eloquage\LaravelDocumentArchive\Support\SafePathSegment;
use Eloquage\LaravelDocumentArchive\Support\Utf8Text;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class TranscriptService implements TranscriptServiceContract
{
    public function __construct(
        private DocumentPathResolver $pathResolver,
    ) {}

    public function read(string $collection, string $basename, string $language): string
    {
        $this->pathResolver->validateCollection($collection);
        $basename = SafePathSegment::assertBasename($basename);

        $language = strtolower($language);
        $disk = $this->pathResolver->transcriptDiskName($collection, $basename, $language);
        $relative = $this->pathResolver->transcriptRelativePath($collection, $basename, $language);

        if (! Storage::disk($disk)->exists($relative)) {
            throw new TranscriptNotFoundException("Transcript not found: {$disk}:{$relative}");
        }

        return Utf8Text::sanitize((string) Storage::disk($disk)->get($relative));
    }

    public function write(
        string $collection,
        string $canonicalBasename,
        string $language,
        string $content,
    ): void {
        $this->pathResolver->validateCollection($collection);
        $canonicalBasename = SafePathSegment::assertBasename($canonicalBasename);

        $language = strtolower($language);
        $suffixes = config('document-archive.suffixes', []);
        $suffix = is_array($suffixes) ? ($suffixes[$language] ?? null) : null;

        if (! is_string($suffix) || $suffix === '') {
            throw new InvalidArgumentException("Unsupported language: {$language}");
        }

        $disk = $this->pathResolver->transcriptWriteDiskName();
        $relative = $this->pathResolver->transcriptWriteRelativePath($collection, $canonicalBasename, $language);

        $content = Utf8Text::sanitize($content);

        $directory = dirname($relative);
        if ($directory !== '.' && $directory !== '') {
            Storage::disk($disk)->makeDirectory($directory);
        }

        Storage::disk($disk)->put($relative, $content);

        $context = new ArchiveContext(
            collection: $collection,
            canonicalBasename: $canonicalBasename,
            language: $language,
        );

        event(new TranscriptWritten($collection, $canonicalBasename, $language, $context));
    }
}

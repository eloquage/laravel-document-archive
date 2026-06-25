<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Contracts\TranscriptService as TranscriptServiceContract;
use Eloquage\LaravelDocumentArchive\Exceptions\TranscriptNotFoundException;
use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
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

        $language = strtolower($language);
        $suffixes = config('document-archive.suffixes', []);
        $suffix = is_array($suffixes) ? ($suffixes[$language] ?? null) : null;

        if (! is_string($suffix) || $suffix === '') {
            throw new InvalidArgumentException("Unsupported language: {$language}");
        }

        $disk = config('document-archive.disks.collections', 'cases');
        $diskName = is_string($disk) ? $disk : 'cases';
        $relative = "{$collection}/{$canonicalBasename}{$suffix}";

        $content = Utf8Text::sanitize($content);

        Storage::disk($diskName)->makeDirectory($collection);
        Storage::disk($diskName)->put($relative, $content);
    }
}

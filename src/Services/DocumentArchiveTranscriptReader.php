<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Contracts\ManifestService;
use Eloquage\LaravelDocumentArchive\Contracts\TranscriptService;
use Eloquage\LaravelDocumentArchive\Exceptions\TranscriptNotFoundException;
use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
use Throwable;

class DocumentArchiveTranscriptReader
{
    public function __construct(
        private ManifestService $manifestService,
        private TranscriptService $transcriptService,
        private DocumentPathResolver $pathResolver,
    ) {}

    /**
     * @return array{success: bool, content?: string, path?: string, error?: string, case?: string, language?: string}
     */
    public function read(
        string $canonicalBasename,
        string $language = 'en',
        ?int $startLine = null,
        ?int $maxChars = null,
    ): array {
        $collection = $this->resolveCollectionForBasename($canonicalBasename);

        if ($collection === null) {
            return ['success' => false, 'error' => "Could not resolve case for basename: {$canonicalBasename}"];
        }

        try {
            $content = $this->transcriptService->read($collection, $canonicalBasename, $language);
        } catch (TranscriptNotFoundException $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        if ($startLine !== null && $startLine > 1) {
            $lines = explode("\n", $content);
            $content = implode("\n", array_slice($lines, $startLine - 1));
        }

        if ($maxChars !== null && strlen($content) > $maxChars) {
            $content = substr($content, 0, $maxChars)."\n\n[truncated]";
        }

        $disk = $this->pathResolver->transcriptDiskName($collection, $canonicalBasename, $language);
        $relative = $this->pathResolver->transcriptRelativePath($collection, $canonicalBasename, $language);

        return [
            'success' => true,
            'content' => $content,
            'path' => "{$disk}:{$relative}",
            'case' => $collection,
            'language' => strtolower($language),
        ];
    }

    private function resolveCollectionForBasename(string $canonicalBasename): ?string
    {
        foreach ($this->pathResolver->collections() as $collection) {
            try {
                $entry = $this->manifestService->lookup($collection, canonicalBasename: $canonicalBasename);

                if ($entry !== null) {
                    return $collection;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Data\ArchiveContext;
use Eloquage\LaravelDocumentArchive\Data\IngestResult;
use Eloquage\LaravelDocumentArchive\Data\ValidationReport;
use Eloquage\LaravelDocumentArchive\Enums\DocumentStatus;
use Eloquage\LaravelDocumentArchive\Events\MissingTranscriptDetected;
use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
use Eloquage\LaravelDocumentArchive\Support\SafePathSegment;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ArchivePipelineService
{
    public function __construct(
        private DocumentPathResolver $pathResolver,
        private ManifestService $manifestService,
    ) {}

    /**
     * @return array<string, bool>
     */
    public function capabilities(): array
    {
        return [
            'ingest_manifest_write' => true,
            'validate_fix' => true,
        ];
    }

    public function ingest(?string $collection = null, bool $dryRun = true): IngestResult
    {
        $candidates = $this->listIntakeCandidates($collection);
        $newFiles = [];
        $registered = [];

        foreach ($candidates as $candidate) {
            $collectionName = $candidate['collection'];
            $sourceFile = $candidate['source_file'];

            if ($this->manifestService->hasSourceFile($collectionName, $sourceFile)) {
                continue;
            }

            $newFiles[] = $candidate['path'];

            if ($dryRun) {
                continue;
            }

            $entries = $this->manifestService->allEntries($collectionName);
            $entry = $this->manifestService->buildPendingEntry($collectionName, $sourceFile);
            $entries[$sourceFile] = $entry;
            $this->manifestService->writeEntries($collectionName, $entries);
            $registered[] = $sourceFile;
        }

        return new IngestResult(
            success: true,
            dryRun: $dryRun,
            count: count($newFiles),
            newFiles: $newFiles,
            registered: $registered,
            message: $dryRun
                ? 'Dry run: files in intake awaiting ingest.'
                : (count($registered) > 0
                    ? 'Registered '.count($registered).' file(s) in manifest.'
                    : 'No new files to register.'),
            capabilities: $this->capabilities(),
        );
    }

    public function validate(?string $collection = null, bool $fix = false): ValidationReport
    {
        $collections = $collection !== null ? [$collection] : $this->pathResolver->collections();
        $issues = [];
        $fixesApplied = 0;
        $languages = config('document-archive.languages', ['en', 'pt']);
        $languages = is_array($languages) ? $languages : ['en', 'pt'];

        foreach ($collections as $collectionName) {
            try {
                $entries = $this->manifestService->allEntries($collectionName);
            } catch (Throwable $exception) {
                $issues[] = [
                    'collection' => $collectionName,
                    'type' => 'manifest_missing',
                    'detail' => $exception->getMessage(),
                ];

                continue;
            }

            foreach ($entries as $entry) {
                if (! is_array($entry)) {
                    $issues[] = [
                        'collection' => $collectionName,
                        'type' => 'invalid_manifest_entry',
                        'detail' => 'Manifest entry is not an object.',
                    ];

                    continue;
                }

                $basename = $entry['canonical_basename'] ?? null;
                $status = (string) ($entry['status'] ?? '');

                if (! is_string($basename)) {
                    $issues[] = [
                        'collection' => $collectionName,
                        'type' => 'invalid_manifest_entry',
                        'detail' => 'Manifest entry is missing canonical_basename.',
                    ];

                    continue;
                }

                if ($status === DocumentStatus::Pending->value) {
                    continue;
                }

                $skipStatuses = config('document-archive.indexing.skip_status', []);
                $skipStatuses = is_array($skipStatuses) ? $skipStatuses : [];

                if (in_array($status, $skipStatuses, true)) {
                    continue;
                }

                $sourceFile = (string) ($entry['source_file'] ?? '');
                $missingLanguages = [];
                $firstExpectedPath = null;

                foreach ($languages as $language) {
                    if (! is_string($language)) {
                        continue;
                    }

                    $disk = $this->pathResolver->transcriptDiskName($collectionName, $basename, $language);
                    $relative = $this->pathResolver->transcriptRelativePath($collectionName, $basename, $language);

                    if (Storage::disk($disk)->exists($relative)) {
                        continue;
                    }

                    $missingLanguages[] = $language;
                    $firstExpectedPath ??= "{$disk}:{$relative}";

                    $issues[] = [
                        'collection' => $collectionName,
                        'type' => 'missing_transcript',
                        'canonical_basename' => $basename,
                        'language' => $language,
                        'expected_path' => "{$disk}:{$relative}",
                        'source_file' => $sourceFile,
                    ];
                }

                if ($sourceFile === '') {
                    $issues[] = [
                        'collection' => $collectionName,
                        'type' => 'missing_source_file',
                        'canonical_basename' => $basename,
                        'detail' => 'Manifest entry has no source_file; cannot auto-fix.',
                    ];

                    continue;
                }

                if ($fix && $missingLanguages !== [] && $firstExpectedPath !== null) {
                    $tier = config('document-processing.default_auto_tier');
                    $tier = is_string($tier) ? $tier : null;

                    $context = new ArchiveContext(
                        collection: $collectionName,
                        canonicalBasename: $basename,
                        sourceFile: $sourceFile,
                        language: null,
                        tier: $tier,
                    );

                    event(new MissingTranscriptDetected(
                        collection: $collectionName,
                        canonicalBasename: $basename,
                        language: null,
                        sourceFile: $sourceFile,
                        expectedPath: $firstExpectedPath,
                        context: $context,
                        tier: $tier,
                    ));
                    $fixesApplied++;

                    foreach ($issues as &$issue) {
                        if (($issue['canonical_basename'] ?? null) === $basename
                            && ($issue['type'] ?? null) === 'missing_transcript') {
                            $issue['fix_action'] = 'event_dispatched';
                        }
                    }
                    unset($issue);
                }
            }
        }

        return new ValidationReport(
            success: true,
            count: count($issues),
            issues: $issues,
            fixed: $fix,
            fixesApplied: $fixesApplied,
            message: count($issues) === 0
                ? 'Manifest and transcripts are consistent.'
                : count($issues).' consistency issue(s) found.'
                    .($fix ? " Dispatched {$fixesApplied} fix event(s)." : ''),
            capabilities: $this->capabilities(),
        );
    }

    /**
     * @return list<array{path: string, collection: string, source_file: string}>
     */
    private function listIntakeCandidates(?string $collectionFilter): array
    {
        $intakeDisk = config('document-archive.disks.intake', 'intake');
        $intakeDisk = is_string($intakeDisk) ? $intakeDisk : 'intake';
        $files = Storage::disk($intakeDisk)->allFiles();
        $candidates = [];

        foreach ($files as $path) {
            if (str_starts_with(basename($path), '.')) {
                continue;
            }

            $resolved = $this->resolveIntakeFile($path, $collectionFilter);

            if ($resolved === null) {
                continue;
            }

            $candidates[] = $resolved;
        }

        return $candidates;
    }

    /**
     * @return array{path: string, collection: string, source_file: string}|null
     */
    private function resolveIntakeFile(string $path, ?string $collectionFilter): ?array
    {
        $parts = explode('/', $path);
        $sourceFile = basename($path);

        try {
            $sourceFile = SafePathSegment::assert($sourceFile, 'intake source file');
        } catch (Throwable) {
            return null;
        }

        if (count($parts) > 1 && in_array($parts[0], $this->pathResolver->collections(), true)) {
            $collection = $parts[0];

            if ($collectionFilter !== null && $collection !== $collectionFilter) {
                return null;
            }

            return [
                'path' => $path,
                'collection' => $collection,
                'source_file' => $sourceFile,
            ];
        }

        if ($collectionFilter === null) {
            return null;
        }

        return [
            'path' => $path,
            'collection' => $collectionFilter,
            'source_file' => $sourceFile,
        ];
    }
}

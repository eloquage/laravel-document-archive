<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Services;

use Eloquage\LaravelDocumentArchive\Contracts\SourceFileLocator as SourceFileLocatorContract;
use Eloquage\LaravelDocumentArchive\Data\LocatedSourceFile;
use Illuminate\Support\Facades\Storage;

class SourceFileLocator implements SourceFileLocatorContract
{
    public function locate(string $collection, string $sourceFile): ?LocatedSourceFile
    {
        $legacyPrefix = config('document-archive.legacy_prefix', 'denuncia-v1');
        $legacyPrefix = is_string($legacyPrefix) ? $legacyPrefix : 'denuncia-v1';
        $searchDisks = config('document-archive.disks.source_search', [
            'intake',
            'dropbox',
            'legacy',
        ]);

        if (! is_array($searchDisks)) {
            $searchDisks = ['intake', 'dropbox', 'legacy'];
        }

        $candidates = [];

        foreach ($searchDisks as $disk) {
            if (! is_string($disk)) {
                continue;
            }

            if (empty(config("filesystems.disks.{$disk}"))) {
                continue;
            }

            if ($disk === 'legacy') {
                $candidates[] = [$disk, "{$legacyPrefix}/{$collection}/{$sourceFile}"];
            }

            $candidates[] = [$disk, "{$collection}/{$sourceFile}"];
            $candidates[] = [$disk, $sourceFile];
        }

        foreach ($candidates as [$disk, $relative]) {
            if (Storage::disk($disk)->exists($relative)) {
                return new LocatedSourceFile(
                    disk: $disk,
                    relativePath: $relative,
                    extension: strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION)),
                );
            }
        }

        return null;
    }
}

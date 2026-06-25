<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive\Support;

use Eloquage\LaravelDocumentArchive\Exceptions\CollectionNotConfiguredException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class DocumentPathResolver
{
    /**
     * @return list<string>
     */
    public function collections(): array
    {
        $cases = config('document-archive.cases', []);
        $legacyCollections = config('document-archive.collections', []);

        if (! is_array($cases)) {
            $cases = [];
        }

        if (! is_array($legacyCollections)) {
            $legacyCollections = [];
        }

        $merged = array_merge($legacyCollections, $cases);

        return array_map(static fn (mixed $key): string => (string) $key, array_keys($merged));
    }

    /**
     * @return list<string>
     *
     * @deprecated Use collections() instead.
     */
    public function cases(): array
    {
        return $this->collections();
    }

    public function validateCollection(string $collection): void
    {
        if (! in_array($collection, $this->collections(), true)) {
            throw new CollectionNotConfiguredException("Unknown collection: {$collection}");
        }
    }

    /**
     * @deprecated Use validateCollection() instead.
     */
    public function validateCase(string $collection): void
    {
        $this->validateCollection($collection);
    }

    public function legacyDisk(): Filesystem
    {
        return Storage::disk($this->diskName('legacy', 'legacy'));
    }

    public function collectionsDisk(): Filesystem
    {
        return Storage::disk($this->diskName('collections', 'collections'));
    }

    /**
     * @deprecated Use collectionsDisk() instead.
     */
    public function casesDisk(): Filesystem
    {
        return $this->collectionsDisk();
    }

    public function manifestsDisk(): Filesystem
    {
        return Storage::disk($this->diskName('manifests', 'manifests'));
    }

    public function intakeDisk(): Filesystem
    {
        return Storage::disk($this->diskName('intake', 'intake'));
    }

    public function manifestRelativePath(string $collection): string
    {
        $this->validateCollection($collection);

        $filename = config('document-archive.manifest_filename', 'evidence_manifest.json');
        $filename = is_string($filename) ? $filename : 'evidence_manifest.json';

        if ($this->manifestsDisk()->exists("{$collection}.json")) {
            return "{$collection}.json";
        }

        if ($this->manifestsDisk()->exists("{$collection}/{$filename}")) {
            return "{$collection}/{$filename}";
        }

        $prefix = config('document-archive.legacy_prefix', 'denuncia-v1');
        $prefix = is_string($prefix) ? $prefix : 'denuncia-v1';

        return "{$prefix}/{$collection}/{$filename}";
    }

    public function manifestDiskName(string $collection): string
    {
        $relative = $this->manifestRelativePath($collection);

        if ($this->manifestsDisk()->exists($relative)) {
            return $this->diskName('manifests', 'manifests');
        }

        return $this->diskName('legacy', 'legacy');
    }

    public function transcriptRelativePath(string $collection, string $canonicalBasename, string $language): string
    {
        $this->validateCollection($collection);

        $suffixes = config('document-archive.suffixes', []);
        $suffix = is_array($suffixes) ? ($suffixes[strtolower($language)] ?? null) : null;

        if (! is_string($suffix) || $suffix === '') {
            throw new InvalidArgumentException("Unsupported language: {$language}");
        }

        $filename = "{$canonicalBasename}{$suffix}";

        if ($this->collectionsDisk()->exists("{$collection}/{$filename}")) {
            return "{$collection}/{$filename}";
        }

        $prefix = config('document-archive.legacy_prefix', 'denuncia-v1');
        $prefix = is_string($prefix) ? $prefix : 'denuncia-v1';

        return "{$prefix}/{$collection}/{$filename}";
    }

    public function transcriptDiskName(string $collection, string $canonicalBasename, string $language): string
    {
        $relative = $this->transcriptRelativePath($collection, $canonicalBasename, $language);
        $prefix = config('document-archive.legacy_prefix', 'denuncia-v1');
        $prefix = is_string($prefix) ? $prefix : 'denuncia-v1';

        if (str_starts_with($relative, "{$prefix}/")) {
            return $this->diskName('legacy', 'legacy');
        }

        return $this->diskName('collections', 'cases');
    }

    public function specialSourceRelativePath(string $collection, string $type): string
    {
        $this->validateCollection($collection);

        $relative = config("document-archive.special_sources.{$type}");

        if (! is_string($relative) || $relative === '') {
            throw new InvalidArgumentException("Unknown special source type: {$type}");
        }

        $prefix = config('document-archive.legacy_prefix', 'denuncia-v1');
        $prefix = is_string($prefix) ? $prefix : 'denuncia-v1';

        return "{$prefix}/{$collection}/{$relative}";
    }

    private function diskName(string $key, string $default): string
    {
        $disks = config('document-archive.disks', []);
        $name = is_array($disks) ? ($disks[$key] ?? $default) : $default;

        return is_string($name) ? $name : $default;
    }
}

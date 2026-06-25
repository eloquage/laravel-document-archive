<?php

declare(strict_types=1);

namespace Eloquage\LaravelDocumentArchive;

use Eloquage\LaravelDocumentArchive\Contracts\ManifestService;
use Eloquage\LaravelDocumentArchive\Contracts\SourceFileLocator;
use Eloquage\LaravelDocumentArchive\Contracts\TranscriptService;
use Eloquage\LaravelDocumentArchive\Services\ArchivePipelineService;
use Eloquage\LaravelDocumentArchive\Services\ManifestService as ManifestServiceImplementation;
use Eloquage\LaravelDocumentArchive\Services\SourceFileLocator as SourceFileLocatorImplementation;
use Eloquage\LaravelDocumentArchive\Services\SpecialSourceService;
use Eloquage\LaravelDocumentArchive\Services\TranscriptService as TranscriptServiceImplementation;
use Eloquage\LaravelDocumentArchive\Support\DocumentPathResolver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelDocumentArchiveServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-document-archive')
            ->hasConfigFile(['document-archive', 'document-archive-disks']);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(DocumentPathResolver::class);

        $this->app->singleton(ManifestServiceImplementation::class);
        $this->app->singleton(TranscriptServiceImplementation::class);
        $this->app->singleton(SourceFileLocatorImplementation::class);
        $this->app->singleton(ArchivePipelineService::class);
        $this->app->singleton(SpecialSourceService::class);

        $this->app->alias(ManifestServiceImplementation::class, ManifestService::class);
        $this->app->alias(TranscriptServiceImplementation::class, TranscriptService::class);
        $this->app->alias(SourceFileLocatorImplementation::class, SourceFileLocator::class);
    }
}

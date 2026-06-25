<?php

namespace Eloquage\LaravelDocumentArchive\Tests;

use Eloquage\LaravelDocumentArchive\LaravelDocumentArchiveServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDocumentArchiveServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        foreach (['intake', 'collections', 'manifests', 'legacy'] as $disk) {
            config()->set("filesystems.disks.{$disk}", [
                'driver' => 'local',
                'root' => storage_path("framework/testing/disks/{$disk}"),
                'throw' => false,
            ]);
        }

        config()->set('document-archive.cases', [
            '479-26-0T9MTS' => [
                'nuipc' => '479/26.0T9MTS',
                'label' => 'Counter-complaint',
            ],
            '384-22-0PIPRT' => [
                'nuipc' => '384/22.0PIPRT',
                'label' => 'Domestic violence complaint',
            ],
        ]);

        config()->set('document-archive.legacy_prefix', 'denuncia-v1');
        config()->set('document-archive.manifest_filename', 'evidence_manifest.json');
        config()->set('document-archive.vector_index', 'document-archive');
        config()->set('document-archive.disks', [
            'intake' => 'intake',
            'manifests' => 'manifests',
            'collections' => 'collections',
            'legacy' => 'legacy',
            'source_search' => ['intake', 'legacy'],
        ]);

        config()->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function manifestFixture(): string
    {
        return file_get_contents(__DIR__.'/Fixtures/evidence_manifest_sample.json');
    }
}

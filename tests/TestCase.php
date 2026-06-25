<?php

namespace Eloquage\LaravelDocumentArchive\Tests;

use Eloquage\LaravelDocumentArchive\LaravelDocumentArchiveServiceProvider;
use Eloquage\LaravelVectorSearch\LaravelVectorSearchServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Eloquage\\LaravelVectorSearch\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDocumentArchiveServiceProvider::class,
            LaravelVectorSearchServiceProvider::class,
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
        config()->set('vector-search.default_embedding_provider', 'null');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        foreach ([
            'create_vector_indexes_table.php.stub',
            'create_vector_documents_table.php.stub',
            'create_vector_chunks_table.php.stub',
        ] as $migration) {
            $path = dirname(__DIR__).'/../laravel-vector-search/database/migrations/'.$migration;
            $migrationInstance = include $path;
            $migrationInstance->up();
        }
    }

    protected function manifestFixture(): string
    {
        return file_get_contents(__DIR__.'/Fixtures/evidence_manifest_sample.json');
    }
}

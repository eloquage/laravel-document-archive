<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Legacy Import Prefix
    |--------------------------------------------------------------------------
    |
    | Subdirectory under the legacy disk where pre-migration documents live.
    |
    */

    'legacy_prefix' => env('DOCUMENT_ARCHIVE_LEGACY_PREFIX', 'denuncia-v1'),

    /*
    |--------------------------------------------------------------------------
    | Collection Directories (BC key: cases)
    |--------------------------------------------------------------------------
    |
    | Publish collection definitions from your application, e.g.:
    | '479-26-0T9MTS' => ['nuipc' => '479/26.0T9MTS', 'label' => '...']
    |
    */

    'cases' => [],

    /*
    |--------------------------------------------------------------------------
    | Manifest
    |--------------------------------------------------------------------------
    */

    'manifest_filename' => env('DOCUMENT_ARCHIVE_MANIFEST_FILENAME', 'evidence_manifest.json'),

    /*
    |--------------------------------------------------------------------------
    | Vector Search Index
    |--------------------------------------------------------------------------
    */

    'vector_index' => env('DOCUMENT_ARCHIVE_VECTOR_INDEX', 'document-archive'),

    /*
    |--------------------------------------------------------------------------
    | Transcript Languages
    |--------------------------------------------------------------------------
    */

    'languages' => ['pt', 'en'],

    'suffixes' => [
        'pt' => '-PT.md',
        'en' => '-EN.md',
    ],

    /*
    |--------------------------------------------------------------------------
    | Special Sources (relative to collection directory on legacy disk)
    |--------------------------------------------------------------------------
    */

    'special_sources' => [
        'facebook_html' => 'Facebook_Conversa_Mimi_Morticia/PROVA_C15_Mensagens_Facebook_Completas.html',
        'whatsapp_glob' => '*WHATSAPP-CHAT*.txt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Indexing Defaults (for downstream packages — not used by this package)
    |--------------------------------------------------------------------------
    */

    'indexing' => [
        'language' => env('DOCUMENT_ARCHIVE_INDEX_LANGUAGE', 'en'),
        'chunk_size' => (int) env('DOCUMENT_ARCHIVE_CHUNK_SIZE', 1500),
        'chunk_overlap' => (int) env('DOCUMENT_ARCHIVE_CHUNK_OVERLAP', 200),
        'batch_size' => (int) env('DOCUMENT_ARCHIVE_EMBED_BATCH_SIZE', 50),
        'skip_status' => ['renamed'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Disk names registered in config/filesystems.php. Merge document-archive-disks
    | stub into your application or define equivalent disks manually.
    |
    */

    'disks' => [
        'intake' => env('DOCUMENT_ARCHIVE_INTAKE_DISK', 'intake'),
        'manifests' => env('DOCUMENT_ARCHIVE_MANIFESTS_DISK', 'manifests'),
        'collections' => env('DOCUMENT_ARCHIVE_COLLECTIONS_DISK', 'cases'),
        'legacy' => env('DOCUMENT_ARCHIVE_LEGACY_DISK', 'legacy'),
        'source_search' => [
            'intake',
            'dropbox',
            'legacy',
        ],
    ],

];

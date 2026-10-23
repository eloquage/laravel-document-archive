<?php

declare(strict_types=1);

/**
 * Merge these disk definitions into your application's config/filesystems.php.
 *
 * php artisan vendor:publish --tag=document-archive-config
 */
return [

    'disks' => [

        'intake' => [
            'driver' => 'local',
            'root' => storage_path('intake'),
            'throw' => false,
        ],

        'cases' => [
            'driver' => 'local',
            'root' => storage_path('collections'),
            'throw' => false,
        ],

        'legacy' => [
            'driver' => 'local',
            'root' => storage_path('legacy-import'),
            'throw' => false,
        ],

        'manifests' => [
            'driver' => 'local',
            'root' => storage_path('manifests'),
            'throw' => false,
        ],

    ],

];

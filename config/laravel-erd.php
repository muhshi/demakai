<?php

return [
    'uri' => env('LARAVEL_ERD_URI', 'laravel-erd'),
    'storage_path' => base_path('docs/erd'),
    'extension' => env('LARAVEL_ERD_EXTENSION', 'sql'),
    'middleware' => [],
    'binary' => [
        'erd-go' => env('LARAVEL_ERD_GO', '/usr/local/bin/erd-go'),
        'dot' => env('LARAVEL_ERD_DOT', '/usr/local/bin/dot'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Connection Overrides
    |--------------------------------------------------------------------------
    |
    | Override specific database connections used during ERD generation.
    | Koneksi 'pgsql' di-override ke SQLite in-memory agar model-model yang
    | menggunakan $connection = 'pgsql' tetap dapat di-scan tanpa DB nyata.
    |
    */
    'connections' => [
        'pgsql' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
    ],
];

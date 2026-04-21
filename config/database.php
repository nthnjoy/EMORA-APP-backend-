<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | PERBAIKAN: Ubah default ke 'mongodb' agar Laravel otomatis menggunakan
    | database MongoDB Atlas kamu untuk semua operasi.
    |
    */

    'default' => env('DB_CONNECTION', 'mongodb'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        /*
        |--------------------------------------------------------------------------
        | MongoDB Connection Configuration
        |--------------------------------------------------------------------------
        */

       'mongodb' => [
            'driver'   => 'mongodb',
            'dsn'      => env('DB_DSN'),
            'database' => env('DB_DATABASE'),
            'options'  => [
                'tls'                         => filter_var(env('DB_TLS', true), FILTER_VALIDATE_BOOLEAN),
                'tlsAllowInvalidCertificates' => filter_var(env('DB_TLS_ALLOW_INVALID_CERTIFICATES', false), FILTER_VALIDATE_BOOLEAN),
                'serverSelectionTimeoutMS'    => (int) env('DB_SERVER_SELECTION_TIMEOUT_MS', 10000),
                'connectTimeoutMS'            => (int) env('DB_CONNECT_TIMEOUT_MS', 10000),
                'socketTimeoutMS'             => (int) env('DB_SOCKET_TIMEOUT_MS', 15000),
            ],
        ],

    ],  // <-- tutup 'connections'

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel')).'_database_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
    ],

];
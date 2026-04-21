<?php

use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    MongoDB\Laravel\MongoDBServiceProvider::class, // Tambahkan baris ini
];

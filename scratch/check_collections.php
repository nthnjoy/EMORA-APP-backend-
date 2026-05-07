<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $collections = \Illuminate\Support\Facades\DB::connection('mongodb')->listCollections();
    echo "✅ Koneksi MongoDB BERHASIL!\n";
    echo "Daftar Koleksi di database 'monitoring':\n";
    foreach ($collections as $collection) {
        echo "- " . $collection->getName() . "\n";
    }
} catch (Throwable $e) {
    echo "❌ Koneksi MongoDB GAGAL:\n";
    echo "Error: " . $e->getMessage() . "\n";
}

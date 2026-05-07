<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $collections = \Illuminate\Support\Facades\DB::connection('mongodb')->listCollections();
    echo "✅ Koneksi MongoDB BERHASIL!\n";
    echo "Daftar Koleksi dan Jumlah Dokumen:\n";
    foreach ($collections as $collection) {
        $name = $collection->getName();
        $count = \Illuminate\Support\Facades\DB::connection('mongodb')->collection($name)->count();
        echo "- $name: $count dokumen\n";
    }
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

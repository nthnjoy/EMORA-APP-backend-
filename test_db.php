<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \Illuminate\Support\Facades\DB::connection('mongodb')->command(['ping' => 1]);
    echo "✅ Koneksi MongoDB ke database 'monitoring' BERHASIL!\n";
    exit(0);
} catch (Throwable $e) {
    echo "❌ Koneksi MongoDB GAGAL:\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = new App\Models\Story();
$s->nim = '999';
$s->description = Illuminate\Support\Facades\Crypt::encryptString('test encryption');
$s->save();

echo "Raw DB Description: " . $s->getRawOriginal('description') . "\n";
echo "Model Description: " . $s->description . "\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$latestStory = App\Models\Story::orderByDesc('created_at')->first();
echo "\n--- LATEST STORY ---\n";
echo "ID: " . $latestStory->_id . "\n";
echo "NIM: " . $latestStory->nim . "\n";
echo "Raw DB Description: " . $latestStory->getRawOriginal('description') . "\n";
echo "Created At: " . $latestStory->created_at . "\n";
echo "--------------------\n";

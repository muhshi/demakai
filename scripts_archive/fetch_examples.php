<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;

// Get 20 random KBLI 2025
$kbli = PgKBLI2025::inRandomOrder()->limit(20)->get(['kode', 'judul']);
// Get 20 random KBJI 2014
$kbji = PgKBJI2014::inRandomOrder()->limit(20)->get(['kode', 'judul']);

echo "### KBLI 2025\n";
foreach ($kbli as $item) {
    echo "- Kode: {$item->kode} | Judul: {$item->judul}\n";
}

echo "\n### KBJI 2014\n";
foreach ($kbji as $item) {
    echo "- Kode: {$item->kode} | Judul: {$item->judul}\n";
}

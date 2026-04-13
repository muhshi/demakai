<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2025;

$kblis = PgKBLI2025::where(function($q) {
        $q->whereNull('contoh_lapangan')
          ->orWhere('contoh_lapangan', '[]')
          ->orWhere('contoh_lapangan', '""');
    })
    ->inRandomOrder()
    ->limit(20)
    ->get(['kode', 'judul']);

echo "Empty KBLI 2025 Records:\n";
foreach ($kblis as $kbli) {
    echo "{$kbli->kode} | {$kbli->judul}\n";
}

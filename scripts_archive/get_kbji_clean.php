<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBJI2014;

// Ambil yang kodenya mirip di screenshot (0111, dll) atau yang kosong lainnya
$kbjis = PgKBJI2014::where(function($q) {
        $q->whereNull('contoh_lapangan')
          ->orWhere('contoh_lapangan', '[]')
          ->orWhere('contoh_lapangan', '""');
    })
    ->orderBy('kode', 'asc') // Urutkan biar rapi seperti di screenshot
    ->limit(20)
    ->get(['kode', 'judul']);

echo "KBJI_LIST_START\n";
foreach ($kbjis as $kbji) {
    echo "{$kbji->kode} | {$kbji->judul}\n";
}
echo "KBJI_LIST_END\n";

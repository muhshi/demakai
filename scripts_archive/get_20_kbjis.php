<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBJI2014;

$kbjis = PgKBJI2014::where(function($q) {
        $q->whereNull('contoh_lapangan')
          ->orWhere('contoh_lapangan', '[]')
          ->orWhere('contoh_lapangan', '""');
    })
    ->limit(20)
    ->get(['kode', 'judul']);

$output = "";
foreach ($kbjis as $kbji) {
    $output .= "{$kbji->kode} | {$kbji->judul}\n";
}

file_put_contents('kbji_20_target_list.txt', $output);
echo "Written to kbji_20_target_list.txt\n";

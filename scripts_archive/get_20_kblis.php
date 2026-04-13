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
    ->limit(20)
    ->get(['kode', 'judul']);

$output = "";
foreach ($kblis as $kbli) {
    $output .= "{$kbli->kode} | {$kbli->judul}\n";
}

file_put_contents('kbli_20_target_list.txt', $output);
echo "Written to kbli_20_target_list.txt\n";

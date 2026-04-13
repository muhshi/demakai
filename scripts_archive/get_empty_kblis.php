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

$output = "KBLI_LIST_START\n";
foreach ($kblis as $kbli) {
    $output .= "{$kbli->kode} | {$kbli->judul}\n";
}
$output .= "KBLI_LIST_END\n";

file_put_contents('kbli_2025_empty_list.txt', $output);
echo "Written to kbli_2025_empty_list.txt\n";

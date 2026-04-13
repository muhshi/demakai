<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;

$kbli = PgKBLI2025::inRandomOrder()->limit(25)->get(['kode', 'judul']);
$kbji = PgKBJI2014::inRandomOrder()->limit(25)->get(['kode', 'judul']);

$output = "KBLI 2025:\n";
foreach ($kbli as $k) {
    if ($k->judul) $output .= "{$k->kode} | {$k->judul}\n";
}
$output .= "\nKBJI 2014:\n";
foreach ($kbji as $k) {
    if ($k->judul) $output .= "{$k->kode} | {$k->judul}\n";
}

file_put_contents('codes_list.txt', $output);
echo "Written to codes_list.txt\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2025;

$kblis = PgKBLI2025::whereNotNull('contoh_lapangan')
    ->where('contoh_lapangan', '!=', '[]')
    ->limit(20)
    ->get(['kode', 'judul', 'contoh_lapangan']);

$output = "### KBLI 2025 (From Admin Dataset)\n\n";
foreach ($kblis as $kbli) {
    $contohArray = is_string($kbli->contoh_lapangan) ? json_decode($kbli->contoh_lapangan, true) : $kbli->contoh_lapangan;
    if (is_array($contohArray) && !empty($contohArray)) {
        foreach ($contohArray as $contoh) {
            $output .= "- **{$kbli->kode}** | {$kbli->judul} | {$contoh}\n";
        }
    }
}

file_put_contents('admin_dataset_examples.txt', $output);
echo "Extracted examples to admin_dataset_examples.txt\n";

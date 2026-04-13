<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2025;

$row = PgKBLI2025::where('kode', '47111')->first();
if ($row) {
    echo "KBLI 2025 (47111):\n";
    echo "Kode: " . $row->kode . "\n";
    echo "Judul: " . $row->judul . "\n";
    echo "Deskripsi: " . $row->deskripsi . "\n";
} else {
    echo "KBLI 2025 (47111) NOT FOUND IN DB.\n";
}

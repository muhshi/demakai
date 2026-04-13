<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;

try {
    $kbli = PgKBLI2025::inRandomOrder()->limit(20)->get(['kode', 'judul'])->toArray();
    $kbji = PgKBJI2014::inRandomOrder()->limit(20)->get(['kode', 'judul'])->toArray();
    echo "KBLI:\n";
    foreach ($kbli as $k) {
        echo "{$k['kode']} - {$k['judul']}\n";
    }
    echo "\nKBJI:\n";
    foreach ($kbji as $k) {
        echo "{$k['kode']} - {$k['judul']}\n";
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}

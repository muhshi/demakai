<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;

function getDiverse($model, $limit) {
    return $model::whereNotNull('judul')->inRandomOrder()->limit($limit)->get(['kode', 'judul']);
}

$kbli = getDiverse(PgKBLI2025::class, 20);
$kbji = getDiverse(PgKBJI2014::class, 20);

echo "KBLI 2025:\n";
foreach ($kbli as $k) echo "{$k->kode} | {$k->judul}\n";
echo "KBJI 2014:\n";
foreach ($kbji as $k) echo "{$k->kode} | {$k->judul}\n";

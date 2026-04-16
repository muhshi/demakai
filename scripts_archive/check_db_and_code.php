<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2020;
use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;

echo "Table Counts:\n";
echo "KBLI 2020: " . PgKBLI2020::count() . "\n";
echo "KBLI 2025: " . PgKBLI2025::count() . "\n";
echo "KBJI 2014: " . PgKBJI2014::count() . "\n";

echo "\nChecking for code 47111:\n";
echo "KBLI 2020 47111: " . PgKBLI2020::where('kode', 'like', '47111%')->count() . "\n";
echo "KBLI 2025 47111: " . PgKBLI2025::where('kode', 'like', '47111%')->count() . "\n";
echo "KBJI 2014 47111: " . PgKBJI2014::where('kode', 'like', '47111%')->count() . "\n";

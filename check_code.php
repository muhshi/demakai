<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$code = '56101';
$kblis = DB::connection('sqlite')->table('kbli2025s')->where('kode', $code)->get();

if ($kblis->isEmpty()) {
    echo "Code $code NOT FOUND in SQLite.\n";
} else {
    foreach ($kblis as $kbli) {
        echo "Found in SQLite: [{$kbli->kode}] {$kbli->judul}\n";
        echo "Deskripsi: " . substr($kbli->deskripsi, 0, 100) . "...\n";
    }
}

// Check Postgres too just in case
try {
    $pgKblis = \App\Models\PgKBLI2025::where('kode', $code)->get();
    if ($pgKblis->isEmpty()) {
        echo "Code $code NOT FOUND in Postgres.\n";
    } else {
        foreach ($pgKblis as $kbli) {
            echo "Found in Postgres: [{$kbli->kode}] {$kbli->judul}\n";
        }
    }
} catch (\Exception $e) {
    echo "Postgres check failed: " . $e->getMessage() . "\n";
}

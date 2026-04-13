<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 30 Ground Truth Keywords (15 KBLI + 15 KBJI)
$keywords = [
    // KBLI (Usaha)
    'bakso',
    'laundry',
    'bengkel',
    'ekspedisi',
    'sembako',
    'jualan bakso',
    'warung kopi',
    'toko bangunan',
    'bengkel motor',
    'usaha laundry',
    'usaha jualan bakso keliling',
    'toko sembako di kampung',
    'usaha perkebunan cabe',
    'jasa pengiriman barang',
    'usaha kos kosan',

    // KBJI (Jabatan)
    'kasir',
    'sopir',
    'tukang',
    'admin',
    'kurir',
    'tukang bangunan',
    'sopir truk',
    'kasir toko',
    'kurir paket',
    'admin kantor',
    'penjaga toko sembako',
    'buruh pabrik harian',
    'tukang listrik rumah',
    'teknisi ac rumahan',
    'cleaning service kantor'
];

echo "=================================================\n";
echo "       SYSTEMATIC SQL LIKE TEST RESULTS          \n";
echo "       (Ground Truth Data)                       \n";
echo "=================================================\n\n";

foreach ($keywords as $index => $keyword) {
    echo str_pad(($index + 1) . ". Keyword: '$keyword'", 50, " ") . "\n";
    echo "-------------------------------------------------\n";

    $found = false;

    // Search KBLI 2025
    $kbli = DB::table('kbli2025s')
        ->where('uraian', 'LIKE', "%$keyword%")
        ->get();

    if ($kbli->isNotEmpty()) {
        foreach ($kbli as $item) {
            echo "[KBLI 2025] Found: [{$item->kode}] {$item->judul}\n";
            $found = true;
        }
    }

    // Search KBJI 2014
    $kbji = DB::table('kbji2014s')
        ->where('uraian', 'LIKE', "%$keyword%")
        ->get();

    if ($kbji->isNotEmpty()) {
        foreach ($kbji as $item) {
            echo "[KBJI 2014] Found: [{$item->kode}] {$item->judul}\n";
            $found = true;
        }
    }

    if (!$found) {
        echo "TIDAK DITEMUKAN\n";
    }

    echo "\n";
}

echo "=================================================\n";
echo "                  TEST COMPLETE                  \n";
echo "=================================================\n";

<?php
/**
 * test_hybrid.php — Validasi Hybrid Search
 * Jalankan: php artisan tinker --execute="require 'test_hybrid.php';"
 * Atau langsung: php test_hybrid.php (dari bootstrap manual)
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$queries = [
    'bengkel motor',
    'petani sawah',
    'guru sekolah',
    'ojol',
    'laundry',
];

/** @var \App\Services\SearchService $svc */
$svc = app(\App\Services\SearchService::class);

foreach ($queries as $q) {
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "QUERY  : \"{$q}\"\n";
    echo str_repeat('-', 60) . "\n";

    // 1. Tampilkan hasil preprocessQuery
    $prep = $svc->preprocessQuery($q);
    echo "Tokens   : " . implode(', ', $prep['tokens']) . "\n";
    echo "Expanded : " . implode(', ', array_slice($prep['expanded'], 0, 8)) . "\n";
    echo str_repeat('-', 60) . "\n";

    // 2. Jalankan search
    $start = microtime(true);
    $results = $svc->search($q, 5);
    $ms = round((microtime(true) - $start) * 1000);

    echo "Results  : " . count($results) . " item(s) [{$ms}ms]\n\n";

    foreach ($results as $rank => $item) {
        $no = $rank + 1;
        $kode = $item->kode ?? '-';
        $judul = mb_substr($item->judul ?? '-', 0, 50);
        $distance = number_format($item->distance ?? 1.0, 4);
        $model = isset($item->sumber) ? $item->sumber : (strlen($kode) <= 4 ? 'KBJI' : 'KBLI');
        $boosted = isset($item->boosted) && $item->boosted ? '[BOOSTED]' : '';
        $equiv = isset($item->is_equivalent) && $item->is_equivalent ? '[EQUIV]' : '';

        echo "  #{$no} [{$model}] {$kode} | dist={$distance} {$boosted}{$equiv}\n";
        echo "      {$judul}\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "TEST SELESAI\n";

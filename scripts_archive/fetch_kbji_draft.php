<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$kbjis = \DB::connection('pgsql')
    ->table('kbji2014s')
    ->where(function($query) {
        $query->whereNull('contoh_lapangan')
              ->orWhere('contoh_lapangan', '[]')
              ->orWhere('contoh_lapangan', '""');
    })
    ->inRandomOrder()
    ->limit(20)
    ->get(['kode', 'judul', 'deskripsi']);

foreach ($kbjis as $k) {
    echo "KODE: {$k->kode}\n";
    echo "JUDUL: {$k->judul}\n";
    echo "DESC: " . substr($k->deskripsi, 0, 150) . "...\n";
    echo "-------------------\n";
}

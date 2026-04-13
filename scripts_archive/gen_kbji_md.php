<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$codes = ['732302', '226601', '421399', '514204', '143106', '722304', '611102', '524410', '261102', '314202', '751203', '522201', '723301', '334102', '933303', '242203', '814301', '216503', '122105', '516104'];

echo "| Kode | Judul | Contoh Lapangan (Draft) |\n";
echo "|------|-------|-------------------------|\n";

foreach ($codes as $c) {
    $item = \DB::connection('pgsql')->table('kbji2014s')->where('kode', $c)->first();
    if ($item) {
        echo "| {$item->kode} | {$item->judul} | ... |\n";
    }
}

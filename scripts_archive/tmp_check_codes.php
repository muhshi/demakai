<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$codes = [
    '46795', '17011', '03217', '20115', '68291', 
    '65121', '01493', '50113', '47113', '56101', 
    '10710', '45411', '47721', '49431', '62019', 
    '43211', '47732', '52101', '47723', '56301'
];

foreach ($codes as $c) {
    $item = \DB::connection('pgsql')->table('kbli2025s')->where('kode', $c)->first();
    if ($item) {
        echo "$c: FOUND - {$item->judul}\n";
    } else {
        echo "$c: MISSING\n";
    }
}

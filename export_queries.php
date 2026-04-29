<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$file = fopen(__DIR__.'/python/queries.csv', 'r');
$queries = [];
$header = fgetcsv($file); // no,query,kode_ground_truth,tipe

while ($row = fgetcsv($file)) {
    // If empty row
    if (!$row || count($row) < 4) continue;

    $q = $row[1];
    $kode = $row[2];
    $tipe = $row[3];
    
    if (!isset($queries[$q])) {
        $queries[$q] = [
            'contoh_lapangan' => $q,
            'kbli' => null,
            'kbji' => null
        ];
    }
    
    if ($tipe == 'KBLI') {
        $db = \Illuminate\Support\Facades\DB::connection('pgsql')->table('kbli2025s')->where('kode', $kode)->first();
        $queries[$q]['kbli'] = [
            'kode' => $kode,
            'judul' => $db ? $db->judul : ''
        ];
    } else {
        $db = \Illuminate\Support\Facades\DB::connection('pgsql')->table('kbji2014s')->where('kode', $kode)->first();
        $queries[$q]['kbji'] = [
            'kode' => $kode,
            'judul' => $db ? $db->judul : ''
        ];
    }
}
fclose($file);

$result = array_values($queries);
file_put_contents(__DIR__.'/contoh_lapangan.json', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "File contoh_lapangan.json generated successfully.\n";

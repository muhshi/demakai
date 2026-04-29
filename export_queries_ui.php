<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$file = fopen(__DIR__.'/python/queries.csv', 'r');
$header = fgetcsv($file); // no,query,kode_ground_truth,tipe

$output = [];

while ($row = fgetcsv($file)) {
    if (!$row || count($row) < 4) continue;

    $q = $row[1];
    $kode = $row[2];
    $tipe = $row[3];
    
    if ($tipe == 'KBLI') {
        $db = \Illuminate\Support\Facades\DB::connection('pgsql')->table('kbli2025s')->where('kode', $kode)->first();
        if ($db) {
            $output[] = $db->judul . ' - ' . $q;
        }
    } else {
        $db = \Illuminate\Support\Facades\DB::connection('pgsql')->table('kbji2014s')->where('kode', $kode)->first();
        if ($db) {
            $output[] = $db->judul . ' - ' . $q;
        }
    }
}
fclose($file);

// Also append data from database (UpdateContohLapanganSeeder)
$kbli = \Illuminate\Support\Facades\DB::connection('pgsql')->table('kbli2025s')->whereNotNull('contoh_lapangan')->get();
foreach($kbli as $k) {
    if (!$k->contoh_lapangan) continue;
    $arr = json_decode($k->contoh_lapangan, true);
    if(is_array($arr)) {
        foreach($arr as $c) {
            // Avoid duplicates
            $str = trim($k->judul . ' - ' . $c);
            if (!in_array($str, $output)) {
                $output[] = $str;
            }
        }
    }
}

$kbji = \Illuminate\Support\Facades\DB::connection('pgsql')->table('kbji2014s')->whereNotNull('contoh_lapangan')->get();
foreach($kbji as $k) {
    if (!$k->contoh_lapangan) continue;
    $arr = json_decode($k->contoh_lapangan, true);
    if(is_array($arr)) {
        foreach($arr as $c) {
            $str = trim($k->judul . ' - ' . $c);
            if (!in_array($str, $output)) {
                $output[] = $str;
            }
        }
    }
}

// Remove empty array entries
$output = array_values(array_unique($output));

file_put_contents(__DIR__.'/contoh_lapangan_ui.json', json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "File contoh_lapangan_ui.json generated successfully.\n";

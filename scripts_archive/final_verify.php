<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\SearchService::class);

$queries = [
    'nelayan teripang',
    'distributor tisu wajah',
    'pabrik bubur kertas',
    'peternak lebah madu'
];

foreach ($queries as $q) {
    echo "\nQuery: $q\n";
    $results = $service->search($q, 3);
    if (empty($results)) {
        echo "  No results found.\n";
    } else {
        foreach ($results as $r) {
            echo "  - " . ($r->kode ?? $r['kode']) . " | " . ($r->judul ?? $r['judul']) . "\n";
            $contoh = is_array($r->contoh_lapangan) ? json_encode($r->contoh_lapangan) : $r->contoh_lapangan;
            echo "    Contoh: $contoh\n";
        }
    }
}

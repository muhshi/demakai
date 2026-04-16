<?php

use App\Models\PgKBJI2014;
use App\Services\SearchService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$query = 'polisi';

echo "--- Keyword Check ---\n";
$k1 = PgKBJI2014::where('judul', 'ILIKE', "%$query%")->orWhere('deskripsi', 'ILIKE', "%$query%")->count();
echo "Matches for '$query': $k1\n";

$k2 = PgKBJI2014::where('judul', 'ILIKE', "%kepolisian%")->orWhere('deskripsi', 'ILIKE', "%kepolisian%")->count();
echo "Matches for 'kepolisian': $k2\n";

echo "\n--- Service Test ---\n";
try {
    $service = app(SearchService::class);

    // Mimic the service logic parts
    // 1. Keyword (Protected method access via Reflection)
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('standardSearch');
    $method->setAccessible(true);
    $keywordParams = $method->invoke($service, $query, 5, 'KBJI');

    echo "Keyword Search Results (Top 5):\n";
    foreach ($keywordParams as $k) {
        echo "- [{$k->kode}] {$k->judul} (Dist: " . ($k->distance ?? 'N/A') . ")\n";
    }

    // 2. Semantic (Need to check if we can invoke it publicly or just replicate)
    // We can't easily invoke protected method, but we can check the public search
    echo "\nHybrid Search Results (Top 5):\n";
    $results = $service->search($query, 5, 'KBJI');
    foreach ($results as $item) {
        echo "- [{$item->kode}] {$item->judul} (Dist: {$item->distance})\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

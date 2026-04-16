<?php

use App\Models\PgKBJI2014;
use App\Services\SearchService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$query = 'polisi';

echo "--- START DEBUG ---\n";

try {
    // 1. Direct Query Test
    $count = PgKBJI2014::where('judul', 'ILIKE', "%$query%")
        ->orWhere('deskripsi', 'ILIKE', "%$query%")
        ->count();
    echo "Direct DB Count: $count\n";

    // 2. Test standardSearch
    $service = app(SearchService::class);
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('standardSearch');
    $method->setAccessible(true);

    $results = $method->invoke($service, $query, 10, 'KBJI');
    echo "standardSearch (Reflection) count: " . $results->count() . "\n";
    foreach ($results as $item) {
        $dist = $item->distance ?? 'N/A';
        echo "- [{$item->kode}] {$item->judul} (Distance: $dist)\n";
    }

} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "--- END DEBUG ---\n";

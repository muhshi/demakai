<?php

use Illuminate\Support\Facades\DB;
use App\Services\SearchService;
use App\Services\GeminiService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gemini = app(GeminiService::class);
$service = new SearchService($gemini);

// Reflection
$vectorSearch = (new ReflectionClass($service))->getMethod('vectorSearch');
$vectorSearch->setAccessible(true);
$standardSearch = (new ReflectionClass($service))->getMethod('standardSearch');
$standardSearch->setAccessible(true);
$mergeResults = (new ReflectionClass($service))->getMethod('mergeResults');
$mergeResults->setAccessible(true);

$keywords = ['bakso', 'bengkel', 'laundry', 'toko bangunan', 'ekspedisi'];

foreach ($keywords as $keyword) {
    echo "\n=== Testing keyword: $keyword ===\n";
    try {
        // Hybrid Search Logic Simulation
        $embedding = $gemini->generateEmbedding($keyword);
        $semanticRes = $embedding ? $vectorSearch->invoke($service, $embedding, 5, null) : collect();
        $keywordRes = $standardSearch->invoke($service, $keyword, 5, null);
        $results = $mergeResults->invoke($service, $semanticRes, $keywordRes, $keyword);

        $results = array_slice($results, 0, 3);
        if (empty($results)) {
            echo "No results found.\n";
        }
        foreach ($results as $item) {
            echo "Found: {$item->kode} - {$item->judul} (Dist: {$item->distance})\n";
        }
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

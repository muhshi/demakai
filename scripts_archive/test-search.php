<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting test script...\n";

require __DIR__ . '/vendor/autoload.php';

echo "Autoload loaded.\n";

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Laravel bootstrapped.\n";

try {
    $gemini = new \App\Services\GeminiService();
    $searchService = new \App\Services\SearchService($gemini);

    $query = "pertanian";
    $limit = 5;

    echo "Testing SearchService with query: '$query'\n";
    echo "------------------------------------------------\n";

    $results = $searchService->search($query, $limit);

    if (empty($results)) {
        echo "No results found.\n";
    } else {
        echo "Found " . count($results) . " results:\n\n";
        foreach ($results as $index => $item) {
            $rank = $index + 1;
            // Handle mismatched object/array access if needed
            $kode = $item->kode ?? 'N/A';
            $judul = $item->judul ?? 'N/A';
            $desc = $item->deskripsi ?? '';
            $score = isset($item->distance) ? number_format((1 - $item->distance) * 100, 2) . '%' : 'N/A';

            echo "#$rank [$kode] $judul\n";
            echo "   Similarity: $score\n";
            $shortDesc = strlen($desc) > 100 ? substr($desc, 0, 100) . "..." : $desc;
            echo "   Desc: $shortDesc\n\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    // Log::error("Test Search Error: " . $e->getMessage());
}

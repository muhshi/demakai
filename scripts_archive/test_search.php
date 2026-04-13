<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\SearchController;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Need to resolve SearchController with dependencies
    $controller = app()->make(App\Http\Controllers\Api\SearchController::class);

    // Test Case 1: "pertanian" (Should return many results)
    $request = new Illuminate\Http\Request(['q' => 'pertanian']);
    $response = $controller->search($request);
    $data = $response->getData(true);

    echo "Results for 'pertanian' (Total KBLI2025: " . count($data['kbli2025']) . "):\n";
    foreach (array_slice($data['kbli2025'], 0, 3) as $item) {
        echo "- [{$item['kode']}] {$item['judul']} (Score: {$item['score']})\n";
    }

    // Test Case 2: "konservasi" (Should NOT match 'tani' semantically, only exact matches)
    // Actually let's test a word that has semantic meaning but different spelling.
    // e.g. 'padi' vs 'beras' if strict LIKE.

    echo "\nTest 'padi' (Should be exact match only):\n";
    $request2 = new Illuminate\Http\Request(['q' => 'padi']);
    $response2 = $controller->search($request2);
    $data2 = $response2->getData(true);

    echo "Results for 'padi' (Total KBLI2025: " . count($data2['kbli2025']) . "):\n";
    foreach (array_slice($data2['kbli2025'], 0, 3) as $item) {
        echo "- [{$item['kode']}] {$item['judul']} (Score: {$item['score']})\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

<?php

use Illuminate\Support\Facades\DB;
use App\Services\SearchService;
use App\Services\GeminiService;
use Illuminate\Support\Collection;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 30 Ground Truth Keywords
$keywords = [
    'bakso',
    'laundry',
    'bengkel',
    'ekspedisi',
    'sembako',
    'jualan bakso',
    'warung kopi',
    'toko bangunan',
    'bengkel motor',
    'usaha laundry',
    'usaha jualan bakso keliling',
    'toko sembako di kampung',
    'usaha perkebunan cabe',
    'jasa pengiriman barang',
    'usaha kos kosan',
    'kasir',
    'sopir',
    'tukang',
    'admin',
    'kurir',
    'tukang bangunan',
    'sopir truk',
    'kasir toko',
    'kurir paket',
    'admin kantor',
    'penjaga toko sembako',
    'buruh pabrik harian',
    'tukang listrik rumah',
    'teknisi ac rumahan',
    'cleaning service kantor'
];

$gemini = app(GeminiService::class);
$service = new SearchService($gemini);
$reflection = new ReflectionClass($service);

// Access protected methods to SIMULATE Hybrid Search
// (Since the public search() method might be in "Simple Mode")
$vectorSearch = $reflection->getMethod('vectorSearch');
$vectorSearch->setAccessible(true);
$standardSearch = $reflection->getMethod('standardSearch');
$standardSearch->setAccessible(true);
$mergeResults = $reflection->getMethod('mergeResults');
$mergeResults->setAccessible(true);

echo "====================================================================================================\n";
echo " COMPARISON TEST: SQL LIKE (SQLite) vs HYBRID SEARCH (Postgres)\n";
echo " Data Source: Full Official Data (Synced from Postgres to SQLite)\n";
echo "====================================================================================================\n\n";

foreach ($keywords as $i => $keyword) {
    echo str_pad(($i + 1) . ". Keyword: '$keyword'", 50, " ") . "\n";
    echo str_repeat("-", 100) . "\n";

    // ---------------------------------------------------------
    // 1. SQL LIKE (SQLite)
    // ---------------------------------------------------------
    $sqliteStart = microtime(true);

    // Search both tables generically (like a real Global Search)
    $kbliDocs = DB::connection('sqlite')->table('kbli2025s')
        ->where('uraian', 'LIKE', "%$keyword%")
        ->select('kode', 'judul', 'deskripsi', DB::raw("'KBLI' as type"))
        ->limit(3)
        ->get();

    $kbjiDocs = DB::connection('sqlite')->table('kbji2014s')
        ->where('uraian', 'LIKE', "%$keyword%")
        ->select('kode', 'judul', 'deskripsi', DB::raw("'KBJI' as type"))
        ->limit(3)
        ->get();

    $sqliteResults = $kbliDocs->merge($kbjiDocs);
    $sqliteTime = (microtime(true) - $sqliteStart) * 1000;

    echo sprintf("[SQL LIKE - SQLite] Found: %d (%.2f ms)\n", $sqliteResults->count(), $sqliteTime);

    if ($sqliteResults->isEmpty()) {
        echo "  - Tidak ditemukan\n";
    } else {
        foreach ($sqliteResults->take(3) as $item) {
            echo "  - [{$item->type} {$item->kode}] {$item->judul}\n";
        }
    }
    echo "\n";

    // ---------------------------------------------------------
    // 2. Hybrid Search (Postgres) - SIMULATED via Reflection
    // ---------------------------------------------------------
    // Note: We search BOTH KBLI and KBJI models implicitily (null model arg)
    $hybridStart = microtime(true);

    try {
        // a. Semantic
        $embedding = $gemini->generateEmbedding($keyword);
        if ($embedding) {
            $semanticRes = $vectorSearch->invoke($service, $embedding, 5, null);
        } else {
            $semanticRes = collect();
        }

        // b. Keyword
        $keywordRes = $standardSearch->invoke($service, $keyword, 5, null);

        // c. Merge
        $hybridResults = $mergeResults->invoke($service, $semanticRes, $keywordRes, $keyword);

        // Take top 3
        $hybridResults = array_slice($hybridResults, 0, 3);

        $hybridTime = (microtime(true) - $hybridStart) * 1000;

        echo sprintf("[Hybrid - Postgres] Found: %d (%.2f ms)\n", count($hybridResults), $hybridTime);
        if (empty($hybridResults)) {
            echo "  - Tidak ditemukan\n";
        } else {
            foreach ($hybridResults as $item) {
                // Determine Type based on table source or class
                $type = ($item instanceof \App\Models\PgKBLI2025) ? 'KBLI' : 'KBJI';
                $dist = number_format($item->distance ?? 0, 4);
                echo "  - [$type {$item->kode}] {$item->judul} (Dist: $dist)\n";
            }
        }
    } catch (\Exception $e) {
        echo "  - ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("=", 100) . "\n\n";
}

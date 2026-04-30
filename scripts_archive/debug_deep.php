<?php

use App\Models\PgKBJI2014;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use App\Services\SearchService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();


$query = $argv[1] ?? 'tani';
$limit = 20;

echo "=== DEEP DEBUG FOR '$query' ===\n";

// 1. Direct Keyword Check
echo "\n[1] Keyword Check (ILIKE '%$query%')\n";
$direct = PgKBJI2014::where('judul', 'ILIKE', "%$query%")->orWhere('deskripsi', 'ILIKE', "%$query%")->get();
echo "Found: " . $direct->count() . "\n";
foreach ($direct as $d) {
    echo "  - [{$d->kode}] {$d->judul}\n";
}

// 2. Semantic Check
echo "\n[2] Semantic Check (Vector Distance)\n";
try {
    $gemini = new GeminiService();
    $embedding = $gemini->generateEmbedding($query);

    if ($embedding) {
        $vectorStr = '[' . implode(',', $embedding) . ']';
        $semantic = PgKBJI2014::query()
            ->select('kode', 'judul', DB::raw("embedding <=> '$vectorStr' as distance"))
            ->orderBy('distance')
            ->limit($limit)
            ->get();

        foreach ($semantic as $s) {
            echo "  - [{$s->kode}] {$s->judul} (Dist: {$s->distance})\n";
        }
    } else {
        echo "  FAILED to generate embedding.\n";
    }

    // 3. SearchService Standard Test
    echo "\n[3] SearchService Standard (Synonyms)\n";
    $service = app(SearchService::class);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('standardSearch');
    $method->setAccessible(true);

    $results = $method->invoke($service, $query, 10, 'KBJI');
    foreach ($results as $r) {
        $dist = $r->distance ?? 'N/A';
        // Show raw data to identify why "Dosen" might appear
        echo "  - [{$r->kode}] {$r->judul} (Score/Dist: $dist)\n";
        echo "    Desc: " . substr($r->deskripsi, 0, 100) . "...\n";
    }

    // 4. Full Integration Test (SearchService::search)
    echo "\n[4] Full Search Integration (search())\n";
    // This calls vectorSearch + standardSearch + mergeResults
    $fullResults = $service->search($query, 10, 'KBJI');
    foreach ($fullResults as $r) {
        $dist = $r->distance ?? 'N/A';
        $boosted = $r->boosted ?? false ? '[BOOSTED]' : '';
        echo "  - [{$r->kode}] {$r->judul} (Score: $dist) $boosted\n";
    }

} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";

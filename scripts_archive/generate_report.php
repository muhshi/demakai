<?php

use Illuminate\Support\Facades\DB;
use App\Services\SearchService;
use App\Services\GeminiService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$keywords = [
    // KBLI
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
    // KBJI
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

$vectorSearch = $reflection->getMethod('vectorSearch');
$vectorSearch->setAccessible(true);
$standardSearch = $reflection->getMethod('standardSearch');
$standardSearch->setAccessible(true);
$mergeResults = $reflection->getMethod('mergeResults');
$mergeResults->setAccessible(true);

$output = "# Detailed Comparison Report: SQL LIKE vs Hybrid Search\n\n";
$output .= "| No | Keyword | SQL LIKE (SQLite) | Hybrid Search (Postgres) | Analysis |\n";
$output .= "|---|---|---|---|---|\n";

foreach ($keywords as $i => $keyword) {
    // 1. SQL LIKE
    $sqliteResults = collect();
    $kbliDocs = DB::connection('sqlite')->table('kbli2025s')->where('uraian', 'LIKE', "%$keyword%")->limit(1)->get();
    $kbjiDocs = DB::connection('sqlite')->table('kbji2014s')->where('uraian', 'LIKE', "%$keyword%")->limit(1)->get();
    $sqliteResults = $kbliDocs->merge($kbjiDocs);

    $sqliteText = "";
    if ($sqliteResults->isEmpty()) {
        $sqliteText = "🔴 **0 Found**";
    } else {
        $item = $sqliteResults->first();
        $code = $item->kode;
        $title = strlen($item->judul) > 30 ? substr($item->judul, 0, 27) . '...' : $item->judul;
        $sqliteText = "🟢 **Found**: `[$code] $title`";
    }

    // 2. Hybrid Search
    $hybridText = "";
    try {
        $embedding = $gemini->generateEmbedding($keyword);
        $semanticRes = $embedding ? $vectorSearch->invoke($service, $embedding, 5, null) : collect();
        $keywordRes = $standardSearch->invoke($service, $keyword, 5, null);
        $hybridResults = $mergeResults->invoke($service, $semanticRes, $keywordRes, $keyword);
        $hybridResults = array_slice($hybridResults, 0, 1); // Take top 1

        if (empty($hybridResults)) {
            $hybridText = "🔴 **0 Found**";
        } else {
            $item = $hybridResults[0];
            $code = $item->kode;
            $title = strlen($item->judul) > 30 ? substr($item->judul, 0, 27) . '...' : $item->judul;
            $dist = number_format($item->distance ?? 0, 3);
            $hybridText = "🟢 **Found**: `[$code] $title` (Dist: $dist)";
        }
    } catch (\Exception $e) {
        $hybridText = "⚠️ Error";
    }

    // Analysis
    $analysis = "";
    if (str_contains($sqliteText, "0 Found") && str_contains($hybridText, "Found")) {
        $analysis = "✅ **Hybrid Wins** (Smart Match)";
    } elseif (str_contains($sqliteText, "Found") && str_contains($hybridText, "Found")) {
        $analysis = "⚖️ **Equal**";
    } elseif (str_contains($sqliteText, "0 Found") && str_contains($hybridText, "0 Found")) {
        $analysis = "❌ **Both Failed**";
    } else {
        $analysis = "❓ Check";
    }

    $num = $i + 1;
    $output .= "| $num | `$keyword` | $sqliteText | $hybridText | $analysis |\n";
}

file_put_contents('comparison_report.md', $output);
echo "Report generated at comparison_report.md";

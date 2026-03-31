<?php

use App\Models\PgKBJI2014;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$query = 'polisi';
$limit = 5;

echo "--- Replicating standardSearch for '$query' ---\n";

// Logic from SearchService::standardSearch
$results = collect();
$exactResults = collect();

// 1. Exact
$exactResults = $exactResults->merge(
    PgKBJI2014::where('judul', 'ILIKE', "%{$query}%")
        ->orWhere('deskripsi', 'ILIKE', "%{$query}%")
        ->limit($limit)
        ->get()
        ->each(fn($item) => $item->distance = 0.0)
);

echo "Exact Results: " . $exactResults->count() . "\n";
if ($exactResults->isNotEmpty()) {
    foreach ($exactResults as $k) {
        echo "- [{$k->kode}] {$k->judul} (Dist: {$k->distance})\n";
    }
}

// 2. Keywords
$keywords = array_filter(explode(' ', strtolower(trim($query))));
echo "Keywords: " . implode(', ', $keywords) . "\n";

$kbji = PgKBJI2014::query()
    ->where(function ($q) use ($keywords) {
        foreach ($keywords as $kw) {
            $q->where(function ($subQ) use ($kw) {
                $like = '%' . $kw . '%';
                $subQ->where('kode', 'ILIKE', $like)
                    ->orWhere('judul', 'ILIKE', $like)
                    ->orWhere('deskripsi', 'ILIKE', $like);
            });
        }
    })
    ->limit($limit)
    ->get()
    ->each(function ($item) {
        $item->distance = 0.05;
    });

echo "Keyword Results: " . $kbji->count() . "\n";
foreach ($kbji as $k) {
    echo "- [{$k->kode}] {$k->judul} (Dist: {$k->distance})\n";
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PgKBJI2014;
use App\Models\PgKBLI2020;
use App\Models\PgKBLI2025;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');
        if (empty($query)) {
            return response()->json(['results' => []]);
        }

        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            return response()->json(['error' => 'API Key not configured'], 500);
        }

        try {
            // 1. Generate Embedding for the query
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$apiKey}", [
                'content' => [
                    'parts' => [
                        ['text' => $query]
                    ]
                ]
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to generate embedding'], 500);
            }

            $embedding = $response->json()['embedding']['values'];
            $vectorString = '[' . implode(',', $embedding) . ']';

            // 2. Search across tables using Weighted Hybrid Logic
            // Split query into individual keywords for better matching
            $keywords = array_filter(explode(' ', strtolower(trim($query))));

            // Build dynamic CASE expressions for each keyword
            $keywordScoreSql = $this->buildKeywordScoreSql($keywords);
            $keywordBindings = $this->buildKeywordBindings($keywords);

            // KBLI 2025 - Hybrid (Keywords + AI Distance)
            $kbli2025 = PgKBLI2025::query()
                ->select(['kode', 'judul', 'deskripsi', 'contoh_lapangan'])
                ->selectRaw('embedding <=> ? as distance', [$vectorString])
                ->selectRaw("({$keywordScoreSql}) as keyword_score", $keywordBindings)
                ->whereNotNull('embedding')
                ->whereRaw('LENGTH(kode) = 5')
                ->orderByDesc('keyword_score')
                ->orderBy('distance')
                ->limit(15)
                ->get()
                ->map(fn($item) => $this->formatResult($item, 'KBLI 2025'));

            // KBLI 2020 - Hybrid
            $kbli2020 = PgKBLI2020::query()
                ->select(['kode', 'judul', 'deskripsi', 'contoh_lapangan'])
                ->selectRaw('embedding <=> ? as distance', [$vectorString])
                ->selectRaw("({$keywordScoreSql}) as keyword_score", $keywordBindings)
                ->whereNotNull('embedding')
                ->whereRaw('LENGTH(kode) = 5')
                ->orderByDesc('keyword_score')
                ->orderBy('distance')
                ->limit(10)
                ->get()
                ->map(fn($item) => $this->formatResult($item, 'KBLI 2020'));

            // KBJI 2014 - Hybrid (Prioritize keyword matching, fallback to AI)
            // For KBJI, we use a combined approach: keyword matches OR embedding similarity
            $kbji = PgKBJI2014::query()
                ->select(['kode', 'judul', 'deskripsi', 'contoh_lapangan'])
                ->selectRaw('COALESCE(embedding <=> ?, 999) as distance', [$vectorString])
                ->selectRaw("({$keywordScoreSql}) as keyword_score", $keywordBindings)
                ->whereRaw('LENGTH(kode) = 4')
                ->where(function ($q) use ($keywords) {
                    // Match if any keyword appears in any field
                    foreach ($keywords as $kw) {
                        $like = '%' . $kw . '%';
                        $q->orWhereRaw('LOWER(judul) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(deskripsi) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(contoh_lapangan::text) LIKE ?', [$like]);
                    }
                    // Or has embedding for AI search
                    $q->orWhereNotNull('embedding');
                })
                ->orderByDesc('keyword_score')
                ->orderBy('distance')
                ->limit(15)
                ->get()
                ->map(fn($item) => $this->formatResult($item, 'KBJI 2014'));

            return response()->json([
                'kbli2025' => $kbli2025,
                'kbli2020' => $kbli2020,
                'kbji' => $kbji->sortByDesc('keyword_score')->values()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function formatResult($item, $type)
    {
        return [
            'type' => $type,
            'kode' => $item->kode,
            'judul' => $item->judul,
            'deskripsi' => $item->deskripsi,
            'contoh' => is_array($item->contoh_lapangan) ? $item->contoh_lapangan : [],
            'distance' => (float) $item->distance,
            'keyword_score' => (int) $item->keyword_score,
            // Score visualization adjusted for hybrid
            'score' => $item->keyword_score > 0
                ? round(90 + (1 - (float) $item->distance) * 10, 1) // Boosted score for keywords
                : round((1 - (float) $item->distance) * 85, 1)     // Standard score for AI only
        ];
    }

    /**
     * Build SQL for calculating keyword score across multiple words
     * Priority: kode (500) > contoh_lapangan (200) > judul (100) > deskripsi (20)
     */
    protected function buildKeywordScoreSql(array $keywords): string
    {
        if (empty($keywords)) {
            return '0';
        }

        $cases = [];
        foreach ($keywords as $kw) {
            // Highest weight for exact kode match
            $cases[] = "(CASE WHEN kode = ? THEN 500 ELSE 0 END)";
            // High weight for kode partial match
            $cases[] = "(CASE WHEN kode LIKE ? THEN 300 ELSE 0 END)";
            // Higher weight for contoh_lapangan matches
            $cases[] = "(CASE WHEN LOWER(contoh_lapangan::text) LIKE ? THEN 200 ELSE 0 END)";
            // Medium weight for judul
            $cases[] = "(CASE WHEN LOWER(judul) LIKE ? THEN 100 ELSE 0 END)";
            // Lower weight for deskripsi
            $cases[] = "(CASE WHEN LOWER(deskripsi) LIKE ? THEN 20 ELSE 0 END)";
        }

        return implode(' + ', $cases);
    }

    /**
     * Build bindings array for the keyword score SQL
     */
    protected function buildKeywordBindings(array $keywords): array
    {
        $bindings = [];
        foreach ($keywords as $kw) {
            $like = '%' . strtolower($kw) . '%';
            // 5 bindings per keyword (kode exact, kode partial, contoh, judul, deskripsi)
            $bindings[] = $kw;           // kode exact match
            $bindings[] = '%' . $kw . '%'; // kode partial match
            $bindings[] = $like;
            $bindings[] = $like;
            $bindings[] = $like;
        }
        return $bindings;
    }
}

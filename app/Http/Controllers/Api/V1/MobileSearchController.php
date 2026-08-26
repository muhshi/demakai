<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FieldExampleSubmission;
use App\Models\Kbli2025Hierarchy;
use App\Models\PgKBJI2014;
use App\Models\PgKBLI2020;
use App\Models\PgKBLI2025;
use App\Services\SearchService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobileSearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search KBLI 2025 / KBJI 2014 optimized for mobile Flutter client.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        if (empty($query)) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'query' => '',
                    'results' => [],
                    'total' => 0,
                ],
                'meta' => [
                    'version' => config('app.version', '2026.08.1'),
                    'search_method' => 'none',
                ]
            ]);
        }

        $limit = min((int) $request->input('limit', 15), 50);
        $type = strtoupper((string) $request->input('type', '')); // 'KBLI' | 'KBJI' | ''
        $searchMethod = $request->input('search_method', config('services.python_search.method', 'hybrid'));
        $processing = $request->input('processing', config('services.python_search.processing', 'expansion'));

        // Set dynamic config for search service
        config([
            'services.python_search.enabled' => config('services.python_search.enabled', true),
            'services.python_search.method' => $searchMethod,
            'services.python_search.processing' => $processing,
        ]);

        $results = [];

        // 1. KBLI 2025
        if (empty($type) || $type === 'KBLI' || $type === 'KBLI2025') {
            $rawKbli = $this->searchService->search($query, $limit, 'KBLI');
            foreach ($rawKbli as $item) {
                $results[] = $this->formatResult($item, 'KBLI 2025');
            }
        }

        // 2. KBJI 2014
        if (empty($type) || $type === 'KBJI' || $type === 'KBJI2014') {
            $rawKbji = $this->searchService->search($query, $limit, 'KBJI');
            foreach ($rawKbji as $item) {
                $results[] = $this->formatResult($item, 'KBJI 2014');
            }
        }

        // Sort combined results by score descending
        usort($results, fn($a, $b) => ($b['score'] <=> $a['score']));

        // Slice to requested limit if combined
        $finalResults = array_slice($results, 0, $limit);

        return response()->json([
            'status' => 'success',
            'data' => [
                'query' => $query,
                'results' => $finalResults,
                'total' => count($finalResults),
            ],
            'meta' => [
                'version' => config('app.version', '2026.08.1'),
                'search_method' => $searchMethod,
                'processing' => $processing,
                'timestamp' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Get KBLI 2025 Hierarchy tree for mobile navigation.
     */
    public function getHierarchy(Request $request): JsonResponse
    {
        $parentKode = $request->query('parent');

        if (empty($parentKode) || $parentKode === 'null') {
            // Level Kategori (A-U)
            $nodes = Kbli2025Hierarchy::whereNull('parent_kode')
                ->orderBy('kode')
                ->get()
                ->map(fn($item) => [
                    'kode' => $item->kode,
                    'judul' => $item->judul,
                    'deskripsi' => $item->deskripsi,
                    'level' => 'kategori',
                    'is_leaf' => false,
                ]);

            return response()->json([
                'status' => 'success',
                'data' => $nodes
            ]);
        }

        // Level 5-digit kelompok (leaf)
        if (strlen($parentKode) >= 4) {
            $children = PgKBLI2025::where('kode', 'like', $parentKode . '%')
                ->orderBy('kode')
                ->get()
                ->map(function ($item) {
                    return [
                        'kode' => $item->kode,
                        'judul' => $item->judul,
                        'deskripsi' => $item->deskripsi,
                        'contoh_lapangan' => $item->contoh_lapangan ?? [],
                        'level' => 'kelompok',
                        'is_leaf' => true,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $children
            ]);
        }

        // Hierarchy intermediate levels
        $nodes = Kbli2025Hierarchy::where('parent_kode', $parentKode)
            ->orderBy('kode')
            ->get()
            ->map(function ($item) {
                return [
                    'kode' => $item->kode,
                    'judul' => $item->judul,
                    'deskripsi' => $item->deskripsi,
                    'level' => $item->level,
                    'is_leaf' => false,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $nodes
        ]);
    }

    /**
     * Single crowdsourcing submission from mobile client.
     */
    public function storeSubmission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:KBLI,KBJI,KBLI 2025,KBJI 2014',
            'kode' => 'required|string|max:10',
            'content' => 'required|string|min:3|max:500',
            'submitter_name' => 'nullable|string|max:100',
            'device_id' => 'nullable|string|max:100',
        ]);

        $submission = FieldExampleSubmission::create([
            'type' => $validated['type'],
            'kode' => $validated['kode'],
            'content' => $validated['content'],
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Terima kasih! Pengajuan contoh lapangan Anda berhasil dikirim dan akan diverifikasi.',
            'data' => [
                'id' => $submission->id,
                'kode' => $submission->kode,
                'status' => $submission->status,
            ]
        ], 201);
    }

    /**
     * Bulk crowdsourcing submissions recorded while surveyor was offline in the field.
     */
    public function bulkSyncSubmissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'submissions' => 'required|array|min:1|max:100',
            'submissions.*.type' => 'required|string',
            'submissions.*.kode' => 'required|string|max:10',
            'submissions.*.content' => 'required|string|min:3|max:500',
            'submissions.*.local_created_at' => 'nullable|string',
            'submissions.*.device_id' => 'nullable|string',
        ]);

        $savedCount = 0;
        foreach ($validated['submissions'] as $item) {
            try {
                FieldExampleSubmission::create([
                    'type' => $item['type'],
                    'kode' => $item['kode'],
                    'content' => $item['content'],
                    'status' => 'pending',
                ]);
                $savedCount++;
            } catch (Exception $e) {
                Log::warning('Bulk submission item error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil menyinkronkan {$savedCount} catatan lapangan offline.",
            'data' => [
                'synced_count' => $savedCount,
            ]
        ]);
    }

    /**
     * Format result row into a standardized response array.
     */
    protected function formatResult($item, string $type): array
    {
        $get = function ($key, $default = null) use ($item) {
            if (is_array($item)) return $item[$key] ?? $default;
            return $item->{$key} ?? $default;
        };

        $distance = $get('distance');
        $score = $distance !== null ? max(0, min(100, round((1 - (float) $distance) * 100))) : 0;

        $contoh = $get('contoh_lapangan');
        if (!is_array($contoh)) {
            if (is_string($contoh)) {
                $contoh = json_decode($contoh, true) ?? [];
            } else {
                $contoh = [];
            }
        }

        $matchType = 'semantic';
        if ($distance === 0.0 || $distance === null) {
            $matchType = 'exact';
        } elseif ($distance <= 0.05) {
            $matchType = 'keyword';
        } elseif (!empty($get('boosted'))) {
            $matchType = 'crowdsourcing';
        }

        return [
            'type' => $type,
            'kode' => (string) $get('kode', ''),
            'judul' => (string) $get('judul', ''),
            'deskripsi' => (string) $get('deskripsi', ''),
            'contoh_lapangan' => $contoh,
            'score' => (int) $score,
            'match_type' => $matchType,
            'is_equivalent' => (bool) $get('is_equivalent', false),
        ];
    }
}

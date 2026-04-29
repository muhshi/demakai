<?php

namespace App\Http\Controllers\Api;

use App\Services\SearchService;
use App\Http\Controllers\Controller;
use App\Models\PgKBJI2014;
use App\Models\PgKBLI2020;
use App\Models\PgKBLI2025;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        if (empty($query)) {
            return response()->json(['results' => []]);
        }

        // Ambil metode yang dipilih dari request (dikirim dari browser)
        // Nilai default dari .env jika tidak dikirim
        $searchMethod = $request->input(
            'search_method',
            config('services.python_search.method', 'sql')
        );
        $processing = $request->input(
            'processing',
            config('services.python_search.processing', 'none')
        );

        // Simpan sementara ke config agar SearchService bisa membacanya
        $pythonEnabled = config('services.python_search.enabled', false);
        config([
            'services.python_search.enabled' => $pythonEnabled,
            'services.python_search.method' => $searchMethod,
            'services.python_search.processing' => $processing,
        ]);

        // 1. KBLI 2025 (Python Search)
        $rawKbli2025 = $this->searchService->search($query, 15, 'KBLI');
        $kbli2025 = collect($rawKbli2025)
            ->map(fn($item) => $this->formatResult($item, 'KBLI 2025'))
            ->values();

        // 2. KBJI 2014 (Python Search)
        $rawKbji = $this->searchService->search($query, 15, 'KBJI');
        $kbji = collect($rawKbji)
            ->map(fn($item) => $this->formatResult($item, 'KBJI 2014'))
            ->values();

        // 3. KBLI 2020 (Legacy SQL - Belum di-index vektor)
        $keywords = array_filter(explode(' ', strtolower(trim($query))));
        $kbli2020 = PgKBLI2020::query()
            ->select(['kode', 'judul', 'deskripsi', 'contoh_lapangan'])
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $like = '%' . $kw . '%';
                    $q->orWhere('kode', 'like', $like)
                        ->orWhereRaw('LOWER(judul) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(deskripsi) LIKE ?', [$like]);
                }
            })
            ->limit(10)
            ->get()
            ->map(fn($item) => $this->formatResult($item, 'KBLI 2020'));

        return response()->json([
            'kbli2025' => $kbli2025,
            'kbli2020' => $kbli2020,
            'kbji' => $kbji,
            'meta' => [
                'search_method' => $searchMethod,
                'processing' => $processing,
            ],
        ]);
    }


    protected function formatResult($item, $type)
    {
        // Support array (dari Python API) dan Eloquent object (dari query PHP)
        $get = function ($key, $default = null) use ($item) {
            if (is_array($item))
                return $item[$key] ?? $default;
            return $item->{$key} ?? $default;
        };

        $distance = $get('distance');
        $score = $distance !== null ? round((1 - (float) $distance) * 100) : 0;

        $contoh = $get('contoh_lapangan');
        if (is_array($contoh)) {
            // sudah array
        } elseif (is_string($contoh)) {
            $contoh = json_decode($contoh, true) ?? [];
        } else {
            $contoh = [];
        }

        return [
            'type' => $type,
            'kode' => $get('kode', ''),
            'judul' => $get('judul', ''),
            'deskripsi' => $get('deskripsi', ''),
            'contoh' => $contoh,
            'score' => $score,
        ];
    }

}

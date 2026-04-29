<?php

namespace App\Services;

use Exception;
use App\Models\SearchHistory;
use App\Models\PgKBJI2014;
use App\Models\PgKBLI2025;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchService
{
    protected GeminiService $gemini;

    // ============================================================
    // STOPWORD LIST — kata yang tidak informatif untuk search
    // ============================================================
    protected array $stopwords = [
        // Kata kerja / status umum
        'usaha',
        'jasa',
        'kerja',
        'kegiatan',
        'aktivitas',
        'bekerja',
        'melakukan',
        'menjalankan',
        'bergerak',
        // Preposisi & konjungsi
        'di',
        'ke',
        'dari',
        'dan',
        'yang',
        'untuk',
        'dengan',
        'atau',
        'pada',
        'dalam',
        'oleh',
        'sebagai',
        'adalah',
        'itu',
        'ini',
        'saya',
        'kami',
        'kita',
        'mereka',
        'anda',
        // Kata umum lain
        'tukang',
        'pedagang',
        'pekerja',
    ];

    // ============================================================
    // SYNONYM DICTIONARY — dikelompokkan per domain
    // Tambahkan entry baru di section yang relevan
    // ============================================================
    protected array $synonymDictionary = [

        // --- TRANSPORTASI & LOGISTIK ---
        'ojol' => ['ojek', 'angkutan', 'kurir', 'pengemudi'],
        'ojek' => ['angkutan', 'pengemudi'],
        'driver' => ['pengemudi', 'sopir', 'angkutan'],
        'sopir' => ['pengemudi', 'angkutan'],
        'kurir' => ['pengantar', 'pos', 'logistik', 'pengiriman'],
        'ekspedisi' => ['angkutan', 'pengiriman', 'kurir', 'logistik'],
        'pengiriman' => ['kurir', 'ekspedisi', 'logistik', 'pos'],

        // --- PERDAGANGAN & RETAIL ---
        'toko' => ['eceran', 'perdagangan', 'retail'],
        'jualan' => ['eceran', 'perdagangan'],
        'dagang' => ['eceran', 'perdagangan'],
        'warung' => ['penyediaan makan', 'kedai', 'rumah makan'],
        'sembako' => ['makanan', 'minuman', 'kebutuhan pokok'],
        'minimarket' => ['eceran', 'perdagangan', 'supermarket'],
        'supermarket' => ['eceran', 'perdagangan', 'minimarket'],

        // --- INDUSTRI & PRODUKSI ---
        'pabrik' => ['industri', 'pengolahan', 'manufaktur', 'pembuatan'],
        'bengkel' => ['reparasi', 'perbaikan', 'service', 'pemeliharaan'],
        'bangunan' => ['konstruksi', 'pembangunan'],
        'kontraktor' => ['konstruksi', 'pembangunan'],

        // --- PERTANIAN & PERIKANAN ---
        'tani' => ['pertanian', 'bercocok tanam'],
        'petani' => ['pertanian', 'perkebunan'],
        'nelayan' => ['perikanan', 'penangkapan ikan', 'tangkap'],
        'kebun' => ['perkebunan', 'pertanian'],
        'sawah' => ['pertanian', 'padi'],
        'ternak' => ['peternakan', 'hewan'],

        // --- KESEHATAN ---
        'dokter' => ['medis', 'kesehatan', 'praktik'],
        'obat' => ['farmasi', 'apotek', 'medis'],
        'bidan' => ['kesehatan', 'kebidanan'],
        'perawat' => ['kesehatan', 'keperawatan'],
        'klinik' => ['kesehatan', 'praktik', 'medis'],
        'apotek' => ['farmasi', 'obat'],

        // --- PENDIDIKAN ---
        'guru' => ['pengajar', 'pendidikan', 'pengajaran'],
        'dosen' => ['pengajar', 'pendidikan tinggi', 'akademik'],
        'mengajar' => ['pendidikan', 'pengajaran'],
        'sekolah' => ['pendidikan', 'pengajaran'],

        // --- KEAMANAN ---
        'polisi' => ['kepolisian'],
        'kepolisian' => ['polisi'],
        'satpam' => ['keamanan', 'penjaga', 'pengamanan'],
        'security' => ['keamanan', 'penjaga', 'pengamanan'],

        // --- KEBERSIHAN & DOMESTIC ---
        'laundry' => ['binatu', 'pencucian', 'cuci'],
        'cuci' => ['binatu', 'pencucian', 'laundry'],
        'cleaning' => ['kebersihan', 'petugas kebersihan'],
        'pembantu' => ['pekerja rumah tangga', 'asisten rumah tangga'],

        // --- ADMINISTRASI & OFFICE ---
        'admin' => ['administrasi', 'tata usaha'],
        'sekretaris' => ['administrasi', 'tata usaha'],
        'kasir' => ['kasir', 'penjualan', 'keuangan'],
        'freelance' => ['pekerja lepas', 'jasa perorangan'],

        // --- MAKANAN & MINUMAN ---
        'masak' => ['masakan', 'makanan', 'restoran'],
        'catering' => ['jasa boga', 'penyediaan makanan'],
        'restoran' => ['rumah makan', 'penyediaan makan'],
        'cafe' => ['kedai', 'rumah makan', 'minuman'],
    ];

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    // ============================================================
    // PREPROCESSING PIPELINE
    // Memproses raw query menjadi token bersih + token terexpand
    // ============================================================
    public function preprocessQuery(string $query): array
    {
        // Step 1: Normalisasi — lowercase, trim, hapus karakter non-alfanumerik
        $clean = strtolower(trim($query));
        $clean = preg_replace('/[^a-z0-9\s]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        // Step 2: Tokenize
        $tokens = array_values(array_filter(explode(' ', $clean)));

        // Step 3: Stopword removal
        $filtered = array_values(array_diff($tokens, $this->stopwords));

        // Fallback: jika semua token dihapus stopword, gunakan token original
        if (empty($filtered)) {
            $filtered = $tokens;
        }

        // Step 4: Synonym expansion
        $expanded = $filtered;
        foreach ($filtered as $token) {
            if (isset($this->synonymDictionary[$token])) {
                foreach ($this->synonymDictionary[$token] as $syn) {
                    // Pecah sinonim multi-kata menjadi token individual
                    $synTokens = explode(' ', $syn);
                    foreach ($synTokens as $st) {
                        if (!empty($st) && !in_array($st, $expanded)) {
                            $expanded[] = $st;
                        }
                    }
                }
            }
        }

        return [
            'original' => $query,                                    // Input asli user
            'clean' => implode(' ', $filtered),                   // Setelah stopword removal
            'tokens' => $filtered,                                 // Token utama (tanpa stopword)
            'expanded' => array_values(array_unique($expanded)),     // Token + sinonim
        ];
    }

    /**
     * Perform hybrid search (Semantic + Keyword)
     * 
     * @param string $query Use query string
     * @param int $limit
     * @param string|null $model 'KBLI' or 'KBJI' (null for both)
     * @return array
     */
    public function search(string $query, int $limit = 10, ?string $model = null): array
    {
        if (config('services.python_search.enabled', false)) {
            $results = $this->searchViaPython($query, $limit, $model);
            
            // Jika hasil tidak null, berarti API sukses (meskipun results-nya kosong [])
            if ($results !== null) {
                return $results;
            }
            
            // Jika hasil NULL, berarti ada error koneksi API -> Lanjut ke fallback SQL di bawah
            Log::info("Falling back to local SQL search for query: {$query}");
        }

        // ============================================================
        // SQL LIKE MODE (Fallback / Legacy)
        // ============================================================
        
        // Step 0: Preprocess query — normalisasi, stopword removal, synonym expansion
        $preprocessed = $this->preprocessQuery($query);
        $tokens = $preprocessed['tokens'];
        $clean = $preprocessed['clean'];

        $results = collect();
        
        // Fungsi helper untuk membangun query per token
        $applyQuery = function ($q) use ($tokens, $clean) {
            // Coba exact phrase dulu (skor tertinggi)
            $q->where(function($sub) use ($clean) {
                $like = '%' . $clean . '%';
                $sub->orWhere('judul', 'ILIKE', $like)
                    ->orWhere('deskripsi', 'ILIKE', $like)
                    ->orWhereRaw("CAST(contoh_lapangan AS TEXT) ILIKE ?", [$like]);
            });

            // Jika banyak kata, tambahkan AND logic per token agar lebih fleksibel
            if (count($tokens) > 1) {
                $q->orWhere(function($sub) use ($tokens) {
                    foreach ($tokens as $token) {
                        $like = '%' . $token . '%';
                        $sub->where(function($tokenQuery) use ($like) {
                            $tokenQuery->orWhere('judul', 'ILIKE', $like)
                                ->orWhere('deskripsi', 'ILIKE', $like)
                                ->orWhereRaw("CAST(contoh_lapangan AS TEXT) ILIKE ?", [$like]);
                        });
                    }
                });
            }
        };

        if ($model === null || $model === 'KBLI') {
            $results = $results->merge(
                PgKBLI2025::where($applyQuery)->limit($limit)->get()
            );
        }
        if ($model === null || $model === 'KBJI') {
            $results = $results->merge(
                PgKBJI2014::where($applyQuery)->limit($limit)->get()
            );
        }

        $finalResults = $results->take($limit)->all();
        $this->logHistory($query, $finalResults);
        return $finalResults;
    }

    /**
     * Panggil Python FastAPI server untuk pencarian.
     * Server harus aktif: uvicorn api:app --host 127.0.0.1 --port 8000
     *
     * Konfigurasi di .env:
     *   PYTHON_SEARCH_ENABLED=true
     *   PYTHON_SEARCH_URL=http://127.0.0.1:8000
     *   PYTHON_SEARCH_METHOD=sql          (sql | hybrid)
     *   PYTHON_SEARCH_PROCESSING=none     (none | basic | advanced)
     */
    public function searchViaPython(string $query, int $limit = 10, ?string $model = null): ?array
    {
        $url = config('services.python_search.url', 'http://127.0.0.1:8000');
        $searchMethod = config('services.python_search.method', 'sql');
        $processing = config('services.python_search.processing', 'none');

        $mode = null;
        if ($searchMethod === 'sql' && $processing === 'expansion') $mode = 'sql_expansion';
        if ($searchMethod === 'hybrid' && $processing === 'expansion') $mode = 'hybrid_expansion';

        $payload = [
            'query' => $query,
            'search' => $searchMethod,
            'processing' => $processing,
            'mode' => $mode,
            'limit' => $limit,
            'model' => $model,
        ];

        try {
            // Gunakan timeout lebih pendek untuk fallback yang cepat
            $response = Http::timeout(8)->post("{$url}/search", $payload);

            if ($response->failed()) {
                Log::warning('Python search API error response: ' . $response->status() . ' - ' . $response->body());
                return null; // Return null agar memicu fallback ke SQL
            }

            $data = $response->json();
            $results = collect($data['results'] ?? []);
            
            // Log sukses untuk debugging
            Log::debug("Python search success: found " . $results->count() . " results.");
            
            $this->logHistory($query, $results->all());
            return $results->all();

        } catch (Exception $e) {
            Log::error('Python search API unreachable (Connection Error): ' . $e->getMessage(), [
                'url' => $url,
                'payload' => $payload
            ]);
            return null; // Return null agar memicu fallback ke SQL
        }
    }


    protected function logHistory(string $query, array $results)
    {
        try {
            $count = count($results);
            $type = 'semantic'; // Default

            if ($count > 0) {
                $firstItem = $results[0];
                // Support array (Python API) dan Eloquent object
                $distance = is_array($firstItem)
                    ? ($firstItem['distance'] ?? 1.0)
                    : ($firstItem->distance ?? 1.0);

                if ($distance === 0.0) {
                    $type = 'exact';
                } elseif ($distance <= 0.05) {
                    $type = 'keyword';
                }
            }

            SearchHistory::create([
                'query'          => $query,
                'results_count'  => $count,
                'detected_type'  => $type,
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'user_id'        => auth()->id(),
            ]);
        } catch (Exception $e) {
            Log::warning('Search history logging failed: ' . $e->getMessage());
        }
    }

    protected function vectorSearch(array $embedding, int $limit, ?string $model = null): Collection
    {
        // Convert array to pgvector string format: [1,2,3]
        // Explicitly ensuring proper formatting for pgvector
        $vectorStr = '[' . implode(',', $embedding) . ']';

        $results = collect();

        // Search KBLI
        if ($model === null || $model === 'KBLI') {
            $kbli = PgKBLI2025::query()
                ->select('*', DB::raw("embedding <=> '$vectorStr' as distance"))
                ->orderBy('distance')
                ->limit($limit)
                ->get();
            $results = $results->merge($kbli);
        }

        // Search KBJI
        if ($model === null || $model === 'KBJI') {
            $kbji = PgKBJI2014::query()
                ->select('*', DB::raw("embedding <=> '$vectorStr' as distance"))
                ->orderBy('distance')
                ->limit($limit)
                ->get();
            $results = $results->merge($kbji);
        }

        return $results->sortBy('distance')->take($limit);
    }

    /**
     * Keyword search menggunakan hasil preprocessQuery().
     * Tidak lagi menduplikasi synonym map — semua ada di $synonymDictionary.
     */
    protected function standardSearch(array $preprocessed, int $limit, ?string $model = null): Collection
    {
        $results = collect();
        $clean = $preprocessed['clean'];      // query bersih (tanpa stopword)
        $tokens = $preprocessed['tokens'];     // token utama
        $expanded = $preprocessed['expanded'];  // token + sinonim

        // 1. Exact Phrase Match — gunakan query bersih
        if (!empty($clean)) {
            if ($model === null || $model === 'KBLI') {
                $results = $results->merge(
                    PgKBLI2025::where('judul', 'ILIKE', "%{$clean}%")
                        ->orWhere('deskripsi', 'ILIKE', "%{$clean}%")
                        ->orWhereRaw("CAST(contoh_lapangan AS TEXT) ILIKE ?", ["%{$clean}%"])
                        ->limit($limit)->get()
                        ->each(fn($item) => $item->distance = 0.0)
                );
            }
            if ($model === null || $model === 'KBJI') {
                $results = $results->merge(
                    PgKBJI2014::where('judul', 'ILIKE', "%{$clean}%")
                        ->orWhere('deskripsi', 'ILIKE', "%{$clean}%")
                        ->orWhereRaw("CAST(contoh_lapangan AS TEXT) ILIKE ?", ["%{$clean}%"])
                        ->limit($limit)->get()
                        ->each(fn($item) => $item->distance = 0.0)
                );
            }

            if ($results->isNotEmpty()) {
                return $results->take($limit);
            }
        }

        // 2. Multi-token Keyword Search — gunakan expanded tokens dari preprocessor
        if (empty($expanded)) {
            return collect();
        }

        // Setiap token jadi satu "group" — record harus match SEMUA token (AND logic)
        $applyQuery = function ($q) use ($expanded) {
            foreach ($expanded as $word) {
                $like = '%' . $word . '%';
                $q->where(function ($subQ) use ($like) {
                    $subQ->orWhere('kode', 'ILIKE', $like)
                        ->orWhere('judul', 'ILIKE', $like)
                        ->orWhere('deskripsi', 'ILIKE', $like)
                        ->orWhereRaw("CAST(contoh_lapangan AS TEXT) ILIKE ?", [$like]);
                });
            }
        };

        if ($model === null || $model === 'KBLI') {
            $kbli = PgKBLI2025::query()->where($applyQuery)->limit($limit)->get()->each(fn($item) => $item->distance = 0.05);
            $results = $results->merge($kbli);
        }
        if ($model === null || $model === 'KBJI') {
            $kbji = PgKBJI2014::query()->where($applyQuery)->limit($limit)->get()->each(fn($item) => $item->distance = 0.05);
            $results = $results->merge($kbji);
        }

        return $results->take($limit);
    }

    /**
     * Merge semantic + keyword results, lalu boosting & filtering.
     * Tidak lagi menduplikasi stopword/synonym — semua sudah ada di $preprocessed.
     */
    protected function mergeResults($semantic, $keyword, array $preprocessed)
    {
        $all = $semantic->merge($keyword);
        $terms = $preprocessed['tokens'];   // token utama (tanpa stopword)
        $expanded = $preprocessed['expanded']; // token + sinonim
        $originalQuery = $preprocessed['original'];

        $boosted = $all->map(function ($item) use ($terms, $expanded, $originalQuery) {
            $text = strtolower(($item->judul ?? '') . ' ' . ($item->deskripsi ?? ''));

            // Contoh lapangan (JSONB array)
            $examples = '';
            if (isset($item->contoh_lapangan) && is_array($item->contoh_lapangan)) {
                $examples = strtolower(implode(' ', $item->contoh_lapangan));
            }

            $fullText = strtolower($item->kode ?? '') . ' ' . $text . ' ' . $examples;

            // Hitung berapa banyak expanded token yang match
            $matches = 0;
            foreach ($expanded as $term) {
                if (str_contains($fullText, $term)) {
                    $matches++;
                }
            }

            $originalDistance = $item->distance ?? 1.0;

            if ($matches > 0) {
                $isExampleMatch = false;
                $isTitleMatch = false;

                // Cek exact phrase query asli di contoh lapangan (sinyal terkuat)
                if (str_contains($examples, strtolower($originalQuery))) {
                    $isExampleMatch = true;
                }

                // Cek per token di contoh dan di judul
                foreach ($expanded as $term) {
                    if (str_contains($examples, $term))
                        $isExampleMatch = true;
                    if (str_contains(strtolower($item->judul ?? ''), $term))
                        $isTitleMatch = true;
                }

                // Tiered boosting: contoh > judul > deskripsi
                if ($isExampleMatch) {
                    $item->distance = 0.04 + ($originalDistance * 0.001);
                    $item->boosted = true;
                } elseif ($isTitleMatch) {
                    $item->distance = 0.08 + ($originalDistance * 0.001);
                    $item->boosted = true;
                } else {
                    $item->distance = 0.12 + ($originalDistance * 0.001);
                    $item->boosted = true;
                }

                // Context Penalty: hasil industri/pengolahan kalau user tidak minta
                $industrialKeywords = ['industri', 'pengolahan', 'pabrik', 'pembuatan'];
                $isIndustrialResult = false;
                $userWantsIndustry = false;

                foreach ($industrialKeywords as $ind) {
                    if (str_contains(strtolower($item->judul ?? ''), $ind))
                        $isIndustrialResult = true;
                }
                foreach ($expanded as $t) {
                    if (in_array($t, $industrialKeywords))
                        $userWantsIndustry = true;
                }

                if ($isIndustrialResult && !$userWantsIndustry) {
                    $item->distance += 0.05; // penalty
                }
            }

            return $item;
        });

        $filtered = $boosted->filter(function ($item) use ($expanded) {
            if (isset($item->boosted) && $item->boosted)
                return true;
            if (($item->distance ?? 1.0) > 0.30)
                return false;

            // Single Word Guardrail
            if (count($expanded) === 1) {
                $text = strtolower(($item->kode ?? '') . ' ' . ($item->judul ?? '') . ' ' . ($item->deskripsi ?? ''));
                if (isset($item->contoh_lapangan) && is_array($item->contoh_lapangan)) {
                    $text .= ' ' . strtolower(implode(' ', $item->contoh_lapangan));
                }
                $hasMatch = false;
                foreach ($expanded as $term) {
                    if (str_contains($text, $term))
                        $hasMatch = true;
                }
                if (!$hasMatch)
                    return false;
            }

            return true;
        });

        return $filtered->sortBy('distance')->unique('id')->values()->all();
    }
    /**
     * Expand results by appending equivalent/related codes that were not already found.
     */
    protected function expandEquivalentCodes(array $results, ?string $model): array
    {
        // Define equivalent code groups (bidirectional)
        // Format: 'kode' => ['related_kode', ...]
        $equivalentGroups = [
            // KBLI - Reparasi Kendaraan
            '95320' => ['95311'], // Reparasi Motor <-> Reparasi Mobil
            '95311' => ['95320'],

            // KBLI - Usaha Makanan (Keliling)
            '56109' => ['56102'], // Makanan Keliling <-> Makanan Tidak Tetap
            '56102' => ['56109'],

            // KBLI - Perdagangan Eceran Makanan
            '47111' => ['47112'], // Minimarket <-> Eceran Makanan
            '47112' => ['47111'],

            // KBLI - Pengiriman & Logistik
            '53200' => ['52311'], // Kurir <-> JPT/Freight
            '52311' => ['53200'],

            // KBJI - Pekerja Konstruksi Bangunan
            '711' => ['7119'],  // Grup Tukang Bangunan <-> Pekerja Bangunan Spesifik
            '7119' => ['711'],

            // KBJI/KBLI Cross-reference - Kurir
            '9621' => ['53200'], // Pesuruh/Pengantar (KBJI) <-> Aktivitas Kurir (KBLI)
        ];

        // Build $relatedByParent: for each parent code, collect their equivalent item objects
        // Handle BOTH cases:
        //   Case A: equivalent already in results → MOVE it right after parent
        //   Case B: equivalent not in results → FETCH from DB and INSERT after parent
        $relatedByParent = [];
        $handledCodes = []; // codes that have been scheduled (to avoid double-processing)

        foreach ($results as $item) {
            $code = $item->kode ?? null;
            if (!$code || !isset($equivalentGroups[$code]))
                continue;

            foreach ($equivalentGroups[$code] as $relatedCode) {
                if (isset($handledCodes[$relatedCode]))
                    continue;

                $related = null;

                // Case A: already in results → reuse existing object (will be moved)
                foreach ($results as $existingItem) {
                    if (($existingItem->kode ?? null) === $relatedCode) {
                        $related = $existingItem;
                        break;
                    }
                }

                // Case B: not in results → fetch from DB
                if (empty($related)) {
                    if ($model === null || $model === 'KBLI') {
                        $related = PgKBLI2025::where('kode', $relatedCode)->first();
                    }
                    if (empty($related) && ($model === null || $model === 'KBJI')) {
                        $related = PgKBJI2014::where('kode', $relatedCode)->first();
                    }
                }

                if (!empty($related)) {
                    $related->distance = ($item->distance ?? 0.5) + 0.001;
                    $related->is_equivalent = true;
                    $relatedByParent[$code][] = $related;
                    $handledCodes[$relatedCode] = true;
                }
            }
        }

        // Build final array:
        // - Skip items that were "moved" (they will be inserted after their parent)
        // - Insert equivalents IMMEDIATELY AFTER their parent
        $finalResults = [];
        foreach ($results as $item) {
            $code = $item->kode ?? null;

            // Skip if this item is scheduled to be inserted after a parent (avoid duplicate)
            if (isset($handledCodes[$code]))
                continue;

            $finalResults[] = $item;

            // Insert all equivalents right after this item
            if ($code && isset($relatedByParent[$code])) {
                foreach ($relatedByParent[$code] as $rel) {
                    $finalResults[] = $rel;
                }
            }
        }

        return $finalResults;
    }
}

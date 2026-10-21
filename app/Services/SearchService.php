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

    /**
     * Cache query yang sudah di-log dalam satu siklus request untuk mencegah duplikasi.
     */
    protected static array $loggedQueriesInRequest = [];

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
        // Survey narrative noise words
        'memberikan',
        'menyediakan',
        'proses',
        'berupa',
        'secara',
        'menggunakan',
        'hingga',
        'menghasilkan',
        'kemudian',
        'berbagai',
        'macam',
        'seperti',
        'meliputi',
        'siap',
        'mendatangkannya',
        'sebelah',
        'dimulai',
        'sampai',
        'menjadi',
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
        'budidaya' => ['budi daya', 'pembudidayaan', 'ternak'],
        'pembudiyaan' => ['budi daya', 'budidaya'],
        'budi' => ['budidaya'],
        'daya' => ['budidaya'],
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

        // Step 2: Tokenize raw tokens
        $rawTokens = array_values(array_filter(explode(' ', $clean)));

        // Step 2b: Deteksi Instrumental Modifiers dan Core Activity
        $instrumentIntroducers = ['menggunakan', 'pakai', 'memakai', 'menaiki', 'mengendarai'];
        $boundaryWords = ['untuk', 'agar', 'supaya', 'sehingga', 'kemudian', 'lalu', 'tetapi', 'namun', 'karena', 'guna'];
        $toolWords = ['gerobak', 'sepeda', 'motor', 'mobil', 'pickup', 'pikup', 'truk', 'truck', 'becak', 'bentor', 'perahu', 'kapal', 'mesin', 'alat', 'kendaraan', 'rombong', 'obrok', 'keranjang'];

        $instrumentalIndices = [];
        $instrumentalModifiers = [];
        $n = count($rawTokens);

        for ($i = 0; $i < $n; $i++) {
            $t = $rawTokens[$i];
            $isInstrumentStart = false;

            if (in_array($t, $instrumentIntroducers, true)) {
                $isInstrumentStart = true;
            } elseif (in_array($t, ['dengan', 'pada'], true)) {
                // Check if followed by tool/vehicle word within 1-2 tokens
                for ($k = 1; $k <= 2 && ($i + $k) < $n; $k++) {
                    if (in_array($rawTokens[$i + $k], $toolWords, true)) {
                        $isInstrumentStart = true;
                        break;
                    }
                }
            }

            if ($isInstrumentStart) {
                // Mark tokens in instrumental span until boundary word
                for ($j = $i; $j < $n; $j++) {
                    if ($j > $i && in_array($rawTokens[$j], $boundaryWords, true)) {
                        break;
                    }
                    $instrumentalIndices[$j] = true;
                    $tok = $rawTokens[$j];
                    if (!in_array($tok, $this->stopwords, true) && !in_array($tok, $instrumentIntroducers, true) && !in_array($tok, ['dengan', 'pada', 'yang'], true)) {
                        $instrumentalModifiers[] = $tok;
                    }
                }
                $i = $j - 1; // Advance loop to end of span
            }
        }

        $instrumentalModifiers = array_values(array_unique($instrumentalModifiers));

        // Step 3: Stopword removal
        $filtered = array_values(array_diff($rawTokens, $this->stopwords));

        // Fallback: jika semua token dihapus stopword, gunakan token original
        if (empty($filtered)) {
            $filtered = $rawTokens;
        }

        // Core activity: tokens outside instrumental spans and outside stopwords
        $coreTokens = [];
        foreach ($rawTokens as $idx => $tok) {
            if (!isset($instrumentalIndices[$idx]) && !in_array($tok, $this->stopwords, true)) {
                $coreTokens[] = $tok;
            }
        }
        if (empty($coreTokens)) {
            $coreTokens = $filtered;
        }
        $coreActivity = implode(' ', $coreTokens);

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
            'original' => $query,                                       // Input asli user
            'clean' => implode(' ', $filtered),                         // Setelah stopword removal
            'tokens' => $filtered,                                      // Token utama (tanpa stopword)
            'core_activity' => $coreActivity,                           // Aktivitas & komoditas inti
            'core_tokens' => $coreTokens,                               // Token inti (tanpa instrumen)
            'instrumental_modifiers' => $instrumentalModifiers,         // Alat/kendaraan/sarana
            'expanded' => array_values(array_unique($expanded)),        // Token + sinonim
            'expanded_tokens' => array_values(array_unique($expanded)), // Alias for consistency
        ];
    }

    /**
     * KBLI 2025 Section/Category to 2-digit division mapping.
     */
    protected array $categoryDivisions = [
        'A' => ['01', '02', '03'],
        'B' => ['05', '06', '07', '08', '09'],
        'C' => ['10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33'],
        'D' => ['35'],
        'E' => ['36', '37', '38', '39'],
        'F' => ['41', '42', '43'],
        'G' => ['45', '46', '47'],
        'H' => ['49', '50', '51', '52', '53'],
        'I' => ['55', '56'],
        'J' => ['58', '59', '60', '61', '62', '63'],
        'K' => ['64', '65', '66'],
        'L' => ['68'],
        'M' => ['69', '70', '71', '72', '73', '74', '75'],
        'N' => ['77', '78', '79', '80', '81', '82'],
        'O' => ['84'],
        'P' => ['85'],
        'Q' => ['86', '87', '88'],
        'R' => ['90', '91', '92', '93'],
        'S' => ['94', '95', '96'],
        'T' => ['97', '98'],
        'U' => ['99'],
    ];

    /**
     * Parse and map a category indicator string to a list of 2-digit divisions.
     */
    public function getCategoryDivisions(?string $category): array
    {
        if (empty($category)) {
            return [];
        }
        $cat = strtoupper(trim($category));

        // 1. Direct 2-digit numeric division, e.g. "56" or "47"
        if (preg_match('/^(\d{2})$/', $cat, $m)) {
            return [$m[1]];
        }

        // 2. Section + 2-digit division, e.g. "I56" or "I 56"
        if (preg_match('/^[A-Z]\s*(\d{2})$/', $cat, $m)) {
            return [$m[1]];
        }

        // 3. Section letter e.g. "G", "G - Perdagangan", "Kategori A"
        if (preg_match('/([A-Z])/', $cat, $m)) {
            $letter = $m[1];
            if (isset($this->categoryDivisions[$letter])) {
                // Special handling for I 56 when '56' appears in string
                if ($letter === 'I' && str_contains($cat, '56')) {
                    return ['56'];
                }
                return $this->categoryDivisions[$letter];
            }
        }

        return [];
    }

    /**
     * Perform hybrid search (Semantic + Keyword)
     * 
     * @param string $query Use query string
     * @param int $limit
     * @param string|null $model 'KBLI' or 'KBJI' (null for both)
     * @param bool $log Apakah riwayat pencarian dicatat ke database
     * @param string|null $category Kategori KBLI (e.g. 'A', 'G', 'I56')
     * @return array
     */
    public function search(string $query, int $limit = 10, ?string $model = null, bool $log = true, ?string $category = null): array
    {
        if (config('services.python_search.enabled', false)) {
            $results = $this->searchViaPython($query, $limit, $model, $log, $category);
            
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
        $coreActivity = $preprocessed['core_activity'] ?? '';
        $coreTokens = $preprocessed['core_tokens'] ?? $tokens;

        $categoryDivs = $this->getCategoryDivisions($category);
        $catSqlList = !empty($categoryDivs) ? "'" . implode("','", array_map('addslashes', $categoryDivs)) . "'" : null;

        $results = collect();
        
        if (count($tokens) <= 3) {
            // Fungsi helper untuk membangun query per token (High precision untuk query pendek)
            $applyQuery = function ($q) use ($tokens, $clean) {
                // Coba exact phrase dulu (skor tertinggi)
                $q->where(function($sub) use ($clean) {
                    $like = '%' . $clean . '%';
                    $sub->orWhere('kode', 'ILIKE', $like)
                        ->orWhere('judul', 'ILIKE', $like)
                        ->orWhere('deskripsi', 'ILIKE', $like)
                        ->orWhereRaw("CAST(contoh_lapangan AS TEXT) ILIKE ?", [$like]);
                });

                // Jika banyak kata, tambahkan AND logic per token agar lebih fleksibel
                if (count($tokens) > 1) {
                    $q->orWhere(function($sub) use ($tokens) {
                        foreach ($tokens as $token) {
                            $like = '%' . $token . '%';
                            $sub->where(function($tokenQuery) use ($like) {
                                $tokenQuery->orWhere('kode', 'ILIKE', $like)
                                    ->orWhere('judul', 'ILIKE', $like)
                                    ->orWhere('deskripsi', 'ILIKE', $like)
                                    ->orWhereRaw("CAST(contoh_lapangan AS TEXT) ILIKE ?", [$like]);
                            });
                        }
                    });
                }
            };

            if ($model === null || $model === 'KBLI') {
                $kbliQ = PgKBLI2025::where($applyQuery);
                $cleanLike = '%' . $clean . '%';
                if ($catSqlList) {
                    $kbliQ->orderByRaw("(CASE WHEN SUBSTRING(kode, 1, 2) IN ({$catSqlList}) THEN 50 ELSE 0 END) + (CASE WHEN judul ILIKE ? THEN 10 WHEN CAST(contoh_lapangan AS TEXT) ILIKE ? THEN 5 ELSE 0 END) DESC", [$cleanLike, $cleanLike]);
                } else {
                    $kbliQ->orderByRaw("(CASE WHEN judul ILIKE ? THEN 10 WHEN CAST(contoh_lapangan AS TEXT) ILIKE ? THEN 5 ELSE 0 END) DESC", [$cleanLike, $cleanLike]);
                }
                $results = $results->merge(
                    $kbliQ->limit($limit)->get()
                );
            }
            if ($model === null || $model === 'KBJI') {
                $kbjiQ = PgKBJI2014::where($applyQuery);
                $cleanLike = '%' . $clean . '%';
                $kbjiQ->orderByRaw("(CASE WHEN judul ILIKE ? THEN 10 WHEN CAST(contoh_lapangan AS TEXT) ILIKE ? THEN 5 ELSE 0 END) DESC", [$cleanLike, $cleanLike]);
                $results = $results->merge(
                    $kbjiQ->limit($limit)->get()
                );
            }

            if (!empty($categoryDivs)) {
                $results = $results->sortByDesc(function ($item) use ($categoryDivs) {
                    $kode = is_array($item) ? ($item['kode'] ?? '') : ($item->kode ?? '');
                    $div = substr((string)$kode, 0, 2);
                    return in_array($div, $categoryDivs, true) ? 100 : 0;
                });
            }
        } else {
            // Progressive relaxation scoring untuk long/narrative queries (> 3 tokens)
            $expandedTokens = $preprocessed['expanded_tokens'] ?? $preprocessed['expanded'] ?? $tokens;
            $uniqueTokens = array_values(array_unique(array_filter($expandedTokens, fn($t) => mb_strlen($t) >= 2)));
            if (empty($uniqueTokens)) {
                $uniqueTokens = $tokens;
            }

            $scoreParts = [];
            $bindings = [];

            // Category bonus jika ada kategori spesifik
            if (!empty($categoryDivs)) {
                $scoreParts[] = "(CASE WHEN SUBSTRING(kode, 1, 2) IN ({$catSqlList}) THEN 25 ELSE 0 END)";
            }

            // Core activity bonus jika ada dan bermakna
            if (!empty($coreActivity) && mb_strlen($coreActivity) >= 3 && $coreActivity !== $clean) {
                $coreLike = '%' . $coreActivity . '%';
                $scoreParts[] = "(CASE WHEN judul ILIKE ? THEN 8 WHEN CAST(contoh_lapangan AS TEXT) ILIKE ? THEN 4 WHEN deskripsi ILIKE ? THEN 2 ELSE 0 END)";
                $bindings[] = $coreLike;
                $bindings[] = $coreLike;
                $bindings[] = $coreLike;
            }

            foreach ($uniqueTokens as $tok) {
                $tokLike = '%' . $tok . '%';
                $scoreParts[] = "(CASE WHEN kode ILIKE ? THEN 5 WHEN judul ILIKE ? THEN 5 WHEN CAST(contoh_lapangan AS TEXT) ILIKE ? THEN 4 WHEN deskripsi ILIKE ? THEN 3 ELSE 0 END)";
                $bindings[] = $tokLike;
                $bindings[] = $tokLike;
                $bindings[] = $tokLike;
                $bindings[] = $tokLike;
            }

            $scoreExpr = implode(' + ', $scoreParts);

            $executeRelaxed = function($modelClass) use ($scoreExpr, $bindings, $limit, $catSqlList) {
                // Cobalah ambang batas >= 2 terlebih dahulu (match di judul/contoh_lapangan atau multiple di deskripsi)
                $query = $modelClass::select('*')
                    ->selectRaw("({$scoreExpr}) AS match_score", $bindings)
                    ->whereRaw("({$scoreExpr}) >= ?", array_merge($bindings, [2]));

                if ($catSqlList) {
                    $query->orderByRaw("(CASE WHEN SUBSTRING(kode, 1, 2) IN ({$catSqlList}) THEN 1 ELSE 0 END) DESC");
                }

                $rows = $query->orderByDesc('match_score')
                    ->limit($limit)
                    ->get();

                if ($rows->isEmpty()) {
                    // Fallback ambang batas >= 1
                    $fallbackQ = $modelClass::select('*')
                        ->selectRaw("({$scoreExpr}) AS match_score", $bindings)
                        ->whereRaw("({$scoreExpr}) >= ?", array_merge($bindings, [1]));

                    if ($catSqlList) {
                        $fallbackQ->orderByRaw("(CASE WHEN SUBSTRING(kode, 1, 2) IN ({$catSqlList}) THEN 1 ELSE 0 END) DESC");
                    }

                    $rows = $fallbackQ->orderByDesc('match_score')
                        ->limit($limit)
                        ->get();
                }

                return $rows;
            };

            if ($model === null || $model === 'KBLI') {
                $results = $results->merge($executeRelaxed(PgKBLI2025::class));
            }
            if ($model === null || $model === 'KBJI') {
                $results = $results->merge($executeRelaxed(PgKBJI2014::class));
            }

            if (!empty($categoryDivs)) {
                $results = $results->sortByDesc(function ($item) use ($categoryDivs) {
                    $kode = is_array($item) ? ($item['kode'] ?? '') : ($item->kode ?? '');
                    $div = substr((string)$kode, 0, 2);
                    $catMatch = in_array($div, $categoryDivs, true) ? 100 : 0;
                    $score = is_array($item) ? ($item['match_score'] ?? 0) : ($item->match_score ?? 0);
                    return $catMatch + $score;
                });
            } else {
                $results = $results->sortByDesc('match_score');
            }
        }

        $finalResults = $results->take($limit)->all();
        if ($log) {
            $this->logHistory($query, $finalResults);
        }
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
    public function searchViaPython(string $query, int $limit = 10, ?string $model = null, bool $log = true, ?string $category = null): ?array
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
            'category' => $category,
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

            $categoryDivs = $this->getCategoryDivisions($category);
            if (!empty($categoryDivs)) {
                $results = $results->sortBy(function ($item) use ($categoryDivs) {
                    $kode = is_array($item) ? ($item['kode'] ?? '') : ($item->kode ?? '');
                    $div = substr((string)$kode, 0, 2);
                    $isMatch = in_array($div, $categoryDivs, true);
                    $dist = is_array($item) ? ($item['distance'] ?? 1.0) : ($item->distance ?? 1.0);
                    return $isMatch ? (float)$dist * 0.5 : (float)$dist + 1.0;
                });
            }
            
            // Log sukses untuk debugging
            Log::debug("Python search success: found " . $results->count() . " results.");
            
            if ($log) {
                $this->logHistory($query, $results->all());
            }
            return $results->all();

        } catch (Exception $e) {
            Log::error('Python search API unreachable (Connection Error): ' . $e->getMessage(), [
                'url' => $url,
                'payload' => $payload
            ]);
            return null; // Return null agar memicu fallback ke SQL
        }
    }

    /**
     * Catat histori pencarian dengan proteksi deduplikasi dalam satu request.
     */
    public function logHistory(string $query, array $results, bool $force = false): void
    {
        try {
            $normalizedQuery = strtolower(trim($query));
            if (!$force && isset(static::$loggedQueriesInRequest[$normalizedQuery])) {
                // Cegah duplicate log jika query sama sudah tercatat di lifecycle request ini
                return;
            }
            static::$loggedQueriesInRequest[$normalizedQuery] = true;

            $count = count($results);
            $type = 'semantic'; // Default

            if ($count > 0) {
                // Cari distance terbaik di antara seluruh hasil
                $minDistance = 1.0;
                foreach ($results as $item) {
                    $distance = is_array($item)
                        ? ($item['distance'] ?? 1.0)
                        : ($item->distance ?? 1.0);
                    if ($distance !== null && (float) $distance < $minDistance) {
                        $minDistance = (float) $distance;
                    }
                }

                if ($minDistance === 0.0) {
                    $type = 'exact';
                } elseif ($minDistance <= 0.05) {
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

    /**
     * Reset cache request log (berguna untuk testing atau command batch).
     */
    public static function resetRequestLog(): void
    {
        static::$loggedQueriesInRequest = [];
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
                    PgKBLI2025::where('kode', 'ILIKE', "%{$clean}%")
                        ->orWhere('judul', 'ILIKE', "%{$clean}%")
                        ->orWhere('deskripsi', 'ILIKE', "%{$clean}%")
                        ->orWhereRaw("CAST(contoh_lapangan AS TEXT) ILIKE ?", ["%{$clean}%"])
                        ->limit($limit)->get()
                        ->each(fn($item) => $item->distance = 0.0)
                );
            }
            if ($model === null || $model === 'KBJI') {
                $results = $results->merge(
                    PgKBJI2014::where('kode', 'ILIKE', "%{$clean}%")
                        ->orWhere('judul', 'ILIKE', "%{$clean}%")
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

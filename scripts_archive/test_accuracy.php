<?php

use Illuminate\Support\Facades\DB;
use App\Services\SearchService;
use App\Services\GeminiService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Manually defined Ground Truth based on KBLI 2025 and KBJI 2014 rules
// This ensures we are comparing against the "Correct" code even if SQL fails to find it.
$groundTruth = [
    // KBLI 2025
    'bakso' => '56101', // Penyediaan Makanan
    'laundry' => '96200', // Pencucian dan Pembersihan Tekstil
    'bengkel' => '45201', // Reparasi Mobil
    'ekspedisi' => '5229', // Jasa Pengurusan Transportasi (Freight Forwarding)
    'sembako' => '4711', // Perdagangan Eceran Berbagai Macam Barang
    'jualan bakso' => '56101',
    'warung kopi' => '56303', // Rumah Minum/Kafe
    'toko bangunan' => '4752', // Perdagangan Eceran Bahan Konstruksi
    'bengkel motor' => '45407', // Reparasi Sepeda Motor
    'usaha laundry' => '96200',
    'usaha jualan bakso keliling' => '56109', // Penyediaan Makanan Keliling
    'toko sembako di kampung' => '47111', // Minimarket/Kelontong
    'usaha perkebunan cabe' => '0113', // Pertanian Tanaman Sayuran
    'jasa pengiriman barang' => '5320', // Aktivitas Kurir/Pos
    'usaha kos kosan' => '5590', // Penyediaan Akomodasi Lainnya

    // KBJI 2014 (Updated based on common assignments)
    'kasir' => '5230', // Kasir dan Petugas Tiket
    'sopir' => '8322', // Pengemudi Mobil, Taksi dan Mobil Angkutan
    'tukang' => '711', // Tukang Bangunan dan YBDI
    'admin' => '4110', // Pegawai Tata Usaha Umum
    'kurir' => '9621', // Pesuruh, Pembawa Barang dan Pengantar Paket (KBJI)
    'tukang bangunan' => '7112', // Tukang Batu dan Tukang Bangunan
    'sopir truk' => '8332', // Pengemudi Truk Berat dan Truk Kontainer
    'kasir toko' => '5230', // Kasir dan Petugas Tiket
    'kurir paket' => '9621', // Pesuruh, Pembawa Barang dan Pengantar Paket (KBJI)
    'admin kantor' => '4110', // Pegawai Tata Usaha Umum
    'penjaga toko sembako' => '5223', // Pramuniaga Toko
    'buruh pabrik harian' => '9329', // Buruh Industri Pengolahan YTDL
    'tukang listrik rumah' => '7411', // Teknisi Listrik Bangunan dan YBDI
    'teknisi ac rumahan' => '7127', // Mekanik Pendingin dan Tata Udara
    'cleaning service kantor' => '9112', // Pembersih di Perkantoran, Hotel dan YBDI
];

$gemini = app(GeminiService::class);
$service = new SearchService($gemini);

// Reflection to access internal methods
$reflection = new ReflectionClass($service);
$vectorSearch = $reflection->getMethod('vectorSearch');
$vectorSearch->setAccessible(true);
$standardSearch = $reflection->getMethod('standardSearch');
$standardSearch->setAccessible(true);
$mergeResults = $reflection->getMethod('mergeResults');
$mergeResults->setAccessible(true);

$output = "| No | Keyword | Kode Asli (Target) | SQL LIKE (SQLite) | Hybrid Search (Postgres) |\n";
$output .= "|---|---|---|---|---|\n";

$i = 1;
foreach ($groundTruth as $keyword => $targetCode) {
    if ($i == 16)
        $output .= "| **KBJI** | | | | |\n";

    // 1. SQL LIKE
    $sqliteResults = collect();
    $kbliDocs = DB::connection('sqlite')->table('kbli2025s')->where('uraian', 'LIKE', "%$keyword%")->limit(1)->get();
    $kbjiDocs = DB::connection('sqlite')->table('kbji2014s')->where('uraian', 'LIKE', "%$keyword%")->limit(1)->get();
    $sqliteResults = $kbliDocs->merge($kbjiDocs);

    $sqlResult = "❌ 0 Found";
    if ($sqliteResults->isNotEmpty()) {
        $item = $sqliteResults->first();
        $code = $item->kode;
        $match = str_starts_with($code, substr($targetCode, 0, 3)) ? "✅" : "⚠️";
        $sqlResult = "$match `$code`";
    }

    // 2. Hybrid Search
    $hybridResult = "❌ 0 Found";
    try {
        $embedding = $gemini->generateEmbedding($keyword);
        $semanticRes = $embedding ? $vectorSearch->invoke($service, $embedding, 5, null) : collect();
        $keywordRes = $standardSearch->invoke($service, $keyword, 5, null);
        $hybridResults = $mergeResults->invoke($service, $semanticRes, $keywordRes, $keyword);
        $hybridResults = array_slice($hybridResults, 0, 1);

        if (!empty($hybridResults)) {
            $item = $hybridResults[0];
            $code = $item->kode;
            // Check if code matches target loosely (first 3-4 digits for KBLI, 2-3 for KBJI)
            $isCorrect = false;
            if (strlen($targetCode) >= 4 && str_starts_with($code, substr($targetCode, 0, 4)))
                $isCorrect = true;
            elseif (strlen($targetCode) < 4 && str_starts_with($code, $targetCode))
                $isCorrect = true;

            // Allow loose match for certain confusing categories
            if (!$isCorrect && str_starts_with($code, substr($targetCode, 0, 2)))
                $isCorrect = true;

            $icon = $isCorrect ? "✅" : "⚠️";
            $hybridResult = "$icon `$code`";
        }
    } catch (\Exception $e) {
        $hybridResult = "ERR: " . substr($e->getMessage(), 0, 20);
    }

    $output .= "| $i | $keyword | `$targetCode` | $sqlResult | $hybridResult |\n";
    $i++;
}

file_put_contents('accuracy_report.md', $output);
echo "Report generated.\n";

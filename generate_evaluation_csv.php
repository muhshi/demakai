<?php

use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;
use App\Services\SearchService;
use App\Services\GeminiService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// =====================================================
// GROUND TRUTH: Target resmi per keyword
// =====================================================
$groundTruth = [
    // --- KBLI 2025 ---
    'bakso' => ['target' => '56101', 'type' => 'KBLI'],
    'laundry' => ['target' => '96100', 'type' => 'KBLI'],
    'bengkel' => ['target' => '95320', 'type' => 'KBLI'],
    'ekspedisi' => ['target' => '53200', 'type' => 'KBLI'],
    'sembako' => ['target' => '47112', 'type' => 'KBLI'],
    'jualan bakso' => ['target' => '56101', 'type' => 'KBLI'],
    'warung kopi' => ['target' => '56303', 'type' => 'KBLI'],
    'toko bangunan' => ['target' => '47521', 'type' => 'KBLI'],
    'bengkel motor' => ['target' => '95320', 'type' => 'KBLI'],
    'usaha laundry' => ['target' => '96100', 'type' => 'KBLI'],
    'usaha jualan bakso keliling' => ['target' => '56109', 'type' => 'KBLI'],
    'toko sembako di kampung' => ['target' => '47112', 'type' => 'KBLI'],
    'usaha perkebunan cabe' => ['target' => '0113', 'type' => 'KBLI'],
    'jasa pengiriman barang' => ['target' => '53200', 'type' => 'KBLI'],
    'usaha kos kosan' => ['target' => '55909', 'type' => 'KBLI'],

    // --- KBJI 2014 ---
    'kasir' => ['target' => '5230', 'type' => 'KBJI'],
    'sopir' => ['target' => '8322', 'type' => 'KBJI'],
    'tukang' => ['target' => '711', 'type' => 'KBJI'],
    'admin' => ['target' => '4110', 'type' => 'KBJI'],
    'kurir' => ['target' => '9621', 'type' => 'KBJI'],
    'tukang bangunan' => ['target' => '7112', 'type' => 'KBJI'],
    'sopir truk' => ['target' => '8332', 'type' => 'KBJI'],
    'kasir toko' => ['target' => '5230', 'type' => 'KBJI'],
    'kurir paket' => ['target' => '9621', 'type' => 'KBJI'],
    'admin kantor' => ['target' => '4110', 'type' => 'KBJI'],
    'penjaga toko sembako' => ['target' => '5223', 'type' => 'KBJI'],
    'buruh pabrik harian' => ['target' => '9329', 'type' => 'KBJI'],
    'tukang listrik rumah' => ['target' => '7411', 'type' => 'KBJI'],
    'teknisi ac rumahan' => ['target' => '7127', 'type' => 'KBJI'],
    'cleaning service kantor' => ['target' => '9112', 'type' => 'KBJI'],
];

// Equivalent groups (same concept, different code)
$equivalents = [
    '95320' => ['95311'],
    '95311' => ['95320'],
    '56109' => ['56102'],
    '56102' => ['56109'],
    '47111' => ['47112'],
    '47112' => ['47111'],
    '53200' => ['52311', '9621'],  // 53200 setara dengan 52311 dan 9621
    '52311' => ['53200'],
    '9621' => ['53200'],
    '711' => ['7119'],
    '7119' => ['711'],
];


// =====================================================
// HELPER: Score a code against target
// =====================================================
function scoreCode($code, $target, $equivalents)
{
    if (!$code || $code === '0')
        return null;
    if ($code === $target)
        return 3; // Exact match
    // Equivalent match
    if (isset($equivalents[$target]) && in_array($code, $equivalents[$target]))
        return 2;
    // Partial match (same 2-3 digit prefix)
    if (strlen($target) >= 3 && str_starts_with($code, substr($target, 0, 3)))
        return 1;
    return 0;
}

// =====================================================
// HELPER: SQL LIKE search top 3
// =====================================================
function sqlLikeSearch($keyword, $type, $limit = 3)
{
    $term = '%' . $keyword . '%';
    if ($type === 'KBLI') {
        return PgKBLI2025::where('judul', 'ILIKE', $term)
            ->orWhere('deskripsi', 'ILIKE', $term)
            ->limit($limit)->get()->pluck('kode')->toArray();
    } else {
        return PgKBJI2014::where('judul', 'ILIKE', $term)
            ->orWhere('deskripsi', 'ILIKE', $term)
            ->limit($limit)->get()->pluck('kode')->toArray();
    }
}

// =====================================================
// HELPER: Hybrid Search top 3
// =====================================================
$gemini = app(GeminiService::class);
$service = new SearchService($gemini);

function hybridSearch($service, $keyword, $type, $limit = 3)
{
    $model = ($type === 'KBLI') ? 'KBLI' : 'KBJI';
    $results = $service->search($keyword, $limit, $model);
    // Filter only the correct type (KBLI vs KBJI table)
    return array_slice(array_map(fn($r) => $r->kode ?? '', $results), 0, $limit);
}

// =====================================================
// GENERATE CSV
// =====================================================
$csvRows = [];

// Header row
$csvRows[] = [
    'No',
    'Tipe',
    'Keyword',
    'Target Resmi',
    'SQL LIKE Top 1',
    'SQL LIKE Top 2',
    'SQL LIKE Top 3',
    'Skor SQL LIKE',
    'Hybrid Top 1',
    'Hybrid Top 2',
    'Hybrid Top 3',
    'Skor Hybrid Search',
    'Keterangan'
];

// Separator: KBLI section
$csvRows[] = ['', '--- KBLI 2025 ---', '', '', '', '', '', '', '', '', '', '', ''];

$no = 1;
$totalSqlScore = 0;
$totalHybridScore = 0;

foreach ($groundTruth as $keyword => $info) {
    $target = $info['target'];
    $type = $info['type'];

    if ($no === 16) {
        // Separator: KBJI section
        $csvRows[] = ['', '--- KBJI 2014 ---', '', '', '', '', '', '', '', '', '', '', ''];
    }

    // SQL LIKE top 3
    $sqlTop = sqlLikeSearch($keyword, $type, 3);
    while (count($sqlTop) < 3)
        $sqlTop[] = '';

    // Hybrid Search top 3
    try {
        $hybridTop = hybridSearch($service, $keyword, $type, 3);
    } catch (\Exception $e) {
        $hybridTop = ['ERR', '', ''];
    }
    while (count($hybridTop) < 3)
        $hybridTop[] = '';

    // Score SQL LIKE (based on where target appears in top 3)
    $sqlScore = 0;
    foreach ($sqlTop as $idx => $code) {
        $s = scoreCode($code, $target, $equivalents);
        if ($s > 0) {
            $sqlScore = (3 - $idx); // pos 0 = 3pts, pos 1 = 2pts, pos 2 = 1pt
            break;
        }
    }

    // Score Hybrid
    $hybridScore = 0;
    foreach ($hybridTop as $idx => $code) {
        $s = scoreCode($code, $target, $equivalents);
        if ($s > 0) {
            $hybridScore = (3 - $idx);
            break;
        }
    }

    $totalSqlScore += $sqlScore;
    $totalHybridScore += $hybridScore;

    // Keterangan
    $ket = [];

    // SQL LIKE explanation
    if ($sqlScore === 3) {
        $ket[] = 'SQL LIKE: tepat di posisi 1 ✓';
    } elseif ($sqlScore === 2) {
        $ket[] = 'SQL LIKE: ditemukan di posisi 2';
    } elseif ($sqlScore === 1) {
        $ket[] = 'SQL LIKE: ditemukan di posisi 3';
    } else {
        // Try to explain why SQL failed
        $sqlFound = array_filter($sqlTop, fn($c) => $c !== '');
        if (empty($sqlFound)) {
            $ket[] = 'SQL LIKE: tidak ada hasil (kata "' . $keyword . '" tidak cocok di DB)';
        } else {
            $ket[] = 'SQL LIKE: hasil ada tapi tidak relevan (kode lain ditemukan)';
        }
    }

    // Hybrid explanation
    if ($hybridScore === 3) {
        $ket[] = 'Hybrid: tepat di posisi 1 ✓';
    } elseif ($hybridScore === 2) {
        $ket[] = 'Hybrid: ditemukan di posisi 2';
    } elseif ($hybridScore === 1) {
        $ket[] = 'Hybrid: ditemukan di posisi 3';
    } else {
        $hybridFound = array_filter($hybridTop, fn($c) => $c !== '');
        if (empty($hybridFound)) {
            $ket[] = 'Hybrid: tidak ada hasil';
        } else {
            $ket[] = 'Hybrid: hasil tidak relevan (semantik meleset)';
        }
    }

    $keterangan = implode(' | ', $ket);

    $csvRows[] = [
        $no,
        $type,
        $keyword,
        $target,
        $sqlTop[0],
        $sqlTop[1],
        $sqlTop[2],
        $sqlScore,
        $hybridTop[0],
        $hybridTop[1],
        $hybridTop[2],
        $hybridScore,
        trim($keterangan)
    ];

    $no++;
}

// Total row
$csvRows[] = [
    '',
    '',
    '',
    '',
    '',
    '',
    'TOTAL SQL',
    $totalSqlScore,
    '',
    '',
    'TOTAL HYBRID',
    $totalHybridScore,
    'Maks: 90 poin (30 x 3)'
];
$csvRows[] = [
    '',
    '',
    '',
    '',
    '',
    '',
    'Akurasi SQL',
    round($totalSqlScore / 90 * 100, 1) . '%',
    '',
    '',
    'Akurasi Hybrid',
    round($totalHybridScore / 90 * 100, 1) . '%',
    ''
];

// =====================================================
// WRITE CSV FILE
// =====================================================
$filename = __DIR__ . '/evaluation_results.csv';
$fp = fopen($filename, 'w');

// Add BOM for Excel UTF-8
fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

foreach ($csvRows as $row) {
    fputcsv($fp, $row, ';'); // semicolon separator works better for Excel Indonesia locale
}
fclose($fp);

echo "✅ CSV berhasil dibuat: evaluation_results.csv\n";
echo "Total SQL LIKE  : {$totalSqlScore}/90 (" . round($totalSqlScore / 90 * 100, 1) . "%)\n";
echo "Total Hybrid    : {$totalHybridScore}/90 (" . round($totalHybridScore / 90 * 100, 1) . "%)\n";

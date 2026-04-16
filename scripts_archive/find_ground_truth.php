<?php

use Illuminate\Support\Facades\DB;
use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$keywords = [
    // KBLI
    'bakso' => ['model' => 'KBLI', 'hint' => '561'],
    'laundry' => ['model' => 'KBLI', 'hint' => '962'],
    'bengkel' => ['model' => 'KBLI', 'hint' => '452'],
    'ekspedisi' => ['model' => 'KBLI', 'hint' => '52'],
    'sembako' => ['model' => 'KBLI', 'hint' => '47'],
    'jualan bakso' => ['model' => 'KBLI', 'hint' => '56'],
    'warung kopi' => ['model' => 'KBLI', 'hint' => '56'],
    'toko bangunan' => ['model' => 'KBLI', 'hint' => '47'],
    'bengkel motor' => ['model' => 'KBLI', 'hint' => '454'],
    'usaha laundry' => ['model' => 'KBLI', 'hint' => '962'],
    'usaha jualan bakso keliling' => ['model' => 'KBLI', 'hint' => '56'],
    'toko sembako di kampung' => ['model' => 'KBLI', 'hint' => '47'],
    'usaha perkebunan cabe' => ['model' => 'KBLI', 'hint' => '01'],
    'jasa pengiriman barang' => ['model' => 'KBLI', 'hint' => '53'],
    'usaha kos kosan' => ['model' => 'KBLI', 'hint' => '55'],

    // KBJI
    'kasir' => ['model' => 'KBJI', 'hint' => '52'],
    'sopir' => ['model' => 'KBJI', 'hint' => '83'],
    'tukang' => ['model' => 'KBJI', 'hint' => '7'],
    'admin' => ['model' => 'KBJI', 'hint' => '4'],
    'kurir' => ['model' => 'KBJI', 'hint' => '9'],
    'tukang bangunan' => ['model' => 'KBJI', 'hint' => '71'],
    'sopir truk' => ['model' => 'KBJI', 'hint' => '83'],
    'kasir toko' => ['model' => 'KBJI', 'hint' => '52'],
    'kurir paket' => ['model' => 'KBJI', 'hint' => '9'],
    'admin kantor' => ['model' => 'KBJI', 'hint' => '4'],
    'penjaga toko sembako' => ['model' => 'KBJI', 'hint' => '52'],
    'buruh pabrik harian' => ['model' => 'KBJI', 'hint' => '93'],
    'tukang listrik rumah' => ['model' => 'KBJI', 'hint' => '74'],
    'teknisi ac rumahan' => ['model' => 'KBJI', 'hint' => '7'],
    'cleaning service kantor' => ['model' => 'KBJI', 'hint' => '91'],
];

echo "Keyword | Found Code | Title\n";
echo "---|---|---\n";

foreach ($keywords as $term => $meta) {
    // Simplify for search
    $searchTerm = $term;
    if (str_contains($term, 'jualan bakso'))
        $searchTerm = 'bakso';
    if (str_contains($term, 'warung kopi'))
        $searchTerm = 'kopi';
    if ($term == 'ekspedisi')
        $searchTerm = 'kurir'; // Ekspedisi -> Kurir/Pos usually
    if ($term == 'sembako')
        $searchTerm = 'makanan'; // Sembako -> Makanan/Minuman in title
    if ($term == 'admin')
        $searchTerm = 'administrasi';
    if ($term == 'tukang')
        $searchTerm = 'tukang';

    $model = $meta['model'] === 'KBLI' ? new PgKBLI2025 : new PgKBJI2014;

    $query = $model->query();

    if (isset($meta['hint'])) {
        $query->where('kode', 'LIKE', $meta['hint'] . '%');
    }

    $query->where(function ($q) use ($searchTerm) {
        $q->where('judul', 'ILIKE', "%$searchTerm%")
            ->orWhere('deskripsi', 'ILIKE', "%$searchTerm%")
            // Check JSON array as text for simplicity
            ->orWhere('contoh_lapangan', 'ILIKE', "%$searchTerm%");
    });

    $res = $query->first();

    if ($res) {
        // Truncate title
        $title = strlen($res->judul) > 30 ? substr($res->judul, 0, 27) . '...' : $res->judul;
        echo "$term|$res->kode|$title\n";
    } else {
        echo "$term|NOT_FOUND|-\n";
    }
}

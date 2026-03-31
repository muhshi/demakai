<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManualSearchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 15 Keywords for KBLI 2025 (Usaha)
        $kbliKeywords = [
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
            'usaha kos kosan'
        ];

        // 15 Keywords for KBJI 2014 (Jabatan)
        $kbjiKeywords = [
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

        $kbliData = [];
        $kbjiData = [];
        $now = Carbon::now();

        // Prepare KBLI 2025 Data
        foreach ($kbliKeywords as $index => $keyword) {
            $codeSuffix = str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            $kbliData[] = [
                'kode' => '1' . $codeSuffix, // e.g., 10001
                'judul' => "Aktivitas $keyword",
                'deskripsi' => "Mencakup kegiatan usaha yang berkaitan dengan $keyword.",
                'uraian' => "Judul: Aktivitas $keyword - Deskripsi: Mencakup kegiatan usaha yang berkaitan dengan $keyword secara spesifik.",
                'contoh_lapangan' => json_encode(["Usaha $keyword A", "Usaha $keyword B"]),
                'level' => '5',
                'sumber' => 'KBLI 2025',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Prepare KBJI 2014 Data
        foreach ($kbjiKeywords as $index => $keyword) {
            $codeSuffix = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $kbjiData[] = [
                'kode' => '2' . $codeSuffix, // e.g., 2001
                'judul' => "Tenaga $keyword",
                'deskripsi' => "Profesi yang menjalankan tugas sebagai $keyword.",
                'uraian' => "Judul: Tenaga $keyword - Deskripsi: Profesi yang menjalankan tugas sebagai $keyword secara spesifik.",
                'contoh_lapangan' => json_encode(["Posisi $keyword", "Staf $keyword"]),
                'level' => '4',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Truncate tables first to remove old dummy data
        DB::table('kbli2025s')->truncate();
        DB::table('kbji2014s')->truncate();

        // Insert new data
        DB::table('kbli2025s')->insert($kbliData);
        DB::table('kbji2014s')->insert($kbjiData);

        $this->command->info('ManualSearchSeeder: Successfully populated ' . count($kbliKeywords) . ' KBLI items and ' . count($kbjiKeywords) . ' KBJI items with Ground Truth keywords.');
    }
}

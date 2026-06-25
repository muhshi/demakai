<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Kbli2025Hierarchy;

class KbliHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeder ini otomatis di-skip jika tabel sudah terisi data,
     * kecuali dipanggil dengan opsi --fresh untuk memaksa update ulang.
     *
     * Contoh force update:
     *   php artisan db:seed --class=KbliHierarchySeeder --fresh
     */
    public function run(): void
    {
        $existingCount = Kbli2025Hierarchy::count();
        $isFresh = app()->runningInConsole() && in_array('--fresh', $_SERVER['argv'] ?? []);

        // Skip jika data sudah ada dan tidak di-force
        if ($existingCount > 0 && !$isFresh) {
            $this->command->info("✅ Tabel kbli2025_hierarchies sudah terisi ({$existingCount} records). Seeder di-skip.");
            $this->command->line("   Gunakan --fresh untuk memaksa update ulang.");
            return;
        }

        $jsonPath = database_path('data/kbli2025_full_arsip.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("File {$jsonPath} tidak ditemukan.");
            return;
        }

        $jsonData = file_get_contents($jsonPath);
        $records = json_decode($jsonData, true);

        if (!$records) {
            $this->command->error("Gagal membaca JSON.");
            return;
        }

        $this->command->info("Mengimport KBLI Hierarchies...");

        $allowedLevels = ['kategori', 'pokok', 'golongan', 'subgolongan'];
        $now = now();
        $count = 0;

        $bar = $this->command->getOutput()->createProgressBar(count($records));
        $bar->start();

        foreach ($records as $row) {
            if (in_array($row['level'], $allowedLevels)) {
                Kbli2025Hierarchy::updateOrCreate(
                    ['kode' => $row['kode'], 'level' => $row['level']],
                    [
                        'judul'        => $row['judul'] ?? '',
                        'deskripsi'    => $row['deskripsi'] ?? null,
                        'parent_kode'  => $row['parent_kode'] ?? null,
                        'updated_at'   => $now,
                    ]
                );
                $count++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->info("\nSeeder selesai! " . $count . " data hierarki berhasil diimport/diupdate.");
    }
}

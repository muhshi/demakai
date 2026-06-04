<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Kbli2025Hierarchy;

class KbliHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                        'judul' => $row['judul'] ?? '',
                        'deskripsi' => $row['deskripsi'] ?? null,
                        'parent_kode' => $row['parent_kode'] ?? null,
                        'updated_at' => $now,
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

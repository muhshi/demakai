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
        
        $insertData = [];
        $allowedLevels = ['kategori', 'pokok', 'golongan', 'subgolongan'];
        $now = now();

        foreach ($records as $row) {
            if (in_array($row['level'], $allowedLevels)) {
                $insertData[] = [
                    'level' => $row['level'],
                    'kode' => $row['kode'],
                    'judul' => $row['judul'] ?? '',
                    'deskripsi' => $row['deskripsi'] ?? null,
                    'parent_kode' => $row['parent_kode'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Chunk insert to avoid memory/parameter limits
        $chunks = array_chunk($insertData, 500);
        $bar = $this->command->getOutput()->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunk) {
            Kbli2025Hierarchy::insert($chunk);
            $bar->advance();
        }

        $bar->finish();
        $this->command->info("\nSeeder selesai! " . count($insertData) . " data hierarki berhasil diimport.");
    }
}

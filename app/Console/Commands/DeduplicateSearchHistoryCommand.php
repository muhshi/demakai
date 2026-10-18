<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateSearchHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:deduplicate 
                            {--dry-run : Hanya hitung dan tampilkan data duplikat tanpa menghapus} 
                            {--force : Jalankan langsung tanpa konfirmasi prompt}
                            {--chunk=20000 : Jumlah baris per batch penghapusan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan baris duplikat ganda pada riwayat pencarian (search_histories) yang tercatat 2x akibat bug sub-pencarian KBLI/KBJI.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $this->info($isDryRun ? '=== SIMULASI PEMBERSIHAN DUPLIKAT SEARCH HISTORY (DRY-RUN) ===' : '=== MEMULAI PEMBERSIHAN DUPLIKAT SEARCH HISTORY ===');

        // Query untuk mencari ID kedua dari pasangan duplikat (b.id)
        // Kriteria: query sama, selisih waktu <= 3 detik, id berurutan atau berpasangan
        $findDuplicatesSql = "
            SELECT b.id
            FROM search_histories a
            JOIN search_histories b
              ON a.id = b.id - 1
             AND a.query = b.query
             AND ABS(EXTRACT(EPOCH FROM (a.created_at - b.created_at))) <= 3
        ";

        $countDuplicatesSql = "
            SELECT COUNT(*) as total
            FROM search_histories a
            JOIN search_histories b
              ON a.id = b.id - 1
             AND a.query = b.query
             AND ABS(EXTRACT(EPOCH FROM (a.created_at - b.created_at))) <= 3
        ";

        $this->comment('Menghitung jumlah baris duplikat...');
        $countResult = DB::select($countDuplicatesSql);
        $totalDuplicates = $countResult[0]->total ?? 0;

        if ($totalDuplicates === 0) {
            $this->info('Tidak ditemukan duplikat ganda pada tabel search_histories.');
            return 0;
        }

        $this->warn("Ditemukan {$totalDuplicates} baris pencarian duplikat (rekaman kedua dari tiap pencarian ganda).");

        // Tampilkan 5 contoh duplikat
        $sample = DB::select("
            SELECT a.id as id_asli, b.id as id_duplikat, a.query, a.created_at
            FROM search_histories a
            JOIN search_histories b
              ON a.id = b.id - 1
             AND a.query = b.query
             AND ABS(EXTRACT(EPOCH FROM (a.created_at - b.created_at))) <= 3
            ORDER BY a.id DESC
            LIMIT 5
        ");

        $this->table(
            ['ID Asli (Disimpan)', 'ID Duplikat (Dihapus)', 'Kata Kunci', 'Waktu'],
            array_map(fn($r) => [$r->id_asli, $r->id_duplikat, $r->query, $r->created_at], $sample)
        );

        if ($isDryRun) {
            $this->info("Simulasi selesai. Sebanyak {$totalDuplicates} baris duplikat dapat dibersihkan.");
            $this->comment("Jalankan tanpa '--dry-run' untuk mengeksekusi penghapusan permanen.");
            return 0;
        }

        if (!$this->option('force') && !$this->confirm("Apakah Anda yakin ingin menghapus {$totalDuplicates} baris duplikat tersebut?", true)) {
            $this->info('Operasi dibatalkan.');
            return 0;
        }

        $this->info("Mengeksekusi penghapusan batch...");
        $bar = $this->output->createProgressBar($totalDuplicates);
        $bar->start();

        // Eksekusi penghapusan berbasis subquery delete
        $deletedTotal = 0;
        do {
            $deleted = DB::delete("
                DELETE FROM search_histories
                WHERE id IN (
                    SELECT b.id
                    FROM search_histories a
                    JOIN search_histories b
                      ON a.id = b.id - 1
                     AND a.query = b.query
                     AND ABS(EXTRACT(EPOCH FROM (a.created_at - b.created_at))) <= 3
                    LIMIT {$chunkSize}
                )
            ");

            $deletedTotal += $deleted;
            $bar->advance($deleted);
        } while ($deleted > 0);

        $bar->finish();
        $this->newLine(2);

        $this->info("Sukses! Total {$deletedTotal} baris duplikat berhasil dihapus.");
        $this->info("Total data search_histories sekarang: " . DB::table('search_histories')->count() . " baris.");

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Exception;
use App\Models\PgKBJI2014;
use App\Models\PgKBLI2025;
use App\Services\GeminiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IndexKBLI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:index-kbli {--model=KBLI : Model to index (KBLI or KBJI)} {--limit=0 : Limit number of records to process (0 for all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate embeddings for KBLI/KBJI data using Gemini API';

    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        parent::__construct();
        $this->gemini = $gemini;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelName = strtoupper($this->option('model'));
        $limit = (int) $this->option('limit');

        $this->info("Starting indexing for: $modelName");

        // 1. Tentukan Model yang mau diproses
        $query = match ($modelName) {
            'KBJI' => PgKBJI2014::whereNull('embedding')->orWhere('embedding', 'LIKE', '%{}%'),
            default => PgKBLI2025::whereNull('embedding')->orWhere('embedding', 'LIKE', '%{}%'),
        };

        // 2. Hitung total yang belum punya otak (embedding)
        $total = $query->count();
        if ($total === 0) {
            $this->info("All records already indexed!");
            return;
        }

        if ($limit > 0) {
            $query->limit($limit);
            $this->info("Processing $limit out of $total remaining records.");
        } else {
            $this->info("Processing all $total remaining records.");
        }

        $bar = $this->output->createProgressBar($limit > 0 ? $limit : $total);
        $bar->start();

        // 3. Loop data
        $records = $query->get(); // Ambil semua (hati-hati memory kalau jutaan, tapi ini cuma ribuan)

        foreach ($records as $record) {
            // Gabungkan teks untuk konteks penuh
            $textToEmbed = $this->buildContext($record, $modelName);

            // Panggil Gemini (Gratis tapi santai)
            $vector = $this->gemini->generateEmbedding($textToEmbed);

            if ($vector) {
                try {
                    // Check dimension
                    $dim = count($vector);
                    if ($dim !== 768) {
                        $this->error("Dimension mismatch! Expected 768, got {$dim}");
                        Log::error("Dimension mismatch for {$record->id}: {$dim}");
                        continue;
                    }
                    // Simpan ke database
                    $record->embedding = $vector; // PGVector otomatis handle array -> vector
                    $record->save();
                    $this->info("Saved embedding for {$record->kode}");
                } catch (Exception $e) {
                    $this->error("Failed to save embedding for {$record->kode}: " . $e->getMessage());
                    Log::error("Failed to save embedding for {$record->id}: " . $e->getMessage());
                }
            } else {
                $this->error("Failed to embed: " . $record->kode);
                Log::warning("Failed to embed record {$record->id} ($modelName)");
            }
            $bar->advance();

            // PENTING: Rate Limiting untuk Free Tier (15 RPM = 1 request per 4 detik)
            // Kita kasih napas 4.5 detik biar aman
            usleep(4500000);
        }
        $bar->finish();
        $this->newLine();
        $this->info("Indexing completed!");
    }
    private function buildContext($record, $type)
    {
        // Format teks yang dikirim ke AI agar dia paham konteks
        if ($type === 'KBJI') {
            // KBJI: Kode + Nama Jabatan + Deskripsi + Contoh
            $contoh = is_array($record->contoh_lapangan) ? implode(', ', $record->contoh_lapangan) : '';
            return "Jabatan KBJI {$record->kode}: {$record->judul}. Deskripsi: {$record->deskripsi}. Contoh: {$contoh}";
        } else {
            // KBLI: Kode + Judul Kategori + Deskripsi + Contoh
            $contoh = is_array($record->contoh_lapangan) ? implode(', ', $record->contoh_lapangan) : '';
            return "Kategori KBLI {$record->kode}: {$record->judul}. Deskripsi: {$record->deskripsi}. Mencakup: {$contoh}";
        }
    }
}

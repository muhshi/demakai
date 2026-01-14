<?php

namespace App\Console\Commands;

use App\Models\PgKBJI2014;
use App\Models\PgKBLI2020;
use App\Models\PgKBLI2025;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:generate {--limit=100 : Limit the number of API calls (embeddings generated) per run} {--delay=1000 : Delay in milliseconds between requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate vector embeddings for KBLI and KBJI data using Google Gemini with Smart Update';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $delay = (int) $this->option('delay');
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            $this->error('GEMINI_API_KEY is missing in .env');
            return Command::FAILURE;
        }

        $this->info('Generating embeddings for KBLI2020 using Gemini...');
        $this->processModel(PgKBLI2020::class, $apiKey, $limit, $delay);

        $this->info('Generating embeddings for KBLI2025 using Gemini...');
        $this->processModel(PgKBLI2025::class, $apiKey, $limit, $delay);

        $this->info('Generating embeddings for KBJI2014 using Gemini...');
        $this->processModel(PgKBJI2014::class, $apiKey, $limit, $delay);

        return Command::SUCCESS;
    }

    protected function processModel($modelClass, $apiKey, $limit, $delay)
    {
        $totalRecords = $modelClass::count();
        $this->info("Scanning {$totalRecords} records for " . class_basename($modelClass) . "...");

        $generatedCount = 0;
        $skippedCount = 0;

        // Use chunkById to efficiently iterate all records
        $modelClass::chunkById(100, function ($records) use ($apiKey, $limit, $delay, &$generatedCount, &$skippedCount) {
            foreach ($records as $record) {
                if ($generatedCount >= $limit) {
                    return false; // Stop processing
                }

                $text = $this->constructPayload($record);
                $currentHash = md5($text);

                // Smart Update Check:
                // If embedding exists AND hash matches, skip it.
                if (!empty($record->embedding) && $record->embed_hash === $currentHash) {
                    $skippedCount++;
                    continue;
                }

                // If we are here, we need to generate an embedding
                $this->generateEmbedding($record, $text, $currentHash, $apiKey, $delay);
                $generatedCount++;
            }
        });

        $this->info("Processed: {$generatedCount} generated, {$skippedCount} skipped (unchanged).");
        $this->newLine();
    }

    protected function generateEmbedding($record, $text, $hash, $apiKey, $delay)
    {
        $attempts = 0;
        $maxAttempts = 3;
        $success = false;

        while (!$success && $attempts < $maxAttempts) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$apiKey}", [
                            'content' => [
                                'parts' => [
                                    ['text' => $text]
                                ]
                            ]
                        ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $embedding = $data['embedding']['values'] ?? null;

                    if ($embedding) {
                        $record->embedding = $embedding;
                        $record->payload = $text;
                        $record->embed_hash = $hash; // Save the hash
                        $record->save();
                        $success = true;
                        $this->line("  <info>✔</info> Embedded ID: {$record->id}");
                    } else {
                        throw new \Exception("No embedding found in response: " . $response->body());
                    }
                } else {
                    throw new \Exception("API Error: " . $response->body());
                }

            } catch (\Exception $e) {
                $attempts++;
                $this->warn("\n  Error processing record {$record->id}: " . $e->getMessage());
                if ($attempts < $maxAttempts) {
                    $this->info("  Retrying in 2 seconds...");
                    sleep(2);
                }
            }
        }

        if ($success) {
            usleep($delay * 1000); // Delay in microseconds
        }
    }

    protected function constructPayload($record)
    {
        // Combine relevant fields into a single string
        $parts = [];

        if (!empty($record->kode)) {
            $parts[] = "Kode: " . $record->kode;
        }

        if (!empty($record->judul)) {
            $parts[] = "Judul: " . $record->judul;
        }

        if (!empty($record->deskripsi)) {
            $parts[] = "Deskripsi: " . $record->deskripsi;
        }

        if (!empty($record->contoh_lapangan)) {
            $contoh = is_array($record->contoh_lapangan) ? implode(', ', $record->contoh_lapangan) : $record->contoh_lapangan;
            $parts[] = "Contoh Lapangan: " . $contoh;
        }

        return implode("\n", $parts);
    }
}

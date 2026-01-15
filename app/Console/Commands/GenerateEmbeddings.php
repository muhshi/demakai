<?php

namespace App\Console\Commands;

use App\Models\PgKBJI2014;
use App\Models\PgKBLI2020;
use App\Models\PgKBLI2025;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateEmbeddings extends Command
{
    protected $signature = 'embeddings:generate 
                            {--limit=5000 : Limit the number of records processed} 
                            {--delay=100 : Delay in ms between requests (default 100ms)}
                            {--model= : Focus on a specific model (KBJI2014, KBLI2020, KBLI2025)}';

    protected $description = 'Generate vector embeddings using Google Gemini embedContent API with Smart Update and Rate Limit handling';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $modelFilter = $this->option('model');
        $apiKey = config('services.gemini.api_key');

        if (empty($apiKey)) {
            $this->error('GEMINI_API_KEY is missing in .env');
            return Command::FAILURE;
        }

        $models = [
            'KBLI2020' => PgKBLI2020::class,
            'KBLI2025' => PgKBLI2025::class,
            'KBJI2014' => PgKBJI2014::class,
        ];

        foreach ($models as $name => $class) {
            if ($modelFilter && strtolower($modelFilter) !== strtolower($name)) {
                continue;
            }

            $this->info("Processing embeddings for {$name}...");
            $this->processModel($class, $apiKey, $limit, $delay);
        }

        return Command::SUCCESS;
    }

    protected function processModel($modelClass, $apiKey, $limit, $delay)
    {
        $q = $modelClass::where(function ($query) {
            $query->whereNull('embedding')
                ->orWhereRaw('embedding IS NULL');
        });

        $totalToProcess = min($q->count(), $limit);

        if ($totalToProcess === 0) {
            $this->info("No pending embeddings for " . class_basename($modelClass));
            return;
        }

        $this->info("Found {$totalToProcess} pending records. Processing...");
        $bar = $this->output->createProgressBar($totalToProcess);
        $bar->start();

        $processedCount = 0;

        $modelClass::whereNull('embedding')
            ->chunkById(50, function ($records) use ($apiKey, $delay, $bar, &$processedCount, $limit) {
                if ($processedCount >= $limit)
                    return false;

                foreach ($records as $record) {
                    $text = $this->constructPayload($record);
                    $hash = md5($text);

                    // Skip if already has correct embedding (safety check)
                    if (!empty($record->embedding) && $record->embed_hash === $hash) {
                        $processedCount++;
                        $bar->advance();
                        continue;
                    }

                    $success = $this->generateSingle($record, $text, $hash, $apiKey);

                    if ($success) {
                        $processedCount++;
                        $bar->advance();
                    } else {
                        // If fatal failure or repeated rate limit, we might want to stop
                        // But for now, we just continue to the next one
                    }

                    if ($delay > 0) {
                        usleep($delay * 1000);
                    }

                    if ($processedCount >= $limit)
                        return false;
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Completed processing " . class_basename($modelClass));
    }

    protected function generateSingle($record, $text, $hash, $apiKey)
    {
        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            try {
                $response = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$apiKey}", [
                            'content' => [
                                'parts' => [
                                    ['text' => $text]
                                ]
                            ]
                        ]);

                if ($response->successful()) {
                    $vector = $response->json()['embedding']['values'] ?? null;
                    if ($vector) {
                        $record->embedding = $vector;
                        $record->payload = $text;
                        $record->embed_hash = $hash;
                        $record->save();
                        return true;
                    }
                } elseif ($response->status() == 429) {
                    $attempts++;
                    $this->warn("\nRate limit hit (429). Waiting 10s before retry...");
                    sleep(10);
                } else {
                    $this->error("\nRequest failed (Status: " . $response->status() . "): " . $response->body());
                    return false;
                }
            } catch (\Exception $e) {
                $attempts++;
                $this->error("\nError: " . $e->getMessage());
                sleep(2);
            }
        }

        return false;
    }

    protected function constructPayload($record)
    {
        $parts = [];
        if (!empty($record->kode))
            $parts[] = "Kode: " . $record->kode;
        if (!empty($record->judul))
            $parts[] = "Judul: " . $record->judul;
        if (!empty($record->deskripsi))
            $parts[] = "Deskripsi: " . $record->deskripsi;

        if (!empty($record->contoh_lapangan)) {
            $contoh = is_array($record->contoh_lapangan) ? implode(', ', $record->contoh_lapangan) : $record->contoh_lapangan;
            $parts[] = "Contoh Lapangan: " . $contoh;
        }

        return implode("\n", $parts);
    }
}

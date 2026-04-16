<?php

namespace Database\Seeders;

use App\Models\PgKBJI2014;
use App\Models\PgKBLI2025;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class FieldExampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/kbli_kbji_examples.json');
        
        if (!File::exists($path)) {
            $this->command->error("JSON file not found at: {$path}");
            return;
        }

        $data = json_decode(File::get($path), true);

        // Process KBLI 2025
        $this->command->info("Processing KBLI 2025 examples...");
        $kbliCount = 0;
        foreach ($data['kbli_examples'] as $item) {
            $model = PgKBLI2025::where('kode', $item['code'])->first();
            
            if ($model) {
                $examples = $model->contoh_lapangan;
                if (is_string($examples)) {
                    $json = json_decode($examples, true);
                    $examples = is_array($json) ? $json : [$examples];
                }
                $examples = $examples ?? [];

                // Ensure unique examples
                if (!in_array($item['example'], $examples)) {
                    $examples[] = $item['example'];
                    $model->update(['contoh_lapangan' => $examples]);
                    $kbliCount++;
                }
            }
        }

        // Process KBJI 2014
        $this->command->info("Processing KBJI 2014 examples...");
        $kbjiCount = 0;
        foreach ($data['kbji_examples'] as $item) {
            $model = PgKBJI2014::where('kode', $item['code'])->first();
            
            if ($model) {
                $examples = $model->contoh_lapangan;
                if (is_string($examples)) {
                    $json = json_decode($examples, true);
                    $examples = is_array($json) ? $json : [$examples];
                }
                $examples = $examples ?? [];

                // Ensure unique examples
                if (!in_array($item['example'], $examples)) {
                    $examples[] = $item['example'];
                    $model->update(['contoh_lapangan' => $examples]);
                    $kbjiCount++;
                }
            }
        }

        $this->command->info("Successfully imported {$kbliCount} KBLI and {$kbjiCount} KBJI field examples.");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PgKBLI2025;
use App\Models\PgKBJI2014;

class AppendJudulToContohLapanganCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demakai:append-judul-contoh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Appends the judul of KBLI/KBJI to their respective contoh_lapangan entries.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to append judul to contoh_lapangan for KBLI 2025...');
        
        $kblis = PgKBLI2025::whereNotNull('contoh_lapangan')->get();
        $updatedKbli = 0;
        
        foreach ($kblis as $kbli) {
            $contohArray = is_string($kbli->contoh_lapangan) ? json_decode($kbli->contoh_lapangan, true) : $kbli->contoh_lapangan;
            
            if (is_array($contohArray)) {
                $newContoh = [];
                $changed = false;
                
                foreach ($contohArray as $contoh) {
                    // Cek biar nggak double append judulnya
                    if (!str_contains(strtolower($contoh), strtolower(trim($kbli->judul)))) {
                        $newContoh[] = trim($kbli->judul) . ' - ' . trim($contoh);
                        $changed = true;
                    } else {
                        $newContoh[] = trim($contoh);
                    }
                }
                
                if ($changed) {
                    $kbli->contoh_lapangan = json_encode($newContoh, JSON_UNESCAPED_UNICODE);
                    $kbli->embedding = null; // force re-embedding
                    $kbli->save();
                    $updatedKbli++;
                }
            }
        }
        $this->info("KBLI 2025 updated: {$updatedKbli} records.");

        
        $this->info('Starting to append judul to contoh_lapangan for KBJI 2014...');
        $kbjis = PgKBJI2014::whereNotNull('contoh_lapangan')->get();
        $updatedKbji = 0;
        
        foreach ($kbjis as $kbji) {
            $contohArray = is_string($kbji->contoh_lapangan) ? json_decode($kbji->contoh_lapangan, true) : $kbji->contoh_lapangan;
            
            if (is_array($contohArray)) {
                $newContoh = [];
                $changed = false;
                
                foreach ($contohArray as $contoh) {
                    if (!str_contains(strtolower($contoh), strtolower(trim($kbji->judul)))) {
                        $newContoh[] = trim($kbji->judul) . ' - ' . trim($contoh);
                        $changed = true;
                    } else {
                        $newContoh[] = trim($contoh);
                    }
                }
                
                if ($changed) {
                    $kbji->contoh_lapangan = json_encode($newContoh, JSON_UNESCAPED_UNICODE);
                    $kbji->embedding = null; // force re-embedding
                    $kbji->save();
                    $updatedKbji++;
                }
            }
        }
        $this->info("KBJI 2014 updated: {$updatedKbji} records.");

        $this->info('Done! Please run the embedding script again.');
    }
}

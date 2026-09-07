<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FieldExampleSubmission;

class RejectDuplicateSubmissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'submissions:reject-duplicates {--dry-run : Hanya tampilkan data duplikat tanpa mengubah status di database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menolak pengajuan contoh lapangan yang duplikat (mempertahankan 1 entri tertua tetap pending).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? '--- MODE SIMULASI (DRY-RUN) ---' : '--- MEMPROSES PENOLAKAN DUPLIKAT ---');

        // Ambil semua pengajuan yang sudah approved untuk referensi
        $approvedSubmissions = FieldExampleSubmission::where('status', 'approved')->get();
        $approvedKeys = [];
        foreach ($approvedSubmissions as $app) {
            $key = trim($app->type) . '|' . trim($app->kode) . '|' . trim(mb_strtolower($app->content));
            $approvedKeys[$key] = $app->id;
        }

        // Ambil semua pengajuan pending urut ID ascending (tertua duluan)
        $pendingSubmissions = FieldExampleSubmission::where('status', 'pending')
            ->orderBy('id', 'asc')
            ->get();

        $seen = [];
        $toReject = [];

        foreach ($pendingSubmissions as $item) {
            $key = trim($item->type) . '|' . trim($item->kode) . '|' . trim(mb_strtolower($item->content));

            // Jika sudah ada di approved
            if (isset($approvedKeys[$key])) {
                $toReject[] = [
                    'item' => $item,
                    'reason' => 'Sudah disetujui sebelumnya di ID ' . $approvedKeys[$key],
                ];
                continue;
            }

            // Jika duplikat sesama pending
            if (isset($seen[$key])) {
                $toReject[] = [
                    'item' => $item,
                    'reason' => 'Duplikat dari pending ID ' . $seen[$key],
                ];
            } else {
                $seen[$key] = $item->id;
            }
        }

        if (empty($toReject)) {
            $this->info('Tidak ditemukan pengajuan duplikat pada status pending.');
            return 0;
        }

        $this->table(
            ['ID', 'Type', 'Kode', 'Content', 'Alasan Tolak'],
            array_map(function ($row) {
                return [
                    $row['item']->id,
                    $row['item']->type,
                    $row['item']->kode,
                    $row['item']->content,
                    $row['reason'],
                ];
            }, $toReject)
        );

        $this->warn('Total pengajuan duplikat yang terdeteksi: ' . count($toReject));

        if ($isDryRun) {
            $this->info('Simulasi selesai. Tidak ada data yang diubah.');
            return 0;
        }

        $idsToReject = array_map(fn($r) => $r['item']->id, $toReject);
        FieldExampleSubmission::whereIn('id', $idsToReject)->update(['status' => 'rejected']);

        $this->info('Sukses! ' . count($idsToReject) . ' pengajuan duplikat telah diubah statusnya menjadi "rejected".');

        return 0;
    }
}

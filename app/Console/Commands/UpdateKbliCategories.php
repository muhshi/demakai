<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kbli2025Hierarchy;

class UpdateKbliCategories extends Command
{
    protected $signature = 'kbli:update-categories';
    protected $description = 'Update KBLI 2025 category (1-letter code) names to match the official 2025 specification';

    /**
     * Correct KBLI 2025 category names as per official BPS publication.
     */
    protected array $categories = [
        'A' => 'Pertanian, Kehutanan, dan Perikanan',
        'B' => 'Pertambangan dan Penggalian',
        'C' => 'Industri',
        'D' => 'Penyediaan Listrik, Gas, Uap/Air Panas Dan Udara Dingin',
        'E' => 'Penyediaan Air, Pengelolaan Air Limbah, Penanganan Limbah, dan Remediasi',
        'F' => 'Konstruksi',
        'G' => 'Perdagangan Besar Dan Eceran',
        'H' => 'Transportasi dan Penyimpanan',
        'I' => 'Aktivitas Penyediaan Akomodasi dan Makan Minum',
        'J' => 'Aktivitas Penerbitan, Penyiaran, Produksi dan Distribusi Konten',
        'K' => 'Aktivitas Telekomunikasi, Pemrograman Komputer, Konsultansi, Infrastruktur Komputasi, dan Jasa Informasi Lainnya',
        'L' => 'Aktivitas Keuangan dan Asuransi',
        'M' => 'Aktivitas Real Estat',
        'N' => 'Aktivitas Profesional, Ilmiah, dan Teknis',
        'O' => 'Aktivitas Administratif dan Penunjang Usaha',
        'P' => 'Administrasi Pemerintahan dan Pertahanan, serta Jaminan Sosial Wajib',
        'Q' => 'Pendidikan',
        'R' => 'Aktivitas Kesehatan Manusia dan Aktivitas Sosial',
        'S' => 'Kesenian, Olahraga, dan Rekreasi',
        'T' => 'Aktivitas Jasa Lainnya',
        'U' => 'Aktivitas Rumah Tangga sebagai Pemberi Kerja dan Aktivitas Produksi Barang dan Jasa oleh Rumah Tangga untuk Keperluan Sendiri yang Tidak Terdiferensiasi',
        'V' => 'Aktivitas Badan Internasional dan Badan Ekstra Internasional Lainnya',
    ];

    public function handle(): int
    {
        $this->info('Updating KBLI 2025 category names...');

        $updated = 0;
        $notFound = 0;

        foreach ($this->categories as $kode => $judul) {
            $rows = Kbli2025Hierarchy::where('level', 'kategori')
                ->where('kode', $kode)
                ->get();

            if ($rows->isEmpty()) {
                $this->warn("  ⚠ Category {$kode} not found in database.");
                $notFound++;
                continue;
            }

            foreach ($rows as $row) {
                $old = $row->judul;
                $row->judul = $judul;
                $row->save();
                $this->line("  ✔ [{$kode}] {$old}");
                $this->line("        → {$judul}");
            }
            $updated++;
        }

        $this->newLine();
        $this->info("Done! Updated: {$updated} | Not found: {$notFound}");

        return self::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PgKBLI2025;

class KbliExamplesSeeder extends Seeder
{
    public function run()
    {
        $examples = [
            '06100' => ['Penyulingan Minyak Bumi'],
            '07221' => ['Mendulang emas'],
            '08104' => ['Penggalian Tanah Laterit'],
            '09900' => ['Eksplorasi bijih logam di lokasi yang berpotensi jadi pertambangan'],
            '10750' => ['Membuat nasi uduk, dibungkus, untuk dijual tanpa penyajian'],
            '13122' => ['Pertenunan kain ulos'],
            '18111' => ['Aktivitas toko fotokopi'],
            '33153' => ['Perawatan pesawat'],
            '41011' => ['Aktivitas borongan membangun rumah'],
            '42102' => ['Aktivitas membangun jembatan'],
            '42207' => ['Pengeboran untuk membuat sumur bor'],
            '43120' => ['Pembersihan lahan untuk aktivitas konstruksi'],
            '43302' => ['Jasa pemasangan plafon terkait penyelesaian bangunan'],
            '01138' => ['Budidaya tanaman cabai rawit'],
            '05101' => ['Pertambangan batu bara', 'Ekstraksi gambut/sedimen batu menjadi batu bara'],
            '10212' => ['Pembuatan ikan cakalang asap'],
            '35111' => ['Pembangkit listrik tenaga uap (PLTU) dari batu bara'],
            '36001' => ['Pengolahan air tanah menjadi air minum dan penyalurannya'],
            '43309' => ['Jasa pembuatan dan instalasi kitchen set'],
            '47744' => ['Jasa perdagangan eceran HP bekas', 'Jual beli dan reparasi HP bekas'],
            '49296' => ['Jasa angkutan ojek motor untuk penumpang secara online (Gojek, Grab)', 'Kurir Shopee Food/GoFood'],
            '56102' => ['Warteg di tenda bongkar pasang', 'Penjual makanan keliling dengan fasilitas memasak di tempat'],
            '58110' => ['Penerbitan buku cetak dan digital'],
            '61209' => ['Penjualan kembali pulsa, kartu perdana, paket internet'],
            '66199' => ['Aktivitas laku pandai', 'Agen BRILink (transfer uang, token listrik, dll)'],
            '68111' => ['Pengembangan dan pengelolaan kawasan perumahan hunian'],
            '71201' => ['Jasa sertifikasi proses manajemen lingkungan'],
            '78101' => ['Perekrutan dan penempatan tenaga kerja di dalam negeri'],
            '85202' => ['Sekolah dasar (SD) swasta', 'Yayasan pendidikan dasar'],
            '86101' => ['Rumah Sakit Umum Daerah (RSUD) pemerintah'],
            '90200' => ['Aktivitas pertunjukan seni ludruk', 'Pertunjukan wayang kulit', 'Content creator/Youtuber/Selebgram sebagai talent video'],
            '96900' => ['Jasa laundry/binatu', 'Jasa joki game online (naik level dll)', 'Jasa dukun/orang pintar independen'],
            '22209' => ['Pembuatan kerajinan kipas dari botol plastik bekas'],
            '38302' => ['Pembuatan granul plastik / bahan baku sekunder dari limbah'],
            '35401' => ['Aktivitas broker dan agen penjualan tenaga listrik'],
            '35402' => ['Aktivitas broker dan agen penjualan gas alam'],
            '43400' => ['Jasa intermediasi konstruksi khusus'],
            '47901' => ['Platform digital intermediasi perdagangan eceran (Jastip melalui platform digital)'],
            '47909' => ['Jastip konvensional (tanpa platform digital khusus)'],
            '52311' => ['Jasa pengurusan transportasi (JPT) barang'],
            '52312' => ['Jasa keagenan kapal / agen perkapalan'],
            '53200' => ['Aktivitas kurir ekspedisi'],
            '56303' => ['Point Coffee Indomaret (pembuatan minuman sesuai pesanan)'],
            '93129' => ['Klub E-Sport'],
            '93197' => ['Aktivitas atlet E-Sport mandiri (tanpa klub)'],
            '5911'  => ['Livestreaming gaming dengan saweran / donasi', 'Pembuatan konten video podcast'],
            '73100' => ['Afiliator dengan tautan link / endorsement (Aktivitas Periklanan)'],
        ];

        $count = 0;
        foreach ($examples as $kode => $newExamples) {
            $kbli = PgKBLI2025::where('kode', $kode)->first();
            if ($kbli) {
                // merge examples
                $current = is_array($kbli->contoh_lapangan) ? $kbli->contoh_lapangan : [];
                if (is_string($current)) {
                    $current = json_decode($current, true) ?? [];
                }
                
                $updated = array_unique(array_merge($current, $newExamples));
                $kbli->contoh_lapangan = array_values($updated);
                $kbli->save();
                $count++;
            }
        }

        $this->command->info("Berhasil menambahkan contoh lapangan ke {$count} kode KBLI 2025.");
    }
}

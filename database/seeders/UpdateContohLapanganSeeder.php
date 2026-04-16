<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateContohLapanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Versi 1 — Kode populer & representatif (disetujui mentor)
     * Target tabel: kbli2025s dan kbji2014s (PostgreSQL)
     *
     * @return void
     */
    public function run()
    {
        // ============================================================
        // 20 KBLI 2025 — Menggunakan data KBLI 2025 asli dari database
        // ============================================================
        $kbliData = [
            '46795' => ['judul' => 'PERDAGANGAN BESAR BARANG DARI KERTAS DAN KARTON SEBAGAI BARANG JADI', 'contoh' => 'Distributor besar tisu wajah, buku tulis, dan kotak karton kemasan untuk ritel.'],
            '17011' => ['judul' => 'INDUSTRI BUBUR KERTAS (PULP)', 'contoh' => 'Pabrik pengolahan serat kayu atau ampas tebu menjadi bubur selulosa untuk bahan baku kertas.'],
            '03217' => ['judul' => 'PENANGKAPAN SUMBER DAYA ALAM HAYATI AIR LAUT LAINNYA YANG TIDAK DILINDUNGI', 'contoh' => 'Nelayan yang mengumpulkan teripang, ubur-ubur, atau rumput laut liar di perairan dangkal.'],
            '20115' => ['judul' => 'INDUSTRI KIMIA DASAR ORGANIK DARI SUMBER DAYA ALAM HAYATI', 'contoh' => 'Produksi biodiesel dari minyak sawit atau pengolahan minyak atsiri sebagai bahan kimia dasar.'],
            '68291' => ['judul' => 'JASA PENAKSIR REAL ESTAT', 'contoh' => 'Tim ahli penilai independen yang menghitung nilai pasar tanah dan bangunan untuk keperluan perbankan atau investasi.'],
            '65121' => ['judul' => 'ASURANSI UMUM KONVENSIONAL', 'contoh' => 'Agen asuransi yang menawarkan perlindungan kerugian atas kebakaran rumah atau kecelakaan kendaraan bermotor.'],
            '01493' => ['judul' => 'BUDI DAYA DAN PEMBIBITAN LEBAH', 'contoh' => 'Peternak lebah madu yang mengelola koloni lebah Trigona untuk produksi madu dan propolis.'],
            '50113' => ['judul' => 'ANGKUTAN LAUT DALEM NEGERI UNTUK WISATA', 'contoh' => 'Operator kapal pinisi yang melayani rute pelayaran wisata antar pulau di wilayah perairan Indonesia.'],
            '47113' => ['judul' => 'PERDAGANGAN ECERAN BERBAGAI MACAM BARANG YANG UTAMANYA MAKANAN DI MINIMARKET', 'contoh' => 'Toko ritel modern berskala kecil yang menjual kebutuhan pokok, makanan ringan, dan minuman dingin di area pemukiman.'],
            '56101' => ['judul' => 'RESTORAN', 'contoh' => 'Rumah makan yang menyediakan layanan prasmanan atau menu lengkap dengan area makan yang luas untuk tamu.'],
            '10710' => ['judul' => 'INDUSTRI PRODUK ROTI DAN KUE', 'contoh' => 'Usaha pembuatan roti tawar, kue kering, and pastry yang didistribusikan ke berbagai toko atau supermarket.'],
            '45411' => ['judul' => 'PERDAGANGAN BESAR SEPEDA MOTOR DAN SUKU CADANGNYA', 'contoh' => 'Dealer utama yang memasok berbagai unit sepeda motor baru dan komponen mesin ke bengkel atau toko eceran.'],
            '47721' => ['judul' => 'PERDAGANGAN ECERAN PAKAIAN', 'contoh' => 'Butik pakaian jadi yang menjual busana muslim, kemeja pria, atau pakaian anak-anak secara langsung ke konsumen.'],
            '49431' => ['judul' => 'ANGKUTAN DARAT UNTUK BARANG', 'contoh' => 'Jasa pengiriman barang menggunakan truk tronton atau kontainer untuk distribusi logistik antar kota.'],
            '62019' => ['judul' => 'AKTIVITAS PEMROGRAMAN KOMPUTER LAINNYA', 'contoh' => 'Pengembang perangkat lunak yang mengerjakan modifikasi sistem atau debugging aplikasi khusus sesuai pesanan klien.'],
            '43211' => ['judul' => 'INSTALASI LISTRIK', 'contoh' => 'Teknisi yang memasang jaringan kabel listrik, panel sirkuit, dan perangkat pencahayaan pada bangunan gedung baru.'],
            '47732' => ['judul' => 'PERDAGANGAN ECERAN KOSMETIK DAN BARANG TOILET', 'contoh' => 'Toko kecantikan yang menjual berbagai produk perawatan kulit (skincare), makeup, dan parfum secara eceran.'],
            '52101' => ['judul' => 'PERGUDANGAN DAN PENYIMPANAN', 'contoh' => 'Layanan penyewaan ruang gudang untuk penyimpanan stok barang industri sebelum didistribusikan ke pasar.'],
            '47723' => ['judul' => 'PERDAGANGAN ECERAN PRODUK TEKSTIL LAINNYA', 'contoh' => 'Toko yang menjual kain kiloan, gorden, atau seprai tempat tidur dengan berbagai macam motif dan bahan.'],
            '56301' => ['judul' => 'BAR', 'contoh' => 'Tempat usaha yang menyediakan berbagai minuman untuk dinikmati di tempat dengan iringan musik.'],
        ];

        // ============================================================
        // 20 KBJI 2014 — Menggunakan data KBJI yang baru didraf
        // ============================================================
        $kbjiData = [
            '732302' => ['judul' => 'Operator Mesin Penyusun Huruf (Typesetting)', 'contoh' => 'Pekerja percetakan yang mengatur tata letak huruf dan simbol pada mesin cetak untuk pembuatan buku atau koran.'],
            '226601' => ['judul' => 'Ahli Audiologi (Audio-Audiometry)', 'contoh' => 'Tenaga medis profesional yang melakukan tes pendengaran dan memberikan alat bantu dengar kepada pasien dengan gangguan fungsi pendengaran.'],
            '421399' => ['judul' => 'Penagih Hutang dan Pekerja Terkait Lainnya', 'contoh' => 'Staf administrasi keuangan yang melakukan penagihan tunggakan pembayaran kepada pelanggan melalui telepon atau kunjungan lapangan.'],
            '514204' => ['judul' => 'Pencuci Kendaraan (Kendaraan Bermotor)', 'contoh' => 'Pekerja di jasa pencucian mobil atau motor yang membersihkan eksterior dan interior kendaraan menggunakan air tekanan tinggi.'],
            '143106' => ['judul' => 'Manajer Sekolah Mengemudi', 'contoh' => 'Pengelola lembaga pelatihan mengemudi yang mengatur jadwal instruktur, armada mobil latihan, dan pendaftaran siswa baru.'],
            '722304' => ['judul' => 'Penyetel Perkakas Mesin (Penyetel Mesin Logam)', 'contoh' => 'Teknisi bengkel bubut yang mengatur program dan memasang alat potong pada mesin perkakas metal untuk produksi komponen industri.'],
            '611102' => ['judul' => 'Petani Tanaman Padi', 'contoh' => 'Petani yang mengelola lahan sawah mulai dari penyemaian benih, penanaman, hingga masa panen padi di lingkungan pedesaan.'],
            '524410' => ['judul' => 'Penjual Pintu ke Pintu (Sales Door-to-Door)', 'contoh' => 'Salesman yang mendatangi rumah penduduk secara langsung untuk menawarkan produk rumah tangga atau jasa secara eceran.'],
            '261102' => ['judul' => 'Pengacara (Advokat)', 'contoh' => 'Praktisi hukum yang memberikan jasa konsultasi hukum dan mewakili kepentingan klien dalam persidangan atau penyelesaian sengketa.'],
            '314202' => ['judul' => 'Teknisi Laboratorium Pertanian', 'contoh' => 'Staf ahli yang membantu riset benih unggul dan menguji kualitas tanah di laboratorium penelitian tanaman.'],
            '751203' => ['judul' => 'Ahli Pembuat Roti dan Kue (Baker)', 'contoh' => 'Pembuat roti (baker) di toko kue yang mencampur adonan, membentuk roti, dan mengawasi proses pemanggangan di dalam oven.'],
            '522201' => ['judul' => 'Kasir Toko', 'contoh' => 'Petugas di konter pembayaran minimarket atau supermarket yang melayani transaksi belanja pelanggan menggunakan mesin POS.'],
            '723301' => ['judul' => 'Mekanik dan Tukang Perbaikan Mesin Pertanian dan Industri', 'contoh' => 'Teknisi yang melakukan servis berkala dan perbaikan kerusakan mesin traktor atau alat mesin pemanen di area pertanian.'],
            '334102' => ['judul' => 'Sekretaris Kantor (Administrasi)', 'contoh' => 'Staf administrasi yang mengurus korespondensi surat, penjadwalan rapat pimpinan, dan pengarsipan dokumen kantor secara rapi.'],
            '933303' => ['judul' => 'Buruh Bongkar Muat Barang', 'contoh' => 'Pekerja kasar yang memindahkan barang atau kargo dari dalam truk ke gudang penyimpanan atau sebaliknya.'],
            '242203' => ['judul' => 'Analis Kebijakan Publik', 'contoh' => 'Staf ahli pemerintah yang melakukan kajian dan evaluasi terhadap efektivitas program bantuan sosial atau regulasi daerah.'],
            '814301' => ['judul' => 'Operator Mesin Cetak Kertas', 'contoh' => 'Petugas yang menjalankan mesin offset untuk mencetak majalah, brosur, atau kalender dalam jumlah besar.'],
            '216503' => ['judul' => 'Kartografer (Pembuat Peta)', 'contoh' => 'Ahli pemetaan yang mengolah data geografis untuk membuat peta digital atau cetak guna keperluan navigasi atau perencanaan wilayah.'],
            '122105' => ['judul' => 'Manajer Pemasaran dan Penjualan (Marketing)', 'contoh' => 'Pemimpin divisi marketing yang merancang strategi promosi produk baru untuk meningkatkan target penjualan perusahaan.'],
            '516104' => ['judul' => 'Numerolog (Peramal dengan Angka)', 'contoh' => 'Seseorang yang memberikan jasa pembacaan karakter atau ramalan berdasarkan hitungan angka tanggal lahir atau nama individu.'],
        ];

        // ------------------------------------------------------------------
        // 1. UPDATE KBLI 2025
        // ------------------------------------------------------------------
        $kbliUpdated = 0;
        $this->command->info('--- Memperbarui KBLI 2025 (tabel: kbli2025s) ---');
        foreach ($kbliData as $kode => $info) {
            $contohArray = [$info['contoh']];

            DB::connection('pgsql')->table('kbli2025s')->updateOrInsert(
                ['kode' => $kode],
                [
                    'judul'           => $info['judul'],
                    'contoh_lapangan' => json_encode($contohArray, JSON_UNESCAPED_UNICODE),
                    'embedding'       => null, // null = paksa regenerasi embedding AI
                ]
            );

            $kbliUpdated++;
            $this->command->line("  [OK] KBLI {$kode} processed.");
        }
        $this->command->info("KBLI 2025: {$kbliUpdated}/20 records processed.");

        // ------------------------------------------------------------------
        // 2. UPDATE KBJI 2014
        // ------------------------------------------------------------------
        $kbjiUpdated = 0;
        $this->command->info('--- Memperbarui KBJI 2014 (tabel: kbji2014s) ---');
        foreach ($kbjiData as $kode => $info) {
            $contohArray = [$info['contoh']];

            DB::connection('pgsql')->table('kbji2014s')->updateOrInsert(
                ['kode' => $kode],
                [
                    'judul'           => $info['judul'],
                    'contoh_lapangan' => json_encode($contohArray, JSON_UNESCAPED_UNICODE),
                    'embedding'       => null, // null = paksa regenerasi embedding AI
                ]
            );

            $kbjiUpdated++;
            $this->command->line("  [OK] KBJI {$kode} processed.");
        }
        $this->command->info("KBJI 2014: {$kbjiUpdated}/20 records processed.");

        // ------------------------------------------------------------------
        // Ringkasan akhir
        // ------------------------------------------------------------------
        $this->command->newLine();
        $total = $kbliUpdated + $kbjiUpdated;
        $this->command->info("Selesai! Total: {$total}/40 records berhasil diperbarui.");
        $this->command->warn('PERHATIAN: Embedding telah di-null. Jalankan re-indexing agar Hybrid Search bisa mengenali contoh lapangan baru ini.');
    }
}

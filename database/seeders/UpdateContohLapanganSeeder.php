<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateContohLapanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $kbliData = [
            '01121' => 'mencabut rumput liar di sawah, menanam benih padi di ladang basah, menggarap lumpur sawah untuk tanam padi',
            '10710' => 'ibu-ibu bikin kue basah di rumah terus dijual, produksi roti tawar untuk dikirim ke warung, pabrik kecil pembuat biskuit kaleng',
            '56102' => 'buka warung tegal jualan nasi sayur, restoran padang pinggir jalan, tempat makan lesehan jualan ikan bakar',
            '56103' => 'jualan bakso dorong pakai gerobak, buka stan martabak telor di pasar malam, warung pecel lele tenda biru',
            '47111' => 'buka toko kelontong jual sembako, warung madura jualan kebutuhan sehari hari, toko sembako grosir dan eceran di perempatan',
            '45201' => 'bengkel tambal ban mobil, jasa ganti oli dan as roda mobil, cuci mobil dan perbaikan mesin turun mesin',
            '45407' => 'bengkel tambal ban motor pinggir jalan, jasa service motor matic, jasa ganti kampas rem dan isi angin motor',
            '96210' => 'buka kapster potong rambut pria asgar, salon kecantikan rebonding dan warnai rambut, jasa cukur rambut pinggir jalan',
            '96301' => 'jasa cuci setrika pakaian kiloan, terima cucian karpet dan bed cover kotor, laundry express baju sehari hari',
            '14111' => 'penjahit rumahan bikin baju kaos partai, pabrik konveksi jahit celana jeans, maklon jahit baju kemeja seragam sekolah',
            '47711' => 'jualan baju anak anak di pasar tumpah, buka distro jual kaos sablon, toko jualan gamis dan hijab muslimah',
            '16221' => 'tukang kayu bikin meja kursi dari jati, tempat pembuatan lemari kayu custom, pengrajin perabotan rak piring dari triplek',
            '23122' => 'membuat lemari kaca atau etalase panjang, tukang potong kaca jendela rumah, bikin akuarium kaca rakitan',
            '68200' => 'menyewakan kamar kos kosan murah untuk mahasiswa, bisnis rumah kontrakan petakan bulanan, jasa sewa paviliun harian',
            '49422' => 'jasa angkut barang pindahan rumah pakai pick up, jasa kirim galon dan gas pakai mobil losbak, supir sewaan angkut material bangunan',
            '03111' => 'berlayar nangkap ikan tongkol pakai jaring, pergi melaut pakai perahu mencari ikan cakalang, nelayan jaring ikan pari di tengah laut',
            '85220' => 'mengelola sekolah negeri tingkat sma, membuka yayasan sekolah swasta setingkat sma, sekolah madrasah aliyah swasta',
            '47721' => 'buka toko obat atau apotek di ruko, jualan obat obatan resep dokter, toko jamu herbal dan obat kimia',
            '86201' => 'buka klinik praktek dokter 24 jam, tempat periksa orang berobat jalan umum, klinik penanganan sakit demam dan batuk',
            '53201' => 'jasa antar paket barang e-commerce, kurir ambil dokumen surat menyurat dari kantor, agen penerima paket kiriman cepat'
        ];

        $kbjiData = [
            '5414' => 'satpam jaga pintu gerbang di rumah sakit, petugas keamanan ronda malam di mall, tukang jaga malam komplek perumahan',
            '8322' => 'supir angkut pesanan lemari kaca pakai mobil carry, driver taksi online narik penumpang, supir pribadi antar jemput majikan pakai mobil',
            '9412' => 'karyawan bagian cuci piring di restoran, tukang kupas bawang dan potong sayur di dapur rumah makan, kenek tukang masak yang bertugas siapkan bahan',
            '9111' => 'asisten rumah tangga tugasnya bersih bersih rumah, pembantu yang tugasnya menyapu ngepel dan cuci piring harian, tukang bersih bersih panggilan ke apartemen',
            '7231' => 'tukang servis ganti oli motor matic rusak, bengkel kerjanya bongkar turun mesin mobil, montir keliling perbaiki motor mogok di jalan',
            '6111' => 'orang yang macul tanah nanam padi di sawah, tukang cabut rumput liar di lahan jagung, petani garap lahan nanam kedelai',
            '7531' => 'mak emak jahit baju daster di rumah, tukang obras pinggiran kain kaos oblong, penjahit yang bikin seragam pns sesuai ukuran',
            '7115' => 'tukang potong kayu bikin kusen pintu, kuli bangunan yang tugasnya pasang atap rumah, orang yang serut kayu untuk bikin meja lemari',
            '5223' => 'mbak mbak kasir minimarket yang jaga mesin kas, petugas toko yang menata barang sembako di rak, pelayan toko baju yang nawarin barang ke pembeli',
            '6210' => 'tukang tebang pohon jati di hutan, mandor yang ngecek bibit pohon sengon, pekerja pengambil getah karet di perkebunan',
            '2341' => 'ibu guru ngajar matematika anak kelas 1 sd, wali kelas yang ngurusin raport anak sekolah dasar, tenaga pengajar honorer di sd negeri',
            '2211' => 'dokter yang ngasih resep obat batuk pilek ke pasien, tenaga medis yang nanganin orang kecelakaan di igd, dokter puskesmas yang periksa ibu hamil',
            '3221' => 'suster yang nyuntik infus pasien di ruang ugd, perawat jaga malam ngontrol tensi pasien di kamar inap, mantri kesehatan yang bantu dokter operasi',
            '9611' => 'bapak bapak yang ambil sampah rumahtangga pakai gerobak, tukang angkut tong sampah ke truk kuning, petugas kebersihan pengumpul limbah masuk plastik',
            '8332' => 'supir tronton bawa kontainer antar provinsi, sopir mobil molen pembawa pasir batu, pengemudi truk gandeng angkut sembako',
            '5111' => 'mbak pramugari yang nawarin makan di pesawat terbang, petugas yang menuntun penumpang cari kursi di pesawat, awak kabin yang kasih instruksi keselamatan udara',
            '5120' => 'chef yang masak nasi goreng di hotel bintang lima, tukang masak utama racik bumbu sate di warteg, koki yang spesialis bakar ikan di restoran seafood',
            '9333' => 'kuli panggul beras di pasar induk, tukang angkat angkat semen ke atas truk, buruh kasar bongkar muat koper di pelabuhan',
            '4111' => 'admin kantor yang tugasnya ngetik surat di microsoft word, karyawan input data rekap jualan ke excel, staf administrasi pembuat laporan dokumen bulanan',
            '2120' => 'orang bps yang nyari data kemiskinan dan bikin grafik, staf ahli hitung peluang asuransi jiwa, petugas penyuluh statistik olah data sensus pakai komputer'
        ];

        // 1. UPDATE KBLI 2025
        foreach ($kbliData as $kode => $contohString) {
            $contohArray = array_map('trim', explode(',', $contohString));
            
            DB::connection('pgsql')->table('kbli2025s')
                ->where('kode', $kode)
                ->update([
                    'contoh_lapangan' => json_encode($contohArray, JSON_UNESCAPED_UNICODE),
                    'embedding' => null // Set null to force embedding regeneration
                ]);
        }
        $this->command->info('Updated 20 KBLI 2025 records.');

        // 2. UPDATE KBJI 2014
        foreach ($kbjiData as $kode => $contohString) {
            $contohArray = array_map('trim', explode(',', $contohString));
            
            DB::connection('pgsql')->table('kbji2014s')
                ->where('kode', $kode)
                ->update([
                    'contoh_lapangan' => json_encode($contohArray, JSON_UNESCAPED_UNICODE),
                    'embedding' => null // Set null to force embedding regeneration
                ]);
        }
        $this->command->info('Updated 20 KBJI 2014 records.');
    }
}

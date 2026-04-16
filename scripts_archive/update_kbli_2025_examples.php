<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PgKBLI2025;

$data = [
    '46795' => 'PERDAGANGAN BESAR BARANG DARI KERTAS DAN KARTON - Distributor besar tisu wajah, buku tulis, dan kotak karton kemasan untuk ritel.',
    '17011' => 'INDUSTRI BUBUR KERTAS (PULP) - Pabrik pengolahan serat kayu atau ampas tebu menjadi bubur selulosa untuk bahan baku kertas.',
    '03217' => 'PENANGKAPAN SUMBER DAYA ALAM HAYATI AIR LAUT LAINNYA - Nelayan yang mengumpulkan teripang, ubur-ubur, atau rumput laut liar di perairan dangkal.',
    '20115' => 'INDUSTRI KIMIA DASAR ORGANIK DARI SUMBER DAYA ALAM HAYATI - Produksi biodiesel dari minyak sawit atau pengolahan minyak atsiri sebagai bahan kimia dasar.',
    '68291' => 'JASA PENAKSIR REAL ESTAT - Tim ahli penilai independen yang menghitung nilai pasar tanah dan bangunan untuk keperluan perbankan atau investasi.',
    '65121' => 'ASURANSI UMUM KONVENSIONAL - Agen asuransi yang menawarkan perlindungan kerugian atas kebakaran rumah atau kecelakaan kendaraan bermotor.',
    '01493' => 'BUDI DAYA DAN PEMBIBITAN LEBAH - Peternak lebah madu yang mengelola koloni lebah Trigona untuk produksi madu dan propolis.',
    '50113' => 'ANGKUTAN LAUT DALAM NEGERI UNTUK WISATA - Operator kapal pinisi yang melayani rute pelayaran wisata antar pulau di wilayah perairan Indonesia.',
    '47113' => 'PERDAGANGAN ECERAN MINIMARKET - Toko ritel modern berskala kecil yang menjual kebutuhan pokok, makanan ringan, dan minuman dingin di area pemukiman.',
    '56101' => 'RESTORAN - Rumah makan yang menyediakan layanan prasmanan atau menu lengkap dengan area makan yang luas untuk tamu.',
    '10710' => 'INDUSTRI PRODUK ROTI DAN KUE - Usaha pembuatan roti tawar, kue kering, dan pastry yang didistribusikan ke berbagai toko atau supermarket.',
    '45411' => 'PERDAGANGAN BESAR SEPEDA MOTOR - Dealer utama yang memasok berbagai unit sepeda motor baru dan komponen mesin ke bengkel atau toko eceran.',
    '47721' => 'PERDAGANGAN ECERAN PAKAIAN - Butik pakaian jadi yang menjual busana muslim, kemeja pria, atau pakaian anak-anak secara langsung ke konsumen.',
    '49431' => 'ANGKUTAN DARAT UNTUK BARANG - Jasa pengiriman barang menggunakan truk tronton atau kontainer untuk distribusi logistik antar kota.',
    '62019' => 'AKTIVITAS PEMROGRAMAN KOMPUTER LAINNYA - Pengembang perangkat lunak yang mengerjakan modifikasi sistem atau debugging aplikasi khusus sesuai pesanan klien.',
    '43211' => 'INSTALASI LISTRIK - Teknisi yang memasang jaringan kabel listrik, panel sirkuit, dan perangkat pencahayaan pada bangunan gedung baru.',
    '47732' => 'PERDAGANGAN ECERAN KOSMETIK - Toko kecantikan yang menjual berbagai produk perawatan kulit (skincare), makeup, dan parfum secara eceran.',
    '52101' => 'PERGUDANGAN DAN PENYIMPANAN - Layanan penyewaan ruang gudang untuk penyimpanan stok barang industri sebelum didistribusikan ke pasar.',
    '47723' => 'PERDAGANGAN ECERAN PRODUK TEKSTIL - Toko yang menjual kain kiloan, gorden, atau seprai tempat tidur dengan berbagai macam motif dan bahan.',
    '56301' => 'BAR - Tempat usaha yang menyediakan berbagai minuman untuk dinikmati di tempat dengan iringan musik.'
];

$count = 0;
foreach ($data as $kode => $contoh) {
    // Model expects an array for contoh_lapangan
    $record = PgKBLI2025::where('kode', $kode)->first();
    if ($record) {
        $record->contoh_lapangan = [$contoh];
        // Set embedding to null so it gets regenerated
        $record->embedding = null; 
        $record->save();
        echo "Updated $kode\n";
        $count++;
    } else {
        echo "Record not found for $kode\n";
    }
}

echo "Total updated: $count\n";


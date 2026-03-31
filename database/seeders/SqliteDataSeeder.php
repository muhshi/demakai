<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SqliteDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // KBLI 2025 Dummy Data
        DB::table('kbli2025s')->insert([
            [
                'kode' => '01111',
                'judul' => 'Pertanian Jagung',
                'deskripsi' => 'Mencakup usaha pertanian jagung mulai dari penyiapan lahan, penanaman, pemeliharaan, hingga pemanenan.',
                'contoh_lapangan' => json_encode(['Budidaya Jagung Hibrida', 'Perkebunan Jagung Manis']),
                'level' => '5',
                'sumber' => 'KBLI 2025'
            ],
            [
                'kode' => '62019',
                'judul' => 'Aktivitas Pemrograman Komputer Lainnya',
                'deskripsi' => 'Mencakup kegiatan pemrograman komputer lainnya yang belum diklasifikasikan di tempat lain.',
                'contoh_lapangan' => json_encode(['Jasa Koding Website', 'Pembuatan Script Otomatisasi']),
                'level' => '5',
                'sumber' => 'KBLI 2025'
            ],
            [
                'kode' => '47111',
                'judul' => 'Perdagangan Eceran Berbagai Macam Barang Yang Utamanya Makanan, Minuman Atau Tembakau Di Supermarket/Minimarket',
                'deskripsi' => 'Mencakup usaha perdagangan eceran berbagai macam barang kebutuhan sehari-hari.',
                'contoh_lapangan' => json_encode(['Minimarket', 'Supermarket', 'Toko Kelontong Modern']),
                'level' => '5',
                'sumber' => 'KBLI 2025'
            ]
        ]);

        // KBLI 2020 Dummy Data
        DB::table('kbli2020s')->insert([
            [
                'kode' => '01111',
                'judul' => 'Pertanian Jagung (2020)',
                'deskripsi' => 'Deskripsi KBLI 2020 untuk pertanian jagung.',
                'contoh_lapangan' => json_encode(['Tanam Jagung']),
                'level' => '5',
            ]
        ]);

        // KBJI 2014 Dummy Data
        DB::table('kbji2014s')->insert([
            [
                'kode' => '2512',
                'judul' => 'Pengembang Perangkat Lunak',
                'deskripsi' => 'Profesional yang mengembangkan, menciptakan, dan memodifikasi aplikasi perangkat lunak komputer umum atau program utilitas khusus.',
                'contoh_lapangan' => json_encode(['Programmer Java', 'Web Developer', 'Software Engineer']),
                'level' => '4',
            ],
            [
                'kode' => '6111',
                'judul' => 'Petani Tanaman Pangan',
                'deskripsi' => 'Petani yang menanam dan memanen jenis tanaman pangan utama.',
                'contoh_lapangan' => json_encode(['Petani Padi', 'Petani Jagung']),
                'level' => '4',
            ]
        ]);
    }
}

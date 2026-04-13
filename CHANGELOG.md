# Changelog Project DEMAKAI & Survey BPS

Riwayat perubahan dan milestone utama dalam pengembangan platform portal BPS dan sistem pencarian DEMAKAI.

---

## [2026-04-13] - Integrasi Query Expansion & Evaluasi Komparatif
### Added
- **Modul `preprocessing/expansion.py`**: Pipeline preprocessing baru (Metode 3) yang menggabungkan stopword removal, sinonim dasar, dan VARIATION_MAP berbasis konteks industri/pekerjaan.
- **Modul `preprocessing/variation_map.py`**: Kamus variasi kontekstual (`VARIATION_MAP`) yang memetakan kata kunci informal ke variasi KBLI dan KBJI formal, mendukung fleksibel matching berbasis token.
- **Mode Pencarian Baru di Web**: Penambahan opsi **"SQL Expansion"** dan **"Hybrid Expansion"** pada antarmuka `welcome.blade.php` sehingga pengguna dapat langsung menguji pipeline ekspansi kueri.
- **`search/utils.py`**: Utilitas bersama untuk modul SQL dan Hybrid search.
- **Toggle Expansion via ENV**: Penambahan flag `ENABLE_EXPANSION` pada environment variable untuk menonaktifkan ekspansi saat evaluasi baseline.

### Changed
- **Arsitektur Pipeline**: Refactor `main.py` menjadi 6 kombinasi pencarian (SQL/Hybrid × None/Expansion/Advanced), mendukung argumen `--all` untuk menjalankan semua kombinasi sekaligus.
- **`SearchController.php`**: Pembaruan routing API untuk mendukung mode `sql_expansion` dan `hybrid_expansion` dari frontend web.
- **Evaluasi Komparatif**: Pengujian sistematis sebelum (`before_snippet.txt`) dan sesudah (`after_snippet.txt`) aktivasi ekspansi menggunakan 60 query orisinal + 40 query deskriptif untuk menilai dampak VARIATION_MAP terhadap metrik MRR dan Top-N.

### Fixed
- Penanganan kasus query kosong setelah stopword removal pada `preprocess_expansion`.
- Sinkronisasi token KBLI/KBJI variations agar kompatibel dengan engine SQL LIKE dan Vector Search.

---

## [2026-04-11] - Optimasi Pencarian & Ekspansi Dataset
### Added
- **Fitur Saran Contoh Lapangan**: Pengguna kini dapat mengajukan contoh lapangan atau sinonim baru untuk meningkatkan akurasi pencarian di masa depan.
- **Pencatatan Riwayat Pencarian**: Implementasi logging kueri ke tabel `search_histories` untuk analisis tren pencarian dan kualitas hasil.
- **Dataset Expansion**: Penambahan 40 data "Contoh Lapangan" baru untuk memperkuat deteksi pada kueri berupa kalimat deskripsi panjang.
- **Sistem Deployment**: Penambahan skrip `deploy.sh` dan konfigurasi Docker untuk standarisasi update layanan Python API dan pembentukan aset frontend.

### Changed
- **Laporan Evaluasi Baru**: Pembaruan format laporan `evaluate.py` dengan metrik `top-5` dan `KK` (Kesesuaian Kode) sesuai standar kebutuhan analisis pengguna.
- **Evaluasi Skala Besar**: Pengujian akurasi kini menggunakan dataset gabungan 100 kueri (60 kueri asli + 40 kueri deskriptif) untuk validasi performa sistem yang lebih komprehensif.
- **Pembaruan Model AI**: Update ke model embedding terbaru untuk meningkatkan pemahaman semantik bahasa Indonesia yang lebih kontekstual.
- **Simplifikasi Arsitektur**: Migrasi penuh panel admin ke PostgreSQL untuk konsolidasi data dan sinkronisasi yang lebih efisien dengan engine pencarian.

---

## [2026-04-08] - Evaluasi & Finalisasi Pencarian Hybrid
### Added
- Pembuatan dataset evaluasi baru dari transkripsi Gambar 1 (60 baris data KBLI & KBJI).
- Penambahan fitur laporan evaluasi performa pencarian dalam format **HTML Gabungan** dan **Excel**.
- Implementasi metrik evaluasi: Rank (Posisi), Top-1, Top-3, Top-10, dan **MRR (Mean Reciprocal Rank)**.
- Perbaikan `AppKeyException` pada Laravel (konfigurasi `.env`).

### Changed
- Optimasi skrip `evaluate.py` untuk mendukung file kueri kustom.
- Sinkronisasi data antara SQLite (Legacy) ke PostgreSQL (Vector Enabled).

---

## [2026-04-07] - Integrasi Autentikasi & Analitik Survei
### Added
- Integrasi sistem autentikasi pada Landing Page (Login & Register).
- Implementasi **Dashboard Analitik** untuk hasil survei menggunakan library `Survey Analytics`.
- Fitur validasi skema JSON pada SurveyJS (Skip-logic, constraints).

### Fixed
- Perbaikan visibilitas SurveyJS Creator pada panel admin Filament.
- Perbaikan error runtime pada halaman analytics.

---

## [2026-04-06] - Pengembangan SurveyJS Management
### Added
- Implementasi **SurveyJS Creator (Visual Form Builder)** ke dalam Filament Admin.
- Custom styling antarmuka SurveyJS agar seragam dengan desain V2.
- Definisi tabel database untuk manajemen survei dinamis (Survei berbasis JSON).
- Penambahan komponen Blade khusus untuk merender editor kuesioner.

---

## [2026-04-03] - Migrasi Menuju SurveyJS
### Added
- Keputusan strategis migrasi dari skema database relasional kaku ke **format JSON SurveyJS** untuk kuesioner.
- Persiapan infrastruktur backend untuk menyimpan skema formulir dinamis.

---

## [2026-04-01] - Landing Page & Branding
### Added
- Pengembangan Landing Page utama Portal BPS Kabupaten Demak.
- Implementasi desain responsif untuk kartu aplikasi dan navigasi.
- Pengaturan logo horizontal BPS di navbar dan hero section.

### Fixed
- Perbaikan error `Vite manifest not found` pada deployment awal.

---

## [Maret 2026] - Inisiasi Proyek & Sistem Pencarian (DEMAKAI)
### Added
- Inisiasi modul pencarian **DEMAKAI** (Deteksi Klasifikasi Pekerjaan dan Industri).
- Implementasi **Hybrid Search**: Kombinasi SQL LIKE (Keywords) dan Vector Search (Semantik).
- Penyiapan database PostgreSQL dengan ekstensi `pgvector`.
- Pembuatan modul Python `api.py` untuk layanan embedding menggunakan Gemini API.
- Proses ekstraksi dan pembersihan data KBLI 2025 dan KBJI 2014.

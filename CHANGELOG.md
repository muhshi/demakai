# Changelog Project DEMAKAI & Survey BPS

Riwayat perubahan dan milestone utama dalam pengembangan platform portal BPS dan sistem pencarian DEMAKAI.

---

## [2026-04-30] - Redesain Universal Detail Modal & Integrasi Data KUWI

### Added
- **Data Integration — Direktori KUWI KBLI**:
  - Ekstraksi otomatis data dari PDF **Direktori KUWI KBLI** menggunakan `pdfplumber`.
  - Pemetaan kolom *Penjelasan Kegiatan Utama* dan *Rekomendasi Penulisan Kegiatan Utama* ke dalam field `contoh_lapangan`.
  - Update massal pada database PostgreSQL untuk **112 kode KBLI 2020** dan **159 kode KBLI 2025**.
- **Universal Detail Modal V2**:
  - Implementasi layout **dua kolom** (Grid Layout) yang responsif untuk menampung informasi lebih banyak.
  - Fitur **Copy-to-Clipboard** pada Kode KBLI/KBJI, Judul, dan setiap item Contoh Lapangan dengan *feedback* visual (centang hijau).
  - Penyelarasan vertikal kolom Contoh Lapangan dengan Deskripsi Lengkap untuk estetika UI yang simetris.
- **Interactive Search Cards**:
  - Fitur **Inline Expansion** pada kartu hasil pencarian di landing page.
  - Limitasi tampilan 2 contoh lapangan pertama dengan tombol **"+X lainnya"** untuk menjaga kerapian landing page.
  - Mekanisme **"Sembunyikan"** untuk merapatkan kembali daftar contoh tanpa perlu me-refresh halaman.

### Changed
- **UX Optimization**: Seluruh area kartu hasil pencarian kini *full clickable* untuk membuka modal detail (dengan pengecualian tombol kontrol internal menggunakan `stopPropagation`).
- **Layout Management**: Penggunaan `display: contents` pada CSS untuk menjaga konsistensi *flex-gap* dan *wrapping* pada daftar tag yang diekspansi.
- **Accessibility**: Penambahan dukungan navigasi keyboard (Enter/Space) dan penutupan modal via tombol `ESC` atau klik luar area.

### Fixed
- Perbaikan masalah tumpang tindih (*overlapping*) pada daftar tag contoh lapangan saat dalam mode ekspansi di kartu hasil pencarian.

---

## [2026-04-27] - Perluasan Framework Evaluasi 10 Metode & Pemisahan Preprocessing

### Added
- **`evaluate_preprocessing_comparison.py` — 10 Metode Lengkap**: Perluasan framework evaluasi dari 6 metode menjadi **10 metode** dengan pemisahan eksplisit antara *Advanced Preprocessing* dan *Query Expansion* serta variasi dengan/tanpa *Contoh Lapangan* (CL):
  | No | Metode | Komponen |
  |----|--------|----------|
  | M1  | SQL LIKE Baseline | — |
  | M2  | SQL LIKE + Advanced Preprocessing | PREP |
  | M3  | SQL LIKE + Advanced + Contoh Lapangan | PREP + CL |
  | M4  | SQL LIKE + Preprocessing + Expansion | PREP + EXP |
  | M5  | SQL LIKE + Preprocessing + Expansion + CL | PREP + EXP + CL |
  | M6  | Hybrid Search Baseline | — |
  | M7  | Hybrid + Advanced Preprocessing | PREP |
  | M8  | Hybrid + Advanced + Contoh Lapangan | PREP + CL |
  | M9  | Hybrid + Preprocessing + Expansion | PREP + EXP |
  | M10 | Hybrid + Preprocessing + Expansion + CL | PREP + EXP + CL |

- **Dashboard HTML Komprehensif (`evaluasi_prep_comparison_<ts>.html`)**: Dashboard evaluasi otomatis dengan:
  - Section per metode (badge PREP / EXP / CL / BASE)
  - Summary cards (Top@1, Top@3, Top@10, MRR) per dataset (KBLI, KBJI, Gabungan)
  - Tabel detail per query: No, Query, Preprocessing, Contoh Lapangan, Rank, Top@1, Top@3, Top@10, RR
  - Tabel perbandingan global semua 10 metode (KBLI vs KBJI vs Gabungan)
  - Tabel dampak delta (↑/↓) antar tahap preprocessing
  - Analisis naratif otomatis per komponen

### Results — Evaluasi 60 Query (30 KBLI + 30 KBJI)

| Metode | Top@1 | Top@3 | Top@10 | MRR |
|--------|-------|-------|--------|-----|
| M1 — SQL LIKE Baseline | 5.0% | 11.7% | 21.7% | 0.0928 |
| M2 — SQL + PREP | 8.3% | 16.7% | 23.3% | 0.1326 |
| M3 — SQL + PREP + CL | 66.7% | 78.3% | 83.3% | 0.7292 |
| M4 — SQL + PREP + EXP | 13.3% | 20.0% | 28.3% | 0.1789 |
| **M5 — SQL + PREP + EXP + CL** | **78.3%** | **88.3%** | **93.3%** | **0.8378** |
| M6 — Hybrid Baseline | 6.7% | 13.3% | 16.7% | 0.1011 |
| M7 — Hybrid + PREP | 11.7% | 21.7% | 25.0% | 0.1633 |
| M8 — Hybrid + PREP + CL | 78.3% | 85.0% | 85.0% | 0.8139 |
| M9 — Hybrid + PREP + EXP | 20.0% | 25.0% | 25.0% | 0.2222 |
| M10 — Hybrid + PREP + EXP + CL | 75.0% | 78.3% | 78.3% | 0.7667 |

**Temuan utama**:
- *Contoh Lapangan* adalah komponen paling berpengaruh: menaikkan MRR SQL dari 0.1789 → **0.8378** (+0.659) dan Hybrid dari 0.1633 → **0.8139** (+0.651).
- *Query Expansion* memberikan peningkatan moderat tanpa CL (MRR +0.046 untuk SQL, +0.059 untuk Hybrid) namun sedikit lebih rendah dibanding versi Expansion+CL vs Advanced+CL.
- SQL LIKE + FULL (M5) unggul dari Hybrid FULL (M10) pada MRR (0.8378 vs 0.7667), menunjukkan bahwa *Query Expansion* lebih efektif pada engine SQL LIKE untuk dataset ini.
- Hybrid Baseline (M6) mengalahkan SQL Baseline (M1) hanya secara tipis (MRR 0.1011 vs 0.0928), menandakan semantic search butuh preprocessing untuk memberi nilai tambah signifikan.

### Output Files
- `output/evaluasi_prep_comparison_<ts>.html` — Dashboard HTML interaktif 10 metode
- `output/evaluasi_prep_comparison_<ts>.csv` — Data mentah seluruh evaluasi (600 baris)

---

## [2026-04-21] - Optimasi Arsitektur Search & Finalisasi Dashboard Skripsi

### Added
- **Dashboard Skripsi Final (`dashboard_skripsi_final.html`)**: Pembuatan dashboard evaluasi komprehensif versi final (bersih tanpa label "sbm fix" atau "bug") otomatis via `gen_dashboard_skripsi.py` untuk dilampirkan dalam pelaporan skripsi.
- Menampilkan 6 tahap evolusi metode evaluasi (Top@1 hingga MRR) pada 60 query (30 KBLI + 30 KBJI):
  1. SQL LIKE (Baseline)
  2. Hybrid Search
  3. SQL LIKE + Preprocessing
  4. Hybrid Search + Preprocessing
  5. SQL LIKE + Preprocessing + Contoh Lapangan
  6. Hybrid Search + Preprocessing + Contoh Lapangan (Terbaik)

### Changed
- **`hybrid.py` — Proporsional Boosting**: Mengubah mekanisme *hardcoded distance override* dengan perhitungan *proportional percentage boost* berdasarkan keberadaan token relevan, mencegah data *noise* (yang tidak relevan) naik ke rank teratas.
- **`hybrid.py` — Penggunaan Query Asli untuk Semantik**: Embedding untuk Gemini kini memproses query *asli* dari pengguna (bukan versi *preprocessed* yang sudah kaku), sehingga pencarian semantik berjalan dengan keselarasan konteks natural yang maksimal.
- **`hybrid.py` — Limitasi Expanded Tokens**: Proses boosting hanya berpatokan pada *core tokens* saja, mengeliminasi pelebaran makna frasa yang menyebabkan *false-positives*.
- **`sql_like.py` — Scoring Bertingkat**: OR Fallback sekarang menggunakan bobot spesifik (Judul = 3, Deskripsi = 2, Contoh Lapangan = 1) untuk mempertajam prioritas ranking apabila pencarian pasti *exact* gagal.
- **`sql_like.py` — Strict OR Fallback**: Mencegah tahap *OR matching* mencemari hasil utama dengan mengeksekusinya HANYA sebagai *last resort* (jika dua langkah sebelumnya = 0 hasil).

### Fixed
- **Anomali Performa Turun**: Penyelesaian *bug* logika komputasi *distance* yang sebelumnya membuat kombinasi metode dengan *Contoh Lapangan* stagnan dan bahkan turun performanya. Semua kombinasi metode Hybrid kini menunjukkan lonjakan peningkatan performa yang positif dan kompetitif (Metode FINAL MRR mencapai 0.9278 untuk KBLI).

---

## [2026-04-20] - Evaluasi Lengkap Pipeline Pencarian DEMAKAI

### Added

- **`evaluate_preprocessing_comparison.py`**: Script evaluasi perbandingan dua metode sekaligus dalam satu HTML dashboard:
  - **Metode A — SQL LIKE + Preprocessing**: Query → `preprocess_expansion()` → `sql_like.search_expansion()`
  - **Metode B — Hybrid + Preprocessing**: Query → `preprocess_expansion()` → `hybrid.search_expansion()`
  - Menghasilkan satu file HTML dengan dua bagian (tema ungu & cyan), tabel perbandingan side-by-side, summary cards, dan analisis otomatis.

- **`evaluate_contoh_lapangan.py`**: Script evaluasi tahap akhir **Hybrid + Preprocessing + Contoh Lapangan**.
  - Pipeline: Query → Preprocessing (Expansion) → Hybrid Search (SQL LIKE + Semantic) → Ranking berbasis boost pada field `contoh_lapangan`.
  - Menampilkan kolom "Contoh Lapangan" (data real dari DB) untuk setiap query.
  - Membuktikan bahwa hampir seluruh query yang berhasil mendapat boost dari field `contoh_lapangan` (`boost=contoh_lapangan`).
  - Dashboard HTML dilengkapi: definisi contoh lapangan, pipeline diagram, kartu perbandingan delta vs tahap sebelumnya, dan analisis tiga aspek.

- **`evaluate_sql_contoh_lapangan.py`**: Script evaluasi **SQL LIKE + Preprocessing + Contoh Lapangan**.
  - Pipeline: Query → `preprocess_expansion()` → `sql_like.search_expansion()` (mencari pada `deskripsi` + `contoh_lapangan`).
  - Tidak menggunakan semantic search maupun hybrid.
  - Dashboard HTML dengan tema ungu, menampilkan kolom preprocessing dan contoh lapangan terpisah per KBLI dan KBJI.

### Changed

- **`evaluate_preprocessing_comparison.py` — Fix Preprocessing Desc**: Fungsi `get_preprocessed_desc()` diubah agar menampilkan variasi (`+KBLI:` atau `+KBJI:`) **sesuai tipe query**, tidak lagi menggabungkan keduanya dalam satu kolom. Query KBLI hanya menampilkan variasi KBLI, dan sebaliknya untuk KBJI.

### Results (Metrik Evaluasi — 60 Query, 30 KBLI + 30 KBJI)

| Metode | MRR KBLI | MRR KBJI | MRR Gabungan | Top@1 | Top@10 |
|--------|----------|----------|--------------|-------|--------|
| Baseline SQL LIKE (tanpa apapun) | 0.0433 | 0.0000 | 0.0217 | 0.0% | 8.3% |
| Hybrid + Preprocessing | 0.8500 | 0.6944 | 0.7722 | 71.7% | 83.3% |
| **SQL LIKE + Preprocessing** | **0.8944** | **0.7811** | **0.8378** | **78.3%** | **93.3%** |
| Hybrid + Prep + Contoh Lapangan | 0.8500 | 0.6944 | 0.7722 | 71.7% | 83.3% |
| SQL LIKE + Prep + Contoh Lapangan | 0.8944 | 0.7811 | 0.8378 | 78.3% | 93.3% |

**Temuan utama**: SQL LIKE + Preprocessing mengungguli Hybrid + Preprocessing (MRR 0.8378 vs 0.7722). Query Expansion (sinonim + variasi KBLI/KBJI) terbukti sangat efektif untuk SQL LIKE. Field `contoh_lapangan` sudah digunakan di kedua metode (sudah ter-embed di `_keyword_search` dan `_merge_and_boost`), sehingga hasil identik dengan versi tanpa label "Contoh Lapangan".

### Output Files

- `output/prep_comparison_*.html` — Dashboard perbandingan SQL LIKE vs Hybrid + Preprocessing
- `output/contoh_lapangan_*.html` — Dashboard Hybrid + Preprocessing + Contoh Lapangan
- `output/sql_contoh_*.html` — Dashboard SQL LIKE + Preprocessing + Contoh Lapangan
- `output/prep_sql_*.csv`, `output/prep_hybrid_*.csv`, `output/contoh_lapangan_*.csv`, `output/sql_contoh_*.csv`

---

## [2026-04-14] - Major Framework & Platform Upgrade (Laravel 13 & Filament 5)
### Added
- **Laravel 13 Support**: Migrasi penuh ke Laravel v13.4.0 yang mendukung fitur-fitur PHP 8.3+ dan arsitektur core terbaru.
- **Filament v5 Support**: Upgrade panel admin ke Filament v5.5.0 yang terintegrasi dengan Livewire v4 "Islands".
- **PHP 8.4 compatibility**: Penyesuaian environment agar optimal berjalan di PHP 8.4.19.
- **Tailwind CSS v4**: Penggunaan sistem styling terbaru dari Tailwind yang menjadi standar di Filament v5.
- **Survey BPS Stack Audit**: Verifikasi teknis stack pada project Survey: Laravel v13.2.0, Filament v5.4.3, Livewire v4.2.3, dan PHP v8.4.19.
- **Dependency Audit**: Audit versi tools pendukung project Survey: Laravel Boost v2.4.1, Pest v4.4.3, Laravel Pail v1.2.6, dan Laravel Pint v1.29.0.

### Changed
- **Refactoring Resource API**: Migrasi seluruh resource Filament (Document, KBLI, KBJI) dari `Filament\Forms\Form` ke API baru `Filament\Schemas\Schema`.
- **Database Driver Update**: Upgrade `mongodb/laravel-mongodb` ke versi 5.2+ untuk mendukung Laravel 13 dan memperbaiki struktur model MongoDB (`$collection` → `$table`).
- **Dependency Refresh**: Pembaruan major pada `phpunit/phpunit` (^12.0), `laravel/tinker` (^3.0), dan `openai-php/client` (^0.19.0).
- **Asset Publishing**: Re-publishing seluruh aset frontend Filament untuk memastikan kompatibilitas dengan Livewire v4.

### Fixed
- **Breaking Changes Mitigation**: Perbaikan manual pada `DocumentResource.php` terkait `BadgeColumn` dan method deprecated pada `Repeater` (`addable`/`deletable`).
- **Autoload Synchronization**: Perbaikan error autoloader setelah penghapusan tool migrasi dan sinkronisasi vendor metadata.

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

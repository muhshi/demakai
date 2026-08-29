# Panduan Integrasi Mobile Flutter — PINTAR KBLI (Sensus Ekonomi 2026)

Dokumen ini adalah spesifikasi teknis dan panduan integrasi resmi untuk Tim Pengembang Mobile Flutter. Sistem didesain dengan **Arsitektur Hybrid (Online Cepat & Cerdas + Full Offline On-Device)** untuk menjamin ketersediaan 100% saat petugas sensus berada di wilayah tanpa sinyal (blind spot).

---

## 1. Arsitektur Komunikasi Sistem

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            APLIKASI FLUTTER MOBILE                          │
│                                                                             │
│  [ UI Search / Hierarchy ] ─────────► [ Connectivity & Sync Manager ]       │
│                                                     │                       │
│                       ┌─────────────────────────────┴──────────┐            │
│                       ▼                                        ▼            │
│              [ MODE ONLINE ]                            [ MODE OFFLINE ]    │
│           (Ada 4G / 5G / WiFi)                      (Di Pelosok / No Signal)│
│                       │                                        │            │
│               [ Dio REST Client ]                     [ Local SQLite FTS5 ] │
└───────────────────────┬────────────────────────────────────────┬────────────┘
                        │                                        │
                        │ HTTPS (REST API JSON)                  │ Local File I/O
                        ▼                                        ▼
┌──────────────────────────────────────────────┐ ┌────────────────────────────┐
│          BACKEND WEB PINTAR KBLI             │ │   SQLite Database Bundle   │
│  • GET  /api/v1/search                       │ │ • kbli_offline_latest.db   │
│  • GET  /api/v1/kbli/hierarchy               │ │ • FTS5 Virtual Tables      │
│  • GET  /api/v1/sync/check & /sync/bundle    │ │ • 1.569 KBLI + 2.735 KBJI  │
│  • POST /api/v1/submissions (Single/Bulk)    │ │ • Pre-computed Embeddings  │
└──────────────────────────────────────────────┘ └────────────────────────────┘
```

---

## 2. Spesifikasi Endpoint REST API (Online Mode)

- **Production Base URL (Live Server):** `https://demakai.bpsdemak.com/api/v1`
- **Local Development Base URL:** `http://127.0.0.1:8000/api/v1` *(atau `http://10.0.2.2:8000/api/v1` untuk Android Emulator)*

---

### A. Pencarian Cepat (`GET /search`)
Melakukan pencarian cerdas pada master KBLI 2025 dan KBJI 2014.

- **Method:** `GET`
- **Path:** `/api/v1/search`
- **Query Parameters:**
  - `q` *(string, required)*: Kata kunci atau kalimat kegiatan usaha (contoh: `padi`, `bengkel motor`, `warung soto`).
  - `type` *(string, optional)*: Filter tipe `KBLI`, `KBJI`, atau kosongkan untuk mencari keduanya.
  - `limit` *(integer, optional)*: Jumlah hasil (default `15`, max `50`).
- **cURL Contoh (Production):**
  ```bash
  curl --location 'https://demakai.bpsdemak.com/api/v1/search?q=padi&limit=5'
  ```
- **Response Format (`200 OK`):**
  ```json
  {
    "status": "success",
    "data": {
      "query": "padi",
      "results": [
        {
          "type": "KBLI 2025",
          "kode": "01121",
          "judul": "PERTANIAN PADI HIBRIDA",
          "deskripsi": "Kelompok ini mencakup kegiatan pertanian padi hibrida, termasuk di dalamnya kegiatan pengolahan lahan...",
          "contoh_lapangan": [
            "PERTANIAN PADI HIBRIDA - mencabut rumput liar di sawah",
            "PERTANIAN PADI HIBRIDA - menanam benih padi di ladang basah"
          ],
          "score": 95,
          "match_type": "keyword",
          "is_equivalent": false
        }
      ],
      "total": 5
    },
    "meta": {
      "version": "2026.08.1",
      "search_method": "hybrid",
      "timestamp": "2026-08-26T07:58:33+00:00"
    }
  }
  ```

---

### B. Eksplorasi Hierarki KBLI (`GET /kbli/hierarchy`)
Mengambil struktur pohon klasifikasi KBLI dari Kategori (A–U) hingga Kelompok (5 digit).

- **Method:** `GET`
- **Path:** `/api/v1/kbli/hierarchy`
- **Query Parameters:**
  - `parent` *(string, optional)*: Kosongkan untuk Kategori level atas (A–U), atau isi kode parent (misal `01`, `011`, `0112`).
- **Contoh Request:**
  - Daftar Kategori: `GET /api/v1/kbli/hierarchy`
  - Anak Golongan dari Kategori A: `GET /api/v1/kbli/hierarchy?parent=A`
  - Subgolongan dari Golongan Pokok 01: `GET /api/v1/kbli/hierarchy?parent=01`
- **Response Format (`200 OK`):**
  ```json
  {
    "status": "success",
    "data": [
      {
        "kode": "A",
        "judul": "Pertanian, Kehutanan Dan Perikanan",
        "deskripsi": "Kategori ini mencakup pemanfaatan sumber daya hayati...",
        "level": "kategori",
        "is_leaf": false
      },
      {
        "kode": "B",
        "judul": "Pertambangan Dan Penggalian",
        "deskripsi": "...",
        "level": "kategori",
        "is_leaf": false
      }
    ]
  }
  ```

---

### C. Pengecekan & Unduhan Database Offline (`GET /sync/*`)

#### 1. Cek Versi Terbaru
- **Method:** `GET`
- **Path:** `/api/v1/sync/check`
- **cURL Contoh (Production):**
  ```bash
  curl --location 'https://demakai.bpsdemak.com/api/v1/sync/check'
  ```
- **Response (`200 OK`):**
  ```json
  {
    "status": "success",
    "data": {
      "status": "ready",
      "version": "2026.08.26",
      "generated_at": "2026-08-26T08:28:11+00:00",
      "kbli_count": 1569,
      "kbji_count": 2735,
      "raw_file_size_bytes": 23592960,
      "raw_file_size_mb": 22.5,
      "file_size_bytes": 14916415,
      "file_size_mb": 14.23,
      "md5": "79e088f4cda94d148184c3bcc391c98d",
      "sha256": "b061733116efdedcf9620b83c56ff4a18281f95a00e4248b4be084bf32530236",
      "download_url": "https://demakai.bpsdemak.com/api/v1/sync/bundle",
      "model_download_url": "https://demakai.bpsdemak.com/api/v1/sync/model"
    }
  }
  ```

#### 2. Download File Bundle
- **Method:** `GET`
- **Path:** `/api/v1/sync/bundle`
- **Response:** Binary Stream (`application/gzip` atau `application/x-sqlite3`).

---

### D. Pengajuan Crowdsourcing Lapangan (`POST /submissions/*`)

#### 1. Pengajuan Tunggal (Single Online Submission)
- **Method:** `POST`
- **Path:** `/api/v1/submissions`
- **Headers:** `Content-Type: application/json`, `Accept: application/json`
- **Body JSON:**
  ```json
  {
    "type": "KBLI",
    "kode": "01121",
    "content": "Petani yang menanam padi hibrida bernas prima di sawah irigasi",
    "submitter_name": "Budi Santoso",
    "device_id": "samsung-a54-uuid-123"
  }
  ```
- **Response (`201 Created`):**
  ```json
  {
    "status": "success",
    "message": "Terima kasih! Pengajuan contoh lapangan Anda berhasil dikirim dan akan diverifikasi.",
    "data": {
      "id": 16,
      "kode": "01121",
      "status": "pending"
    }
  }
  ```

#### 2. Sinkronisasi Massal Hasil Catatan Offline (Bulk Sync)
- **Method:** `POST`
- **Path:** `/api/v1/submissions/bulk-sync`
- **Headers:** `Content-Type: application/json`, `Accept: application/json`
- **Body JSON:**
  ```json
  {
    "submissions": [
      {
        "type": "KBLI",
        "kode": "01111",
        "content": "Petani jagung manis pipil",
        "local_created_at": "2026-08-26T10:15:30Z",
        "device_id": "samsung-a54-uuid-123"
      },
      {
        "type": "KBJI",
        "kode": "6111",
        "content": "Buruh pemotong tebu saat panen raya",
        "local_created_at": "2026-08-26T11:20:00Z",
        "device_id": "samsung-a54-uuid-123"
      }
    ]
  }
  ```
- **Response (`200 OK`):**
  ```json
  {
    "status": "success",
    "message": "Berhasil menyinkronkan 2 catatan lapangan offline.",
    "data": {
      "synced_count": 2
    }
  }
  ```

---

## 3. Struktur Database SQLite Offline (On-Device)

File bundle SQLite (`kbli_offline_latest.db`) memiliki struktur tabel berikut:

### 1. Tabel Master & Full-Text Search FTS5
- `kbli2025` *(id, kode, judul, deskripsi, kategori, contoh_lapangan, embedding)*
- `kbji2014` *(id, kode, judul, deskripsi, contoh_lapangan, embedding)*
- `kbli_fts` *(rowid, kode, judul, deskripsi, contoh_lapangan)* — **Virtual Table FTS5**
- `kbji_fts` *(rowid, kode, judul, deskripsi, contoh_lapangan)* — **Virtual Table FTS5**
- `meta` *(key, value)* — menyimpan informasi versi bundle dan tanggal generate.

---

## 4. Contoh Implementasi di Flutter (Dart)

### A. Dependencies (`pubspec.yaml`)
```yaml
dependencies:
  flutter:
    sdk: flutter
  dio: ^5.7.0
  sqflite: ^2.4.1
  path_provider: ^2.1.5
  path: ^1.9.0
  connectivity_plus: ^6.1.0
  archive: ^4.0.2
  shared_preferences: ^2.3.2
```

---

### B. Data Model (`lib/models/kbli_item.dart`)
```dart
class KbliItem {
  final String type;
  final String kode;
  final String judul;
  final String deskripsi;
  final List<String> contohLapangan;
  final int score;
  final String matchType;

  KbliItem({
    required this.type,
    required this.kode,
    required this.judul,
    required this.deskripsi,
    required this.contohLapangan,
    required this.score,
    required this.matchType,
  });

  factory KbliItem.fromJson(Map<String, dynamic> json) {
    var rawContoh = json['contoh_lapangan'];
    List<String> listContoh = [];
    if (rawContoh is List) {
      listContoh = rawContoh.map((e) => e.toString()).toList();
    }

    return KbliItem(
      type: json['type'] ?? 'KBLI 2025',
      kode: json['kode']?.toString() ?? '',
      judul: json['judul']?.toString() ?? '',
      deskripsi: json['deskripsi']?.toString() ?? '',
      contohLapangan: listContoh,
      score: (json['score'] is num) ? (json['score'] as num).toInt() : 0,
      matchType: json['match_type'] ?? 'exact',
    );
  }
}
```

---

### C. Local Database Service (SQLite FTS5) (`lib/services/local_db_service.dart`)
```dart
import 'dart:io';
import 'package:flutter/services.dart';
import 'package:path/path.dart';
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';
import '../models/kbli_item.dart';

class LocalDbService {
  static Database? _database;

  static Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDb();
    return _database!;
  }

  static Future<Database> _initDb() async {
    final docsDir = await getApplicationDocumentsDirectory();
    final dbPath = join(docsDir.path, 'kbli_offline.db');

    if (!await File(dbPath).exists()) {
      // Inisialisasi awal dari asset bawaan aplikasi jika ada
      try {
        final byteData = await rootBundle.load('assets/database/kbli_offline.db');
        final bytes = byteData.buffer.asUint8List(byteData.offsetInBytes, byteData.lengthInBytes);
        await File(dbPath).writeAsBytes(bytes, flush: true);
      } catch (e) {
        // Fallback jika file asset belum dibundle
      }
    }

    return await openDatabase(dbPath, readOnly: true);
  }

  /// Pencarian Cepat Offline menggunakan SQLite FTS5
  static Future<List<KbliItem>> searchFts(String query, {int limit = 15}) async {
    final cleanQuery = query.replaceAll(RegExp(r'[^a-zA-Z0-9\s]'), '').trim();
    if (cleanQuery.isEmpty) return [];

    final db = await database;
    final terms = cleanQuery.split(RegExp(r'\s+')).map((t) => '$t*').join(' ');

    final rows = await db.rawQuery('''
      SELECT k.kode, k.judul, k.deskripsi, k.contoh_lapangan, 'KBLI 2025' as type,
             bm25(kbli_fts) as rank
      FROM kbli_fts f
      JOIN kbli2025 k ON f.rowid = k.id
      WHERE kbli_fts MATCH ?
      ORDER BY rank ASC
      LIMIT ?
    ''', [terms, limit]);

    return rows.map((r) => KbliItem(
      type: 'KBLI 2025',
      kode: r['kode'].toString(),
      judul: r['judul'].toString(),
      deskripsi: r['deskripsi']?.toString() ?? '',
      contohLapangan: [],
      score: 80,
      matchType: 'offline_fts',
    )).toList();
  }
}
```

---

### D. Repository Pintar (Auto Switch Online / Offline) (`lib/repositories/kbli_repository.dart`)
```dart
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:dio/dio.dart';
import '../models/kbli_item.dart';
import '../services/local_db_service.dart';

class KbliRepository {
  final Dio _dio = Dio(BaseOptions(
    baseUrl: 'https://demakai.bpsdemak.com/api/v1', // URL Server Production
    connectTimeout: const Duration(seconds: 4),
    receiveTimeout: const Duration(seconds: 4),
  ));

  Future<List<KbliItem>> search(String query) async {
    final connectivity = await Connectivity().checkConnectivity();
    final isOnline = connectivity.contains(ConnectivityResult.mobile) ||
                     connectivity.contains(ConnectivityResult.wifi);

    if (isOnline) {
      try {
        final res = await _dio.get('/search', queryParameters: {'q': query});
        if (res.statusCode == 200 && res.data['status'] == 'success') {
          final List rawList = res.data['data']['results'] ?? [];
          return rawList.map((e) => KbliItem.fromJson(e)).toList();
        }
      } catch (e) {
        // Jika server timeout/gagal, otomatis fallback ke SQLite lokal HP
      }
    }

    // Eksekusi pencarian offline di HP
    return await LocalDbService.searchFts(query);
  }
}
```

---

## 5. Checklist Validasi untuk Tim Flutter

- [x] Endpoint Online Search `/api/v1/search` teruji dengan response format terstandarisasi.
- [x] Endpoint Hierarki `/api/v1/kbli/hierarchy` teruji untuk navigasi drill-down.
- [x] Endpoint Sync Check `/api/v1/sync/check` teruji memberikan hash MD5 & link download bundle.
- [x] Endpoint Bundle Download `/api/v1/sync/bundle` siap menyajikan file terkompresi `.db.gz`.
- [x] Endpoint Submission `/api/v1/submissions` & Bulk Sync `/api/v1/submissions/bulk-sync` teruji dengan format status `201/200`.

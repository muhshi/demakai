# Panduan Integrasi PINTAR KBLI ke Flutter (Mode Hybrid: Online & Full Offline)

Dokumen ini adalah panduan arsitektur dan implementasi teknis untuk mengintegrasikan sistem pencarian **PINTAR KBLI (Sensus Ekonomi 2026)** ke aplikasi Flutter.

---

## 1. Arsitektur Komunikasi

```
                          ┌────────────────────────┐
                          │   Aplikasi Flutter     │
                          └───────────┬────────────┘
                                      │
                         [ Connectivity Manager ]
                                      │
              ┌───────────────────────┴───────────────────────┐
              ▼                                               ▼
       [ MODE ONLINE ]                                 [ MODE OFFLINE ]
(Ada Koneksi 4G / WiFi)                          (Blind Spot / Tanpa Internet)
              │                                               │
   [ Dio HTTP Client ]                              [ SQLite FTS5 + Isolate ]
              │                                               │
              ▼                                               ▼
┌───────────────────────────┐                   ┌───────────────────────────┐
│  Backend Web PINTAR KBLI  │                   │  Local Database (HP)      │
│  GET /api/v1/search       │                   │  • kbli_offline.db (2MB)  │
│  • Hybrid Search pgvector │                   │  • FTS5 BM25 Keyword      │
│  • AI Cloud Transformers  │                   │  • Pre-computed Vectors   │
│  • Realtime Crowdsourcing │                   │  • Dart Cosine Sim (<5ms) │
└───────────────────────────┘                   └───────────────────────────┘
```

---

## 2. Spesifikasi Endpoint API Backend (Online Mode)

Base URL: `https://domain-pintarkbli.bps.go.id/api/v1`

### A. Pencarian KBLI / KBJI
- **Method:** `GET`
- **Path:** `/search`
- **Query Parameters:**
  - `q` *(string, required)*: Kata kunci atau kalimat kegiatan usaha (contoh: `warung kopi`, `jual pulsa keliling`).
  - `type` *(string, optional)*: `KBLI` atau `KBJI` (default: keduanya digabung).
  - `limit` *(integer, optional)*: Jumlah hasil (default: `15`, max: `50`).
- **Response Format:**
```json
{
  "status": "success",
  "data": {
    "query": "warung makan",
    "results": [
      {
        "type": "KBLI 2025",
        "kode": "56102",
        "judul": "Rumah/Warung Makan",
        "deskripsi": "Kelompok ini mencakup penyediaan makanan untuk dikonsumsi di tempat...",
        "contoh_lapangan": [
          "Warung Tegal (Warteg)",
          "Warung Makan Padang",
          "Warung Soto Ayam"
        ],
        "score": 95,
        "match_type": "semantic",
        "is_equivalent": false
      }
    ],
    "total": 1
  },
  "meta": {
    "version": "2026.08.1",
    "search_method": "hybrid",
    "timestamp": "2026-08-26T07:42:20+00:00"
  }
}
```

---

### B. Hierarki Pohon KBLI
- **Method:** `GET`
- **Path:** `/kbli/hierarchy?parent={kode}`
- **Query Parameters:**
  - `parent`: Kosongkan untuk level Kategori (A–U), atau isi kode parent (misal `01`, `011`, `0111`).
- **Response Format:**
```json
{
  "status": "success",
  "data": [
    {
      "kode": "A",
      "judul": "PERTANIAN, KEHUTANAN DAN PERIKANAN",
      "deskripsi": "...",
      "level": "kategori",
      "is_leaf": false
    }
  ]
}
```

---

### C. Sinkronisasi Bundle Database Offline
- **Method:** `GET`
- **Path:** `/sync/check`
- **Response:**
```json
{
  "status": "success",
  "data": {
    "version": "2026.08.26",
    "kbli_count": 1569,
    "kbji_count": 2735,
    "file_size_mb": 2.09,
    "md5": "8da8c13ead0ce5dc7eb86b6fc9ec30bd",
    "download_url": "https://domain-pintarkbli.bps.go.id/api/v1/sync/bundle"
  }
}
```

---

### D. Crowdsourcing Masukan Lapangan (Offline Sync)
- **Method:** `POST`
- **Path:** `/submissions/bulk-sync`
- **Request Body:**
```json
{
  "submissions": [
    {
      "type": "KBLI 2025",
      "kode": "56102",
      "content": "Jual pecel lele tenda malam hari",
      "device_id": "android_uuid_123"
    }
  ]
}
```

---

## 3. Implementasi di Flutter (Dart)

### A. Rekomendasi Dependencies (`pubspec.yaml`)
```yaml
dependencies:
  flutter:
    sdk: flutter
  dio: ^5.7.0                     # HTTP client untuk API Online
  connectivity_plus: ^6.1.0       # Auto detect online/offline
  sqflite: ^2.4.1                 # SQLite lokal di Android & iOS
  path_provider: ^2.1.5           # Path penyimpanan file di HP
  archive: ^4.0.2                 # Untuk decompress .db.gz
```

---

### B. Inisialisasi Database Offline di HP
```dart
import 'dart:io';
import 'package:flutter/services.dart';
import 'package:path/path.dart';
import 'package:path_provider/path_provider.dart';
import 'package:sqflite/sqflite.dart';
import 'package:archive/archive.dart';

class LocalKbliDatabase {
  static Database? _db;

  static Future<Database> get instance async {
    if (_db != null) return _db!;
    _db = await _initDatabase();
    return _db!;
  }

  static Future<Database> _initDatabase() async {
    final docsDir = await getApplicationDocumentsDirectory();
    final dbPath = join(docsDir.path, 'kbli_offline.db');

    // Jika belum ada di penyimpanan lokal, salin dari asset bundle awal
    if (!await File(dbPath).exists()) {
      final byteData = await rootBundle.load('assets/database/kbli_offline.db');
      final bytes = byteData.buffer.asUint8List(byteData.offsetInBytes, byteData.lengthInBytes);
      await File(dbPath).writeAsBytes(bytes, flush: true);
    }

    return await openDatabase(dbPath, readOnly: true);
  }

  /// Pencarian Offline menggunakan SQLite FTS5 (Super Cepat < 5ms)
  static Future<List<Map<String, dynamic>>> searchFts(String query, {int limit = 15}) async {
    final db = await instance;
    final cleanQuery = query.replaceAll(RegExp(r'[^a-zA-Z0-9\s]'), '').trim();
    if (cleanQuery.isEmpty) return [];

    // FTS5 MATCH query dengan prefix search (*)
    final terms = cleanQuery.split(' ').map((term) => '$term*').join(' ');

    final results = await db.rawQuery('''
      SELECT k.id, k.kode, k.judul, k.deskripsi, k.kategori, k.contoh_lapangan,
             bm25(kbli_fts) as rank
      FROM kbli_fts f
      JOIN kbli2025 k ON f.rowid = k.id
      WHERE kbli_fts MATCH ?
      ORDER BY rank ASC
      LIMIT ?
    ''', [terms, limit]);

    return results;
  }
}
```

---

### C. Smart Search Repository (Auto-Switch Online / Offline)
```dart
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:dio/dio.dart';

class KbliSearchRepository {
  final Dio _dio = Dio(BaseOptions(
    baseUrl: 'https://domain-pintarkbli.bps.go.id/api/v1',
    connectTimeout: const Duration(seconds: 3),
    receiveTimeout: const Duration(seconds: 3),
  ));

  Future<List<Map<String, dynamic>>> search(String query) async {
    final connectivity = await Connectivity().checkConnectivity();
    final isOnline = connectivity != ConnectivityResult.none;

    if (isOnline) {
      try {
        // Coba Online Search API
        final res = await _dio.get('/search', queryParameters: {'q': query});
        if (res.statusCode == 200 && res.data['status'] == 'success') {
          return List<Map<String, dynamic>>.from(res.data['data']['results']);
        }
      } catch (e) {
        // Jika server timeout/error, otomatis fallback ke offline
      }
    }

    // Fallback Offline via SQLite FTS5
    return await LocalKbliDatabase.searchFts(query);
  }
}
```

---

## 4. Rangkuman Keuntungan Arsitektur Ini
1. **Responsif & Tangguh:** Aplikasi Flutter tidak akan pernah macet atau menampilkan layar blank saat petugas sensus kehilangan sinyal di pedalaman.
2. **Ukuran Efisien:** File database offline terkompresi hanya **2.09 MB** untuk seluruh KBLI 2025 dan KBJI 2014.
3. **Pencarian Instan:** Indeks SQLite FTS5 melakukan pencarian kata kunci dalam hitungan milidetik secara lokal di CPU ponsel.

# 🔍 DEMAKAI — Python Search Module

Modul Python modular untuk sistem pencarian KBLI/KBJI dengan 4 kombinasi metode.

## Struktur Folder

```
python/
├── config/
│   ├── __init__.py
│   ├── database.py         # Koneksi PostgreSQL (psycopg2)
│   └── settings.py         # Baca konfigurasi dari .env
├── preprocessing/
│   ├── __init__.py
│   ├── stopwords.py        # Daftar stopword Bahasa Indonesia
│   ├── basic.py            # Metode 1: case folding + tokenizing
│   └── advanced.py         # Metode 2: + stopword removal + stemming
├── search/
│   ├── __init__.py
│   ├── sql_like.py         # SQL ILIKE search
│   └── hybrid.py           # Hybrid: vector + keyword search
├── utils/
│   ├── __init__.py
│   └── result_formatter.py # Pretty-print hasil
├── main.py                 # Demo semua 4 kombinasi
└── requirements.txt
```

---

## ⚙️ Setup

```bash
# Dari root project (backend-demakai/)
cd python

# Install dependencies
pip install -r requirements.txt

# Pastikan .env di root project sudah terisi (DB_HOST, DB_DATABASE, dll.)
```

> **Catatan:** `settings.py` membaca `.env` dari `backend-demakai/.env` secara otomatis.

---

## 🚀 Menjalankan

```bash
# Demo dengan query default
python main.py

# Query custom
python main.py --query "ojek online"

# Hanya hasil KBLI, limit 10
python main.py --query "petani padi" --model KBLI --limit 10
```

---

## 4 Kombinasi Metode

| # | Preprocessing | Search Method | Fungsi |
|---|--------------|---------------|--------|
| 1 | **Basic** | SQL LIKE | `sql_basic(preprocess_basic(q))` |
| 2 | **Advanced** | SQL LIKE | `sql_advanced(preprocess_advanced(q))` |
| 3 | **Basic** | Hybrid | `hybrid_basic(preprocess_basic(q))` |
| 4 | **Advanced** | Hybrid | `hybrid_advanced(preprocess_advanced(q))` |

---

## 📖 Contoh Penggunaan Per Kombinasi

### Kombinasi 1 — SQL LIKE + Basic

```python
from preprocessing.basic import preprocess_basic
from search.sql_like import search_basic

query = "tukang ojek online"
preprocessed = preprocess_basic(query)
# {'original': 'tukang ojek online', 'clean': 'tukang ojek online', 'tokens': ['tukang', 'ojek', 'online']}

results = search_basic(preprocessed, limit=5)
for r in results:
    print(r['kode'], r['judul'])
```

### Kombinasi 2 — SQL LIKE + Advanced

```python
from preprocessing.advanced import preprocess_advanced
from search.sql_like import search_advanced

preprocessed = preprocess_advanced("saya bekerja sebagai tukang ojek online")
# stopword removal: hapus 'saya', 'bekerja', 'sebagai', 'tukang'
# stemming: 'ojek' → 'ojek', 'online' → 'online'

results = search_advanced(preprocessed, limit=5)
```

### Kombinasi 3 — Hybrid + Basic

```python
from preprocessing.basic import preprocess_basic
from search.hybrid import search_basic as hybrid_basic

preprocessed = preprocess_basic("petani padi sawah")
results = hybrid_basic(preprocessed, limit=5, model="KBLI")
```

### Kombinasi 4 — Hybrid + Advanced

```python
from preprocessing.advanced import preprocess_advanced
from search.hybrid import search_advanced as hybrid_advanced

preprocessed = preprocess_advanced("saya adalah seorang petani padi sawah")
results = hybrid_advanced(preprocessed, limit=5)
```

---

## 🔬 Alur Preprocessing

### Metode Basic
```
"Saya bekerja sebagai tukang ojek online"
         ↓ case folding
"saya bekerja sebagai tukang ojek online"
         ↓ remove punctuation
"saya bekerja sebagai tukang ojek online"
         ↓ tokenizing
['saya', 'bekerja', 'sebagai', 'tukang', 'ojek', 'online']
```

### Metode Advanced
```
"Saya bekerja sebagai tukang ojek online"
         ↓ case folding + remove punctuation + tokenizing
['saya', 'bekerja', 'sebagai', 'tukang', 'ojek', 'online']
         ↓ stopword removal
['ojek', 'online']
         ↓ stemming (PySastrawi)
['ojek', 'online']   ← stemmed_tokens
```

---

## 📦 Dependencies

| Library | Kegunaan |
|---------|---------|
| `psycopg2-binary` | Koneksi PostgreSQL |
| `python-dotenv` | Load `.env` |
| `PySastrawi` | Stemmer Bahasa Indonesia |
| `google-generativeai` | Gemini embedding (Hybrid Search) |
| `numpy` | Operasi vektor (opsional) |

---

## 📝 Catatan

- **Hybrid Search** tanpa `GEMINI_API_KEY` akan otomatis fallback ke **keyword-only search**
- **Advanced preprocessing** tanpa `PySastrawi` terinstall akan melewati tahap stemming (tidak error)
- Semua fungsi search menerima parameter `model='KBLI'` atau `model='KBJI'` untuk filter tabel

"""
export_sqlite_bundle.py
-----------------------
Utility script to export PostgreSQL (pgvector + KBLI 2025 / KBJI 2014) 
into an optimized, compressed SQLite offline bundle (with FTS5 and BLOB embeddings)
for Flutter mobile on-device search.
"""

import gzip
import hashlib
import json
import os
import sqlite3
import sys
import time
from datetime import datetime
import psycopg2
from dotenv import load_dotenv

# Load .env
_root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
load_dotenv(os.path.join(_root_dir, '.env'))

def export_bundle(version: str = None):
    if not version:
        version = datetime.now().strftime("%Y.%m.%d")

    print("=" * 55)
    print(f"  PINTAR KBLI - SQLite Offline Bundle Exporter v{version}")
    print("=" * 55)

    bundle_dir = os.path.join(_root_dir, 'storage', 'app', 'bundles')
    os.makedirs(bundle_dir, exist_ok=True)

    db_path = os.path.join(bundle_dir, 'kbli_offline_latest.db')
    gz_path = os.path.join(bundle_dir, 'kbli_offline_latest.db.gz')
    meta_path = os.path.join(bundle_dir, 'bundle_meta.json')
    temp_db_path = os.path.join(bundle_dir, f'kbli_offline_temp_{int(time.time())}.db')

    # Connect to PostgreSQL
    pg_conn = psycopg2.connect(
        host=os.getenv("PG_DB_HOST", "127.0.0.1"),
        port=int(os.getenv("PG_DB_PORT", "5432")),
        dbname=os.getenv("PG_DB_DATABASE", "postgres"),
        user=os.getenv("PG_DB_USERNAME", "postgres"),
        password=os.getenv("PG_DB_PASSWORD", "")
    )
    pg_cur = pg_conn.cursor()

    print(f"1. Creating SQLite database at: {temp_db_path}")
    lite_conn = sqlite3.connect(temp_db_path)
    lite_cur = lite_conn.cursor()

    # Pragmas for fast inserts
    lite_cur.execute("PRAGMA journal_mode = OFF;")
    lite_cur.execute("PRAGMA synchronous = 0;")
    lite_cur.execute("PRAGMA cache_size = 100000;")

    # Create tables & FTS5 indexes
    print("2. Creating schemas & FTS5 full-text search indexes...")
    lite_cur.executescript("""
        CREATE TABLE meta (
            key TEXT PRIMARY KEY,
            value TEXT
        );

        CREATE TABLE kbli2025 (
            id INTEGER PRIMARY KEY,
            kode TEXT UNIQUE,
            judul TEXT,
            deskripsi TEXT,
            kategori TEXT,
            contoh_lapangan TEXT,
            embedding BLOB
        );

        CREATE TABLE kbji2014 (
            id INTEGER PRIMARY KEY,
            kode TEXT UNIQUE,
            judul TEXT,
            deskripsi TEXT,
            contoh_lapangan TEXT,
            embedding BLOB
        );

        CREATE VIRTUAL TABLE kbli_fts USING fts5(
            kode,
            judul,
            deskripsi,
            contoh_lapangan,
            tokenize='unicode61 remove_diacritics 2'
        );

        CREATE VIRTUAL TABLE kbji_fts USING fts5(
            kode,
            judul,
            deskripsi,
            contoh_lapangan,
            tokenize='unicode61 remove_diacritics 2'
        );
    """)

    # 1. Export KBLI 2025
    print("3. Exporting KBLI 2025 records from PostgreSQL...")
    pg_cur.execute("""
        SELECT id, kode, judul, deskripsi, kategori, contoh_lapangan, embedding::text 
        FROM kbli2025s ORDER BY id
    """)
    kbli_rows = pg_cur.fetchall()
    kbli_count = 0

    for r in kbli_rows:
        _id, kode, judul, deskripsi, kategori, contoh, emb_str = r
        contoh_json = json.dumps(contoh if isinstance(contoh, list) else (json.loads(contoh) if contoh else []), ensure_ascii=False)
        contoh_text = " ".join(contoh) if isinstance(contoh, list) else str(contoh or '')

        emb_blob = None
        if emb_str:
            try:
                import struct
                floats = json.loads(emb_str) if emb_str.startswith('[') else [float(x) for x in emb_str.strip('[]').split(',')]
                emb_blob = struct.pack(f'{len(floats)}f', *floats)
            except Exception as e:
                pass

        lite_cur.execute(
            "INSERT INTO kbli2025 (id, kode, judul, deskripsi, kategori, contoh_lapangan, embedding) VALUES (?, ?, ?, ?, ?, ?, ?)",
            (_id, str(kode), str(judul), str(deskripsi or ''), str(kategori or ''), contoh_json, emb_blob)
        )
        lite_cur.execute(
            "INSERT INTO kbli_fts (rowid, kode, judul, deskripsi, contoh_lapangan) VALUES (?, ?, ?, ?, ?)",
            (_id, str(kode), str(judul), str(deskripsi or ''), contoh_text)
        )
        kbli_count += 1

    lite_conn.commit()
    print(f"   -> Exported {kbli_count} KBLI 2025 records.")

    # 2. Export KBJI 2014
    print("4. Exporting KBJI 2014 records from PostgreSQL...")
    pg_cur.execute("""
        SELECT id, kode, judul, deskripsi, contoh_lapangan, embedding::text 
        FROM kbji2014s ORDER BY id
    """)
    kbji_rows = pg_cur.fetchall()
    kbji_count = 0

    for r in kbji_rows:
        _id, kode, judul, deskripsi, contoh, emb_str = r
        contoh_json = json.dumps(contoh if isinstance(contoh, list) else (json.loads(contoh) if contoh else []), ensure_ascii=False)
        contoh_text = " ".join(contoh) if isinstance(contoh, list) else str(contoh or '')

        emb_blob = None
        if emb_str:
            try:
                import struct
                floats = json.loads(emb_str) if emb_str.startswith('[') else [float(x) for x in emb_str.strip('[]').split(',')]
                emb_blob = struct.pack(f'{len(floats)}f', *floats)
            except Exception:
                pass

        lite_cur.execute(
            "INSERT INTO kbji2014 (id, kode, judul, deskripsi, contoh_lapangan, embedding) VALUES (?, ?, ?, ?, ?, ?)",
            (_id, str(kode), str(judul), str(deskripsi or ''), contoh_json, emb_blob)
        )
        lite_cur.execute(
            "INSERT INTO kbji_fts (rowid, kode, judul, deskripsi, contoh_lapangan) VALUES (?, ?, ?, ?, ?)",
            (_id, str(kode), str(judul), str(deskripsi or ''), contoh_text)
        )
        kbji_count += 1

    lite_conn.commit()
    print(f"   -> Exported {kbji_count} KBJI 2014 records.")

    # Meta
    now_iso = datetime.now().isoformat()
    lite_cur.execute("INSERT INTO meta (key, value) VALUES (?, ?)", ("version", version))
    lite_cur.execute("INSERT INTO meta (key, value) VALUES (?, ?)", ("app_name", "PINTAR KBLI"))
    lite_cur.execute("INSERT INTO meta (key, value) VALUES (?, ?)", ("generated_at", now_iso))
    lite_cur.execute("INSERT INTO meta (key, value) VALUES (?, ?)", ("kbli_count", str(kbli_count)))
    lite_cur.execute("INSERT INTO meta (key, value) VALUES (?, ?)", ("kbji_count", str(kbji_count)))
    lite_conn.commit()

    lite_cur.execute("PRAGMA synchronous = 1;")
    lite_cur.execute("VACUUM;")
    lite_conn.close()
    pg_conn.close()

    raw_size = os.path.getsize(temp_db_path)
    raw_size_mb = round(raw_size / (1024 * 1024), 2)
    print(f"5. Raw SQLite database size: {raw_size_mb} MB ({raw_size} bytes)")

    # GZIP compress
    print("6. Compressing bundle with GZIP...")
    with open(temp_db_path, 'rb') as f_in, gzip.open(gz_path, 'wb', compresslevel=9) as f_out:
        while chunk := f_in.read(1024 * 512):
            f_out.write(chunk)

    # Move temp to final db
    import shutil
    shutil.copyfile(temp_db_path, db_path)
    try:
        os.remove(temp_db_path)
    except Exception:
        pass

    gz_size = os.path.getsize(gz_path)
    gz_size_mb = round(gz_size / (1024 * 1024), 2)

    with open(gz_path, 'rb') as f:
        data = f.read()
        md5_hash = hashlib.md5(data).hexdigest()
        sha256_hash = hashlib.sha256(data).hexdigest()

    meta_data = {
        "status": "ready",
        "version": version,
        "generated_at": now_iso,
        "kbli_count": kbli_count,
        "kbji_count": kbji_count,
        "raw_file_size_bytes": raw_size,
        "raw_file_size_mb": raw_size_mb,
        "file_size_bytes": gz_size,
        "file_size_mb": gz_size_mb,
        "md5": md5_hash,
        "sha256": sha256_hash
    }

    with open(meta_path, 'w', encoding='utf-8') as f:
        json.dump(meta_data, f, indent=2)

    print("=" * 55)
    print(f"  SUCCESS! Offline Bundle v{version} is Ready.")
    print(f"  Bundle: {gz_path} ({gz_size_mb} MB)")
    print(f"  Meta:   {meta_path}")
    print("=" * 55)

if __name__ == '__main__':
    v = sys.argv[1] if len(sys.argv) > 1 else None
    export_bundle(v)

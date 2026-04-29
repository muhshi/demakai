"""
build_sqlite_baseline.py -- Membangun Database SQLite untuk Evaluasi Baseline
Mengekstrak data KBLI dan KBJI dari PostgreSQL ke SQLite lokal (demakai_baseline.db).

KOLOM yang diambil (BASELINE -- tanpa contoh_lapangan):
  - kode
  - judul
  - deskripsi

Jalankan SEKALI sebelum evaluate_baseline.py:
  python build_sqlite_baseline.py
"""

import sqlite3
import sys
import os

sys.path.insert(0, os.path.dirname(__file__))

from config.database import get_connection, release_connection

SQLITE_PATH = os.path.join(os.path.dirname(__file__), "demakai_baseline.db")


def build_sqlite():
    print("=" * 60)
    print("  DEMAKAI - Build SQLite Baseline Database")
    print("=" * 60)

    print("\n[1/4] Menghubungkan ke PostgreSQL...")
    pg_conn = get_connection()
    pg_cur  = pg_conn.cursor()

    print("[2/4] Membuat SQLite database di: " + SQLITE_PATH)
    if os.path.exists(SQLITE_PATH):
        os.remove(SQLITE_PATH)
        print("      (file lama dihapus)")

    sq_conn = sqlite3.connect(SQLITE_PATH)
    sq_cur  = sq_conn.cursor()

    sq_cur.execute("""
        CREATE TABLE IF NOT EXISTS kbli2025s (
            id    INTEGER PRIMARY KEY AUTOINCREMENT,
            kode  TEXT,
            judul TEXT,
            deskripsi TEXT
        )
    """)
    sq_cur.execute("""
        CREATE TABLE IF NOT EXISTS kbji2014s (
            id    INTEGER PRIMARY KEY AUTOINCREMENT,
            kode  TEXT,
            judul TEXT,
            deskripsi TEXT
        )
    """)
    sq_conn.commit()

    print("[3/4] Mengekstrak data KBLI 2025 dari PostgreSQL...")
    pg_cur.execute("SELECT kode, judul, deskripsi FROM kbli2025s ORDER BY id")
    kbli_rows = pg_cur.fetchall()
    sq_cur.executemany(
        "INSERT INTO kbli2025s (kode, judul, deskripsi) VALUES (?, ?, ?)",
        [(r["kode"], r["judul"], r["deskripsi"]) for r in kbli_rows]
    )
    sq_conn.commit()
    print("      [OK] " + str(len(kbli_rows)) + " record KBLI berhasil disimpan.")

    print("[4/4] Mengekstrak data KBJI 2014 dari PostgreSQL...")
    pg_cur.execute("SELECT kode, judul, deskripsi FROM kbji2014s ORDER BY id")
    kbji_rows = pg_cur.fetchall()
    sq_cur.executemany(
        "INSERT INTO kbji2014s (kode, judul, deskripsi) VALUES (?, ?, ?)",
        [(r["kode"], r["judul"], r["deskripsi"]) for r in kbji_rows]
    )
    sq_conn.commit()
    print("      [OK] " + str(len(kbji_rows)) + " record KBJI berhasil disimpan.")

    release_connection(pg_conn)
    sq_conn.close()

    print("\n" + "=" * 60)
    print("  [SELESAI] SQLite Baseline Database SIAP!")
    print("  Path  : " + SQLITE_PATH)
    print("  KBLI  : " + str(len(kbli_rows)) + " record")
    print("  KBJI  : " + str(len(kbji_rows)) + " record")
    print("  NOTE  : Kolom 'contoh_lapangan' TIDAK disertakan (baseline)")
    print("=" * 60)


if __name__ == "__main__":
    build_sqlite()

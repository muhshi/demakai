"""
database.py
-----------
Manajemen koneksi PostgreSQL menggunakan psycopg2.
Setiap pemanggilan get_connection() membuka koneksi baru yang harus
ditutup secara eksplisit. Gunakan selalu:

    conn = get_connection()
    try:
        ...
    finally:
        conn.close()
"""

import psycopg2
import psycopg2.extras
from .settings import Settings


def get_connection():
    """
    Buka koneksi baru ke PostgreSQL.
    WAJIB ditutup dengan conn.close() setelah selesai.
    """
    return psycopg2.connect(
        host=Settings.DB_HOST,
        port=Settings.DB_PORT,
        dbname=Settings.DB_NAME,
        user=Settings.DB_USER,
        password=Settings.DB_PASSWORD,
        cursor_factory=psycopg2.extras.RealDictCursor,
        connect_timeout=10,
    )


def release_connection(conn):
    """Tutup koneksi ke PostgreSQL (kompatibel dengan kode lama)."""
    try:
        if conn and not conn.closed:
            conn.close()
    except Exception:
        pass

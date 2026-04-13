"""
settings.py
-----------
Konfigurasi umum untuk sistem pencarian DEMAKAI.
Nilai default dibaca dari environment variable atau .env file.
"""

import os
from dotenv import load_dotenv

# Load .env dari root project (satu level di atas folder python/)
_env_path = os.path.join(os.path.dirname(__file__), '..', '..', '.env')
load_dotenv(dotenv_path=_env_path)


class Settings:
    # ── Database ──────────────────────────────────────────────
    DB_HOST: str     = os.getenv("PG_DB_HOST",     "127.0.0.1")
    DB_PORT: int     = int(os.getenv("PG_DB_PORT", "5432"))
    DB_NAME: str     = os.getenv("PG_DB_DATABASE", "postgres")
    DB_USER: str     = os.getenv("PG_DB_USERNAME", "postgres")
    DB_PASSWORD: str = os.getenv("PG_DB_PASSWORD", "")

    # ── Tabel ─────────────────────────────────────────────────
    TABLE_KBLI: str = "kbli2025s"
    TABLE_KBJI: str = "kbji2014s"

    # ── Kolom yang di-search ───────────────────────────────────
    SEARCH_COLUMNS: list = ["kode", "judul", "deskripsi", "contoh_lapangan"]

    # ── Gemini API (untuk Hybrid Search) ──────────────────────
    GEMINI_API_KEY: str = os.getenv("GEMINI_API_KEY", "")
    EMBEDDING_MODEL: str = "models/gemini-embedding-001"

    # ── Default limit hasil ────────────────────────────────────
    DEFAULT_LIMIT: int = 10

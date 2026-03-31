"""
sql_like.py — SQL LIKE Search
------------------------------
Modul pencarian menggunakan SQL ILIKE query ke database PostgreSQL.
Mendukung tiga mode input:
  - search_raw()      → query mentah, tanpa preprocessing apapun
  - search_basic()    → dari hasil preprocess_basic()
  - search_advanced() → dari hasil preprocess_advanced() (pakai stemmed_clean)

Cara kerja:
  Query dibersihkan oleh preprocessing, lalu digunakan sebagai pola ILIKE
  terhadap kolom: judul, deskripsi, contoh_lapangan.
"""

from config.database import get_connection, release_connection
from config.settings import Settings


def _run_query(cursor, table: str, pattern: str, limit: int) -> list:
    """
    Jalankan SQL ILIKE query terhadap satu tabel.
    Mengembalikan list of dict.
    """
    sql = f"""
        SELECT *
        FROM {table}
        WHERE
            judul       ILIKE %(pattern)s OR
            deskripsi   ILIKE %(pattern)s OR
            CAST(contoh_lapangan AS TEXT) ILIKE %(pattern)s
        LIMIT %(limit)s
    """
    cursor.execute(sql, {"pattern": pattern, "limit": limit})
    return cursor.fetchall()


def _run_query_or_tokens(cursor, table: str, tokens: list, limit: int) -> list:
    """
    Fallback: SQL ILIKE per token dengan OR logic.
    Setiap token dicek di judul/deskripsi/contoh_lapangan.
    Record yang match token APAPUN (bukan semua) akan dikembalikan.
    Diurutkan berdasarkan jumlah token yang cocok (DESC).
    """
    if not tokens:
        return []

    # Bangun CASE untuk menghitung token yang cocok (untuk ordering)
    score_parts = []
    params = []
    for token in tokens:
        pat = f"%{token}%"
        score_parts.append(
            "(CASE WHEN judul ILIKE %s OR deskripsi ILIKE %s "
            "OR CAST(contoh_lapangan AS TEXT) ILIKE %s THEN 1 ELSE 0 END)"
        )
        params.extend([pat, pat, pat])

    # OR conditions untuk WHERE
    or_conditions = []
    where_params = []
    for token in tokens:
        pat = f"%{token}%"
        or_conditions.append(
            "(judul ILIKE %s OR deskripsi ILIKE %s "
            "OR CAST(contoh_lapangan AS TEXT) ILIKE %s)"
        )
        where_params.extend([pat, pat, pat])

    score_expr = " + ".join(score_parts)
    where_expr = " OR ".join(or_conditions)

    sql = f"""
        SELECT *, ({score_expr}) AS _match_score
        FROM {table}
        WHERE {where_expr}
        ORDER BY _match_score DESC
        LIMIT %s
    """
    cursor.execute(sql, params + where_params + [limit])
    return cursor.fetchall()


def search_basic(preprocessed: dict, limit: int = None, model: str = None) -> list:
    """
    SQL LIKE search menggunakan hasil preprocessing BASIC.
    Keyword yang digunakan: preprocessed['clean'] (tanpa stopword/stemming).
    Jika frasa lengkap tidak ditemukan, fallback ke OR per token.

    Args:
        preprocessed : output dari preprocess_basic()
        limit        : jumlah maksimum hasil. Default: Settings.DEFAULT_LIMIT
        model        : 'KBLI', 'KBJI', atau None (keduanya)

    Returns:
        list of dict — setiap item adalah satu record dari DB
    """
    limit = limit or Settings.DEFAULT_LIMIT
    keyword = preprocessed.get("clean", "")
    tokens  = preprocessed.get("tokens", [])
    if not keyword:
        return []

    pattern = f"%{keyword}%"
    results = []
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            # Step 1: Coba frasa lengkap
            if model is None or model == "KBLI":
                rows = _run_query(cur, Settings.TABLE_KBLI, pattern, limit)
                results.extend([{**r, "_source": "KBLI", "_boost": "phrase"} for r in rows])

            if model is None or model == "KBJI":
                rows = _run_query(cur, Settings.TABLE_KBJI, pattern, limit)
                results.extend([{**r, "_source": "KBJI", "_boost": "phrase"} for r in rows])

            # Step 2 (fallback): OR per token jika step 1 kosong
            if not results and len(tokens) > 1:
                if model is None or model == "KBLI":
                    rows = _run_query_or_tokens(cur, Settings.TABLE_KBLI, tokens, limit)
                    results.extend([{**r, "_source": "KBLI", "_boost": "or_token"} for r in rows])

                if model is None or model == "KBJI":
                    rows = _run_query_or_tokens(cur, Settings.TABLE_KBJI, tokens, limit)
                    results.extend([{**r, "_source": "KBJI", "_boost": "or_token"} for r in rows])

            # Step 3 (fallback ke-2): satu token terpanjang
            if not results and tokens:
                best_token = max(tokens, key=len)
                single_pattern = f"%{best_token}%"
                if model is None or model == "KBLI":
                    rows = _run_query(cur, Settings.TABLE_KBLI, single_pattern, limit)
                    results.extend([{**r, "_source": "KBLI", "_boost": "single_token"} for r in rows])
                if model is None or model == "KBJI":
                    rows = _run_query(cur, Settings.TABLE_KBJI, single_pattern, limit)
                    results.extend([{**r, "_source": "KBJI", "_boost": "single_token"} for r in rows])
    finally:
        release_connection(conn)

    return results[:limit]


def search_advanced(preprocessed: dict, limit: int = None, model: str = None) -> list:
    """
    SQL LIKE search menggunakan hasil preprocessing ADVANCED.
    Keyword yang digunakan: preprocessed['stemmed_clean'] (setelah stopword removal + stemming).
    Jika stemmed phrase tidak ditemukan, fallback ke OR per stemmed token.

    Args:
        preprocessed : output dari preprocess_advanced()
        limit        : jumlah maksimum hasil. Default: Settings.DEFAULT_LIMIT
        model        : 'KBLI', 'KBJI', atau None (keduanya)

    Returns:
        list of dict — setiap item adalah satu record dari DB
    """
    limit = limit or Settings.DEFAULT_LIMIT
    keyword = preprocessed.get("stemmed_clean", "") or preprocessed.get("clean", "")
    tokens  = preprocessed.get("stemmed_tokens") or preprocessed.get("tokens", [])
    if not keyword:
        return []

    pattern = f"%{keyword}%"
    results = []
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            # Step 1: Coba frasa stemmed lengkap
            if model is None or model == "KBLI":
                rows = _run_query(cur, Settings.TABLE_KBLI, pattern, limit)
                results.extend([{**r, "_source": "KBLI", "_boost": "stemmed_phrase"} for r in rows])

            if model is None or model == "KBJI":
                rows = _run_query(cur, Settings.TABLE_KBJI, pattern, limit)
                results.extend([{**r, "_source": "KBJI", "_boost": "stemmed_phrase"} for r in rows])

            # Step 2 (fallback): OR per stemmed token jika step 1 kosong
            if not results and len(tokens) > 1:
                if model is None or model == "KBLI":
                    rows = _run_query_or_tokens(cur, Settings.TABLE_KBLI, tokens, limit)
                    results.extend([{**r, "_source": "KBLI", "_boost": "or_stemmed"} for r in rows])

                if model is None or model == "KBJI":
                    rows = _run_query_or_tokens(cur, Settings.TABLE_KBJI, tokens, limit)
                    results.extend([{**r, "_source": "KBJI", "_boost": "or_stemmed"} for r in rows])

            # Step 3 (fallback ke-2): satu token terpanjang
            if not results and tokens:
                best_token = max(tokens, key=len)
                single_pattern = f"%{best_token}%"
                if model is None or model == "KBLI":
                    rows = _run_query(cur, Settings.TABLE_KBLI, single_pattern, limit)
                    results.extend([{**r, "_source": "KBLI", "_boost": "single_stemmed"} for r in rows])
                if model is None or model == "KBJI":
                    rows = _run_query(cur, Settings.TABLE_KBJI, single_pattern, limit)
                    results.extend([{**r, "_source": "KBJI", "_boost": "single_stemmed"} for r in rows])
    finally:
        release_connection(conn)

    return results[:limit]


def search_multi_token(preprocessed: dict, limit: int = None, model: str = None,
                       use_stemmed: bool = False) -> list:
    """
    SQL LIKE search per token (AND logic) — setiap token harus muncul di record.
    Berguna saat frase lengkap tidak cocok tapi keyword individual relevan.

    Args:
        preprocessed : output dari preprocess_basic() atau preprocess_advanced()
        limit        : jumlah maksimum hasil
        model        : 'KBLI', 'KBJI', atau None
        use_stemmed  : jika True, gunakan stemmed_tokens dari Advanced

    Returns:
        list of dict
    """
    limit = limit or Settings.DEFAULT_LIMIT

    if use_stemmed:
        tokens = preprocessed.get("stemmed_tokens", preprocessed.get("tokens", []))
    else:
        tokens = preprocessed.get("tokens", [])

    if not tokens:
        return []

    results = []

    def build_query(table: str) -> tuple:
        """Bangun SQL query dengan AND condition per token."""
        conditions = []
        params = []
        for token in tokens:
            pat = f"%{token}%"
            conditions.append(
                "(judul ILIKE %s OR deskripsi ILIKE %s OR "
                "CAST(contoh_lapangan AS TEXT) ILIKE %s)"
            )
            params.extend([pat, pat, pat])

        where_clause = " AND ".join(conditions)
        sql = f"SELECT * FROM {table} WHERE {where_clause} LIMIT %s"
        params.append(limit)
        return sql, params

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            if model is None or model == "KBLI":
                sql, params = build_query(Settings.TABLE_KBLI)
                cur.execute(sql, params)
                rows = cur.fetchall()
                results.extend([{**r, "_source": "KBLI"} for r in rows])

            if model is None or model == "KBJI":
                sql, params = build_query(Settings.TABLE_KBJI)
                cur.execute(sql, params)
                rows = cur.fetchall()
                results.extend([{**r, "_source": "KBJI"} for r in rows])
    finally:
        conn.close()

    return results[:limit]



def search_raw(query: str, limit: int = None, model: str = None) -> list:
    """
    SQL LIKE search tanpa preprocessing — query digunakan apa adanya (mentah).
    Pola: ILIKE '%<query asli>%'
    Jika frasa lengkap tidak ditemukan, fallback ke OR per kata.

    Args:
        query : string query langsung dari user (tidak diproses)
        limit : jumlah maksimum hasil. Default: Settings.DEFAULT_LIMIT
        model : 'KBLI', 'KBJI', atau None (keduanya)

    Returns:
        list of dict — setiap item adalah satu record dari DB
    """
    limit = limit or Settings.DEFAULT_LIMIT
    if not query:
        return []

    pattern = f"%{query}%"
    tokens  = [t for t in query.split() if t]
    results = []
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            # Step 1: Coba frasa lengkap
            if model is None or model == "KBLI":
                rows = _run_query(cur, Settings.TABLE_KBLI, pattern, limit)
                results.extend([{**r, "_source": "KBLI", "_boost": "phrase"} for r in rows])

            if model is None or model == "KBJI":
                rows = _run_query(cur, Settings.TABLE_KBJI, pattern, limit)
                results.extend([{**r, "_source": "KBJI", "_boost": "phrase"} for r in rows])

            # Step 2 (fallback): OR per kata jika step 1 kosong
            if not results and len(tokens) > 1:
                if model is None or model == "KBLI":
                    rows = _run_query_or_tokens(cur, Settings.TABLE_KBLI, tokens, limit)
                    results.extend([{**r, "_source": "KBLI", "_boost": "or_token"} for r in rows])

                if model is None or model == "KBJI":
                    rows = _run_query_or_tokens(cur, Settings.TABLE_KBJI, tokens, limit)
                    results.extend([{**r, "_source": "KBJI", "_boost": "or_token"} for r in rows])

            # Step 3 (fallback ke-2): satu kata terpanjang
            if not results and tokens:
                best_token = max(tokens, key=len)
                single_pattern = f"%{best_token}%"
                if model is None or model == "KBLI":
                    rows = _run_query(cur, Settings.TABLE_KBLI, single_pattern, limit)
                    results.extend([{**r, "_source": "KBLI", "_boost": "single_token"} for r in rows])
                if model is None or model == "KBJI":
                    rows = _run_query(cur, Settings.TABLE_KBJI, single_pattern, limit)
                    results.extend([{**r, "_source": "KBJI", "_boost": "single_token"} for r in rows])
    finally:
        release_connection(conn)

    return results[:limit]

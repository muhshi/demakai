"""
hybrid.py — Hybrid Search (Semantic + Keyword)
------------------------------------------------
Menggabungkan dua strategi pencarian:
  1. Vector/Semantic Search  — menggunakan embedding dari Gemini API
                               dan query cosine distance ke pgvector
  2. Keyword Search          — SQL ILIKE multi-token (AND logic)

Hasil keduanya di-merge dan di-boost berdasarkan lokasi match:
  contoh_lapangan > judul > deskripsi

Mendukung opsi mode:
  - search_raw()      → query mentah, tanpa preprocessing apapun
  - search_advanced() → embedding dari 'stemmed_clean', keyword dari 'stemmed_tokens'
  - search_expansion()→ embedding dari 'clean', keyword dari 'expanded_tokens'
"""

import json
from config.database import get_connection
from config.settings import Settings
from .utils import search_numeric_code

# ── Gemini embedding (opsional) ───────────────────────────────────────────────
try:
    import google.generativeai as genai
    genai.configure(api_key=Settings.GEMINI_API_KEY)
    GEMINI_AVAILABLE = bool(Settings.GEMINI_API_KEY)
except ImportError:
    GEMINI_AVAILABLE = False
    print("[WARNING] google-generativeai tidak terinstall. Semantic search dinonaktifkan.")


# ─────────────────────────────────────────────────────────────────────────────
# Helper: generate embedding
# ─────────────────────────────────────────────────────────────────────────────

def _generate_embedding(text: str) -> list | None:
    """
    Membuat embedding vector dari teks menggunakan Gemini API.
    Mengembalikan None jika gagal atau API tidak tersedia.
    """
    if not GEMINI_AVAILABLE or not text:
        return None
    try:
        result = genai.embed_content(
            model=Settings.EMBEDDING_MODEL,
            content=text,
            task_type="retrieval_query",
            output_dimensionality=768
        )
        return result["embedding"]
    except Exception as e:
        print(f"[WARNING] Gagal membuat embedding: {e}")
        return None


# ─────────────────────────────────────────────────────────────────────────────
# Helper: vector search via pgvector
# ─────────────────────────────────────────────────────────────────────────────

def _vector_search(cursor, table: str, embedding: list, limit: int) -> list:
    """
    Query pgvector dengan cosine distance (<=>).
    Mengembalikan list of dict dengan field tambahan 'distance'.
    """
    vector_str = "[" + ",".join(str(v) for v in embedding) + "]"
    sql = f"""
        SELECT *, (embedding <=> %s::vector) AS distance
        FROM {table}
        ORDER BY distance ASC
        LIMIT %s
    """
    cursor.execute(sql, (vector_str, limit))
    return cursor.fetchall()


# ─────────────────────────────────────────────────────────────────────────────
# Helper: keyword LIKE search (multi-token AND logic)
# ─────────────────────────────────────────────────────────────────────────────

def _keyword_search(cursor, table: str, tokens: list, limit: int) -> list:
    """
    SQL LIKE dengan AND logic (semua token harus ada).
    Jika menghasilkan kosong, fallback ke OR logic (cukup salah satu token cocok).
    Diurutkan berdasarkan jumlah token yang cocok (DESC).
    """
    if not tokens:
        return []

    # ── Step 1: AND logic (strict) ────────────────────────────────────────────
    and_conditions = []
    and_params = []
    for token in tokens:
        pat = f"%{token}%"
        and_conditions.append(
            "(kode ILIKE %s OR judul ILIKE %s OR deskripsi ILIKE %s OR "
            "CAST(contoh_lapangan AS TEXT) ILIKE %s)"
        )
        and_params.extend([pat, pat, pat, pat])

    and_clause = " AND ".join(and_conditions)
    sql_and = f"SELECT *, 0.05 AS distance FROM {table} WHERE {and_clause} LIMIT %s"
    and_params.append(limit)
    cursor.execute(sql_and, and_params)
    rows = cursor.fetchall()

    if rows:
        return rows

    # ── Step 2: OR fallback (lebih longgar) ───────────────────────────────────
    # Hitung skor: jumlah token yang cocok per record (untuk ordering)
    score_parts = []
    score_params = []
    or_conditions = []
    or_params = []
    for token in tokens:
        pat = f"%{token}%"
        score_parts.append(
            "(CASE WHEN kode ILIKE %s OR judul ILIKE %s OR deskripsi ILIKE %s "
            "OR CAST(contoh_lapangan AS TEXT) ILIKE %s THEN 1 ELSE 0 END)"
        )
        score_params.extend([pat, pat, pat, pat])
        or_conditions.append(
            "(kode ILIKE %s OR judul ILIKE %s OR deskripsi ILIKE %s "
            "OR CAST(contoh_lapangan AS TEXT) ILIKE %s)"
        )
        or_params.extend([pat, pat, pat, pat])

    score_expr = " + ".join(score_parts)
    or_clause  = " OR ".join(or_conditions)

    sql_or = f"""
        SELECT *, 0.05 AS distance, ({score_expr}) AS _match_score
        FROM {table}
        WHERE {or_clause}
        ORDER BY _match_score DESC
        LIMIT %s
    """
    cursor.execute(sql_or, score_params + or_params + [limit])
    return cursor.fetchall()


# ─────────────────────────────────────────────────────────────────────────────
# Helper: merge + boost results
# ─────────────────────────────────────────────────────────────────────────────

def _merge_and_boost(semantic: list, keyword: list, tokens: list) -> list:
    """
    Menggabungkan hasil semantic dan keyword, lalu menerapkan boosting:
      - Match di contoh_lapangan → distance dikurangi (prioritas tertinggi)
      - Match di judul           → prioritas menengah
      - Match di deskripsi saja  → prioritas terendah
    """
    all_results = list(semantic) + list(keyword)

    # Deduplikasi berdasarkan 'id' — simpan yang distance-nya terkecil
    seen: dict = {}
    for row in all_results:
        rid = row.get("id")
        if rid is None:
            continue
        if rid not in seen or (row.get("distance", 1.0) < seen[rid].get("distance", 1.0)):
            seen[rid] = dict(row)

    boosted = []
    for item in seen.values():
        judul   = (item.get("judul") or "").lower()
        desc    = (item.get("deskripsi") or "").lower()
        contoh  = item.get("contoh_lapangan")

        # contoh_lapangan bisa berupa JSONB list atau string
        if isinstance(contoh, list):
            contoh_str = " ".join(contoh).lower()
        elif isinstance(contoh, str):
            try:
                parsed = json.loads(contoh)
                contoh_str = " ".join(parsed).lower() if isinstance(parsed, list) else contoh.lower()
            except (json.JSONDecodeError, TypeError):
                contoh_str = contoh.lower()
        else:
            contoh_str = ""

        original_distance = float(item.get("distance") or 1.0)

        match_example = any(t in contoh_str for t in tokens)
        match_title   = any(t in judul       for t in tokens)
        match_desc    = any(t in desc         for t in tokens)

        if match_example:
            item["distance"] = 0.04 + original_distance * 0.001
            item["_boost"]   = "contoh_lapangan"
        elif match_title:
            item["distance"] = 0.08 + original_distance * 0.001
            item["_boost"]   = "judul"
        elif match_desc:
            item["distance"] = 0.12 + original_distance * 0.001
            item["_boost"]   = "deskripsi"

        boosted.append(item)

    # Urutkan berdasarkan distance (ascending = lebih relevan)
    boosted.sort(key=lambda x: x.get("distance", 1.0))
    return boosted


# ─────────────────────────────────────────────────────────────────────────────
# Public API
# ─────────────────────────────────────────────────────────────────────────────

def search_expansion(preprocessed: dict, limit: int = None, model: str = None) -> list:
    """
    Hybrid Search menggunakan hasil preprocessing QUERY EXPANSION.
      - Embedding dibuat dari 'clean' (teks asli lebih baik untuk semantik)
      - Keyword tokens diambil dari 'expanded_tokens' (menangkap sinonim)

    Args:
        preprocessed : output dari preprocess_expansion()
        limit        : jumlah maksimum hasil. Default: Settings.DEFAULT_LIMIT
        model        : 'KBLI', 'KBJI', atau None (keduanya)

    Returns:
        list of dict — hasil pencarian yang sudah di-merge dan di-boost
    """
    limit = limit or Settings.DEFAULT_LIMIT

    # ── PRIORITY: Numeric Code Match (Skip Embedding) ─────────────────
    clean_val = preprocessed.get("original", "").strip()
    if clean_val.isdigit():
        conn = get_connection()
        try:
            with conn.cursor() as cur:
                numeric_results = search_numeric_code(cur, clean_val, limit, model)
                if numeric_results:
                    return numeric_results
        finally:
            conn.close()
    base_tokens = preprocessed.get("expanded_tokens", [])
    
    # Siapkan token augmentasi untuk masing-masing model secara terpisah
    kbli_tokens = sorted(list(set(base_tokens + preprocessed.get("kbli_variations", []))))
    kbji_tokens = sorted(list(set(base_tokens + preprocessed.get("kbji_variations", []))))

    embed_text = preprocessed.get("clean", "")
    embedding = _generate_embedding(embed_text)

    semantic_kbli, semantic_kbji = [], []
    keyword_kbli,  keyword_kbji  = [], []

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            if model is None or model == "KBLI":
                if embedding:
                    semantic_kbli = _vector_search(cur, Settings.TABLE_KBLI, embedding, limit)
                keyword_kbli = _keyword_search(cur, Settings.TABLE_KBLI, kbli_tokens, limit)

            if model is None or model == "KBJI":
                if embedding:
                    semantic_kbji = _vector_search(cur, Settings.TABLE_KBJI, embedding, limit)
                keyword_kbji = _keyword_search(cur, Settings.TABLE_KBJI, kbji_tokens, limit)
    finally:
        conn.close()

    semantic = [
        {**r, "_source": "KBLI"} for r in semantic_kbli
    ] + [
        {**r, "_source": "KBJI"} for r in semantic_kbji
    ]
    keyword = [
        {**r, "_source": "KBLI"} for r in keyword_kbli
    ] + [
        {**r, "_source": "KBJI"} for r in keyword_kbji
    ]
    all_tokens = sorted(list(set(kbli_tokens + kbji_tokens)))

    return _merge_and_boost(semantic, keyword, all_tokens)[:limit]


def search_advanced(preprocessed: dict, limit: int = None, model: str = None) -> list:
    """
    Hybrid Search menggunakan hasil preprocessing ADVANCED.
      - Basic preprocessing telah dihapus.
      - Embedding dibuat dari 'stemmed_clean' (lebih bersih)
      - Keyword tokens diambil dari 'stemmed_tokens'

    Args:
        preprocessed : output dari preprocess_advanced()
        limit        : jumlah maksimum hasil. Default: Settings.DEFAULT_LIMIT
        model        : 'KBLI', 'KBJI', atau None (keduanya)

    Returns:
        list of dict — hasil pencarian yang sudah di-merge dan di-boost
    """
    limit = limit or Settings.DEFAULT_LIMIT

    # ── PRIORITY: Numeric Code Match (Skip Embedding) ─────────────────
    clean_val = preprocessed.get("original", "").strip()
    if clean_val.isdigit():
        conn = get_connection()
        try:
            with conn.cursor() as cur:
                numeric_results = search_numeric_code(cur, clean_val, limit, model)
                if numeric_results:
                    return numeric_results
        finally:
            conn.close()
    embed_text = preprocessed.get("stemmed_clean") or preprocessed.get("clean", "")
    tokens     = preprocessed.get("stemmed_tokens") or preprocessed.get("tokens", [])

    embedding = _generate_embedding(embed_text)

    semantic_kbli, semantic_kbji = [], []
    keyword_kbli,  keyword_kbji  = [], []

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            if model is None or model == "KBLI":
                if embedding:
                    semantic_kbli = _vector_search(cur, Settings.TABLE_KBLI, embedding, limit)
                keyword_kbli = _keyword_search(cur, Settings.TABLE_KBLI, tokens, limit)

            if model is None or model == "KBJI":
                if embedding:
                    semantic_kbji = _vector_search(cur, Settings.TABLE_KBJI, embedding, limit)
                keyword_kbji = _keyword_search(cur, Settings.TABLE_KBJI, tokens, limit)
    finally:
        conn.close()

    semantic = [
        {**r, "_source": "KBLI"} for r in semantic_kbli
    ] + [
        {**r, "_source": "KBJI"} for r in semantic_kbji
    ]
    keyword = [
        {**r, "_source": "KBLI"} for r in keyword_kbli
    ] + [
        {**r, "_source": "KBJI"} for r in keyword_kbji
    ]

    return _merge_and_boost(semantic, keyword, tokens)[:limit]


def search_raw(query: str, limit: int = None, model: str = None) -> list:
    """
    Hybrid Search tanpa preprocessing — query digunakan apa adanya (mentah).
      - Embedding dibuat langsung dari raw query
      - Keyword tokens = split whitespace dari raw query (tanpa cleaning)

    Args:
        query : string query langsung dari user (tidak diproses)
        limit : jumlah maksimum hasil. Default: Settings.DEFAULT_LIMIT
        model : 'KBLI', 'KBJI', atau None (keduanya)

    Returns:
        list of dict — hasil pencarian yang sudah di-merge dan di-boost
    """
    limit = limit or Settings.DEFAULT_LIMIT

    # ── PRIORITY: Numeric Code Match (Skip Embedding) ─────────────────
    clean_val = query.strip()
    if clean_val.isdigit():
        conn = get_connection()
        try:
            with conn.cursor() as cur:
                numeric_results = search_numeric_code(cur, clean_val, limit, model)
                if numeric_results:
                    return numeric_results
        finally:
            conn.close()
    if not query:
        return []

    embed_text = query
    tokens     = [t for t in query.split() if t]

    embedding = _generate_embedding(embed_text)

    semantic_kbli, semantic_kbji = [], []
    keyword_kbli,  keyword_kbji  = [], []

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            if model is None or model == "KBLI":
                if embedding:
                    semantic_kbli = _vector_search(cur, Settings.TABLE_KBLI, embedding, limit)
                keyword_kbli = _keyword_search(cur, Settings.TABLE_KBLI, tokens, limit)

            if model is None or model == "KBJI":
                if embedding:
                    semantic_kbji = _vector_search(cur, Settings.TABLE_KBJI, embedding, limit)
                keyword_kbji = _keyword_search(cur, Settings.TABLE_KBJI, tokens, limit)
    finally:
        conn.close()

    semantic = [
        {**r, "_source": "KBLI"} for r in semantic_kbli
    ] + [
        {**r, "_source": "KBJI"} for r in semantic_kbji
    ]
    keyword = [
        {**r, "_source": "KBLI"} for r in keyword_kbli
    ] + [
        {**r, "_source": "KBJI"} for r in keyword_kbji
    ]

    return _merge_and_boost(semantic, keyword, tokens)[:limit]

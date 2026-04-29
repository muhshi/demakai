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

USE_CL = True  # Global flag for toggling contoh_lapangan eval

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

def _keyword_search(cursor, table: str, token_groups: list, limit: int, variations: list = None) -> list:
    """
    SQL LIKE dengan AND logic per grup token.
    Setiap grup (misal: ["pabrik", "industri"]) adalah OR.
    Sedangkan antar grup adalah AND.
    Bisa diekstensi dengan variations sebagai OR fallback di level atas.
    """
    if not token_groups and not variations:
        return []
    variations = variations or []
    token_groups = [g for g in token_groups if g]

    and_conditions = []
    and_params = []
    for group in token_groups:
        group_conds = []
        for token in group:
            pat = f"%{token}%"
            group_conds.append(
                f"(kode ILIKE %s OR judul ILIKE %s OR deskripsi ILIKE %s OR (CAST(contoh_lapangan AS TEXT) ILIKE %s AND {1 if USE_CL else 0}=1))"
            )
            and_params.extend([pat, pat, pat, pat])
        and_conditions.append("(" + " OR ".join(group_conds) + ")")

    if and_conditions:
        and_clause = " AND ".join(and_conditions)
    else:
        and_clause = "1=0"

    if variations:
        var_conds = []
        for var in variations:
            pat = f"%{var}%"
            var_conds.append(
                f"(kode ILIKE %s OR judul ILIKE %s OR deskripsi ILIKE %s OR (CAST(contoh_lapangan AS TEXT) ILIKE %s AND {1 if USE_CL else 0}=1))"
            )
            and_params.extend([pat, pat, pat, pat])
        var_clause = " OR ".join(var_conds)
        combined_clause = f"({and_clause}) OR ({var_clause})" if and_conditions else var_clause
    else:
        combined_clause = and_clause

    if combined_clause and combined_clause != "1=0":
        sql_and = f"SELECT *, 0.05 AS distance FROM {table} WHERE {combined_clause} LIMIT %s"
        cursor.execute(sql_and, and_params + [limit])
        rows = cursor.fetchall()
        if rows:
            return rows

    # ── Step 2: OR fallback (lebih longgar) ───────────────────────────────────
    all_tokens = []
    for group in token_groups:
        all_tokens.extend(group)
    all_tokens.extend(variations)
    all_tokens = list(set(all_tokens))
    
    if not all_tokens:
        return []

    score_expr_parts = []
    or_conds = []
    or_params = []
    for token in all_tokens:
        pat = f"%{token}%"
        score_expr_parts.append(
            "(CASE WHEN kode ILIKE %s OR judul ILIKE %s OR deskripsi ILIKE %s "
            f"OR (CAST(contoh_lapangan AS TEXT) ILIKE %s AND {1 if USE_CL else 0}=1) THEN 1 ELSE 0 END)"
        )
        or_params.extend([pat, pat, pat, pat])
        or_conds.append(
            f"(kode ILIKE %s OR judul ILIKE %s OR deskripsi ILIKE %s OR (CAST(contoh_lapangan AS TEXT) ILIKE %s AND {1 if USE_CL else 0}=1))"
        )
        or_params.extend([pat, pat, pat, pat])

    score_expr = " + ".join(score_expr_parts)
    or_clause  = " OR ".join(or_conds)

    sql_or = f"""
        SELECT *, 0.05 AS distance, ({score_expr}) AS _match_score
        FROM {table}
        WHERE {or_clause}
        ORDER BY _match_score DESC
        LIMIT %s
    """
    cursor.execute(sql_or, or_params + [limit])
    return cursor.fetchall()


# ─────────────────────────────────────────────────────────────────────────────
# Helper: merge + boost results
# ─────────────────────────────────────────────────────────────────────────────

# ── Boost weights (proporsional, bukan override hardcoded) ──────────────────
# Nilai = persen pengurangan distance. Semakin besar = semakin banyak boost.
# Semantic score tetap dominan karena hasilnya proporsional terhadap distance asli.
_BOOST_CONTOH   = 0.20   # contoh_lapangan match  → kurangi 20% dari distance
_BOOST_JUDUL    = 0.12   # judul match             → kurangi 12%
_BOOST_DESKRIPSI = 0.06  # deskripsi match         → kurangi 6%


def _merge_and_boost(semantic: list, keyword: list, tokens: list) -> list:
    """
    Menggabungkan hasil semantic dan keyword, lalu menerapkan boosting proporsional:
      - Match di contoh_lapangan → distance * (1 - 0.20)  [prioritas tertinggi]
      - Match di judul           → distance * (1 - 0.12)  [prioritas menengah]
      - Match di deskripsi saja  → distance * (1 - 0.06)  [prioritas terendah]

    CATATAN: Boost bersifat proporsional — item yang relevan secara semantik
    (distance kecil) tetap unggul atas item yang hanya kebetulan match teks.
    Ini mencegah false-positive dari keyword expansion menyerobot ranking.
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
        boost_factor = 0.0
        boost_label  = None

        # FIX: Gunakan proporsi — bukan override paksa ke nilai tetap.
        # Efek: item relevan semantik (distance=0.15) dengan match CL:
        #   → 0.15 * (1 - 0.20) = 0.12  (naik sedikit)
        # Item tidak relevan (distance=0.65) dengan match CL:
        #   → 0.65 * (1 - 0.20) = 0.52  (masih jauh di bawah item relevan)
        if any(t in contoh_str for t in tokens):
            boost_factor = _BOOST_CONTOH
            boost_label  = "contoh_lapangan"
        elif any(t in judul for t in tokens):
            boost_factor = _BOOST_JUDUL
            boost_label  = "judul"
        elif any(t in desc for t in tokens):
            boost_factor = _BOOST_DESKRIPSI
            boost_label  = "deskripsi"

        if boost_label:
            item["distance"] = max(0.005, original_distance * (1.0 - boost_factor))
            item["_boost"]   = boost_label

        boosted.append(item)

    boosted.sort(key=lambda x: x.get("distance", 1.0))
    return boosted


# ─────────────────────────────────────────────────────────────────────────────
# Public API
# ─────────────────────────────────────────────────────────────────────────────

def search_expansion(preprocessed: dict, limit: int = None, model: str = None) -> list:
    limit = limit or Settings.DEFAULT_LIMIT

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

    token_groups = preprocessed.get("expanded_groups", [])
    kbli_vars = preprocessed.get("kbli_variations", [])
    kbji_vars = preprocessed.get("kbji_variations", [])

    # FIX #1: Embed dari query ORIGINAL (bukan clean/expanded).
    # Model Gemini lebih akurat untuk teks pendek natural.
    # Teks expansion yang panjang menambah noise ke representasi vektor.
    embed_text = preprocessed.get("original", "") or preprocessed.get("clean", "")
    embedding = _generate_embedding(embed_text)

    semantic_kbli, semantic_kbji = [], []
    keyword_kbli,  keyword_kbji  = [], []

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            if model is None or model == "KBLI":
                if embedding:
                    try:
                        semantic_kbli = _vector_search(cur, Settings.TABLE_KBLI, embedding, limit)
                    except Exception as e:
                        print(f"[ERROR] Semantic search KBLI failed: {e}")
                        semantic_kbli = []
                keyword_kbli = _keyword_search(cur, Settings.TABLE_KBLI, token_groups, limit, kbli_vars)

            if model is None or model == "KBJI":
                if embedding:
                    try:
                        semantic_kbji = _vector_search(cur, Settings.TABLE_KBJI, embedding, limit)
                    except Exception as e:
                        print(f"[ERROR] Semantic search KBJI failed: {e}")
                        semantic_kbji = []
                keyword_kbji = _keyword_search(cur, Settings.TABLE_KBJI, token_groups, limit, kbji_vars)
    finally:
        conn.close()

    semantic = [{**r, "_source": "KBLI"} for r in semantic_kbli] + \
               [{**r, "_source": "KBJI"} for r in semantic_kbji]
    keyword = [{**r, "_source": "KBLI"} for r in keyword_kbli] + \
              [{**r, "_source": "KBJI"} for r in keyword_kbji]

    # FIX #2: Gunakan core tokens (token asli tanpa expansion) untuk boost matching.
    # Expanded tokens (25+ kata) menyebabkan terlalu banyak false-positive match
    # di field contoh_lapangan/judul, sehingga item tidak relevan di-boost secara salah.
    # Core tokens (~5 kata) jauh lebih presisi untuk menentukan boost.
    core_tokens = preprocessed.get("tokens", [])

    return _merge_and_boost(semantic, keyword, core_tokens)[:limit]


def search_advanced(preprocessed: dict, limit: int = None, model: str = None) -> list:
    limit = limit or Settings.DEFAULT_LIMIT

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

    # FIX: Embed dari query original — stemmed_clean lebih baik dari expanded,
    # tapi query original paling akurat untuk Gemini embedding.
    embed_text = preprocessed.get("original", "") or preprocessed.get("stemmed_clean") or preprocessed.get("clean", "")
    tokens     = preprocessed.get("stemmed_tokens") or preprocessed.get("tokens", [])
    token_groups = [[t] for t in tokens]

    embedding = _generate_embedding(embed_text)

    semantic_kbli, semantic_kbji = [], []
    keyword_kbli,  keyword_kbji  = [], []

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            if model is None or model == "KBLI":
                if embedding:
                    try:
                        semantic_kbli = _vector_search(cur, Settings.TABLE_KBLI, embedding, limit)
                    except Exception as e:
                        print(f"[ERROR] Semantic search KBLI failed: {e}")
                        semantic_kbli = []
                keyword_kbli = _keyword_search(cur, Settings.TABLE_KBLI, token_groups, limit)

            if model is None or model == "KBJI":
                if embedding:
                    try:
                        semantic_kbji = _vector_search(cur, Settings.TABLE_KBJI, embedding, limit)
                    except Exception as e:
                        print(f"[ERROR] Semantic search KBJI failed: {e}")
                        semantic_kbji = []
                keyword_kbji = _keyword_search(cur, Settings.TABLE_KBJI, token_groups, limit)
    finally:
        conn.close()

    semantic = [{**r, "_source": "KBLI"} for r in semantic_kbli] + \
               [{**r, "_source": "KBJI"} for r in semantic_kbji]
    keyword = [{**r, "_source": "KBLI"} for r in keyword_kbli] + \
              [{**r, "_source": "KBJI"} for r in keyword_kbji]

    # Gunakan stemmed tokens (bukan expanded) — presisi lebih tinggi untuk boost
    return _merge_and_boost(semantic, keyword, tokens)[:limit]


def search_raw(query: str, limit: int = None, model: str = None) -> list:
    limit = limit or Settings.DEFAULT_LIMIT

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

    embed_text   = query
    # Gunakan hanya kata bermakna (bukan stopword pendek) untuk boost matching
    tokens       = [t.lower() for t in query.split() if len(t) > 2]
    token_groups = [[t] for t in tokens]

    embedding = _generate_embedding(embed_text)

    semantic_kbli, semantic_kbji = [], []
    keyword_kbli,  keyword_kbji  = [], []

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            if model is None or model == "KBLI":
                if embedding:
                    try:
                        semantic_kbli = _vector_search(cur, Settings.TABLE_KBLI, embedding, limit)
                    except Exception as e:
                        print(f"[ERROR] Semantic search KBLI failed: {e}")
                        semantic_kbli = []
                keyword_kbli = _keyword_search(cur, Settings.TABLE_KBLI, token_groups, limit)

            if model is None or model == "KBJI":
                if embedding:
                    try:
                        semantic_kbji = _vector_search(cur, Settings.TABLE_KBJI, embedding, limit)
                    except Exception as e:
                        print(f"[ERROR] Semantic search KBJI failed: {e}")
                        semantic_kbji = []
                keyword_kbji = _keyword_search(cur, Settings.TABLE_KBJI, token_groups, limit)
    finally:
        conn.close()

    semantic = [{**r, "_source": "KBLI"} for r in semantic_kbli] + \
               [{**r, "_source": "KBJI"} for r in semantic_kbji]
    keyword = [{**r, "_source": "KBLI"} for r in keyword_kbli] + \
              [{**r, "_source": "KBJI"} for r in keyword_kbji]

    return _merge_and_boost(semantic, keyword, tokens)[:limit]

"""
search/utils.py — Utility functions for search modules.
"""

from config.settings import Settings

def search_numeric_code(cursor, query: str, limit: int, model: str = None) -> list:
    """
    Search for items by 'kode' with priority: 
    1. Exact Match (kode = query)
    2. Prefix Match (kode LIKE query%)
    
    Returns list of dicts if found, otherwise an empty list.
    """
    if not query.isdigit():
        return []

    tables = []
    if model == "KBLI":
        tables = [Settings.TABLE_KBLI]
    elif model == "KBJI":
        tables = [Settings.TABLE_KBJI]
    else:
        # Defaults to both
        tables = [Settings.TABLE_KBLI, Settings.TABLE_KBJI]

    results = []
    
    # ── Step 1: Exact Match ──────────────────────────────────────────────────
    for table in tables:
        sql_exact = f"SELECT * FROM {table} WHERE kode = %s LIMIT %s"
        cursor.execute(sql_exact, (query, limit))
        rows = cursor.fetchall()
        source = "KBLI" if table == Settings.TABLE_KBLI else "KBJI"
        results.extend([{**r, "_source": source, "_boost": "exact_code", "distance": 0.0} for r in rows])

    if results:
        return results[:limit]

    # ── Step 2: Prefix Match ─────────────────────────────────────────────────
    for table in tables:
        sql_prefix = f"SELECT * FROM {table} WHERE kode LIKE %s ORDER BY kode ASC LIMIT %s"
        cursor.execute(sql_prefix, (f"{query}%", limit))
        rows = cursor.fetchall()
        source = "KBLI" if table == Settings.TABLE_KBLI else "KBJI"
        results.extend([{**r, "_source": source, "_boost": "prefix_code", "distance": 0.01} for r in rows])

    return results[:limit]

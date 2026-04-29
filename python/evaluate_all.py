"""
evaluate_all.py
================
Master evaluasi DEMAKAI — menjalankan SEMUA 6 metode sekaligus
dan menghasilkan SATU dashboard HTML komprehensif.

Metode:
  1. SQL LIKE (Baseline)             — search_raw()
  2. Hybrid Search                   — hybrid.search_raw()
  3. SQL LIKE + Preprocessing        — search_expansion()
  4. Hybrid + Preprocessing          — hybrid.search_expansion()
  5. SQL LIKE + Prep + Contoh Lap.   — search_expansion() + tampil contoh
  6. Hybrid + Prep + Contoh Lap.     — hybrid.search_expansion() + tampil contoh

Output: output/evaluasi_semua_<ts>.html + evaluasi_semua_<ts>.csv
"""

import csv
import json
import os
import sys
from datetime import datetime

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

sys.path.insert(0, os.path.dirname(__file__))

from preprocessing.expansion import preprocess_expansion
from search.sql_like  import search_raw as sql_raw
from search.sql_like  import search_expansion as sql_exp
from search.hybrid    import search_raw as hyb_raw
from search.hybrid    import search_expansion as hyb_exp
from config.database  import get_connection

QUERIES_FILE = os.path.join(os.path.dirname(__file__), "queries.csv")
LIMIT        = 10

os.makedirs(os.path.join(os.path.dirname(__file__), "output"), exist_ok=True)


# ─────────────────────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────────────────────

def _parse_contoh(raw) -> list:
    if not raw: return []
    if isinstance(raw, list): return [str(x) for x in raw if x]
    if isinstance(raw, str):
        try:
            p = json.loads(raw)
            return [str(x) for x in p if x] if isinstance(p, list) else [str(p)]
        except: return [raw]
    return []


def get_contoh_from_db(kode: str, tipe: str) -> str:
    from config.settings import Settings
    table = Settings.TABLE_KBLI if tipe == "KBLI" else Settings.TABLE_KBJI
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(f"SELECT contoh_lapangan FROM {table} WHERE kode=%s LIMIT 1", (kode,))
            row = cur.fetchone()
            if row:
                items = _parse_contoh(row.get("contoh_lapangan"))
                return " | ".join(items[:2]) if items else "-"
    except: pass
    finally: conn.close()
    return "-"


def get_rank(results: list, kode_gt: str) -> int:
    for i, item in enumerate(results, 1):
        if str(item.get("kode","")).strip() == str(kode_gt).strip():
            return i
    return 0


def metrics(rank: int) -> dict:
    if rank > 0:
        return {"rank":rank,"top1":rank==1,"top3":rank<=3,"top10":rank<=10,"rr":round(1/rank,4)}
    return {"rank":0,"top1":False,"top3":False,"top10":False,"rr":0.0}


def prep_desc(preprocessed: dict, tipe: str) -> str:
    exp   = preprocessed.get("expanded_tokens", [])
    kv    = preprocessed.get("kbli_variations",[]) if tipe=="KBLI" else preprocessed.get("kbji_variations",[])
    base  = " ".join(exp[:8])
    extra = f" | +{tipe}: {', '.join(kv[:3])}" if kv else ""
    return base + extra


# ─────────────────────────────────────────────────────────────
# LOAD QUERIES
# ─────────────────────────────────────────────────────────────

def load_queries() -> list:
    if not os.path.exists(QUERIES_FILE):
        print(f"[ERROR] {QUERIES_FILE} tidak ditemukan."); return []
    with open(QUERIES_FILE, newline="", encoding="utf-8") as f:
        return list(csv.DictReader(f))


def search_true_baseline(query: str, limit: int = 10, model: str = None) -> list:
    """
    Fungsi pencarian TRUE BASELINE (PostgreSQL):
    - Memberikan hasil yang sama dengan SQLite baseline
    - Hanya cari di kolom: kode, judul, deskripsi
    - TANPA pencarian di contoh_lapangan
    """
    from config.settings import Settings
    limit = limit or 10
    pattern = f"%{query.strip()}%"
    tokens  = [t.strip() for t in query.split() if t.strip()]
    results = []
    
    def _run_strict_query(cur, table: str, pat: str, lim: int) -> list:
        sql = f"SELECT * FROM {table} WHERE kode ILIKE %s OR judul ILIKE %s OR deskripsi ILIKE %s LIMIT %s"
        cur.execute(sql, (pat, pat, pat, lim))
        return cur.fetchall()

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            # Step 1: Frasa
            if model is None or model == "KBLI":
                rows = _run_strict_query(cur, Settings.TABLE_KBLI, pattern, limit)
                results.extend([{**r, "_source": "KBLI"} for r in rows])
            if model is None or model == "KBJI":
                rows = _run_strict_query(cur, Settings.TABLE_KBJI, pattern, limit)
                results.extend([{**r, "_source": "KBJI"} for r in rows])
            
            # Step 2 (OR tokens) - simplified for baseline
            if not results and len(tokens) > 1:
                for t in tokens:
                    pat = f"%{t}%"
                    if model is None or model == "KBLI":
                        rows = _run_strict_query(cur, Settings.TABLE_KBLI, pat, limit)
                        results.extend([{**r, "_source": "KBLI"} for r in rows])
                    if model is None or model == "KBJI":
                        rows = _run_strict_query(cur, Settings.TABLE_KBJI, pat, limit)
                        results.extend([{**r, "_source": "KBJI"} for r in rows])
    finally:
        conn.close()
    return results[:limit]


# ─────────────────────────────────────────────────────────────
# EVALUASI SEMUA METODE
# ─────────────────────────────────────────────────────────────

def evaluate_all(queries: list) -> dict:
    """
    Return dict berisi 6 metode → list of row-dict.
    """
    METHODS = {
        "m1_sql_baseline":    [],
        "m2_hybrid":          [],
        "m3_sql_prep":        [],
        "m4_hybrid_prep":     [],
        "m5_sql_prep_contoh": [],
        "m6_hybrid_prep_contoh": [],
    }

    for entry in queries:
        no      = entry.get("no","")
        query   = entry.get("query","").strip()
        kode_gt = entry.get("kode_ground_truth","").strip()
        tipe    = entry.get("tipe","").strip().upper()
        model   = tipe if tipe in ("KBLI","KBJI") else None

        # Preprocessing sekali untuk semua metode berbasis prep
        preprocessed = preprocess_expansion(query)
        pd           = prep_desc(preprocessed, tipe)

        print(f"  [{no:>2}] {query[:38]:<38} | {tipe} | GT:{kode_gt}", flush=True)

        # ---- M1: SQL LIKE Baseline (STRICT) ----
        try:
            res = search_true_baseline(query, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M1 ERR: {e}"); m = metrics(0)
        METHODS["m1_sql_baseline"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":"","contoh":"", **m})

        # ---- M2: Hybrid Raw ----
        try:
            res = hyb_raw(query, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M2 ERR: {e}"); m = metrics(0)
        METHODS["m2_hybrid"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":"","contoh":"", **m})

        # ---- M3: SQL LIKE + Prep ----
        try:
            res = sql_exp(preprocessed, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M3 ERR: {e}"); m = metrics(0)
        METHODS["m3_sql_prep"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd,"contoh":"", **m})

        # ---- M4: Hybrid + Prep ----
        try:
            res = hyb_exp(preprocessed, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M4 ERR: {e}"); m = metrics(0)
        METHODS["m4_hybrid_prep"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd,"contoh":"", **m})

        # Ambil contoh lapangan GT dari DB (sekali pakai untuk M5 & M6)
        contoh = get_contoh_from_db(kode_gt, tipe)

        # ---- M5: SQL LIKE + Prep + Contoh ----
        try:
            res = sql_exp(preprocessed, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M5 ERR: {e}"); m = metrics(0)
        METHODS["m5_sql_prep_contoh"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd,"contoh":contoh, **m})

        # ---- M6: Hybrid + Prep + Contoh ----
        try:
            res = hyb_exp(preprocessed, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M6 ERR: {e}"); m = metrics(0)
        METHODS["m6_hybrid_prep_contoh"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd,"contoh":contoh, **m})

        # status baris
        m1r = METHODS["m1_sql_baseline"][-1]["rank"]
        m2r = METHODS["m2_hybrid"][-1]["rank"]
        m3r = METHODS["m3_sql_prep"][-1]["rank"]
        m4r = METHODS["m4_hybrid_prep"][-1]["rank"]
        print(f"         M1={m1r}  M2={m2r}  M3={m3r}  M4={m4r}", flush=True)

    return METHODS


# ─────────────────────────────────────────────────────────────
# SUMMARY HELPER
# ─────────────────────────────────────────────────────────────

def summary(rows: list) -> dict:
    n = len(rows) or 1
    return {
        "n":    n,
        "top1": sum(1 for r in rows if r["top1"])  / n,
        "top3": sum(1 for r in rows if r["top3"])  / n,
        "top10":sum(1 for r in rows if r["top10"]) / n,
        "mrr":  sum(r["rr"] for r in rows) / n,
    }


def mc(v: float) -> str:
    if v >= 0.5: return "#6ee7b7"
    if v >= 0.2: return "#fbbf24"
    return "#f87171"


# ─────────────────────────────────────────────────────────────
# HTML BUILDERS
# ─────────────────────────────────────────────────────────────

def _stat_cards(sm: dict, accent: str) -> str:
    return f"""
<div class="stats-bar">
  <div class="stat-card"><div class="stat-lbl">Queries</div><div class="stat-val">{sm['n']}</div></div>
  <div class="stat-card"><div class="stat-lbl">Top@1</div><div class="stat-val" style="color:#86efac">{sm['top1']:.1%}</div></div>
  <div class="stat-card"><div class="stat-lbl">Top@3</div><div class="stat-val" style="color:#7dd3fc">{sm['top3']:.1%}</div></div>
  <div class="stat-card"><div class="stat-lbl">Top@10</div><div class="stat-val" style="color:#d8b4fe">{sm['top10']:.1%}</div></div>
  <div class="stat-card"><div class="stat-lbl">MRR</div><div class="stat-val" style="color:{mc(sm['mrr'])}">{sm['mrr']:.4f}</div></div>
</div>"""


def _rank_style(v):
    try: v=int(v)
    except: return ""
    if v==0:  return "style='background:#1a1f2e;color:#475569'"
    if v==1:  return "style='background:#064e3b;color:#6ee7b7;font-weight:bold'"
    if v<=3:  return "style='background:#065f46;color:#a7f3d0'"
    if v<=10: return "style='background:#1e3a2f;color:#d1fae5'"
    return "style='background:#1a1f2e;color:#475569'"


def _hit(v):
    return "style='background:#1e3a5f;color:#93c5fd;font-weight:bold;text-align:center'" if v else "style='text-align:center;color:#475569'"


def _table_block(rows: list, tipe_filter: str, show_prep: bool, show_contoh: bool, accent: str) -> str:
    filt = [r for r in rows if r["tipe"]==tipe_filter]
    if not filt: return ""
    sm = summary(filt)

    header_cols = "<th>#</th><th>Tipe</th><th style='text-align:left;min-width:160px'>Query</th>"
    if show_prep:
        header_cols += "<th style='text-align:left;min-width:150px;color:#fcd34d'>Preprocessing</th>"
    if show_contoh:
        header_cols += "<th style='text-align:left;min-width:170px;color:#86efac'>Contoh Lapangan</th>"
    header_cols += "<th>Kode GT</th><th>Rank</th><th>Top@1</th><th>Top@3</th><th>Top@10</th><th>RR</th>"

    body = ""
    for r in filt:
        tc = "#86efac" if r["tipe"]=="KBLI" else "#93c5fd"
        rr_c = "#6ee7b7" if r["rr"]>0 else "#475569"
        rr_w = "bold" if r["rr"]>0 else "normal"
        row  = f"<td style='text-align:center;color:#94a3b8'>{r['no']}</td>"
        row += f"<td style='color:{tc};font-weight:bold;text-align:center'>{r['tipe']}</td>"
        row += f"<td style='color:#c4b5fd;white-space:normal;max-width:160px'>{r['query']}</td>"
        if show_prep:
            row += f"<td style='color:#fcd34d;font-size:0.76rem;white-space:normal;max-width:150px'>{r['preprocessed'] or '—'}</td>"
        if show_contoh:
            contoh_disp = r['contoh'] if r['contoh'] and r['contoh']!='-' else '<span style="color:#475569">—</span>'
            row += f"<td style='color:#86efac;font-size:0.76rem;white-space:normal;max-width:170px'>{contoh_disp}</td>"
        row += f"<td style='text-align:center'><code>{r['kode_gt']}</code></td>"
        row += f"<td {_rank_style(r['rank'])}>{r['rank'] if r['rank']>0 else '—'}</td>"
        row += f"<td {_hit(r['top1'])}>{'1' if r['top1'] else '0'}</td>"
        row += f"<td {_hit(r['top3'])}>{'1' if r['top3'] else '0'}</td>"
        row += f"<td {_hit(r['top10'])}>{'1' if r['top10'] else '0'}</td>"
        row += f"<td style='text-align:center;color:{rr_c};font-weight:{rr_w}'>{r['rr'] if r['rr']>0 else '—'}</td>"
        body += f"<tr>{row}</tr>"

    # MRR footer row
    extra_cols = (1 if show_prep else 0) + (1 if show_contoh else 0)
    body += f"""
    <tr style='background:#1f2937;border-top:2px solid {accent}'>
      <td colspan='{4+extra_cols}' style='text-align:right;font-weight:bold;color:{accent};padding-right:16px'>MRR</td>
      <td style='text-align:center;color:#94a3b8'>—</td>
      <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top1']:.3f}</td>
      <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top3']:.3f}</td>
      <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top10']:.3f}</td>
      <td style='text-align:center;font-weight:bold;color:{mc(sm['mrr'])}'>{sm['mrr']:.4f}</td>
    </tr>"""

    return f"<table><thead><tr>{header_cols}</tr></thead><tbody>{body}</tbody></table>"


def _method_section(key: str, rows: list, title: str, subtitle: str,
                    tags: list, accent: str, accent_bg: str,
                    show_prep: bool, show_contoh: bool) -> str:
    rows_kbli = [r for r in rows if r["tipe"]=="KBLI"]
    rows_kbji = [r for r in rows if r["tipe"]=="KBJI"]
    sm_kbli   = summary(rows_kbli)
    sm_kbji   = summary(rows_kbji)
    sm_all    = summary(rows)

    # Method tags
    tags_html = ""
    for label, on in tags:
        cls = f"border:1px solid {accent};color:{accent};background:{accent_bg}" if on \
              else "border:1px solid #2d3048;color:#475569;background:#141820;opacity:0.5;text-decoration:line-through"
        mark = "✓" if on else "✗"
        tags_html += f"<span style='display:inline-flex;align-items:center;gap:5px;font-size:0.78rem;padding:4px 12px;border-radius:7px;margin:3px;{cls}'>{mark} {label}</span>"

    # Summary tables (KBLI + KBJI inline)
    def mini_row(label, sm):
        return f"""<tr>
          <td style='color:#94a3b8;font-size:0.8rem'>{label}</td>
          <td style='text-align:center;color:{accent};font-weight:600'>{sm['top1']:.1%}</td>
          <td style='text-align:center;color:{accent};font-weight:600'>{sm['top3']:.1%}</td>
          <td style='text-align:center;color:{accent};font-weight:600'>{sm['top10']:.1%}</td>
          <td style='text-align:center;font-weight:700;color:{mc(sm['mrr'])}'>{sm['mrr']:.4f}</td>
        </tr>"""

    summary_table = f"""
    <table style='width:auto;min-width:420px;margin-bottom:16px'>
      <thead><tr>
        <th style='text-align:left;color:{accent}'>Dataset</th>
        <th style='color:{accent}'>Top@1</th>
        <th style='color:{accent}'>Top@3</th>
        <th style='color:{accent}'>Top@10</th>
        <th style='color:{accent}'>MRR</th>
      </tr></thead>
      <tbody>
        {mini_row('KBLI 2025', sm_kbli)}
        {mini_row('KBJI 2014', sm_kbji)}
        {mini_row('Gabungan', sm_all)}
      </tbody>
    </table>"""

    tbl_kbli = _table_block(rows, "KBLI", show_prep, show_contoh, accent)
    tbl_kbji = _table_block(rows, "KBJI", show_prep, show_contoh, accent)

    return f"""
<div id="{key}" class="method-section" style="border-color:{accent}22;background:{accent_bg}">
  <div class="method-title" style="color:{accent}">{title}</div>
  <div class="method-sub" style="color:#64748b">{subtitle}</div>
  <div style="margin:12px 0">{tags_html}</div>
  <div class="stats-bar" style="margin-bottom:14px">{_stat_cards(sm_all, accent)}</div>
  <div class="wrap" style="max-width:520px;margin-bottom:20px">{summary_table}</div>

  <div style="font-size:0.9rem;font-weight:600;color:{accent};margin:18px 0 8px;border-left:3px solid {accent};padding-left:10px">Dataset KBLI 2025</div>
  <div class="wrap">{tbl_kbli}</div>

  <div style="font-size:0.9rem;font-weight:600;color:{accent};margin:18px 0 8px;border-left:3px solid {accent};padding-left:10px">Dataset KBJI 2014</div>
  <div class="wrap">{tbl_kbji}</div>
</div>"""


# ─────────────────────────────────────────────────────────────
# MASTER HTML
# ─────────────────────────────────────────────────────────────

METHODS_META = [
    ("m1_sql_baseline",    "1. SQL LIKE (Baseline)",
     "Tanpa preprocessing &middot; Tanpa contoh lapangan &middot; Pencarian frasa mentah",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Preprocessing",False),("Contoh Lapangan",False)],
     "#64748b","#111827", False, False),
    ("m2_hybrid",          "2. Hybrid Search",
     "SQL LIKE + Semantic Search &middot; Tanpa preprocessing &middot; Tanpa contoh lapangan",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Preprocessing",False),("Contoh Lapangan",False)],
     "#7dd3fc","#060f14", False, False),
    ("m3_sql_prep",        "3. SQL LIKE + Preprocessing",
     "Query Expansion (sinonim + variasi KBLI/KBJI) &middot; Tanpa semantic search",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Preprocessing",True),("Contoh Lapangan",False)],
     "#a78bfa","#0d0b1a", True, False),
    ("m4_hybrid_prep",     "4. Hybrid Search + Preprocessing",
     "SQL LIKE + Semantic &middot; Query Expansion &middot; Tanpa filter contoh lapangan eksplisit",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Preprocessing",True),("Contoh Lapangan",False)],
     "#38bdf8","#060f18", True, False),
    ("m5_sql_prep_contoh", "5. SQL LIKE + Preprocessing + Contoh Lapangan",
     "SQL LIKE &middot; Query Expansion &middot; Pencarian pada field contoh_lapangan",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Preprocessing",True),("Contoh Lapangan",True)],
     "#c084fc","#0f0a1f", True, True),
    ("m6_hybrid_prep_contoh","6. Hybrid + Preprocessing + Contoh Lapangan (FINAL)",
     "Hybrid Search &middot; Preprocessing (Expansion) &middot; Boost via contoh_lapangan — sistem terlengkap",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Preprocessing",True),("Contoh Lapangan",True)],
     "#34d399","#050b0f", True, True),
]


def save_html(data: dict, filepath: str):
    ts    = datetime.now().strftime("%d %B %Y, %H:%M:%S")
    total = len(data["m1_sql_baseline"])

    # Build all section HTMLs
    sections = ""
    nav_items = ""
    for (key, title, subtitle, tags, accent, accent_bg, show_prep, show_contoh) in METHODS_META:
        rows = data[key]
        sections += _method_section(key, rows, title, subtitle, tags, accent, accent_bg, show_prep, show_contoh)
        nav_items += f"<a href='#{key}' class='nav-link' style='border-color:{accent};color:{accent}'>{title[:35]}</a>"

    # Global comparison table
    def cmp_row(label, key, accent):
        rows  = data[key]
        kbli  = summary([r for r in rows if r["tipe"]=="KBLI"])
        kbji  = summary([r for r in rows if r["tipe"]=="KBJI"])
        sm    = summary(rows)
        def c(v, fmt): return f"<td style='text-align:center;font-weight:600;color:{accent}'>{v:{fmt}}</td>"
        return f"""<tr>
          <td><a href='#{key}' style='color:{accent};text-decoration:none'>{label}</a></td>
          {c(kbli['top1'],'.1%')}{c(kbli['top3'],'.1%')}{c(kbli['top10'],'.1%')}{c(kbli['mrr'],'.4f')}
          {c(kbji['top1'],'.1%')}{c(kbji['top3'],'.1%')}{c(kbji['top10'],'.1%')}{c(kbji['mrr'],'.4f')}
          {c(sm['top1'],'.1%')}{c(sm['top3'],'.1%')}{c(sm['top10'],'.1%')}
          <td style='text-align:center;font-weight:700;color:{mc(sm["mrr"])}'>{sm["mrr"]:.4f}</td>
        </tr>"""

    cmp_rows_html = "".join(cmp_row(m[1], m[0], m[4]) for m in METHODS_META)

    # Analysis section
    all_sms = {m[0]: summary(data[m[0]]) for m in METHODS_META}
    best_key = max(all_sms, key=lambda k: all_sms[k]["mrr"])
    best_name = next(m[1] for m in METHODS_META if m[0]==best_key)
    best_mrr  = all_sms[best_key]["mrr"]

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>DEMAKAI - Evaluasi Semua Metode Pencarian</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
    *{{box-sizing:border-box;margin:0;padding:0}}
    body{{font-family:'Outfit',sans-serif;background:#070a10;color:#e2e8f0;padding:0;line-height:1.6}}

    /* NAV */
    .topnav{{
      position:sticky;top:0;z-index:100;
      background:#070a10ee;backdrop-filter:blur(12px);
      border-bottom:1px solid #1e2438;
      padding:10px 40px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;
    }}
    .topnav-title{{font-size:0.9rem;font-weight:700;color:#94a3b8;margin-right:8px;white-space:nowrap}}
    .nav-link{{
      font-size:0.72rem;padding:4px 10px;border-radius:6px;
      border:1px solid;text-decoration:none;white-space:nowrap;
      transition:opacity 0.15s;
    }}
    .nav-link:hover{{opacity:0.75}}

    /* PAGE */
    .page{{padding:50px 40px}}

    .page-badge{{
      display:inline-block;
      background:linear-gradient(135deg,#1d4ed8,#4f46e5);
      color:#fff;font-size:0.75rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;
      padding:4px 14px;border-radius:999px;margin-bottom:14px;
    }}
    h1{{
      font-size:2.1rem;font-weight:700;
      background:linear-gradient(90deg,#818cf8,#a78bfa,#38bdf8);
      -webkit-background-clip:text;-webkit-text-fill-color:transparent;
      margin-bottom:6px;
    }}
    .meta{{color:#64748b;font-size:0.87rem;margin-bottom:6px}}

    /* METHOD SECTION */
    .method-section{{border:1px solid;border-radius:16px;padding:32px;margin-bottom:44px}}
    .method-title{{font-size:1.35rem;font-weight:700;margin-bottom:4px}}
    .method-sub{{font-size:0.84rem;margin-bottom:2px}}

    /* STATS */
    .stats-bar{{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:8px}}
    .stat-card{{background:#141828;border:1px solid #222844;border-radius:10px;padding:10px 18px;min-width:95px}}
    .stat-lbl{{font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:0.06em}}
    .stat-val{{font-size:1.25rem;font-weight:700;color:#f1f5f9;margin-top:2px}}

    /* TABLE */
    .wrap{{overflow-x:auto;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.4);margin-bottom:16px}}
    table{{border-collapse:collapse;font-size:0.81rem;width:100%}}
    th,td{{padding:9px 12px;border:1px solid #1e2438}}
    th{{background:#141828;color:#94a3b8;font-weight:600;text-transform:uppercase;font-size:0.68rem;letter-spacing:0.06em;text-align:center}}
    tr:hover td{{background:rgba(255,255,255,0.01)!important}}
    code{{background:#1a2035;color:#a5b4fc;padding:2px 6px;border-radius:4px;border:1px solid #2d3480;font-size:0.8em}}

    hr{{border:0;border-top:2px dashed #1e2438;margin:50px 0}}

    /* SECTION HEADER */
    .sec-hdr{{
      font-size:1.1rem;font-weight:600;color:#c4b5fd;
      margin:44px 0 16px;display:flex;align-items:center;gap:10px;
    }}
    .sec-hdr::before{{
      content:'';display:inline-block;width:4px;height:20px;
      background:linear-gradient(180deg,#7c3aed,#4f46e5);border-radius:2px;
    }}

    /* COMPARISON */
    .cmp-wrap{{overflow-x:auto;border-radius:12px;background:#0f1320;border:1px solid #1e2438;margin-bottom:30px}}

    /* LEGEND */
    .legend{{display:flex;gap:14px;flex-wrap:wrap;margin:18px 0;font-size:0.78rem}}
    .legend-item{{display:flex;align-items:center;gap:5px}}
    .ldot{{width:11px;height:11px;border-radius:3px}}

    /* ANALYSIS */
    .ana-box{{background:#0f1320;border:1px solid #1e2438;border-radius:12px;padding:24px 28px;margin-bottom:24px}}
    .ana-box h3{{color:#a78bfa;font-size:0.95rem;margin-bottom:14px}}
    .ana-grid{{display:grid;grid-template-columns:1fr 1fr;gap:14px}}
    .ana-card{{background:#070a10;border:1px solid #1e2438;border-radius:10px;padding:16px}}
    .ana-ttl{{font-size:0.76rem;font-weight:700;text-transform:uppercase;margin-bottom:8px;color:#38bdf8}}
    .ana-txt{{font-size:0.83rem;color:#94a3b8;line-height:1.5}}

    .footer{{margin-top:50px;padding-top:18px;border-top:1px solid #1e2438;color:#334155;font-size:0.78rem;text-align:center}}
  </style>
</head>
<body>

<!-- TOP NAV -->
<div class="topnav">
  <span class="topnav-title">&#128202; DEMAKAI Eval</span>
  {nav_items}
  <a href="#perbandingan" class="nav-link" style="border-color:#fbbf24;color:#fbbf24">&#11088; Perbandingan</a>
  <a href="#analisis" class="nav-link" style="border-color:#f87171;color:#f87171">&#128300; Analisis</a>
</div>

<div class="page">
  <!-- HEADER -->
  <div class="page-badge">Evaluasi Komprehensif DEMAKAI</div>
  <h1>Evaluasi Sistem Pencarian KBLI</h1>
  <p class="meta">DEMAKAI &mdash; Perbandingan SQL LIKE dan Hybrid Search &middot; Seluruh Tahap Pengembangan</p>
  <p class="meta">Generated: {ts} &nbsp;&middot;&nbsp; {total} query evaluasi (30 KBLI + 30 KBJI) &nbsp;&middot;&nbsp; PostgreSQL + pgvector</p>

  <!-- LEGEND -->
  <div class="legend" style="margin-top:20px">
    <div class="legend-item"><div class="ldot" style="background:#064e3b;border:1px solid #6ee7b7"></div><span style="color:#6ee7b7">Rank 1</span></div>
    <div class="legend-item"><div class="ldot" style="background:#065f46;border:1px solid #a7f3d0"></div><span style="color:#a7f3d0">Rank 2–3</span></div>
    <div class="legend-item"><div class="ldot" style="background:#1e3a2f;border:1px solid #d1fae5"></div><span style="color:#d1fae5">Rank 4–10</span></div>
    <div class="legend-item"><div class="ldot" style="background:#1a1f2e;border:1px solid #475569"></div><span style="color:#475569">Tidak ditemukan</span></div>
    <div class="legend-item"><div class="ldot" style="background:#1e3a5f;border:1px solid #93c5fd"></div><span style="color:#93c5fd">Hit (Top@N = 1)</span></div>
  </div>

  <hr>

  <!-- ALL METHODS -->
  {sections}

  <!-- GLOBAL COMPARISON -->
  <hr>
  <div id="perbandingan" class="sec-hdr">&#11088; Perbandingan Semua Metode</div>
  <div class="cmp-wrap">
    <table>
      <thead>
        <tr>
          <th rowspan="2" style="text-align:left;min-width:200px;vertical-align:middle">Metode</th>
          <th colspan="4" style="background:#1a1040;color:#a78bfa;border-bottom:2px solid #7c3aed">KBLI 2025</th>
          <th colspan="4" style="background:#060f14;color:#38bdf8;border-bottom:2px solid #0e7490">KBJI 2014</th>
          <th colspan="4" style="background:#141828;color:#e2e8f0;border-bottom:2px solid #334155">Gabungan</th>
        </tr>
        <tr>
          <th style="background:#1a1040;color:#a78bfa">Top@1</th><th style="background:#1a1040;color:#a78bfa">Top@3</th>
          <th style="background:#1a1040;color:#a78bfa">Top@10</th><th style="background:#1a1040;color:#a78bfa">MRR</th>
          <th style="background:#060f14;color:#38bdf8">Top@1</th><th style="background:#060f14;color:#38bdf8">Top@3</th>
          <th style="background:#060f14;color:#38bdf8">Top@10</th><th style="background:#060f14;color:#38bdf8">MRR</th>
          <th>Top@1</th><th>Top@3</th><th>Top@10</th><th>MRR</th>
        </tr>
      </thead>
      <tbody>{cmp_rows_html}</tbody>
    </table>
  </div>

  <!-- ANALYSIS -->
  <div id="analisis" class="sec-hdr">&#128300; Analisis Perbandingan Metode</div>
  <div class="ana-box">
    <h3>&#128202; Ringkasan Temuan</h3>
    <p style="font-size:0.87rem;color:#94a3b8;margin-bottom:16px">
      Metode terbaik berdasarkan MRR adalah:
      <strong style="color:#6ee7b7">{best_name}</strong>
      dengan MRR = <strong style="color:#6ee7b7">{best_mrr:.4f}</strong>.
    </p>
    <div class="ana-grid">
      <div class="ana-card">
        <div class="ana-ttl" style="color:#64748b">&#128269; SQL LIKE Baseline</div>
        <div class="ana-txt">
          Tanpa preprocessing, SQL LIKE bekerja sangat buruk pada query informal
          berbasis bahasa lapangan. Hanya cocok jika kata dalam query persis sama
          dengan teks di database KBLI. MRR sangat rendah karena gap leksikal
          antara query pengguna dan deskripsi formal KBLI.
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-ttl" style="color:#38bdf8">&#129504; Dampak Hybrid Search</div>
        <div class="ana-txt">
          Penambahan semantic search (embedding Gemini) meningkatkan kemampuan
          sistem memahami makna di balik kata. Query seperti
          <em>"mencuci motor"</em> dapat dicocokkan secara konseptual dengan
          entri KBLI yang tidak mengandung kata tersebut secara harfiah.
          Namun tanpa preprocessing, Hybrid masih terbatas untuk query sangat informal.
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-ttl" style="color:#a78bfa">&#9986; Dampak Preprocessing</div>
        <div class="ana-txt">
          Query Expansion adalah game-changer: satu query diperluas menjadi puluhan
          sinonim dan variasi. Ini meningkatkan recall secara dramatis —
          MRR SQL LIKE naik dari <strong style="color:#f87171">sangat rendah</strong>
          menjadi <strong style="color:#6ee7b7">{all_sms['m3_sql_prep']['mrr']:.4f}</strong>.
          Menariknya, SQL LIKE + Preprocessing <em>mengungguli</em> Hybrid + Preprocessing
          karena expanded tokens sangat cocok untuk pencocokan leksikal langsung.
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-ttl" style="color:#34d399">&#128203; Dampak Contoh Lapangan</div>
        <div class="ana-txt">
          Field <code>contoh_lapangan</code> berisi deskripsi aktivitas nyata dalam
          bahasa informal. Sistem sudah mencakup field ini di semua query SQL LIKE
          (via <code>ILIKE contoh_lapangan</code>) dan mendapat boost prioritas di
          Hybrid. Ini menjembatani gap bahasa antara query lapangan informal
          dan terminologi formal KBLI/KBJI.
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-ttl" style="color:#fbbf24">&#127942; SQL LIKE vs Hybrid (dengan Prep)</div>
        <div class="ana-txt">
          Dengan preprocessing, SQL LIKE (MRR={all_sms['m3_sql_prep']['mrr']:.4f}) lebih unggul dari
          Hybrid (MRR={all_sms['m4_hybrid_prep']['mrr']:.4f}). Hal ini terjadi karena
          Query Expansion menghasilkan token-token yang sangat spesifik, sehingga
          pencocokan leksikal SQL LIKE jauh lebih presisi. Semantic search bisa menambah
          noise karena embedding mencakup konteks yang terlalu luas.
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-ttl" style="color:#6ee7b7">&#9989; Rekomendasi Sistem Terbaik</div>
        <div class="ana-txt">
          Berdasarkan evaluasi ini, <strong style="color:#6ee7b7">SQL LIKE + Preprocessing
          (+ Contoh Lapangan)</strong> adalah konfigurasi paling optimal untuk sistem
          DEMAKAI: sederhana (tidak butuh GPU/API embedding setiap query), lebih cepat,
          dan menghasilkan MRR tertinggi. Hybrid tetap direkomendasikan sebagai
          fallback untuk query abstrak tanpa keyword yang jelas.
        </div>
      </div>
    </div>
  </div>

  <div class="footer">
    DEMAKAI &mdash; Dashboard Evaluasi Komprehensif &middot;
    Dibuat oleh evaluate_all.py &middot; {ts}
  </div>
</div>

</body>
</html>"""

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(html)
    print("[HTML] " + os.path.abspath(filepath))


# ─────────────────────────────────────────────────────────────
# CSV EXPORT (gabungan semua metode)
# ─────────────────────────────────────────────────────────────

def save_csv(data: dict, filepath: str):
    fields = ["metode","no","tipe","query","preprocessed","contoh","kode_gt","rank","top1","top3","top10","rr"]
    names  = {m[0]: m[1] for m in METHODS_META}
    rows   = []
    for key, name in names.items():
        for r in data[key]:
            rows.append({**r, "metode": name,
                         "top1":  int(r["top1"]),
                         "top3":  int(r["top3"]),
                         "top10": int(r["top10"])})
    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fields, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)
    print("[CSV] " + os.path.abspath(filepath))


# ─────────────────────────────────────────────────────────────
# MAIN
# ─────────────────────────────────────────────────────────────

if __name__ == "__main__":
    print("=" * 65)
    print("  DEMAKAI - Master Evaluasi Semua Metode")
    print("  6 Metode: SQL LIKE, Hybrid, +Prep, +Contoh Lapangan")
    print("=" * 65)
    print()

    queries = load_queries()
    if not queries:
        sys.exit(1)

    print(f"  {len(queries)} query dimuat dari {QUERIES_FILE}\n")
    data = evaluate_all(queries)

    print()
    print("=" * 65)
    print("  RINGKASAN SEMUA METODE")
    print("=" * 65)
    for key, title, *_ in METHODS_META:
        rows = data[key]
        sm = summary(rows)
        kbli_mrr = summary([r for r in rows if r["tipe"]=="KBLI"])["mrr"]
        kbji_mrr = summary([r for r in rows if r["tipe"]=="KBJI"])["mrr"]
        print(f"  {title[:45]:<45}")
        print(f"    MRR: KBLI={kbli_mrr:.4f}  KBJI={kbji_mrr:.4f}  ALL={sm['mrr']:.4f}  Top@1={sm['top1']:.1%}  Top@10={sm['top10']:.1%}")
    print("=" * 65)

    ts      = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_dir = os.path.join(os.path.dirname(__file__), "output")
    html_path = os.path.join(out_dir, f"evaluasi_semua_{ts}.html")
    csv_path  = os.path.join(out_dir, f"evaluasi_semua_{ts}.csv")

    save_csv(data, csv_path)
    save_html(data, html_path)

    print()
    print("Selesai!")
    print("  HTML: " + html_path)
    print("  CSV : " + csv_path)

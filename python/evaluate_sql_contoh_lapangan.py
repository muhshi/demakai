"""
evaluate_sql_contoh_lapangan.py
================================
Evaluasi metode SQL LIKE + Preprocessing + Contoh Lapangan.

Pipeline:
    Query
      -> Preprocessing (Expansion: sinonim + variasi KBLI/KBJI)
      -> SQL LIKE Search
         (pada: kode, judul, deskripsi, contoh_lapangan)
      -> Ranking
      -> Evaluasi

TIDAK menggunakan:
  - Semantic Search (embedding)
  - Hybrid Search

Metrik: Rank, Top@1, Top@3, Top@10, RR, MRR
Output : HTML dashboard + CSV
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
from search.sql_like import search_expansion as sql_search_expansion
from config.database import get_connection

# ---- Konfigurasi -----------------------------------------------------------
QUERIES_FILE = os.path.join(os.path.dirname(__file__), "queries.csv")
LIMIT        = 10

os.makedirs(os.path.join(os.path.dirname(__file__), "output"), exist_ok=True)

# Angka pembanding dari baseline SQL LIKE (tanpa apapun)
BASELINE_KBLI_MRR = 0.0433
BASELINE_KBJI_MRR = 0.0000
BASELINE_ALL_MRR  = 0.0217
BASELINE_TOP1     = 0.0
BASELINE_TOP10    = 8.3


# ============================================================================
# HELPER: contoh_lapangan
# ============================================================================

def _parse_contoh(raw) -> list:
    if not raw:
        return []
    if isinstance(raw, list):
        return [str(x) for x in raw if x]
    if isinstance(raw, str):
        try:
            parsed = json.loads(raw)
            if isinstance(parsed, list):
                return [str(x) for x in parsed if x]
            return [str(parsed)]
        except (json.JSONDecodeError, TypeError):
            return [raw]
    return []


def get_contoh_from_db(kode: str, tipe: str) -> list:
    """Ambil contoh_lapangan dari DB berdasarkan kode GT."""
    from config.settings import Settings
    table = Settings.TABLE_KBLI if tipe == "KBLI" else Settings.TABLE_KBJI
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(
                f"SELECT contoh_lapangan FROM {table} WHERE kode = %s LIMIT 1",
                (kode,)
            )
            row = cur.fetchone()
            if row:
                return _parse_contoh(row.get("contoh_lapangan"))
    except Exception as e:
        print(f"    [WARN] Gagal ambil contoh DB: {e}")
    finally:
        conn.close()
    return []


# ============================================================================
# METRIK
# ============================================================================

def get_rank(results: list, kode_gt: str) -> int:
    for i, item in enumerate(results, start=1):
        if str(item.get("kode", "")).strip() == str(kode_gt).strip():
            return i
    return 0


def compute_metrics(rank: int, top_n: int = 10) -> dict:
    if 0 < rank <= top_n:
        return {
            "rank":  rank,
            "top1":  1 if rank == 1 else 0,
            "top3":  1 if rank <= 3 else 0,
            "top10": 1 if rank <= 10 else 0,
            "rr":    round(1.0 / rank, 4),
        }
    return {"rank": 0, "top1": 0, "top3": 0, "top10": 0, "rr": 0.0}


def get_preprocessed_desc(preprocessed: dict, tipe: str) -> str:
    expanded  = preprocessed.get("expanded_tokens", [])
    kbli_vars = preprocessed.get("kbli_variations", [])
    kbji_vars = preprocessed.get("kbji_variations", [])
    base  = " ".join(expanded[:8])
    extra = []
    if tipe == "KBLI" and kbli_vars:
        extra.append(f"+KBLI: {', '.join(kbli_vars[:3])}")
    elif tipe == "KBJI" and kbji_vars:
        extra.append(f"+KBJI: {', '.join(kbji_vars[:3])}")
    return base + (" | " + " | ".join(extra) if extra else "")


# ============================================================================
# EVALUASI
# ============================================================================

def run_evaluation(queries_file: str = QUERIES_FILE, limit: int = LIMIT) -> list:
    if not os.path.exists(queries_file):
        print("[ERROR] File '" + queries_file + "' tidak ditemukan.")
        return []

    with open(queries_file, newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        queries = list(reader)

    all_rows = []

    for entry in queries:
        no      = entry.get("no", "")
        query   = entry.get("query", "").strip()
        kode_gt = entry.get("kode_ground_truth", "").strip()
        tipe    = entry.get("tipe", "").strip().upper()
        model   = tipe if tipe in ("KBLI", "KBJI") else None

        preprocessed = preprocess_expansion(query)
        prep_desc    = get_preprocessed_desc(preprocessed, tipe)

        print(f"  [{no:>2}] {query[:42]:<42} | GT: {kode_gt} | {tipe}", flush=True)

        try:
            results = sql_search_expansion(preprocessed, limit=limit, model=model)
            rank    = get_rank(results, kode_gt)
            metrics = compute_metrics(rank, top_n=limit)

            # Ambil contoh lapangan dari GT entry di DB
            contoh_list = get_contoh_from_db(kode_gt, tipe)
            contoh_str  = " | ".join(contoh_list[:2]) if contoh_list else "-"

            # Cek apakah hasil yang match mengandung contoh_lapangan sebagai boost
            match_boost = ""
            for item in results:
                if str(item.get("kode", "")).strip() == str(kode_gt).strip():
                    match_boost = item.get("_boost", "")
                    break

            print(f"         -> rank={metrics['rank']}  rr={metrics['rr']}  boost={match_boost or '-'}", flush=True)

        except Exception as e:
            print(f"         -> ERROR: {e}", flush=True)
            metrics    = {"rank": 0, "top1": 0, "top3": 0, "top10": 0, "rr": 0.0}
            contoh_str = "-"
            match_boost = ""

        all_rows.append({
            "no":           no,
            "query":        query,
            "preprocessed": prep_desc,
            "contoh":       contoh_str,
            "kode_gt":      kode_gt,
            "tipe":         tipe,
            "boost":        match_boost,
            "rank":         metrics["rank"],
            "top1":         metrics["top1"],
            "top3":         metrics["top3"],
            "top10":        metrics["top10"],
            "rr":           metrics["rr"],
        })

    return all_rows


# ============================================================================
# CSV
# ============================================================================

def save_csv(rows: list, filepath: str):
    if not rows:
        return
    fields = ["no", "tipe", "query", "preprocessed", "contoh",
              "kode_gt", "rank", "top1", "top3", "top10", "rr", "boost"]
    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fields, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)
    print("[CSV] " + os.path.abspath(filepath))


# ============================================================================
# HTML
# ============================================================================

def _calc(rows: list) -> dict:
    n = len(rows) or 1
    return {
        "n":     n,
        "top1":  sum(r["top1"]  for r in rows) / n,
        "top3":  sum(r["top3"]  for r in rows) / n,
        "top10": sum(r["top10"] for r in rows) / n,
        "mrr":   sum(r["rr"]    for r in rows) / n,
    }


def _mrr_col(v: float) -> str:
    if v >= 0.5: return "#6ee7b7"
    if v >= 0.2: return "#fbbf24"
    return "#f87171"


def _build_table(rows: list, tipe_filter: str, accent: str) -> str:
    filt = [r for r in rows if r["tipe"] == tipe_filter]
    if not filt:
        return ""

    sm = _calc(filt)

    def rank_style(v):
        try: v = int(v)
        except: return ""
        if v == 0:  return "style='background:#1a1f2e;color:#475569'"
        if v == 1:  return "style='background:#064e3b;color:#6ee7b7;font-weight:bold'"
        if v <= 3:  return "style='background:#065f46;color:#a7f3d0'"
        if v <= 10: return "style='background:#1e3a2f;color:#d1fae5'"
        return "style='background:#1a1f2e;color:#475569'"

    def hit_style(v):
        if int(v) == 1:
            return "style='background:#1e3a5f;color:#93c5fd;font-weight:bold;text-align:center'"
        return "style='text-align:center;color:#475569'"

    rows_html = ""
    for r in filt:
        tc   = "#86efac" if r["tipe"] == "KBLI" else "#93c5fd"
        rr_c = "#6ee7b7" if r["rr"] > 0 else "#475569"
        rr_w = "bold"   if r["rr"] > 0 else "normal"

        boost_badge = ""
        if r.get("boost") in ("original_phrase", "and_expanded_groups"):
            boost_badge = " <span style='background:#1a3060;color:#93c5fd;font-size:0.62rem;padding:1px 5px;border-radius:4px;margin-left:4px'>phrase</span>"
        elif r.get("boost") == "expanded_token":
            boost_badge = " <span style='background:#2a1a60;color:#c4b5fd;font-size:0.62rem;padding:1px 5px;border-radius:4px;margin-left:4px'>expand</span>"

        rows_html += f"""
        <tr>
          <td style='text-align:center;color:#94a3b8'>{r['no']}</td>
          <td style='color:{tc};font-weight:bold;text-align:center'>{r['tipe']}</td>
          <td style='color:#c4b5fd;text-align:left;white-space:normal;max-width:180px'>{r['query']}</td>
          <td style='color:#fcd34d;font-size:0.77rem;text-align:left;white-space:normal;max-width:170px'>{r['preprocessed']}</td>
          <td style='color:#86efac;font-size:0.77rem;text-align:left;white-space:normal;max-width:200px'>{r['contoh']}</td>
          <td style='text-align:center'><code>{r['kode_gt']}</code></td>
          <td {rank_style(r['rank'])}>{r['rank'] if r['rank'] > 0 else '-'}{boost_badge}</td>
          <td {hit_style(r['top1'])}>{r['top1']}</td>
          <td {hit_style(r['top3'])}>{r['top3']}</td>
          <td {hit_style(r['top10'])}>{r['top10']}</td>
          <td style='text-align:center;color:{rr_c};font-weight:{rr_w}'>{r['rr'] if r['rr'] > 0 else '-'}</td>
        </tr>"""

    mc = _mrr_col(sm["mrr"])
    rows_html += f"""
        <tr style='background:#1f2937;border-top:2px solid {accent}'>
          <td colspan='6' style='text-align:right;font-weight:bold;color:{accent};padding-right:20px'>MRR (Mean Reciprocal Rank)</td>
          <td style='text-align:center;color:#94a3b8'>&mdash;</td>
          <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top1']:.3f}</td>
          <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top3']:.3f}</td>
          <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top10']:.3f}</td>
          <td style='text-align:center;font-weight:bold;color:{mc}'>{sm['mrr']:.4f}</td>
        </tr>"""

    return f"""
    <table>
      <thead>
        <tr>
          <th>#</th><th>Tipe</th>
          <th style='text-align:left;min-width:170px'>Query Asli</th>
          <th style='text-align:left;min-width:160px;color:#fcd34d'>Preprocessing &#10003;</th>
          <th style='text-align:left;min-width:180px;color:#86efac'>Contoh Lapangan &#10003;</th>
          <th>Kode GT</th><th>Rank</th>
          <th>Top@1</th><th>Top@3</th><th>Top@10</th><th>RR</th>
        </tr>
      </thead>
      <tbody>{rows_html}</tbody>
    </table>"""


def _stats_bar(sm: dict, accent: str) -> str:
    mc = _mrr_col(sm["mrr"])
    return f"""
    <div class="stats-bar">
      <div class="stat-card">
        <div class="stat-label">Total Query</div>
        <div class="stat-value">{sm['n']}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Top@1</div>
        <div class="stat-value" style="color:#86efac">{sm['top1']:.1%}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Top@3</div>
        <div class="stat-value" style="color:#7dd3fc">{sm['top3']:.1%}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Top@10</div>
        <div class="stat-value" style="color:#d8b4fe">{sm['top10']:.1%}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">MRR</div>
        <div class="stat-value" style="color:{mc}">{sm['mrr']:.4f}</div>
      </div>
    </div>"""


def save_html(rows: list, filepath: str):
    ts    = datetime.now().strftime("%d %B %Y, %H:%M:%S")
    total = len(rows)

    rows_kbli = [r for r in rows if r["tipe"] == "KBLI"]
    rows_kbji = [r for r in rows if r["tipe"] == "KBJI"]

    sm_kbli = _calc(rows_kbli)
    sm_kbji = _calc(rows_kbji)
    sm_all  = _calc(rows)
    mc_all  = _mrr_col(sm_all["mrr"])

    tbl_kbli = _build_table(rows, "KBLI", "#7c3aed")
    tbl_kbji = _build_table(rows, "KBJI", "#7c3aed")

    # Delta vs Baseline SQL LIKE
    delta_mrr_kbli = sm_kbli["mrr"] - BASELINE_KBLI_MRR
    delta_mrr_kbji = sm_kbji["mrr"] - BASELINE_KBJI_MRR
    delta_mrr_all  = sm_all["mrr"]  - BASELINE_ALL_MRR
    delta_top1     = sm_all["top1"] * 100 - BASELINE_TOP1
    delta_top10    = sm_all["top10"]* 100 - BASELINE_TOP10

    def delta_html(v, fmt="+.4f"):
        color = "#6ee7b7" if v >= 0 else "#f87171"
        return f"<span style='color:{color};font-weight:bold'>{v:{fmt}}</span>"

    # Query list table (30 unique queries)
    seen_q, uq = set(), []
    for r in sorted(rows, key=lambda x: int(x["no"])):
        if r["query"] not in seen_q:
            uq.append(r)
            seen_q.add(r["query"])

    qlist_html = "".join(f"""
    <tr>
      <td style='text-align:center;color:#64748b;width:36px'>{i}</td>
      <td style='color:#c4b5fd'>{r['query']}</td>
      <td style='color:#fcd34d;font-size:0.8rem;white-space:normal'>{r['preprocessed']}</td>
      <td style='color:#86efac;font-size:0.8rem;white-space:normal'>{r['contoh'] if r['contoh'] != '-' else '<span style="color:#475569">-</span>'}</td>
    </tr>""" for i, r in enumerate(uq, 1))

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DEMAKAI - SQL LIKE + Preprocessing + Contoh Lapangan</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
    * {{ box-sizing: border-box; margin: 0; padding: 0; }}
    body {{ font-family: 'Outfit', sans-serif; background: #0a0c14; color: #e2e8f0; padding: 40px; line-height: 1.6; }}

    .page-badge {{
      display: inline-block;
      background: linear-gradient(135deg, #6d28d9, #4f46e5);
      color: #fff; font-size: 0.75rem; font-weight: 600;
      letter-spacing: 0.1em; text-transform: uppercase;
      padding: 4px 14px; border-radius: 999px; margin-bottom: 14px;
    }}
    h1 {{
      font-size: 2rem; font-weight: 700;
      background: linear-gradient(90deg, #a78bfa, #818cf8);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      margin-bottom: 6px;
    }}
    .meta {{ color: #64748b; font-size: 0.88rem; margin-bottom: 6px; }}

    .method-badge {{
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 0.8rem; padding: 5px 14px; border-radius: 8px;
      margin-right: 8px; margin-bottom: 8px;
    }}
    .mb-active {{ background: #1e1b4b22; border: 1px solid #6d28d9; color: #a78bfa; }}
    .mb-star   {{ background: #0c2a1c; border: 2px solid #059669; color: #6ee7b7; font-weight: 700; }}
    .mb-off    {{ background: #141820; border: 1px solid #2d3048; color: #475569; opacity: 0.5; text-decoration: line-through; }}

    .summary-card {{
      background: linear-gradient(135deg, #110a2a, #0d0b1a);
      border: 1px solid #4c1d95;
      border-radius: 16px; padding: 28px 36px; margin-bottom: 40px;
      display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 20px;
    }}
    .sum-item {{ text-align: center; }}
    .sum-label {{ font-size: 0.78rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }}
    .sum-val   {{ font-size: 1.8rem; font-weight: 700; }}

    .section-hdr {{
      font-size: 1.25rem; font-weight: 600; color: #a78bfa;
      margin: 40px 0 14px;
      display: flex; align-items: center; gap: 10px;
    }}
    .section-hdr::before {{
      content: ''; display: inline-block; width: 4px; height: 22px;
      background: linear-gradient(180deg, #6d28d9, #4f46e5); border-radius: 2px;
    }}

    .stats-bar {{ display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }}
    .stat-card {{
      background: #110a2a; border: 1px solid #4c1d9522;
      border-radius: 10px; padding: 12px 20px; min-width: 100px;
    }}
    .stat-label {{ font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.07em; }}
    .stat-value {{ font-size: 1.3rem; font-weight: 700; color: #f1f5f9; margin-top: 2px; }}

    .wrap {{
      overflow-x: auto; border-radius: 10px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.5); margin-bottom: 20px;
    }}
    table {{ border-collapse: collapse; font-size: 0.82rem; width: 100%; }}
    th, td {{ padding: 10px 14px; border: 1px solid #1e1038; white-space: nowrap; }}
    th {{
      background: #110a2a; color: #a78bfa; font-weight: 600;
      text-transform: uppercase; font-size: 0.7rem;
      letter-spacing: 0.07em; text-align: center;
    }}
    tr:hover td {{ background: rgba(124,58,237,0.04) !important; }}
    code {{
      background: #110a2a; color: #a78bfa;
      padding: 2px 7px; border-radius: 4px;
      border: 1px solid #4c1d95; font-size: 0.82em;
    }}

    hr {{ border: 0; border-top: 2px dashed #1e103855; margin: 40px 0; }}

    .legend {{ display: flex; gap: 18px; flex-wrap: wrap; margin: 20px 0; font-size: 0.8rem; }}
    .legend-item {{ display: flex; align-items: center; gap: 6px; }}
    .legend-dot {{ width: 12px; height: 12px; border-radius: 3px; }}

    /* Pipeline */
    .pipeline-box {{
      background: #110a2a; border: 1px solid #4c1d95;
      border-radius: 12px; padding: 20px 28px; margin-bottom: 30px;
      display: flex; align-items: center; gap: 0; flex-wrap: wrap;
    }}
    .pipe-step {{
      background: #0d0b1a; border: 1px solid #6d28d9;
      border-radius: 8px; padding: 8px 14px;
      color: #a78bfa; font-size: 0.83rem; font-weight: 500; white-space: nowrap;
    }}
    .pipe-step small {{ display: block; color: #94a3b8; font-weight: 400; font-size: 0.7rem; }}
    .pipe-arrow {{ color: #6d28d9; font-size: 1.2rem; padding: 0 8px; }}

    /* Definition box */
    .def-box {{
      background: #110a2a; border: 1px solid #4c1d95;
      border-radius: 12px; padding: 22px 28px; margin-bottom: 20px;
    }}
    .def-box h3 {{ color: #a78bfa; font-size: 0.95rem; margin-bottom: 10px; }}
    .def-box p  {{ color: #94a3b8; font-size: 0.88rem; line-height: 1.6; }}
    .def-box ul {{ color: #94a3b8; font-size: 0.88rem; line-height: 1.7; padding-left: 20px; }}

    /* Analysis */
    .analysis-box {{
      background: #110a2a; border: 1px solid #4c1d95;
      border-radius: 12px; padding: 24px 28px; margin-bottom: 30px;
    }}
    .analysis-box h3 {{ color: #a78bfa; font-size: 0.95rem; margin-bottom: 14px; }}
    .ana-grid {{ display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }}
    .ana-card {{ background: #0a0c14; border: 1px solid #1e1038; border-radius: 10px; padding: 16px; }}
    .ana-title {{ font-size: 0.78rem; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; color: #a78bfa; }}
    .ana-text  {{ font-size: 0.84rem; color: #94a3b8; line-height: 1.5; }}

    .cmp-grid {{ display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-top: 20px; }}
    .cmp-card {{ background: #0a0c14; border: 1px solid #1e1038; border-radius: 10px; padding: 16px; }}
    .cmp-label {{ font-size: 0.72rem; color: #64748b; text-transform: uppercase; margin-bottom: 10px; }}
    .cmp-row {{ display: flex; justify-content: space-between; font-size: 0.83rem; margin-bottom: 5px; }}

    .footer {{
      margin-top: 50px; padding-top: 18px; border-top: 1px solid #1e1038;
      color: #334155; font-size: 0.8rem; text-align: center;
    }}
  </style>
</head>
<body>

  <div class="page-badge">SQL LIKE + Preprocessing + Contoh Lapangan</div>
  <h1>DEMAKAI &mdash; SQL LIKE + Preprocessing + Contoh Lapangan</h1>
  <p class="meta">Generated: {ts} &nbsp;&middot;&nbsp; {total} query evaluasi</p>
  <p class="meta">Database: PostgreSQL &nbsp;&middot;&nbsp; Preprocessing: Expansion (sinonim + variasi KBLI/KBJI)</p>

  <div style="margin-top:16px;margin-bottom:28px">
    <span class="method-badge mb-active">&#10003; SQL LIKE</span>
    <span class="method-badge mb-off">&#10007; Semantic Search</span>
    <span class="method-badge mb-off">&#10007; Hybrid Search</span>
    <span class="method-badge mb-active">&#10003; Preprocessing (Advanced + Expansion)</span>
    <span class="method-badge mb-star">&#10003; Contoh Lapangan &#9733;</span>
  </div>

  <!-- Definisi -->
  <div class="def-box">
    <h3>&#128269; Sistem SQL LIKE + Preprocessing + Contoh Lapangan</h3>
    <p>
      Pada metode ini, query pengguna terlebih dahulu diproses menggunakan
      <strong style="color:#a78bfa">preprocessing</strong> (advanced + expansion sinonim),
      kemudian hasilnya digunakan untuk <strong style="color:#a78bfa">pencarian berbasis teks (SQL LIKE)</strong>
      pada dua field utama:
    </p>
    <ul style="margin-top:10px">
      <li><strong>Deskripsi resmi KBLI/KBJI</strong> &mdash; teks formal dari klasifikasi baku</li>
      <li><strong>Contoh lapangan</strong> &mdash; deskripsi aktivitas nyata, bahasa informal, deskripsi pekerjaan di masyarakat</li>
    </ul>
    <p style="margin-top:12px;padding:10px 14px;background:#1a1040;border-radius:8px;border-left:3px solid #6d28d9">
      <strong style="color:#a78bfa">Perbedaan dari Baseline:</strong>
      Baseline SQL LIKE hanya mencari pada deskripsi formal tanpa preprocessing.
      Di sini, query diperluas dengan sinonim dan variasi, lalu dicari pada KBLI + contoh lapangan,
      sehingga jangkauan pencarian jauh lebih luas.
    </p>
  </div>

  <!-- Pipeline -->
  <div class="pipeline-box">
    <div class="pipe-step">&#128196; Query Asli</div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step">&#9986; Preprocessing<br><small>lowercase, stopword, expansion</small></div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step">&#128269; SQL LIKE<br><small>expanded tokens, AND logic</small></div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step" style="border-color:#059669;color:#6ee7b7">&#128203; Contoh Lapangan &#10003;<br><small style="color:#94a3b8">deskripsi + contoh</small></div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step">&#127941; Ranking</div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step">&#128202; Evaluasi</div>
  </div>

  <!-- Summary Card -->
  <div class="summary-card">
    <div class="sum-item">
      <div class="sum-label">Total Query</div>
      <div class="sum-val" style="color:#a78bfa">{total}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@1</div>
      <div class="sum-val" style="color:#86efac">{sm_all['top1']:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@3</div>
      <div class="sum-val" style="color:#7dd3fc">{sm_all['top3']:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@10</div>
      <div class="sum-val" style="color:#d8b4fe">{sm_all['top10']:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">MRR</div>
      <div class="sum-val" style="color:{mc_all}">{sm_all['mrr']:.4f}</div>
    </div>
  </div>

  <!-- Legend -->
  <div class="legend">
    <div class="legend-item">
      <div class="legend-dot" style="background:#064e3b;border:1px solid #6ee7b7"></div>
      <span style="color:#6ee7b7">Rank 1</span>
    </div>
    <div class="legend-item">
      <div class="legend-dot" style="background:#065f46;border:1px solid #a7f3d0"></div>
      <span style="color:#a7f3d0">Rank 2&ndash;3</span>
    </div>
    <div class="legend-item">
      <div class="legend-dot" style="background:#1e3a2f;border:1px solid #d1fae5"></div>
      <span style="color:#d1fae5">Rank 4&ndash;10</span>
    </div>
    <div class="legend-item">
      <div class="legend-dot" style="background:#1a1f2e;border:1px solid #475569"></div>
      <span style="color:#475569">Tidak ditemukan</span>
    </div>
    <div class="legend-item">
      <div class="legend-dot" style="background:#422006;border:1px solid #fcd34d"></div>
      <span style="color:#fcd34d">Kolom kuning = hasil preprocessing</span>
    </div>
    <div class="legend-item">
      <div class="legend-dot" style="background:#065f46;border:1px solid #6ee7b7"></div>
      <span style="color:#6ee7b7">Kolom hijau = contoh lapangan</span>
    </div>
  </div>

  <!-- KBLI -->
  <div class="section-hdr">Dataset KBLI 2025</div>
  {_stats_bar(sm_kbli, '#7c3aed')}
  <div class="wrap">{tbl_kbli}</div>

  <hr>

  <!-- KBJI -->
  <div class="section-hdr">Dataset KBJI 2014</div>
  {_stats_bar(sm_kbji, '#7c3aed')}
  <div class="wrap">{tbl_kbji}</div>

  <hr>

  <!-- Ringkasan -->
  <div class="section-hdr">Ringkasan Hasil Evaluasi</div>
  <div class="wrap" style="max-width:760px;">
    <table>
      <thead>
        <tr>
          <th style="text-align:left;color:#a78bfa">Metrik</th>
          <th style="color:#86efac">KBLI 2025</th>
          <th style="color:#93c5fd">KBJI 2014</th>
          <th style="color:#e2e8f0">Gabungan</th>
        </tr>
      </thead>
      <tbody>
        <tr><td style="font-weight:600">Top@1</td>
            <td style="text-align:center;color:#94a3b8">{sm_kbli['top1']:.1%}</td>
            <td style="text-align:center;color:#94a3b8">{sm_kbji['top1']:.1%}</td>
            <td style="text-align:center;font-weight:bold">{sm_all['top1']:.1%}</td></tr>
        <tr><td style="font-weight:600">Top@3</td>
            <td style="text-align:center;color:#94a3b8">{sm_kbli['top3']:.1%}</td>
            <td style="text-align:center;color:#94a3b8">{sm_kbji['top3']:.1%}</td>
            <td style="text-align:center;font-weight:bold">{sm_all['top3']:.1%}</td></tr>
        <tr><td style="font-weight:600">Top@10</td>
            <td style="text-align:center;color:#94a3b8">{sm_kbli['top10']:.1%}</td>
            <td style="text-align:center;color:#94a3b8">{sm_kbji['top10']:.1%}</td>
            <td style="text-align:center;font-weight:bold">{sm_all['top10']:.1%}</td></tr>
        <tr><td style="font-weight:600">MRR</td>
            <td style="text-align:center;color:#94a3b8">{sm_kbli['mrr']:.4f}</td>
            <td style="text-align:center;color:#94a3b8">{sm_kbji['mrr']:.4f}</td>
            <td style="text-align:center;font-weight:bold;color:{_mrr_col(sm_all['mrr'])}">{sm_all['mrr']:.4f}</td></tr>
      </tbody>
    </table>
  </div>

  <hr>

  <!-- Data Query -->
  <div class="section-hdr">Data Query &amp; Contoh Lapangan</div>
  <div class="wrap" style="max-width:1100px;">
    <table>
      <thead>
        <tr>
          <th style="width:36px">#</th>
          <th style="text-align:left">Query Asli</th>
          <th style="text-align:left;color:#fcd34d">Preprocessing &#10003;</th>
          <th style="text-align:left;color:#86efac">Contoh Lapangan (dari GT) &#10003;</th>
        </tr>
      </thead>
      <tbody>{qlist_html}</tbody>
    </table>
  </div>

  <hr>

  <!-- Analisis -->
  <div class="section-hdr">Analisis Dampak Preprocessing + Contoh Lapangan pada SQL LIKE</div>
  <div class="analysis-box">
    <h3>&#128202; Dampak terhadap SQL LIKE</h3>
    <div class="ana-grid">
      <div class="ana-card">
        <div class="ana-title">&#128269; Preprocessing Meningkatkan Jangkauan</div>
        <div class="ana-text">
          Dengan query expansion, satu query dapat diubah menjadi puluhan token dan sinonim.
          Contoh: <em>"TUKANG BATU"</em> diperluas menjadi
          <em>"tukang, batu, teknisi, pekerja, operator"</em>.
          SQL LIKE kemudian mencari semua kemungkinan tersebut sehingga recall meningkat drastis
          dibanding baseline yang hanya mencari kata asli.
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-title">&#128203; Contoh Lapangan Menambah Cakupan</div>
        <div class="ana-text">
          SQL LIKE di sini mencari tidak hanya pada deskripsi formal KBLI, tetapi juga
          pada field <code>contoh_lapangan</code>. Ini berarti query informal dari pengguna
          lapangan kini dapat dicocokkan dengan data aktivitas nyata yang menggunakan
          bahasa sehari-hari &mdash; mengurangi ketidakcocokan bahasa (lexical gap).
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-title">&#128300; Perbandingan dengan Baseline</div>
        <div class="ana-text">
          Baseline SQL LIKE (tanpa preprocessing, tanpa contoh lapangan) menghasilkan
          MRR hanya <strong style="color:#f87171">{BASELINE_ALL_MRR:.4f}</strong> dan
          Top@10 sebesar <strong style="color:#f87171">{BASELINE_TOP10:.1f}%</strong>.
          Dengan penambahan preprocessing + contoh lapangan, sistem ini menghasilkan
          MRR <strong style="color:#6ee7b7">{sm_all['mrr']:.4f}</strong> dan
          Top@10 <strong style="color:#6ee7b7">{sm_all['top10']:.1%}</strong> &mdash;
          peningkatan yang sangat signifikan hanya dari SQL LIKE saja.
        </div>
      </div>
    </div>

    <h3 style="margin-top:24px;">&#128300; Perbandingan dengan Baseline SQL LIKE</h3>
    <div class="cmp-grid">
      <div class="cmp-card">
        <div class="cmp-label">Baseline SQL LIKE (tanpa apapun)</div>
        <div class="cmp-row"><span>MRR KBLI</span><span style="color:#f87171">{BASELINE_KBLI_MRR:.4f}</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span style="color:#f87171">{BASELINE_KBJI_MRR:.4f}</span></div>
        <div class="cmp-row"><span>MRR Gabungan</span><span style="color:#f87171">{BASELINE_ALL_MRR:.4f}</span></div>
        <div class="cmp-row"><span>Top@1</span><span style="color:#f87171">{BASELINE_TOP1:.1f}%</span></div>
        <div class="cmp-row"><span>Top@10</span><span style="color:#f87171">{BASELINE_TOP10:.1f}%</span></div>
      </div>
      <div class="cmp-card">
        <div class="cmp-label">SQL LIKE + Preprocessing + Contoh Lapangan (ini)</div>
        <div class="cmp-row"><span>MRR KBLI</span><span style="color:#a78bfa;font-weight:bold">{sm_kbli['mrr']:.4f}</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span style="color:#a78bfa;font-weight:bold">{sm_kbji['mrr']:.4f}</span></div>
        <div class="cmp-row"><span>MRR Gabungan</span><span style="color:#a78bfa;font-weight:bold">{sm_all['mrr']:.4f}</span></div>
        <div class="cmp-row"><span>Top@1</span><span style="color:#a78bfa;font-weight:bold">{sm_all['top1']:.1%}</span></div>
        <div class="cmp-row"><span>Top@10</span><span style="color:#a78bfa;font-weight:bold">{sm_all['top10']:.1%}</span></div>
      </div>
      <div class="cmp-card">
        <div class="cmp-label">Selisih (Delta vs Baseline)</div>
        <div class="cmp-row"><span>MRR KBLI</span>{delta_html(delta_mrr_kbli)}</div>
        <div class="cmp-row"><span>MRR KBJI</span>{delta_html(delta_mrr_kbji)}</div>
        <div class="cmp-row"><span>MRR Gabungan</span>{delta_html(delta_mrr_all)}</div>
        <div class="cmp-row"><span>Top@1</span>{delta_html(delta_top1, '+.1f')}%</div>
        <div class="cmp-row"><span>Top@10</span>{delta_html(delta_top10, '+.1f')}%</div>
      </div>
    </div>
  </div>

  <div class="footer">
    DEMAKAI &mdash; Evaluasi SQL LIKE + Preprocessing + Contoh Lapangan &middot;
    Dibuat otomatis oleh evaluate_sql_contoh_lapangan.py
  </div>

</body>
</html>"""

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(html)
    print("[HTML] " + os.path.abspath(filepath))


# ============================================================================
# ENTRY POINT
# ============================================================================

if __name__ == "__main__":
    print("=" * 66)
    print("  DEMAKAI - SQL LIKE + Preprocessing + Contoh Lapangan")
    print("  Pipeline: Query -> Expansion -> SQL LIKE (desc + contoh)")
    print("  Database : PostgreSQL")
    print("=" * 66)
    print()

    rows = run_evaluation(QUERIES_FILE, limit=LIMIT)
    if not rows:
        sys.exit(0)

    rows_kbli = [r for r in rows if r["tipe"] == "KBLI"]
    rows_kbji = [r for r in rows if r["tipe"] == "KBJI"]

    print()
    print("=" * 66)
    print("  RINGKASAN HASIL")
    print("=" * 66)
    for label, subset in [("KBLI", rows_kbli), ("KBJI", rows_kbji), ("ALL", rows)]:
        n   = len(subset) or 1
        t1  = sum(r["top1"]  for r in subset) / n
        t3  = sum(r["top3"]  for r in subset) / n
        t10 = sum(r["top10"] for r in subset) / n
        mrr = sum(r["rr"]    for r in subset) / n
        print(f"  {label:4}: Top@1={t1:.3f}  Top@3={t3:.3f}  Top@10={t10:.3f}  MRR={mrr:.4f}")

    print()
    print("  PERBANDINGAN vs BASELINE:")
    print(f"  Baseline SQL LIKE     : MRR={BASELINE_ALL_MRR:.4f}  Top@10={BASELINE_TOP10:.1f}%")
    all_mrr = sum(r["rr"] for r in rows) / (len(rows) or 1)
    all_top10 = sum(r["top10"] for r in rows) / (len(rows) or 1)
    print(f"  SQL+Prep+Contoh (ini) : MRR={all_mrr:.4f}  Top@10={all_top10:.1%}")
    print("=" * 66)

    ts      = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_dir = os.path.join(os.path.dirname(__file__), "output")
    html_path = os.path.join(out_dir, f"sql_contoh_{ts}.html")
    csv_path  = os.path.join(out_dir, f"sql_contoh_{ts}.csv")

    save_csv(rows, csv_path)
    save_html(rows, html_path)

    print()
    print("Selesai! File output tersimpan di folder output/")
    print("  HTML: " + html_path)
    print("  CSV : " + csv_path)

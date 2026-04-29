"""
evaluate_baseline.py -- Evaluasi Baseline DEMAKAI (SQL LIKE + SQLite)
======================================================================
Konfigurasi:
  - Database : SQLite lokal (demakai_baseline.db) -- BUKAN PostgreSQL
  - Search   : SQL LIKE sederhana (tanpa preprocessing, tanpa hybrid)
  - Kolom    : kode, judul, deskripsi -- TANPA contoh_lapangan
  - Metrik   : Rank, Top1, Top3, Top10, RR (Reciprocal Rank)

Jalankan:
  python evaluate_baseline.py

Prasyarat: Jalankan build_sqlite_baseline.py terlebih dahulu.
"""

import csv
import os
import sys
import sqlite3
from datetime import datetime

sys.path.insert(0, os.path.dirname(__file__))

# ---- Konfigurasi -----------------------------------------------------------
SQLITE_PATH  = os.path.join(os.path.dirname(__file__), "demakai_baseline.db")
QUERIES_FILE = os.path.join(os.path.dirname(__file__), "queries.csv")
LIMIT        = 10  # Jumlah top-N hasil yang dikembalikan

os.makedirs(os.path.join(os.path.dirname(__file__), "output"), exist_ok=True)


# ============================================================================
# FUNGSI PENCARIAN BASELINE: SQL LIKE Sederhana
# ============================================================================

def search_baseline_sqlite(query: str, table: str, limit: int = 10) -> list:
    """
    Fungsi pencarian BASELINE:
    - Gunakan SQL LIKE sederhana
    - Hanya cari di kolom: kode, judul, deskripsi
    - TIDAK menggunakan: contoh_lapangan, hybrid search, preprocessing
    
    Strategi fallback:
      1. Coba frasa lengkap ('%query%')
      2. Jika kosong, coba OR per kata
      3. Jika masih kosong, coba kata terpanjang saja
    """
    conn = sqlite3.connect(SQLITE_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()
    results = []

    try:
        pattern = f"%{query.strip()}%"
        tokens  = [t.strip() for t in query.split() if t.strip()]

        # Step 1: Frasa lengkap (highest precision)
        cur.execute(
            f"SELECT * FROM {table} WHERE kode LIKE ? OR judul LIKE ? OR deskripsi LIKE ? LIMIT ?",
            (pattern, pattern, pattern, limit)
        )
        rows = cur.fetchall()
        results = [dict(r) for r in rows]

        # Step 2 (fallback): OR per kata -- jika step 1 kosong dan ada >1 token
        if not results and len(tokens) > 1:
            or_conditions  = " OR ".join(
                "(kode LIKE ? OR judul LIKE ? OR deskripsi LIKE ?)"
                for _ in tokens
            )
            params = []
            for t in tokens:
                pat = f"%{t}%"
                params.extend([pat, pat, pat])
            params.append(limit)
            cur.execute(
                f"SELECT * FROM {table} WHERE {or_conditions} LIMIT ?",
                params
            )
            rows = cur.fetchall()
            results = [dict(r) for r in rows]

        # Step 3 (fallback ke-2): satu kata terpanjang
        if not results and tokens:
            best = max(tokens, key=len)
            pat  = f"%{best}%"
            cur.execute(
                f"SELECT * FROM {table} WHERE kode LIKE ? OR judul LIKE ? OR deskripsi LIKE ? LIMIT ?",
                (pat, pat, pat, limit)
            )
            rows = cur.fetchall()
            results = [dict(r) for r in rows]

    finally:
        conn.close()

    return results[:limit]


def search_both_tables(query: str, model: str = None, limit: int = 10) -> list:
    """
    Jalankan pencarian di satu atau kedua tabel (KBLI dan/atau KBJI).
    """
    results = []
    if model is None or model == "KBLI":
        rows = search_baseline_sqlite(query, "kbli2025s", limit)
        for r in rows:
            results.append({**r, "_source": "KBLI"})

    if model is None or model == "KBJI":
        rows = search_baseline_sqlite(query, "kbji2014s", limit)
        for r in rows:
            results.append({**r, "_source": "KBJI"})

    return results[:limit]


# ============================================================================
# METRIK EVALUASI
# ============================================================================

def get_rank(results: list, kode_gt: str) -> int:
    """Cari posisi (1-based) kode_gt di dalam list results. Return 0 jika tidak ada."""
    for i, item in enumerate(results, start=1):
        if str(item.get("kode", "")).strip() == str(kode_gt).strip():
            return i
    return 0


def compute_metrics(rank: int, top_n: int = 10) -> dict:
    """
    Hitung metrik:
      - Top1  = 1 jika rank == 1
      - Top3  = 1 jika rank <= 3
      - Top10 = 1 jika rank <= 10
      - RR    = 1 / rank  (Reciprocal Rank)
    """
    if 0 < rank <= top_n:
        return {
            "rank":  rank,
            "top1":  1 if rank == 1 else 0,
            "top3":  1 if rank <= 3 else 0,
            "top10": 1 if rank <= 10 else 0,
            "rr":    round(1.0 / rank, 4),
        }
    return {"rank": 0, "top1": 0, "top3": 0, "top10": 0, "rr": 0.0}


# ============================================================================
# FUNGSI EVALUASI UTAMA
# ============================================================================

def run_evaluation(queries_file: str = QUERIES_FILE, limit: int = LIMIT) -> list:
    """
    Baca queries.csv dan jalankan evaluasi baseline untuk setiap query.
    Mengembalikan list of dict (satu baris per query).
    """
    if not os.path.exists(queries_file):
        print("[ERROR] File '" + queries_file + "' tidak ditemukan.")
        return []

    if not os.path.exists(SQLITE_PATH):
        print("[ERROR] SQLite database tidak ditemukan: " + SQLITE_PATH)
        print("  Jalankan terlebih dahulu: python build_sqlite_baseline.py")
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

        print(f"  [{no:>2}] {query[:50]:<50} | GT: {kode_gt} | {tipe}", flush=True)

        try:
            results = search_both_tables(query, model=model, limit=limit)
            rank    = get_rank(results, kode_gt)
            metrics = compute_metrics(rank, top_n=limit)
            print(f"       -> rank={metrics['rank']}  top1={metrics['top1']}  rr={metrics['rr']}")
        except Exception as e:
            print(f"       -> ERROR: {e}")
            metrics = {"rank": 0, "top1": 0, "top3": 0, "top10": 0, "rr": 0.0}

        all_rows.append({
            "no":              no,
            "query":           query,
            "kode_gt":         kode_gt,
            "tipe":            tipe,
            "rank":            metrics["rank"],
            "top1":            metrics["top1"],
            "top3":            metrics["top3"],
            "top10":           metrics["top10"],
            "rr":              metrics["rr"],
        })

    return all_rows


# ============================================================================
# SIMPAN CSV
# ============================================================================

def save_csv(rows: list, filepath: str):
    if not rows:
        return
    fieldnames = ["no", "tipe", "query", "kode_gt", "rank", "top1", "top3", "top10", "rr"]
    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)
    print("[CSV] Disimpan -> " + os.path.abspath(filepath))


# ============================================================================
# SIMPAN HTML DASHBOARD
# ============================================================================

def save_html(rows_kbli: list, rows_kbji: list, filepath: str):
    ts = datetime.now().strftime("%d %B %Y, %H:%M:%S")
    total_queries = len(rows_kbli) + len(rows_kbji)

    def build_table(rows: list, title: str) -> str:
        if not rows:
            return f"<p style='color:#64748b;margin:20px 0'>Tidak ada data untuk {title}</p>"

        # -- Summary stats
        n = len(rows)
        top1_avg  = sum(r["top1"]  for r in rows) / n
        top3_avg  = sum(r["top3"]  for r in rows) / n
        top10_avg = sum(r["top10"] for r in rows) / n
        mrr       = sum(r["rr"]    for r in rows) / n

        def rank_style(val):
            try:
                v = int(val)
            except:
                return ""
            if v == 0:
                return "style='background:#1a1f2e;color:#475569'"
            if v == 1:
                return "style='background:#064e3b;color:#6ee7b7;font-weight:bold'"
            if v <= 3:
                return "style='background:#065f46;color:#a7f3d0'"
            if v <= 10:
                return "style='background:#1e3a2f;color:#d1fae5'"
            return "style='background:#1a1f2e;color:#475569'"

        def hit_style(val):
            if int(val) == 1:
                return "style='background:#1e3a5f;color:#93c5fd;font-weight:bold;text-align:center'"
            return "style='text-align:center;color:#475569'"

        rows_html = ""
        for r in rows:
            tipe_color = "#86efac" if r["tipe"] == "KBLI" else "#93c5fd"
            rows_html += f"""
            <tr>
              <td style='text-align:center;color:#94a3b8'>{r['no']}</td>
              <td style='color:{tipe_color};font-weight:bold;text-align:center'>{r['tipe']}</td>
              <td style='color:#c4b5fd;text-align:left'>{r['query']}</td>
              <td style='text-align:center'><code>{r['kode_gt']}</code></td>
              <td {rank_style(r['rank'])}>{r['rank'] if r['rank'] > 0 else '-'}</td>
              <td {hit_style(r['top1'])}>{r['top1']}</td>
              <td {hit_style(r['top3'])}>{r['top3']}</td>
              <td {hit_style(r['top10'])}>{r['top10']}</td>
              <td style='text-align:center;color:{"#6ee7b7" if r["rr"] > 0 else "#475569"};font-weight:{"bold" if r["rr"] > 0 else "normal"}'>{r['rr'] if r['rr'] > 0 else '-'}</td>
            </tr>"""

        # MRR summary row
        mrr_color = "#6ee7b7" if mrr >= 0.5 else "#fbbf24" if mrr >= 0.2 else "#f87171"
        rows_html += f"""
            <tr style='background:#1f2937;border-top:2px solid #4f46e5'>
              <td colspan='4' style='text-align:right;font-weight:bold;color:#a78bfa;padding-right:20px'>
                MRR (Mean Reciprocal Rank)
              </td>
              <td style='text-align:center;color:#94a3b8'>&mdash;</td>
              <td style='text-align:center;font-weight:bold;color:#a78bfa'>{top1_avg:.3f}</td>
              <td style='text-align:center;font-weight:bold;color:#a78bfa'>{top3_avg:.3f}</td>
              <td style='text-align:center;font-weight:bold;color:#a78bfa'>{top10_avg:.3f}</td>
              <td style='text-align:center;font-weight:bold;color:{mrr_color}'>{mrr:.4f}</td>
            </tr>"""

        return f"""
        <div class="section-header">{title}</div>
        <div class="stats-bar">
          <div class="stat-card">
            <div class="stat-label">Total Query</div>
            <div class="stat-value">{n}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Top@1</div>
            <div class="stat-value" style="color:#86efac">{top1_avg:.1%}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Top@3</div>
            <div class="stat-value" style="color:#7dd3fc">{top3_avg:.1%}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">Top@10</div>
            <div class="stat-value" style="color:#d8b4fe">{top10_avg:.1%}</div>
          </div>
          <div class="stat-card">
            <div class="stat-label">MRR</div>
            <div class="stat-value" style="color:{mrr_color}">{mrr:.4f}</div>
          </div>
        </div>
        <div class="wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Tipe</th>
                <th style='text-align:left;min-width:300px'>Query</th>
                <th>Kode GT</th>
                <th>Rank</th>
                <th>Top@1</th>
                <th>Top@3</th>
                <th>Top@10</th>
                <th>RR</th>
              </tr>
            </thead>
            <tbody>
              {rows_html}
            </tbody>
          </table>
        </div>"""

    section_kbli = build_table(rows_kbli, "Dataset KBLI 2025")
    section_kbji = build_table(rows_kbji, "Dataset KBJI 2014")

    # -- Overall summary
    all_rows = rows_kbli + rows_kbji
    n_all = len(all_rows) or 1
    overall_top1  = sum(r["top1"]  for r in all_rows) / n_all
    overall_top3  = sum(r["top3"]  for r in all_rows) / n_all
    overall_top10 = sum(r["top10"] for r in all_rows) / n_all
    overall_mrr   = sum(r["rr"]    for r in all_rows) / n_all
    mrr_color_all = "#6ee7b7" if overall_mrr >= 0.5 else "#fbbf24" if overall_mrr >= 0.2 else "#f87171"

    n_kbli = len(rows_kbli) or 1
    top1_kbli  = sum(r["top1"] for r in rows_kbli) / n_kbli
    top3_kbli  = sum(r["top3"] for r in rows_kbli) / n_kbli
    top10_kbli = sum(r["top10"] for r in rows_kbli) / n_kbli
    mrr_kbli   = sum(r["rr"] for r in rows_kbli) / n_kbli

    n_kbji = len(rows_kbji) or 1
    top1_kbji  = sum(r["top1"] for r in rows_kbji) / n_kbji
    top3_kbji  = sum(r["top3"] for r in rows_kbji) / n_kbji
    top10_kbji = sum(r["top10"] for r in rows_kbji) / n_kbji
    mrr_kbji   = sum(r["rr"] for r in rows_kbji) / n_kbji

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DEMAKAI - Evaluasi Baseline (SQL LIKE + SQLite)</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

    * {{ box-sizing: border-box; margin: 0; padding: 0; }}

    body {{
      font-family: 'Outfit', sans-serif;
      background: #0a0c14;
      color: #e2e8f0;
      padding: 40px;
      line-height: 1.6;
    }}

    /* ---- Header ---- */
    .header {{
      margin-bottom: 40px;
    }}
    .badge {{
      display: inline-block;
      background: linear-gradient(135deg, #7c3aed, #4f46e5);
      color: #fff;
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 4px 14px;
      border-radius: 999px;
      margin-bottom: 14px;
    }}
    h1 {{
      font-size: 2.2rem;
      font-weight: 700;
      background: linear-gradient(90deg, #a78bfa, #818cf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 8px;
    }}
    .meta {{
      color: #64748b;
      font-size: 0.9rem;
      margin-bottom: 6px;
    }}

    /* ---- Methodology Tag ---- */
    .method-tags {{
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 18px;
      margin-bottom: 8px;
    }}
    .tag {{
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #1e2030;
      border: 1px solid #2d3048;
      color: #94a3b8;
      font-size: 0.8rem;
      padding: 5px 14px;
      border-radius: 8px;
    }}
    .tag.active {{
      border-color: #4f46e5;
      color: #a5b4fc;
      background: #1e1b4b22;
    }}
    .tag.disabled {{
      color: #475569;
      text-decoration: line-through;
      opacity: 0.6;
    }}

    /* ---- Overall Summary Card ---- */
    .summary-card {{
      background: linear-gradient(135deg, #1a1f35, #13192b);
      border: 1px solid #2d3480;
      border-radius: 16px;
      padding: 28px 36px;
      margin-bottom: 40px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 20px;
    }}
    .sum-item {{
      text-align: center;
    }}
    .sum-label {{
      font-size: 0.78rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 6px;
    }}
    .sum-val {{
      font-size: 1.8rem;
      font-weight: 700;
    }}

    /* ---- Section heading ---- */
    .section-header {{
      font-size: 1.3rem;
      font-weight: 600;
      color: #c4b5fd;
      margin: 50px 0 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }}
    .section-header::before {{
      content: '';
      display: inline-block;
      width: 4px;
      height: 22px;
      background: linear-gradient(180deg, #7c3aed, #4f46e5);
      border-radius: 2px;
    }}

    /* ---- Stats Bar ---- */
    .stats-bar {{
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }}
    .stat-card {{
      background: #141828;
      border: 1px solid #222840;
      border-radius: 10px;
      padding: 12px 20px;
      min-width: 100px;
    }}
    .stat-label {{
      font-size: 0.72rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.07em;
    }}
    .stat-value {{
      font-size: 1.4rem;
      font-weight: 700;
      color: #f1f5f9;
      margin-top: 2px;
    }}

    /* ---- Table ---- */
    .wrap {{
      overflow-x: auto;
      background: #0f1320;
      border: 1px solid #1e2438;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.5);
      margin-bottom: 40px;
    }}
    table {{
      border-collapse: collapse;
      font-size: 0.85rem;
      width: 100%;
    }}
    th, td {{
      padding: 12px 16px;
      border: 1px solid #1e2438;
      white-space: nowrap;
    }}
    th {{
      background: #141828;
      color: #94a3b8;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.07em;
      text-align: center;
    }}
    tr:hover td {{ background: rgba(255,255,255,0.015) !important; }}
    code {{
      background: #1a2035;
      color: #a5b4fc;
      padding: 2px 8px;
      border-radius: 4px;
      border: 1px solid #2d3480;
      font-size: 0.85em;
    }}

    /* ---- Divider ---- */
    hr {{
      border: 0;
      border-top: 2px dashed #1e2438;
      margin: 50px 0;
    }}

    /* ---- Legend ---- */
    .legend {{
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      margin: 30px 0;
      font-size: 0.82rem;
    }}
    .legend-item {{
      display: flex;
      align-items: center;
      gap: 8px;
    }}
    .legend-dot {{
      width: 12px;
      height: 12px;
      border-radius: 3px;
    }}

    /* ---- Footer ---- */
    .footer {{
      margin-top: 60px;
      padding-top: 20px;
      border-top: 1px solid #1e2438;
      color: #334155;
      font-size: 0.82rem;
      text-align: center;
    }}
  </style>
</head>
<body>

  <div class="header">
    <div class="badge">Evaluasi Baseline</div>
    <h1>DEMAKAI &mdash; SQL LIKE Baseline</h1>
    <p class="meta">Generated: {ts} &nbsp;&middot;&nbsp; {total_queries} query</p>
    <p class="meta">Database: SQLite &nbsp;&middot;&nbsp; Pencarian: SQL LIKE (tanpa preprocessing)</p>

    <div class="method-tags">
      <span class="tag active">&#10003; SQL LIKE</span>
      <span class="tag active">&#10003; SQLite</span>
      <span class="tag disabled">&#10007; Hybrid Search</span>
      <span class="tag disabled">&#10007; Preprocessing</span>
      <span class="tag disabled">&#10007; Contoh Lapangan</span>
    </div>
  </div>

  <!-- Overall Summary -->
  <div class="summary-card">
    <div class="sum-item">
      <div class="sum-label">Total Query</div>
      <div class="sum-val" style="color:#c4b5fd">{total_queries}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@1</div>
      <div class="sum-val" style="color:#86efac">{overall_top1:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@3</div>
      <div class="sum-val" style="color:#7dd3fc">{overall_top3:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@10</div>
      <div class="sum-val" style="color:#d8b4fe">{overall_top10:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">MRR</div>
      <div class="sum-val" style="color:{mrr_color_all}">{overall_mrr:.4f}</div>
    </div>
  </div>

  <!-- Legend -->
  <div class="legend">
    <div class="legend-item">
      <div class="legend-dot" style="background:#064e3b;border:1px solid #6ee7b7"></div>
      <span style="color:#6ee7b7">Rank 1 (sangat baik)</span>
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
      <span style="color:#475569">Tidak ditemukan dalam top-10</span>
    </div>
  </div>

  <!-- Section KBLI -->
  {section_kbli}

  <hr>

  <!-- Section KBJI -->
  {section_kbji}

  <hr>

  <div class="section-header">Ringkasan Hasil Evaluasi</div>
  <div class="wrap" style="max-width: 800px;">
    <table>
      <thead>
        <tr>
          <th style="background:#1a1f35;color:#c4b5fd;text-align:left;font-size:0.8rem">Metrik</th>
          <th style="background:#1a1f35;color:#c4b5fd;font-size:0.8rem">Dataset KBLI 2025</th>
          <th style="background:#1a1f35;color:#c4b5fd;font-size:0.8rem">Dataset KBJI 2014</th>
          <th style="background:#1a1f35;color:#f1f5f9;font-size:0.8rem">Gabungan (Overall)</th>
        </tr>
      </thead>
      <tbody>
        <tr style="border-bottom:1px solid #1e2438;">
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">Top@1</td>
          <td style="text-align:center;color:#94a3b8">{top1_kbli:.1%}</td>
          <td style="text-align:center;color:#94a3b8">{top1_kbji:.1%}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{overall_top1:.1%}</td>
        </tr>
        <tr style="border-bottom:1px solid #1e2438;">
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">Top@3</td>
          <td style="text-align:center;color:#94a3b8">{top3_kbli:.1%}</td>
          <td style="text-align:center;color:#94a3b8">{top3_kbji:.1%}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{overall_top3:.1%}</td>
        </tr>
        <tr style="border-bottom:1px solid #1e2438;">
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">Top@10</td>
          <td style="text-align:center;color:#94a3b8">{top10_kbli:.1%}</td>
          <td style="text-align:center;color:#94a3b8">{top10_kbji:.1%}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{overall_top10:.1%}</td>
        </tr>
        <tr>
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">MRR</td>
          <td style="text-align:center;color:#94a3b8">{mrr_kbli:.4f}</td>
          <td style="text-align:center;color:#94a3b8">{mrr_kbji:.4f}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{overall_mrr:.4f}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="footer">
    DEMAKAI &mdash; Sistem Evaluasi Baseline &middot; Dibuat otomatis oleh evaluate_baseline.py
  </div>

</body>
</html>"""

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(html)
    print("[HTML] Disimpan -> " + os.path.abspath(filepath))


# ============================================================================
# ENTRY POINT
# ============================================================================

if __name__ == "__main__":
    print("=" * 60)
    print("  DEMAKAI - Evaluasi Baseline (SQL LIKE + SQLite)")
    print("  Metode  : SQL LIKE Sederhana")
    print("  Database: SQLite (demakai_baseline.db)")
    print("  Fitur   : Tanpa hybrid, preprocessing, contoh_lapangan")
    print("=" * 60)
    print()

    rows = run_evaluation(QUERIES_FILE, limit=LIMIT)
    if not rows:
        sys.exit(0)

    # Pisah KBLI dan KBJI
    rows_kbli = [r for r in rows if r.get("tipe") == "KBLI"]
    rows_kbji = [r for r in rows if r.get("tipe") == "KBJI"]

    # Summary
    print()
    print("=" * 60)
    print("  RINGKASAN HASIL")
    print("=" * 60)
    for label, subset in [("KBLI", rows_kbli), ("KBJI", rows_kbji)]:
        n = len(subset) or 1
        t1  = sum(r["top1"]  for r in subset) / n
        t3  = sum(r["top3"]  for r in subset) / n
        t10 = sum(r["top10"] for r in subset) / n
        mrr = sum(r["rr"]    for r in subset) / n
        print(f"  {label}:")
        print(f"    Top@1={t1:.3f}  Top@3={t3:.3f}  Top@10={t10:.3f}  MRR={mrr:.4f}")

    all_rows = rows_kbli + rows_kbji
    n_all = len(all_rows) or 1
    print(f"  GABUNGAN:")
    print(f"    Top@1={sum(r['top1'] for r in all_rows)/n_all:.3f}  "
          f"Top@3={sum(r['top3'] for r in all_rows)/n_all:.3f}  "
          f"Top@10={sum(r['top10'] for r in all_rows)/n_all:.3f}  "
          f"MRR={sum(r['rr'] for r in all_rows)/n_all:.4f}")
    print("=" * 60)

    # Simpan output
    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_dir = os.path.join(os.path.dirname(__file__), "output")
    os.makedirs(out_dir, exist_ok=True)

    csv_path  = os.path.join(out_dir, f"baseline_{ts}.csv")
    html_path = os.path.join(out_dir, f"baseline_{ts}.html")

    save_csv(rows, csv_path)
    save_html(rows_kbli, rows_kbji, html_path)

    print()
    print("Selesai! File output disimpan di folder output/")
    print("  HTML: " + html_path)
    print("  CSV : " + csv_path)

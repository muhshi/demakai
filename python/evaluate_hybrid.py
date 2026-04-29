"""
evaluate_hybrid.py -- Evaluasi Hybrid Search DEMAKAI
======================================================
Konfigurasi:
  - Database   : PostgreSQL (production) + pgvector
  - Search     : Hybrid Search RAW (Semantic + Keyword, TANPA preprocessing)
  - Kolom      : kode, judul, deskripsi, contoh_lapangan (pencarian saja, bukan filter)
  - NOTE       : contoh_lapangan dipakai di internal _merge_and_boost untuk boosting
                 tapi BUKAN sebagai filter tambahan dari user. Sesuai dengan search_raw().
  - Metrik     : Rank, Top1, Top3, Top10, RR (Reciprocal Rank)

Cara pakai:
  python evaluate_hybrid.py
"""

import csv
import os
import sys
from datetime import datetime

sys.path.insert(0, os.path.dirname(__file__))

from search.hybrid import search_raw as hybrid_search_raw

# ---- Konfigurasi -----------------------------------------------------------
QUERIES_FILE = os.path.join(os.path.dirname(__file__), "queries.csv")
LIMIT        = 10

os.makedirs(os.path.join(os.path.dirname(__file__), "output"), exist_ok=True)


# ============================================================================
# METRIK EVALUASI
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


# ============================================================================
# FUNGSI EVALUASI UTAMA
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

        print(f"  [{no:>2}] {query[:50]:<50} | GT: {kode_gt} | {tipe}", flush=True)

        try:
            results = hybrid_search_raw(query, limit=limit, model=model)
            rank    = get_rank(results, kode_gt)
            metrics = compute_metrics(rank, top_n=limit)
            print(f"       -> rank={metrics['rank']}  top1={metrics['top1']}  rr={metrics['rr']}")
        except Exception as e:
            print(f"       -> ERROR: {e}")
            metrics = {"rank": 0, "top1": 0, "top3": 0, "top10": 0, "rr": 0.0}

        all_rows.append({
            "no":      no,
            "query":   query,
            "kode_gt": kode_gt,
            "tipe":    tipe,
            "rank":    metrics["rank"],
            "top1":    metrics["top1"],
            "top3":    metrics["top3"],
            "top10":   metrics["top10"],
            "rr":      metrics["rr"],
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

    # ---- Helper: bangun tabel per section ----
    def build_table(rows: list, title: str) -> str:
        if not rows:
            return f"<p style='color:#64748b;margin:20px 0'>Tidak ada data untuk {title}</p>"

        n         = len(rows)
        top1_avg  = sum(r["top1"]  for r in rows) / n
        top3_avg  = sum(r["top3"]  for r in rows) / n
        top10_avg = sum(r["top10"] for r in rows) / n
        mrr       = sum(r["rr"]    for r in rows) / n
        mrr_color = "#6ee7b7" if mrr >= 0.5 else "#fbbf24" if mrr >= 0.2 else "#f87171"

        def rank_style(val):
            try: v = int(val)
            except: return ""
            if v == 0:  return "style='background:#1a1f2e;color:#475569'"
            if v == 1:  return "style='background:#064e3b;color:#6ee7b7;font-weight:bold'"
            if v <= 3:  return "style='background:#065f46;color:#a7f3d0'"
            if v <= 10: return "style='background:#1e3a2f;color:#d1fae5'"
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

        mrr_color_row = "#6ee7b7" if mrr >= 0.5 else "#fbbf24" if mrr >= 0.2 else "#f87171"
        rows_html += f"""
            <tr style='background:#1f2937;border-top:2px solid #4f46e5'>
              <td colspan='4' style='text-align:right;font-weight:bold;color:#a78bfa;padding-right:20px'>
                MRR (Mean Reciprocal Rank)
              </td>
              <td style='text-align:center;color:#94a3b8'>&mdash;</td>
              <td style='text-align:center;font-weight:bold;color:#a78bfa'>{top1_avg:.3f}</td>
              <td style='text-align:center;font-weight:bold;color:#a78bfa'>{top3_avg:.3f}</td>
              <td style='text-align:center;font-weight:bold;color:#a78bfa'>{top10_avg:.3f}</td>
              <td style='text-align:center;font-weight:bold;color:{mrr_color_row}'>{mrr:.4f}</td>
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

    # ---- Hitung semua summary ----
    all_rows   = rows_kbli + rows_kbji
    n_all      = len(all_rows) or 1
    ov_top1    = sum(r["top1"]  for r in all_rows) / n_all
    ov_top3    = sum(r["top3"]  for r in all_rows) / n_all
    ov_top10   = sum(r["top10"] for r in all_rows) / n_all
    ov_mrr     = sum(r["rr"]    for r in all_rows) / n_all
    ov_mrr_col = "#6ee7b7" if ov_mrr >= 0.5 else "#fbbf24" if ov_mrr >= 0.2 else "#f87171"

    n_kbli     = len(rows_kbli) or 1
    t1_kbli    = sum(r["top1"]  for r in rows_kbli) / n_kbli
    t3_kbli    = sum(r["top3"]  for r in rows_kbli) / n_kbli
    t10_kbli   = sum(r["top10"] for r in rows_kbli) / n_kbli
    mrr_kbli   = sum(r["rr"]    for r in rows_kbli) / n_kbli

    n_kbji     = len(rows_kbji) or 1
    t1_kbji    = sum(r["top1"]  for r in rows_kbji) / n_kbji
    t3_kbji    = sum(r["top3"]  for r in rows_kbji) / n_kbji
    t10_kbji   = sum(r["top10"] for r in rows_kbji) / n_kbji
    mrr_kbji   = sum(r["rr"]    for r in rows_kbji) / n_kbji

    section_kbli = build_table(rows_kbli, "Dataset KBLI 2025")
    section_kbji = build_table(rows_kbji, "Dataset KBJI 2014")

    # ---- Daftar query untuk section Data Query ----
    all_queries_sorted = sorted(all_rows, key=lambda r: int(r["no"]))
    unique_queries = []
    seen_q = set()
    for r in all_queries_sorted:
        if r["query"] not in seen_q:
            unique_queries.append(r["query"])
            seen_q.add(r["query"])

    query_list_html = ""
    for idx, q in enumerate(unique_queries, 1):
        query_list_html += f"""
        <tr>
          <td style='text-align:center;color:#64748b;width:40px'>{idx}</td>
          <td style='color:#c4b5fd'>{q}</td>
        </tr>"""

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DEMAKAI - Evaluasi Hybrid Search</title>
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

    .header {{ margin-bottom: 40px; }}

    .badge {{
      display: inline-block;
      background: linear-gradient(135deg, #0f766e, #0e7490);
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
      background: linear-gradient(90deg, #34d399, #22d3ee);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 8px;
    }}

    .meta {{ color: #64748b; font-size: 0.9rem; margin-bottom: 6px; }}

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
      border-color: #0e7490;
      color: #67e8f9;
      background: #082f4922;
    }}
    .tag.disabled {{
      color: #475569;
      text-decoration: line-through;
      opacity: 0.6;
    }}

    .summary-card {{
      background: linear-gradient(135deg, #0f2520, #0c2030);
      border: 1px solid #134e4a;
      border-radius: 16px;
      padding: 28px 36px;
      margin-bottom: 40px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 20px;
    }}
    .sum-item {{ text-align: center; }}
    .sum-label {{
      font-size: 0.78rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 6px;
    }}
    .sum-val {{ font-size: 1.8rem; font-weight: 700; }}

    .section-header {{
      font-size: 1.3rem;
      font-weight: 600;
      color: #67e8f9;
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
      background: linear-gradient(180deg, #0f766e, #0e7490);
      border-radius: 2px;
    }}

    .stats-bar {{ display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }}
    .stat-card {{
      background: #0d1f1e;
      border: 1px solid #134e4a;
      border-radius: 10px;
      padding: 12px 20px;
      min-width: 100px;
    }}
    .stat-label {{ font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.07em; }}
    .stat-value {{ font-size: 1.4rem; font-weight: 700; color: #f1f5f9; margin-top: 2px; }}

    .wrap {{
      overflow-x: auto;
      background: #0b1615;
      border: 1px solid #134e4a;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.5);
      margin-bottom: 40px;
    }}
    table {{ border-collapse: collapse; font-size: 0.85rem; width: 100%; }}
    th, td {{ padding: 12px 16px; border: 1px solid #134e4a; white-space: nowrap; }}
    th {{
      background: #0d1f1e;
      color: #94a3b8;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.07em;
      text-align: center;
    }}
    tr:hover td {{ background: rgba(255,255,255,0.015) !important; }}
    code {{
      background: #0d1f1e;
      color: #67e8f9;
      padding: 2px 8px;
      border-radius: 4px;
      border: 1px solid #134e4a;
      font-size: 0.85em;
    }}

    hr {{ border: 0; border-top: 2px dashed #134e4a; margin: 50px 0; }}

    .legend {{ display: flex; gap: 20px; flex-wrap: wrap; margin: 30px 0; font-size: 0.82rem; }}
    .legend-item {{ display: flex; align-items: center; gap: 8px; }}
    .legend-dot {{ width: 12px; height: 12px; border-radius: 3px; }}

    /* Analysis box */
    .analysis-box {{
      background: #0d1f1e;
      border: 1px solid #134e4a;
      border-radius: 12px;
      padding: 24px 32px;
      margin-bottom: 30px;
    }}
    .analysis-box h3 {{
      color: #34d399;
      font-size: 1rem;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }}
    .analysis-box p {{
      color: #94a3b8;
      font-size: 0.9rem;
      margin-bottom: 10px;
    }}
    .comparison-grid {{
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 16px;
      margin-top: 20px;
    }}
    .cmp-card {{
      background: #0a0c14;
      border: 1px solid #1e2438;
      border-radius: 10px;
      padding: 16px;
    }}
    .cmp-label {{ font-size: 0.75rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }}
    .cmp-row {{ display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px; }}
    .cmp-baseline {{ color: #64748b; }}
    .cmp-hybrid {{ color: #34d399; font-weight: 600; }}
    .cmp-arrow {{ color: #fbbf24; }}

    .footer {{
      margin-top: 60px;
      padding-top: 20px;
      border-top: 1px solid #134e4a;
      color: #334155;
      font-size: 0.82rem;
      text-align: center;
    }}
  </style>
</head>
<body>

  <div class="header">
    <div class="badge">Evaluasi Hybrid Search</div>
    <h1>DEMAKAI &mdash; Hybrid Search</h1>
    <p class="meta">Generated: {ts} &nbsp;&middot;&nbsp; {total_queries} query</p>
    <p class="meta">Database: PostgreSQL + pgvector &nbsp;&middot;&nbsp; Semantic: Gemini Embedding + Cosine Distance</p>

    <div class="method-tags">
      <span class="tag active">&#10003; Hybrid Search (Semantic + Keyword)</span>
      <span class="tag active">&#10003; PostgreSQL + pgvector</span>
      <span class="tag active">&#10003; Gemini Embedding</span>
      <span class="tag disabled">&#10007; Preprocessing</span>
      <span class="tag disabled">&#10007; Contoh Lapangan (filter)</span>
    </div>
  </div>

  <!-- Overall Summary Card -->
  <div class="summary-card">
    <div class="sum-item">
      <div class="sum-label">Total Query</div>
      <div class="sum-val" style="color:#67e8f9">{total_queries}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@1</div>
      <div class="sum-val" style="color:#86efac">{ov_top1:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@3</div>
      <div class="sum-val" style="color:#7dd3fc">{ov_top3:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">Top@10</div>
      <div class="sum-val" style="color:#d8b4fe">{ov_top10:.1%}</div>
    </div>
    <div class="sum-item">
      <div class="sum-label">MRR</div>
      <div class="sum-val" style="color:{ov_mrr_col}">{ov_mrr:.4f}</div>
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

  <!-- Ringkasan Perbandingan -->
  <div class="section-header">Ringkasan Hasil Evaluasi</div>
  <div class="wrap" style="max-width: 800px;">
    <table>
      <thead>
        <tr>
          <th style="background:#0d1f1e;color:#67e8f9;text-align:left;font-size:0.8rem">Metrik</th>
          <th style="background:#0d1f1e;color:#67e8f9;font-size:0.8rem">Dataset KBLI 2025</th>
          <th style="background:#0d1f1e;color:#67e8f9;font-size:0.8rem">Dataset KBJI 2014</th>
          <th style="background:#0d1f1e;color:#f1f5f9;font-size:0.8rem">Gabungan (Overall)</th>
        </tr>
      </thead>
      <tbody>
        <tr style="border-bottom:1px solid #134e4a;">
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">Top@1</td>
          <td style="text-align:center;color:#94a3b8">{t1_kbli:.1%}</td>
          <td style="text-align:center;color:#94a3b8">{t1_kbji:.1%}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{ov_top1:.1%}</td>
        </tr>
        <tr style="border-bottom:1px solid #134e4a;">
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">Top@3</td>
          <td style="text-align:center;color:#94a3b8">{t3_kbli:.1%}</td>
          <td style="text-align:center;color:#94a3b8">{t3_kbji:.1%}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{ov_top3:.1%}</td>
        </tr>
        <tr style="border-bottom:1px solid #134e4a;">
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">Top@10</td>
          <td style="text-align:center;color:#94a3b8">{t10_kbli:.1%}</td>
          <td style="text-align:center;color:#94a3b8">{t10_kbji:.1%}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{ov_top10:.1%}</td>
        </tr>
        <tr>
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">MRR</td>
          <td style="text-align:center;color:#94a3b8">{mrr_kbli:.4f}</td>
          <td style="text-align:center;color:#94a3b8">{mrr_kbji:.4f}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{ov_mrr:.4f}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Data Query Evaluasi -->
  <div class="section-header">Data Query Evaluasi</div>
  <div class="wrap" style="max-width: 700px;">
    <table>
      <thead>
        <tr>
          <th style="background:#0d1f1e;color:#67e8f9;font-size:0.75rem;width:40px">#</th>
          <th style="background:#0d1f1e;color:#67e8f9;text-align:left;font-size:0.75rem">Query</th>
        </tr>
      </thead>
      <tbody>
        {query_list_html}
      </tbody>
    </table>
  </div>

  <!-- Analisis Singkat -->
  <div class="section-header">Analisis Singkat</div>
  <div class="analysis-box">
    <h3>&#128202; Perbandingan dengan Baseline (SQL LIKE + SQLite)</h3>
    <p>
      Baseline menggunakan SQL LIKE sederhana di SQLite tanpa preprocessing atau embedding apapun.
      Hybrid Search menambahkan lapisan <strong>semantic search berbasis vektor Gemini</strong>, yang memungkinkan
      sistem memahami <em>makna</em> query — bukan sekadar pencocokan kata per kata.
    </p>
    <p>
      Perbedaan utama: query informal seperti <em>"tukang batu"</em> atau <em>"buruh bangunan"</em>
      sekarang dapat dicocokkan secara semantik dengan entri KBLI/KBJI yang menggunakan terminologi formal
      seperti <em>"Konstruksi Gedung"</em> atau <em>"Pekerja Konstruksi"</em>, meskipun tidak ada kecocokan kata secara langsung.
    </p>
    <div class="comparison-grid">
      <div class="cmp-card">
        <div class="cmp-label">Baseline (SQL LIKE)</div>
        <div class="cmp-row"><span>MRR KBLI</span><span class="cmp-baseline">0.0433</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span class="cmp-baseline">0.0000</span></div>
        <div class="cmp-row"><span>Top@10 Gabungan</span><span class="cmp-baseline">8.3%</span></div>
      </div>
      <div class="cmp-card">
        <div class="cmp-label">Hybrid Search (ini)</div>
        <div class="cmp-row"><span>MRR KBLI</span><span class="cmp-hybrid">{mrr_kbli:.4f}</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span class="cmp-hybrid">{mrr_kbji:.4f}</span></div>
        <div class="cmp-row"><span>Top@10 Gabungan</span><span class="cmp-hybrid">{ov_top10:.1%}</span></div>
      </div>
      <div class="cmp-card">
        <div class="cmp-label">Perubahan</div>
        <div class="cmp-row"><span>MRR KBLI</span><span class="cmp-arrow">{"+" if mrr_kbli >= 0.0433 else ""}{mrr_kbli - 0.0433:+.4f}</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span class="cmp-arrow">{"+" if mrr_kbji > 0 else ""}{mrr_kbji:+.4f}</span></div>
        <div class="cmp-row"><span>Top@10 Gabungan</span><span class="cmp-arrow">{ov_top10 - 0.083:+.1%}</span></div>
      </div>
    </div>
  </div>

  <div class="footer">
    DEMAKAI &mdash; Sistem Evaluasi Hybrid Search &middot; Dibuat otomatis oleh evaluate_hybrid.py
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
    print("  DEMAKAI - Evaluasi Hybrid Search")
    print("  Metode  : Hybrid (Semantic Gemini + SQL LIKE)")
    print("  Database: PostgreSQL + pgvector")
    print("  Fitur   : Tanpa preprocessing, tanpa filter contoh_lapangan")
    print("=" * 60)
    print()

    rows = run_evaluation(QUERIES_FILE, limit=LIMIT)
    if not rows:
        sys.exit(0)

    rows_kbli = [r for r in rows if r.get("tipe") == "KBLI"]
    rows_kbji = [r for r in rows if r.get("tipe") == "KBJI"]

    # Summary ke terminal
    print()
    print("=" * 60)
    print("  RINGKASAN HASIL HYBRID SEARCH")
    print("=" * 60)
    for label, subset in [("KBLI", rows_kbli), ("KBJI", rows_kbji)]:
        n   = len(subset) or 1
        t1  = sum(r["top1"]  for r in subset) / n
        t3  = sum(r["top3"]  for r in subset) / n
        t10 = sum(r["top10"] for r in subset) / n
        mrr = sum(r["rr"]    for r in subset) / n
        print(f"  {label}: Top@1={t1:.3f}  Top@3={t3:.3f}  Top@10={t10:.3f}  MRR={mrr:.4f}")

    all_rows = rows_kbli + rows_kbji
    n_all = len(all_rows) or 1
    print(f"  GABUNGAN: Top@1={sum(r['top1'] for r in all_rows)/n_all:.3f}  "
          f"Top@3={sum(r['top3'] for r in all_rows)/n_all:.3f}  "
          f"Top@10={sum(r['top10'] for r in all_rows)/n_all:.3f}  "
          f"MRR={sum(r['rr'] for r in all_rows)/n_all:.4f}")
    print()
    print("  PERBANDINGAN DENGAN BASELINE (SQL LIKE + SQLite):")
    print("  Baseline: Top@1=0.000  Top@3=0.033  Top@10=0.083  MRR=0.0217")
    print("=" * 60)

    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_dir   = os.path.join(os.path.dirname(__file__), "output")
    csv_path  = os.path.join(out_dir, f"hybrid_{ts}.csv")
    html_path = os.path.join(out_dir, f"hybrid_{ts}.html")

    save_csv(rows, csv_path)
    save_html(rows_kbli, rows_kbji, html_path)

    print()
    print("Selesai! File output tersimpan di folder output/")
    print("  HTML: " + html_path)
    print("  CSV : " + csv_path)

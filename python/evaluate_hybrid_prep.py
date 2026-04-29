"""
evaluate_hybrid_prep.py -- Evaluasi Hybrid Search + Preprocessing (Advanced)
=============================================================================
Pipeline:
  Query → preprocess_advanced() → search_advanced() → Ranking → Metrik

Preprocessing (Advanced):
  1. Case folding (lowercase)
  2. Hapus tanda baca
  3. Tokenisasi
  4. Stopword removal (Bahasa Indonesia)
  5. Stemming (PySastrawi)

Search:
  - Keyword : SQL LIKE dengan stemmed_tokens (AND logic)
  - Semantic : Gemini embedding dari stemmed_clean (cosine distance pgvector)
  - Merge   : boosting berdasarkan posisi match (judul > deskripsi)

Metrik: Rank, Top@1, Top@3, Top@10, RR, MRR
Output : HTML dashboard + CSV

Jalankan:
  python evaluate_hybrid_prep.py
"""

import csv
import os
import sys
import io
from datetime import datetime

# Fix encoding untuk terminal Windows (cp1252 tidak support beberapa karakter)
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
if hasattr(sys.stderr, 'reconfigure'):
    sys.stderr.reconfigure(encoding='utf-8', errors='replace')

sys.path.insert(0, os.path.dirname(__file__))

from preprocessing.advanced import preprocess_advanced
from search.hybrid import search_advanced

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

        # Preprocessing
        preprocessed = preprocess_advanced(query)
        preprocessed_str = preprocessed.get("stemmed_clean", query)

        print(f"  [{no:>2}] {query[:45]:<45} | GT: {kode_gt} | {tipe}", flush=True)
        print(f"         Prep: '{preprocessed_str}'", flush=True)

        try:
            results = search_advanced(preprocessed, limit=limit, model=model)
            rank    = get_rank(results, kode_gt)
            metrics = compute_metrics(rank, top_n=limit)
            print(f"         -> rank={metrics['rank']}  top1={metrics['top1']}  rr={metrics['rr']}")
        except Exception as e:
            print(f"         -> ERROR: {e}")
            metrics = {"rank": 0, "top1": 0, "top3": 0, "top10": 0, "rr": 0.0}

        all_rows.append({
            "no":            no,
            "query":         query,
            "preprocessed":  preprocessed_str,
            "kode_gt":       kode_gt,
            "tipe":          tipe,
            "rank":          metrics["rank"],
            "top1":          metrics["top1"],
            "top3":          metrics["top3"],
            "top10":         metrics["top10"],
            "rr":            metrics["rr"],
        })

    return all_rows


# ============================================================================
# SIMPAN CSV
# ============================================================================

def save_csv(rows: list, filepath: str):
    if not rows:
        return
    fieldnames = ["no", "tipe", "query", "preprocessed", "kode_gt", "rank", "top1", "top3", "top10", "rr"]
    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)
    print("[CSV] Disimpan -> " + os.path.abspath(filepath))


# ============================================================================
# SIMPAN HTML DASHBOARD
# ============================================================================

def save_html(rows_kbli: list, rows_kbji: list, filepath: str):
    ts            = datetime.now().strftime("%d %B %Y, %H:%M:%S")
    total_queries = len(rows_kbli) + len(rows_kbji)

    # ---- Hitung semua metrik summary ----
    all_rows   = rows_kbli + rows_kbji
    n_all      = len(all_rows) or 1
    ov_top1    = sum(r["top1"]  for r in all_rows) / n_all
    ov_top3    = sum(r["top3"]  for r in all_rows) / n_all
    ov_top10   = sum(r["top10"] for r in all_rows) / n_all
    ov_mrr     = sum(r["rr"]    for r in all_rows) / n_all
    ov_mrr_col = "#6ee7b7" if ov_mrr >= 0.5 else "#fbbf24" if ov_mrr >= 0.2 else "#f87171"

    n_kbli   = len(rows_kbli) or 1
    t1_kbli  = sum(r["top1"]  for r in rows_kbli) / n_kbli
    t3_kbli  = sum(r["top3"]  for r in rows_kbli) / n_kbli
    t10_kbli = sum(r["top10"] for r in rows_kbli) / n_kbli
    mrr_kbli = sum(r["rr"]    for r in rows_kbli) / n_kbli

    n_kbji   = len(rows_kbji) or 1
    t1_kbji  = sum(r["top1"]  for r in rows_kbji) / n_kbji
    t3_kbji  = sum(r["top3"]  for r in rows_kbji) / n_kbji
    t10_kbji = sum(r["top10"] for r in rows_kbji) / n_kbji
    mrr_kbji = sum(r["rr"]    for r in rows_kbji) / n_kbji

    # ---- Helper bangun tabel section ----
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
            prep_display = r.get("preprocessed", "-")
            rows_html += f"""
            <tr>
              <td style='text-align:center;color:#94a3b8'>{r['no']}</td>
              <td style='color:{tipe_color};font-weight:bold;text-align:center'>{r['tipe']}</td>
              <td style='color:#c4b5fd;text-align:left;max-width:220px;white-space:normal'>{r['query']}</td>
              <td style='color:#fcd34d;text-align:left;max-width:200px;white-space:normal;font-size:0.8rem'>{prep_display}</td>
              <td style='text-align:center'><code>{r['kode_gt']}</code></td>
              <td {rank_style(r['rank'])}>{r['rank'] if r['rank'] > 0 else '-'}</td>
              <td {hit_style(r['top1'])}>{r['top1']}</td>
              <td {hit_style(r['top3'])}>{r['top3']}</td>
              <td {hit_style(r['top10'])}>{r['top10']}</td>
              <td style='text-align:center;color:{"#6ee7b7" if r["rr"] > 0 else "#475569"};font-weight:{"bold" if r["rr"] > 0 else "normal"}'>{r['rr'] if r['rr'] > 0 else '-'}</td>
            </tr>"""

        mrr_color_row = "#6ee7b7" if mrr >= 0.5 else "#fbbf24" if mrr >= 0.2 else "#f87171"
        rows_html += f"""
            <tr style='background:#1f2937;border-top:2px solid #f59e0b'>
              <td colspan='5' style='text-align:right;font-weight:bold;color:#fcd34d;padding-right:20px'>
                MRR (Mean Reciprocal Rank)
              </td>
              <td style='text-align:center;color:#94a3b8'>&mdash;</td>
              <td style='text-align:center;font-weight:bold;color:#fcd34d'>{top1_avg:.3f}</td>
              <td style='text-align:center;font-weight:bold;color:#fcd34d'>{top3_avg:.3f}</td>
              <td style='text-align:center;font-weight:bold;color:#fcd34d'>{top10_avg:.3f}</td>
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
                <th style='text-align:left;min-width:200px'>Query Asli</th>
                <th style='text-align:left;min-width:180px'>Preprocessing ✓</th>
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

    # ---- Data Query list (unique, urut no) ----
    all_sorted = sorted(all_rows, key=lambda r: int(r["no"]))
    seen_q = set()
    unique_queries = []
    for r in all_sorted:
        if r["query"] not in seen_q:
            unique_queries.append({"query": r["query"], "prep": r.get("preprocessed", "-")})
            seen_q.add(r["query"])

    query_list_html = ""
    for idx, item in enumerate(unique_queries, 1):
        query_list_html += f"""
        <tr>
          <td style='text-align:center;color:#64748b;width:40px'>{idx}</td>
          <td style='color:#c4b5fd'>{item['query']}</td>
          <td style='color:#fcd34d;font-size:0.82rem'>{item['prep']}</td>
        </tr>"""

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DEMAKAI - Hybrid Search + Preprocessing</title>
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
      background: linear-gradient(135deg, #b45309, #d97706);
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
      background: linear-gradient(90deg, #fbbf24, #f59e0b);
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
    .tag.active {{ border-color: #d97706; color: #fcd34d; background: #1c160522; }}
    .tag.disabled {{ color: #475569; text-decoration: line-through; opacity: 0.6; }}

    .summary-card {{
      background: linear-gradient(135deg, #1c1404, #1c1208);
      border: 1px solid #78350f;
      border-radius: 16px;
      padding: 28px 36px;
      margin-bottom: 40px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
      gap: 20px;
    }}
    .sum-item {{ text-align: center; }}
    .sum-label {{ font-size: 0.78rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }}
    .sum-val {{ font-size: 1.8rem; font-weight: 700; }}

    .section-header {{
      font-size: 1.3rem;
      font-weight: 600;
      color: #fcd34d;
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
      background: linear-gradient(180deg, #b45309, #d97706);
      border-radius: 2px;
    }}

    .stats-bar {{ display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }}
    .stat-card {{
      background: #150e00;
      border: 1px solid #78350f;
      border-radius: 10px;
      padding: 12px 20px;
      min-width: 100px;
    }}
    .stat-label {{ font-size: 0.72rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.07em; }}
    .stat-value {{ font-size: 1.4rem; font-weight: 700; color: #f1f5f9; margin-top: 2px; }}

    .wrap {{
      overflow-x: auto;
      background: #100b00;
      border: 1px solid #78350f;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.5);
      margin-bottom: 40px;
    }}
    table {{ border-collapse: collapse; font-size: 0.85rem; width: 100%; }}
    th, td {{ padding: 12px 16px; border: 1px solid #78350f; white-space: nowrap; }}
    th {{
      background: #150e00;
      color: #94a3b8;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.07em;
      text-align: center;
    }}
    tr:hover td {{ background: rgba(255,255,255,0.015) !important; }}
    code {{
      background: #150e00;
      color: #fcd34d;
      padding: 2px 8px;
      border-radius: 4px;
      border: 1px solid #78350f;
      font-size: 0.85em;
    }}

    hr {{ border: 0; border-top: 2px dashed #78350f; margin: 50px 0; }}

    .legend {{ display: flex; gap: 20px; flex-wrap: wrap; margin: 30px 0; font-size: 0.82rem; }}
    .legend-item {{ display: flex; align-items: center; gap: 8px; }}
    .legend-dot {{ width: 12px; height: 12px; border-radius: 3px; }}

    /* Pipeline box */
    .pipeline-box {{
      background: #100b00;
      border: 1px solid #78350f;
      border-radius: 12px;
      padding: 20px 28px;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 0;
      flex-wrap: wrap;
    }}
    .pipe-step {{
      background: #1c1404;
      border: 1px solid #d97706;
      border-radius: 8px;
      padding: 8px 16px;
      color: #fcd34d;
      font-size: 0.85rem;
      font-weight: 500;
      white-space: nowrap;
    }}
    .pipe-arrow {{
      color: #d97706;
      font-size: 1.2rem;
      padding: 0 10px;
    }}

    /* Analysis box */
    .analysis-box {{
      background: #100b00;
      border: 1px solid #78350f;
      border-radius: 12px;
      padding: 24px 32px;
      margin-bottom: 30px;
    }}
    .analysis-box h3 {{ color: #fbbf24; font-size: 1rem; margin-bottom: 14px; }}
    .analysis-box p {{ color: #94a3b8; font-size: 0.9rem; margin-bottom: 10px; }}
    .analysis-grid {{
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 16px;
      margin-top: 20px;
    }}
    .ana-card {{
      background: #0a0c14;
      border: 1px solid #1e2438;
      border-radius: 10px;
      padding: 16px;
    }}
    .ana-title {{ font-size: 0.78rem; color: #fcd34d; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; }}
    .ana-text {{ font-size: 0.85rem; color: #94a3b8; line-height: 1.5; }}

    .comparison-grid {{
      display: grid;
      grid-template-columns: 1fr 1fr 1fr 1fr;
      gap: 14px;
      margin-top: 20px;
    }}
    .cmp-card {{ background: #0a0c14; border: 1px solid #1e2438; border-radius: 10px; padding: 16px; }}
    .cmp-label {{ font-size: 0.72rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }}
    .cmp-row {{ display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px; }}
    .cmp-baseline {{ color: #64748b; }}
    .cmp-hybrid {{ color: #34d399; }}
    .cmp-prep {{ color: #fbbf24; font-weight: 600; }}
    .cmp-arrow {{ color: #a78bfa; }}

    .footer {{
      margin-top: 60px;
      padding-top: 20px;
      border-top: 1px solid #78350f;
      color: #334155;
      font-size: 0.82rem;
      text-align: center;
    }}
  </style>
</head>
<body>

  <div class="header">
    <div class="badge">Evaluasi Hybrid + Preprocessing</div>
    <h1>DEMAKAI &mdash; Hybrid Search + Preprocessing</h1>
    <p class="meta">Generated: {ts} &nbsp;&middot;&nbsp; {total_queries} query</p>
    <p class="meta">Database: PostgreSQL + pgvector &nbsp;&middot;&nbsp; Preprocessing: Lowercase → Stopword → Stemming (PySastrawi)</p>

    <div class="method-tags">
      <span class="tag active">&#10003; SQL LIKE (Keyword)</span>
      <span class="tag active">&#10003; Semantic Search (Gemini Embedding)</span>
      <span class="tag active">&#10003; Hybrid Search</span>
      <span class="tag active">&#10003; Preprocessing (Stopword + Stemming)</span>
      <span class="tag disabled">&#10007; Contoh Lapangan (filter)</span>
    </div>
  </div>

  <!-- Pipeline -->
  <div class="pipeline-box">
    <div class="pipe-step">&#128196; Query Asli</div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step">&#9986; Preprocessing<br><small style="color:#94a3b8;font-weight:400">lowercase, stopword, stemming</small></div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step">&#128269; SQL LIKE<br><small style="color:#94a3b8;font-weight:400">stemmed tokens, AND logic</small></div>
    <div class="pipe-arrow">&#43;</div>
    <div class="pipe-step">&#129504; Semantic<br><small style="color:#94a3b8;font-weight:400">stemmed_clean → embedding</small></div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step">&#127941; Ranking<br><small style="color:#94a3b8;font-weight:400">merge + boost</small></div>
    <div class="pipe-arrow">&#8594;</div>
    <div class="pipe-step">&#128202; Evaluasi</div>
  </div>

  <!-- Overall Summary Card -->
  <div class="summary-card">
    <div class="sum-item">
      <div class="sum-label">Total Query</div>
      <div class="sum-val" style="color:#fcd34d">{total_queries}</div>
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
    <div class="legend-item">
      <div class="legend-dot" style="background:#422006;border:1px solid #fcd34d"></div>
      <span style="color:#fcd34d">Kolom kuning = query hasil preprocessing</span>
    </div>
  </div>

  <!-- Section KBLI -->
  {section_kbli}

  <hr>

  <!-- Section KBJI -->
  {section_kbji}

  <hr>

  <!-- Ringkasan Evaluasi -->
  <div class="section-header">Ringkasan Hasil Evaluasi</div>
  <div class="wrap" style="max-width:800px;">
    <table>
      <thead>
        <tr>
          <th style="background:#150e00;color:#fcd34d;text-align:left;font-size:0.8rem">Metrik</th>
          <th style="background:#150e00;color:#fcd34d;font-size:0.8rem">Dataset KBLI 2025</th>
          <th style="background:#150e00;color:#fcd34d;font-size:0.8rem">Dataset KBJI 2014</th>
          <th style="background:#150e00;color:#f1f5f9;font-size:0.8rem">Gabungan (Overall)</th>
        </tr>
      </thead>
      <tbody>
        <tr style="border-bottom:1px solid #78350f;">
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">Top@1</td>
          <td style="text-align:center;color:#94a3b8">{t1_kbli:.1%}</td>
          <td style="text-align:center;color:#94a3b8">{t1_kbji:.1%}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{ov_top1:.1%}</td>
        </tr>
        <tr style="border-bottom:1px solid #78350f;">
          <td style="font-weight:bold;color:#f1f5f9;font-size:0.9rem">Top@3</td>
          <td style="text-align:center;color:#94a3b8">{t3_kbli:.1%}</td>
          <td style="text-align:center;color:#94a3b8">{t3_kbji:.1%}</td>
          <td style="text-align:center;font-weight:bold;color:#e2e8f0">{ov_top3:.1%}</td>
        </tr>
        <tr style="border-bottom:1px solid #78350f;">
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
  <div class="wrap" style="max-width:900px;">
    <table>
      <thead>
        <tr>
          <th style="background:#150e00;color:#fcd34d;font-size:0.75rem;width:40px">#</th>
          <th style="background:#150e00;color:#fcd34d;text-align:left;font-size:0.75rem">Query Asli</th>
          <th style="background:#150e00;color:#fcd34d;text-align:left;font-size:0.75rem">Setelah Preprocessing ✓</th>
        </tr>
      </thead>
      <tbody>
        {query_list_html}
      </tbody>
    </table>
  </div>

  <!-- Analisis -->
  <div class="section-header">Analisis Dampak Preprocessing</div>
  <div class="analysis-box">
    <h3>&#129302; Dampak Preprocessing pada Sistem Hybrid</h3>
    <p>
      Preprocessing mengubah query mentah (informal, bercampur tanda baca) menjadi bentuk yang lebih
      bersih dan terstandardisasi sebelum diteruskan ke mesin pencarian. Ada dua dampak utama:
    </p>
    <div class="analysis-grid">
      <div class="ana-card">
        <div class="ana-title">&#128269; Dampak pada SQL LIKE</div>
        <div class="ana-text">
          Token setelah stemming (<em>stemmed_tokens</em>) digunakan sebagai basis pencarian LIKE.
          Kata "mencabut" dan "cabut" menghasilkan token yang sama, sehingga query lebih presisi
          dan menghindari noise dari stopword ("di", "untuk", "di", dll.).
          Logika AND antar token memastikan hasil lebih spesifik.
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-title">&#129504; Dampak pada Semantic Search</div>
        <div class="ana-text">
          String <em>stemmed_clean</em> (token yang sudah di-stem, digabung kembali) digunakan
          sebagai input embedding Gemini. Teks yang lebih ringkas, padat, dan konsisten
          menghasilkan vektor representasi yang lebih terfokus dan akurat secara semantik,
          mengurangi "noise" dari filler words.
        </div>
      </div>
      <div class="ana-card">
        <div class="ana-title">&#127919; Stabilitas dan Akurasi</div>
        <div class="ana-text">
          Dengan preprocessing, variasi penulisan yang berbeda (typo, ejaan berbeda, kata formal vs
          informal) lebih mudah disamakan ke bentuk dasar yang sama. Hasilnya adalah pencarian yang
          lebih <strong style="color:#fcd34d">konsisten</strong> dan tidak bergantung pada
          penulisan persis dari user.
        </div>
      </div>
    </div>

    <h3 style="margin-top:24px;">&#128202; Perbandingan Lengkap Tiga Tahap</h3>
    <div class="comparison-grid">
      <div class="cmp-card">
        <div class="cmp-label">Baseline (SQL LIKE)</div>
        <div class="cmp-row"><span>MRR Overall</span><span class="cmp-baseline">0.0217</span></div>
        <div class="cmp-row"><span>MRR KBLI</span><span class="cmp-baseline">0.0433</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span class="cmp-baseline">0.0000</span></div>
        <div class="cmp-row"><span>Top@10</span><span class="cmp-baseline">8.3%</span></div>
      </div>
      <div class="cmp-card">
        <div class="cmp-label">Hybrid Search (tanpa prep)</div>
        <div class="cmp-row"><span>MRR Overall</span><span class="cmp-hybrid">0.8499</span></div>
        <div class="cmp-row"><span>MRR KBLI</span><span class="cmp-hybrid">0.9167</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span class="cmp-hybrid">0.7831</span></div>
        <div class="cmp-row"><span>Top@10</span><span class="cmp-hybrid">93.3%</span></div>
      </div>
      <div class="cmp-card">
        <div class="cmp-label">Hybrid + Preprocessing (ini)</div>
        <div class="cmp-row"><span>MRR Overall</span><span class="cmp-prep">{ov_mrr:.4f}</span></div>
        <div class="cmp-row"><span>MRR KBLI</span><span class="cmp-prep">{mrr_kbli:.4f}</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span class="cmp-prep">{mrr_kbji:.4f}</span></div>
        <div class="cmp-row"><span>Top@10</span><span class="cmp-prep">{ov_top10:.1%}</span></div>
      </div>
      <div class="cmp-card">
        <div class="cmp-label">Perubahan vs Hybrid</div>
        <div class="cmp-row"><span>MRR Overall</span><span class="cmp-arrow">{ov_mrr - 0.8499:+.4f}</span></div>
        <div class="cmp-row"><span>MRR KBLI</span><span class="cmp-arrow">{mrr_kbli - 0.9167:+.4f}</span></div>
        <div class="cmp-row"><span>MRR KBJI</span><span class="cmp-arrow">{mrr_kbji - 0.7831:+.4f}</span></div>
        <div class="cmp-row"><span>Top@10</span><span class="cmp-arrow">{ov_top10 - 0.933:+.1%}</span></div>
      </div>
    </div>
  </div>

  <div class="footer">
    DEMAKAI &mdash; Sistem Evaluasi Hybrid + Preprocessing &middot; Dibuat otomatis oleh evaluate_hybrid_prep.py
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
    print("  DEMAKAI - Evaluasi Hybrid Search + Preprocessing")
    print("  Pipeline: Query -> preprocess_advanced -> search_advanced")
    print("  Preprocessing: lowercase, stopword, stemming (PySastrawi)")
    print("  Database: PostgreSQL + pgvector")
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
    print("  RINGKASAN HASIL HYBRID + PREPROCESSING")
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
    print("  PERBANDINGAN:")
    print("  Baseline  : Top@1=0.000  Top@3=0.033  Top@10=0.083  MRR=0.0217")
    print("  Hybrid    : Top@1=0.800  Top@3=0.900  Top@10=0.933  MRR=0.8499")
    print("=" * 60)

    ts = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_dir   = os.path.join(os.path.dirname(__file__), "output")
    csv_path  = os.path.join(out_dir, f"hybrid_prep_{ts}.csv")
    html_path = os.path.join(out_dir, f"hybrid_prep_{ts}.html")

    save_csv(rows, csv_path)
    save_html(rows_kbli, rows_kbji, html_path)

    print()
    print("Selesai! File output tersimpan di folder output/")
    print("  HTML: " + html_path)
    print("  CSV : " + csv_path)

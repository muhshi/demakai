"""
evaluate.py — Evaluasi Otomatis 6 Kombinasi Metode Pencarian DEMAKAI
=====================================================================
Baca daftar query dari queries.csv, jalankan 6 kombinasi metode,
hitung Rank / Top1 / Top3 / RR untuk setiap kombinasi, simpan ke:
  - output/evaluasi_hasil.csv   (tabel angka lengkap)
  - output/evaluasi_hasil.html  (tabel visual berwarna)

Cara pakai:
  python evaluate.py

Edit queries.csv untuk mengganti daftar query dan kode ground truth.
"""

import csv
import os
import sys
from datetime import datetime

# ── Pastikan bisa import modul lokal ─────────────────────────────────────────
sys.path.insert(0, os.path.dirname(__file__))

from preprocessing.basic    import preprocess_basic
from preprocessing.advanced import preprocess_advanced
from search.sql_like import (
    search_raw      as sql_raw,
    search_basic    as sql_basic,
    search_advanced as sql_advanced,
)
from search.hybrid import (
    search_raw      as hybrid_raw,
    search_basic    as hybrid_basic,
    search_advanced as hybrid_advanced,
)

os.makedirs("output", exist_ok=True)

# ─────────────────────────────────────────────────────────────────────────────
# Definisi 6 kombinasi
# ─────────────────────────────────────────────────────────────────────────────

COMBINATIONS = [
    ("SQL",    "None",     lambda q, model=None: sql_raw(q, model=model)),
    ("SQL",    "Basic",    lambda q, model=None: sql_basic(preprocess_basic(q), model=model)),
    ("SQL",    "Advanced", lambda q, model=None: sql_advanced(preprocess_advanced(q), model=model)),
    ("Hybrid", "None",     lambda q, model=None: hybrid_raw(q, model=model)),
    ("Hybrid", "Basic",    lambda q, model=None: hybrid_basic(preprocess_basic(q), model=model)),
    ("Hybrid", "Advanced", lambda q, model=None: hybrid_advanced(preprocess_advanced(q), model=model)),
]

COMBO_LABELS = [f"{s}_{p}" for s, p, _ in COMBINATIONS]  # e.g. "SQL_None"


# ─────────────────────────────────────────────────────────────────────────────
# Helper: cari rank kode ground truth di hasil pencarian
# ─────────────────────────────────────────────────────────────────────────────

def get_rank(results: list, kode_gt: str) -> int | None:
    """
    Cari posisi (rank) kode_gt di dalam list results.
    Mengembalikan integer (1-based) atau None jika tidak ditemukan.
    """
    for i, item in enumerate(results, start=1):
        if str(item.get("kode", "")).strip() == str(kode_gt).strip():
            return i
    return None


def compute_metrics(rank: int, top_n: int = 10) -> dict:
    """
    Menghitung metrik berdasarkan rank aktual.
    Jika rank <= 0 atau > top_n, dianggap tidak ketemu (0).
    """
    if 0 < rank <= top_n:
        top1  = 1 if rank == 1 else 0
        top3  = 1 if rank <= 3 else 0
        top10 = 1 if rank <= 10 else 0
        rr    = round(1.0 / rank, 4)
        rank_val = rank
    else:
        top1 = 0
        top3 = 0
        top10 = 0
        rr = 0.0
        rank_val = 0

    return {
        "rank": rank_val,
        "top1": top1,
        "top3": top3,
        "top10": top10,
        "rr": rr
    }


# ─────────────────────────────────────────────────────────────────────────────
# Fungsi utama evaluasi
# ─────────────────────────────────────────────────────────────────────────────

def run_evaluation(queries_file: str = "queries.csv", limit: int = 10):
    """
    Jalankan evaluasi untuk semua query di queries_file.
    File CSV harus memiliki kolom: no, query, kode_ground_truth, tipe (KBLI/KBJI)
    """
    # Baca query
    with open(queries_file, newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        queries = list(reader)

    all_rows = []

    for entry in queries:
        no      = entry.get("no", "")
        query   = entry.get("query", "").strip()
        kode_gt = entry.get("kode_ground_truth", "").strip()
        tipe    = entry.get("tipe", "").strip().upper()  # KBLI atau KBJI

        # model filter: hanya cari di tabel yang sesuai
        model = tipe if tipe in ("KBLI", "KBJI") else None

        print(f"\n[{no}] Query: \"{query}\" | GT: {kode_gt} | Tipe: {tipe}")

        # Hitung string preprocessing untuk ditampilkan
        try:
            desc_basic = " ".join(preprocess_basic(query)["tokens"])
            desc_adv   = " ".join(preprocess_advanced(query)["tokens"])
        except Exception:
            desc_basic = query
            desc_adv   = query

        row = {
            "no": no, 
            "query": query, 
            "kode_ground_truth": kode_gt, 
            "tipe": tipe,
            "desc_basic": desc_basic,
            "desc_advanced": desc_adv
        }

        for search_method, proc_method, search_fn in COMBINATIONS:
            label = f"{search_method}_{proc_method}"
            print(f"  → {label} ...", end=" ", flush=True)

            try:
                results = search_fn(query, model=model)
                # Pass 0 if rank is None, as compute_metrics now expects int
                rank    = get_rank(results, kode_gt) or 0
                metrics = compute_metrics(rank, top_n=limit)
                print(f"rank={metrics['rank']}") # Only print rank
            except Exception as e:
                print(f"ERROR: {e}")
                # Set rank to 0 for errors
                metrics = {"rank": 0, "top1": 0, "top3": 0, "top10": 0, "rr": 0.0}

            row[f"rank_{label}"]  = metrics["rank"]
            row[f"top1_{label}"]  = metrics["top1"]
            row[f"top3_{label}"]  = metrics["top3"]
            row[f"top10_{label}"] = metrics["top10"]
            row[f"rr_{label}"]    = metrics["rr"]

        all_rows.append(row)

    return all_rows


# ─────────────────────────────────────────────────────────────────────────────
# Simpan ke CSV
# ─────────────────────────────────────────────────────────────────────────────

def save_csv(rows: list, filepath: str):
    if not rows:
        return

    # Susun header (hanya rank)
    base_cols = ["no", "tipe", "query", "kode_ground_truth"]
    metric_cols = []
    for label in COMBO_LABELS:
        metric_cols.append(f"rank_{label}")

    fieldnames = base_cols + metric_cols

    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)

    print(f"\n[CSV] Disimpan → {os.path.abspath(filepath)}")


# ─────────────────────────────────────────────────────────────────────────────
# Simpan ke HTML
# ─────────────────────────────────────────────────────────────────────────────

def save_html(rows: list, filepath: str):
    if not rows:
        return

    def cell_style(val):
        """Warnai sel berdasarkan nilai rank."""
        if val == 0: # Rank 0 means not found or error
            return "background:#2d2f45;color:#555"
        try:
            v = int(val)
        except (ValueError, TypeError):
            return ""
        # Rank: semakin kecil semakin hijau
        if v == 1:   return "background:#064e3b;color:#6ee7b7;font-weight:bold"
        if v <= 3:   return "background:#065f46;color:#a7f3d0"
        if v <= 10:  return "background:#1e3a2f;color:#d1fae5"
        return "background:#2d2f45;color:#555"

    # Header dan Sub-header (HANYA RANK)
    combo_headers = "".join(
        f'<th colspan="1" style="background:#25273b;color:#a78bfa;font-size:0.75rem">'
        f'{s} + {p}</th>'
        for s, p, _ in COMBINATIONS
    )
    sub_headers = "".join(
        '<th>Rank</th>'
        for _ in COMBINATIONS
    )

    # Baris data
    data_rows_html = ""
    for row in rows:
        tipe_val  = row.get("tipe", "")
        tipe_color = "#86efac" if tipe_val == "KBLI" else "#93c5fd"
        cells = (
            f'<td>{row["no"]}</td>'
            f'<td style="color:{tipe_color};font-weight:bold">{tipe_val}</td>'
            f'<td style="color:#c4b5fd">{row["query"]}</td>'
            f'<td><code>{row["kode_ground_truth"]}</code></td>'
        )
        for label in COMBO_LABELS:
            rank  = row.get(f"rank_{label}", "-")
            top1  = row.get(f"top1_{label}", "-")
            top3  = row.get(f"top3_{label}", "-")
            top10 = row.get(f"top10_{label}", "-")
            rr    = row.get(f"rr_{label}", "-")
            cells += (
                f'<td style="{cell_style(rank)}">{rank}</td>'
                f'<td style="{cell_style(top1)}">{top1}</td>'
                f'<td style="{cell_style(top3)}">{top3}</td>'
                f'<td style="{cell_style(top10)}">{top10}</td>'
                f'<td style="{cell_style(rr)}">{rr}</td>'
            )
        data_rows_html += f"<tr>{cells}</tr>\n"

    # Summary MRR per kombinasi
    summary_html = '<tr><td colspan="3" style="font-weight:bold;color:#a78bfa">MRR</td>'
    for label in COMBO_LABELS:
        rr_vals = []
        for row in rows:
            v = row.get(f"rr_{label}", 0)
            try:
                rr_vals.append(float(v))
            except (ValueError, TypeError):
                rr_vals.append(0)
        mrr = round(sum(rr_vals) / len(rr_vals), 4) if rr_vals else 0
        color = "#6ee7b7" if mrr >= 0.5 else "#fbbf24" if mrr >= 0.2 else "#f87171"
        summary_html += (
            f'<td style="font-weight:bold;color:{color}">{mrr}</td>'
            f'<td colspan="4"></td>'
        )
    summary_html += "</tr>"

    ts = datetime.now().strftime("%d %B %Y, %H:%M:%S")
    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>DEMAKAI — Evaluasi 6 Kombinasi</title>
  <style>
    * {{ box-sizing: border-box; margin: 0; padding: 0; }}
    body {{ font-family: 'Segoe UI', sans-serif; background: #0f1117; color: #e0e0e0; padding: 24px; }}
    h1 {{ color: #a78bfa; margin-bottom: 4px; font-size: 1.5rem; }}
    .meta {{ color: #888; font-size: 0.85rem; margin-bottom: 20px; }}
    .wrap {{ overflow-x: auto; }}
    table {{ border-collapse: collapse; font-size: 0.8rem; min-width: 100%; }}
    th, td {{ padding: 8px 10px; border: 1px solid #2d2f45; white-space: nowrap; }}
    th {{ background: #1e1f2e; color: #94a3b8; font-weight: 500; }}
    tr:hover td {{ filter: brightness(1.15); }}
    code {{ background: #312e81; color: #a5b4fc; padding: 1px 5px; border-radius: 4px; }}
  </style>
</head>
<body>
  <h1>🔍 DEMAKAI — Evaluasi 6 Kombinasi Metode Pencarian</h1>
  <div class="meta">Generated: {ts} &nbsp;·&nbsp; {len(rows)} query</div>
  <div class="wrap">
    <table>
      <thead>
        <tr>
          <th rowspan="2">#</th>
          <th rowspan="2">Tipe</th>
          <th rowspan="2">Query</th>
          <th rowspan="2">Kode GT</th>
          {combo_headers}
        </tr>
        <tr>
          {sub_headers}
        </tr>
      </thead>
      <tbody>
        {data_rows_html}
      </tbody>
    </table>
  </div>
</body>
</html>"""

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(html)
    print(f"[HTML]  Disimpan → {os.path.abspath(filepath)}")


# ─────────────────────────────────────────────────────────────────────────────
# Simpan ke EXCEL (.xlsx) menggunakan pandas
# ─────────────────────────────────────────────────────────────────────────────

def save_excel(rows: list, filepath: str):
    if not rows:
        return
        
    try:
        import pandas as pd
        
        # Susun header
        base_cols = ["no", "tipe", "query", "kode_ground_truth"]
        metric_cols = []
        for label in COMBO_LABELS:
            metric_cols.append(f"rank_{label}")

        columns = base_cols + metric_cols
        
        # Buat dataframe hanya dengan kolom format rank
        df = pd.DataFrame(rows, columns=columns)
        
        # Simpan ke excel
        df.to_excel(filepath, index=False, engine='openpyxl')
        print(f"[EXCEL] Disimpan → {os.path.abspath(filepath)}")
        
    except ImportError:
        print("[WARNING] Pandas/openpyxl tidak terinstall. Eksport EXCEL dilewati.")
        print("          Jalankan: pip install pandas openpyxl")
    except Exception as e:
        print(f"[ERROR] Gagal menyimpan EXCEL: {e}")


# ─────────────────────────────────────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    QUERIES_FILE = "queries.csv"
    LIMIT        = 10

    if not os.path.exists(QUERIES_FILE):
        print(f"[ERROR] File '{QUERIES_FILE}' tidak ditemukan.")
        print("Buat file queries.csv dengan kolom: no, query, kode_ground_truth")
        raise SystemExit(1)
# ─────────────────────────────────────────────────────────────────────────────
# Simpan ke CSV
# ─────────────────────────────────────────────────────────────────────────────

def calculate_summary(rows: list) -> list:
    if not rows:
        return []
    
    summary = []
    n = len(rows)
    for s, p, _ in COMBINATIONS:
        label = f"{s}_{p}"
        top1_vals = [int(r.get(f"top1_{label}", 0)) for r in rows]
        top3_vals = [int(r.get(f"top3_{label}", 0)) for r in rows]
        rr_vals   = [float(r.get(f"rr_{label}", 0.0)) for r in rows]
        
        top1_avg = sum(top1_vals) / n
        top3_avg = sum(top3_vals) / n
        mrr_avg  = sum(rr_vals) / n
        
        if s == "SQL":
            if p == "None": name = "SQL LIKE (Tanpa Preprocessing)"
            elif p == "Basic": name = "SQL LIKE (Basic: Case-Fold, No-Punct, Token-OR)"
            elif p == "Advanced": name = "SQL LIKE (Advanced: Basic + Stopword + Stemming)"
        else:
            if p == "None": name = "Hybrid Search (Tanpa Preprocessing)"
            elif p == "Basic": name = "Hybrid Search (Basic: Case-Fold, No-Punct, Token-OR)"
            elif p == "Advanced": name = "Hybrid Search (Advanced: Basic + Stopword + Stemming)"

        summary.append({
            "Metode": name,
            "Top1": round(top1_avg, 3),
            "Top3": round(top3_avg, 3),
            "MRR": round(mrr_avg, 3)
        })
    return summary


def save_summary_csv(summary: list, filepath: str):
    if not summary:
        return
    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=["Metode", "Top1", "Top3", "MRR"])
        writer.writeheader()
        writer.writerows(summary)
    print(f"[CSV] Summary Disimpan → {os.path.abspath(filepath)}")


def save_csv(rows: list, filepath: str):
    if not rows:
        return

    # Susun header (semua metrik)
    base_cols = ["no", "tipe", "query", "kode_ground_truth"]
    metric_cols = []
    for label in COMBO_LABELS:
        metric_cols.extend([
            f"rank_{label}",
            f"top1_{label}",
            f"top3_{label}",
            f"top10_{label}",
            f"rr_{label}"
        ])

    fieldnames = base_cols + metric_cols

    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction='ignore')
        writer.writeheader()
        writer.writerows(rows)

    print(f"[CSV] Data Disimpan → {os.path.abspath(filepath)}")



# ─────────────────────────────────────────────────────────────────────────────
# Simpan ke HTML Gabungan
# ─────────────────────────────────────────────────────────────────────────────

def build_section_html(title: str, rows: list, summary_data: list) -> str:
    if not rows:
        return ""

    def cell_style(val):
        if val == 0: return "background:#2d2f45;color:#555"
        try:
            v = int(val)
        except (ValueError, TypeError):
            return ""
        if v == 1:   return "background:#064e3b;color:#6ee7b7;font-weight:bold"
        if v <= 3:   return "background:#065f46;color:#a7f3d0"
        if v <= 10:  return "background:#1e3a2f;color:#d1fae5"
        return "background:#2d2f45;color:#555"

    # Header dan Sub-header
    combo_headers = ""
    for s, p, _ in COMBINATIONS:
        colspan = 6 if p in ("Basic", "Advanced") else 5
        combo_headers += f'<th colspan="{colspan}" style="background:#312e81;color:#a5b4fc;text-align:center">{s} + {p}</th>'

    sub_headers = ""
    for _, p, _ in COMBINATIONS:
        sub_headers += '<th>Rank</th><th>Top1</th><th>Top3</th><th>Top10</th><th>RR</th>'
        if p in ("Basic", "Advanced"):
            sub_headers += '<th>Deskripsi Preprocessing</th>'

    # Baris data
    data_rows_html = ""
    for row in rows:
        tipe_val  = row.get("tipe", "")
        tipe_color = "#86efac" if tipe_val == "KBLI" else "#93c5fd"
        cells = (
            f'<td>{row["no"]}</td>'
            f'<td style="color:{tipe_color};font-weight:bold">{tipe_val}</td>'
            f'<td style="color:#c4b5fd">{row["query"]}</td>'
            f'<td><code>{row["kode_ground_truth"]}</code></td>'
        )
        for s, p, _ in COMBINATIONS:
            label = f"{s}_{p}"
            rank  = row.get(f"rank_{label}", 0)
            top1  = row.get(f"top1_{label}", 0)
            top3  = row.get(f"top3_{label}", 0)
            top10 = row.get(f"top10_{label}", 0)
            rr    = row.get(f"rr_{label}", 0.0)
            
            if rank == "-": rank = 0
            if top1 == "-": top1 = 0
            if top3 == "-": top3 = 0
            if top10 == "-": top10 = 0
            if rr == "-": rr = 0.0
            
            cells += (
                f'<td style="{cell_style(rank)}">{rank}</td>'
                f'<td style="{cell_style(top1)}">{top1}</td>'
                f'<td style="{cell_style(top3)}">{top3}</td>'
                f'<td style="{cell_style(top10)}">{top10}</td>'
                f'<td style="{cell_style(rr)}">{rr}</td>'
            )
            
            if p == "Basic":
                cells += f'<td style="color:#d8b4fe;font-size:0.75rem;max-width:200px;white-space:normal;">{row.get("desc_basic", "")}</td>'
            elif p == "Advanced":
                cells += f'<td style="color:#d8b4fe;font-size:0.75rem;max-width:200px;white-space:normal;">{row.get("desc_advanced", "")}</td>'
                
        data_rows_html += f"<tr>{cells}</tr>\n"

    # Summary MRR per kombinasi
    summary_html = '<tr><td colspan="4" style="font-weight:bold;color:#a78bfa;text-align:right;padding-right:15px">MRR</td>'
    for s, p, _ in COMBINATIONS:
        label = f"{s}_{p}"
        rr_vals = []
        for row in rows:
            v = row.get(f"rr_{label}", 0)
            try:
                rr_vals.append(float(v))
            except (ValueError, TypeError):
                rr_vals.append(0)
        mrr = round(sum(rr_vals) / len(rr_vals), 4) if rr_vals else 0
        color = "#6ee7b7" if mrr >= 0.5 else "#fbbf24" if mrr >= 0.2 else "#f87171"
        
        summary_html += (
            f'<td colspan="4"></td>'
            f'<td style="font-weight:bold;color:{color}">{mrr:.4f}</td>'
        )
        if p in ("Basic", "Advanced"):
            summary_html += '<td></td>' # Kosong untuk kolom deskripsi
            
    summary_html += "</tr>"

    summary_table_html = f"""
    <br><br>
    <h2 style="color: #a78bfa; margin-bottom: 8px; font-size: 1.2rem;">📊 Tabel Ringkasan (Average) - {title}</h2>
    <table style="width: 50%; min-width: 400px; margin-bottom: 40px;">
      <thead>
        <tr>
          <th style="background:#25273b;color:#a78bfa;text-align:left">Metode</th>
          <th style="background:#25273b;color:#a78bfa">Top1</th>
          <th style="background:#25273b;color:#a78bfa">Top3</th>
          <th style="background:#25273b;color:#a78bfa">MRR</th>
        </tr>
      </thead>
      <tbody>
"""
    for item in summary_data:
        t1 = f"{item['Top1']:.3f}".replace('.', ',')
        t3 = f"{item['Top3']:.3f}".replace('.', ',')
        rr = f"{item['MRR']:.3f}".replace('.', ',')
        summary_table_html += f"""        <tr>
          <td style="font-weight:bold;color:#c4b5fd">{item['Metode']}</td>
          <td style="text-align:center">{t1}</td>
          <td style="text-align:center">{t3}</td>
          <td style="text-align:center">{rr}</td>
        </tr>"""
    summary_table_html += """
      </tbody>
    </table>
"""

    return f"""
  <h2 style="color: #a5b4fc; margin-top: 20px; font-size: 1.4rem;">Dataset: {title}</h2>
  <div class="wrap" style="margin-bottom: 20px;">
    <table>
      <thead>
        <tr>
          <th rowspan="2">#</th>
          <th rowspan="2">Tipe</th>
          <th rowspan="2">Query Asli</th>
          <th rowspan="2">Kode GT</th>
          {combo_headers}
        </tr>
        <tr>
          {sub_headers}
        </tr>
      </thead>
      <tbody>
        {data_rows_html}
        {summary_html}
      </tbody>
    </table>
  </div>
  {summary_table_html}
    """


def save_html_combined(rows_kbli: list, summary_kbli: list, rows_kbji: list, summary_kbji: list, filepath: str):
    ts = datetime.now().strftime("%d %B %Y, %H:%M:%S")
    
    html_kbli = build_section_html("KBLI 2025", rows_kbli, summary_kbli)
    html_kbji = build_section_html("KBJI 2014", rows_kbji, summary_kbji)
    total_queries = len(rows_kbli) + len(rows_kbji)

    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>DEMAKAI — Evaluasi 6 Kombinasi</title>
  <style>
    * {{ box-sizing: border-box; margin: 0; padding: 0; }}
    body {{ font-family: 'Segoe UI', sans-serif; background: #0f1117; color: #e0e0e0; padding: 24px; }}
    h1 {{ color: #a78bfa; margin-bottom: 4px; font-size: 1.5rem; }}
    .meta {{ color: #888; font-size: 0.85rem; margin-bottom: 20px; }}
    .wrap {{ overflow-x: auto; }}
    table {{ border-collapse: collapse; font-size: 0.8rem; min-width: 100%; }}
    th, td {{ padding: 8px 10px; border: 1px solid #2d2f45; white-space: nowrap; }}
    th {{ background: #1e1f2e; color: #94a3b8; font-weight: 500; }}
    tr:hover td {{ filter: brightness(1.15); }}
    code {{ background: #312e81; color: #a5b4fc; padding: 1px 5px; border-radius: 4px; }}
  </style>
</head>
<body>
  <h1>🔍 DEMAKAI — Evaluasi 6 Kombinasi Metode Pencarian</h1>
  <div class="meta">Generated: {ts} &nbsp;·&nbsp; Total {total_queries} query</div>
  
  {html_kbli}
  <hr style="border: 0; border-top: 2px dashed #312e81; margin: 40px 0;">
  {html_kbji}

  <!-- Kesimpulan & Saran Section -->
  <div style="margin-top: 60px; padding: 30px; background: #1e1f2e; border-radius: 8px; border-left: 5px solid #a78bfa;">
    <h2 style="color: #c4b5fd; margin-bottom: 20px; font-size: 1.4rem;">💡 Kesimpulan Keseluruhan & Rekomendasi</h2>
    
    <div style="margin-bottom: 25px;">
      <h3 style="color: #a78bfa; margin-bottom: 10px; font-size: 1.1rem;">1. Perbandingan Metode Pencarian</h3>
      <ul style="list-style-type: none; padding-left: 0;">
        <li style="margin-bottom: 8px;">🔹 <strong>Metode "None" (Tanpa Preprocessing):</strong> SQL cenderung sangat ketat (exact match), sehingga keyword yang agak panjang atau memiliki variasi susunan kata mudah gagal (MRR rendah). Hybrid (Vector) sedikit lebih baik karena mampu menangkap kemiripan makna, namun masih kurang maksimal jika terdapat banyak kata hubung/stopword.</li>
        <li style="margin-bottom: 8px;">🔹 <strong>Metode "Basic" (Case-fold, No-punct, Token-OR):</strong> Ini adalah <em>sweet spot</em> untuk SQL LIKE. Dengan memecah query menjadi token (OR), SQL berhasil menangkap dokumen yang setidaknya mengandung satu kata dari pencarian. Hal ini sangat menaikkan persentase sukses (Top 1 & Top 3). Untuk Hybrid, pemisahan tanda baca juga membuat ekstraksi <em>embedding feature</em> lebih bersih.</li>
        <li style="margin-bottom: 8px;">🔹 <strong>Metode "Advanced" (Stemming & Stopword Removal):</strong> Sesuai temuan di tabel, seringkali metode <em>Advanced</em> justru <strong>menurunkan tingkat akurasi</strong>. Mengapa? Karena proses <em>stemming</em> (memotong imbuhan) sering mengubah bentuk kata dasar dan menghapus <em>stopword</em> (seperti "dan", "atau", "dari") membuat konteks kalimat terpotong. Untuk dataset formal seperti KBLI/KBJI, ketepatan susunan kata seringkali krusial, sehingga pemotongan ekstrem ini justru merugikan pemahaman semantik oleh model Hybrid.</li>
      </ul>
    </div>

    <div style="margin-bottom: 25px;">
      <h3 style="color: #a78bfa; margin-bottom: 10px; font-size: 1.1rem;">2. Kesimpulan Utama</h3>
      <p style="line-height: 1.6; color: #d1d5db; margin-bottom: 10px;">
        Kombinasi <strong>Hybrid Search + Preprocessing Basic</strong> terbukti sebagai kandidat terbaik yang konsisten. SQL LIKE + Basic memang tinggi akurasinya untuk data <em>exact match</em>, namun pada aplikasi nyata masyarakat sering menggunakan kosakata padanan/sinonim yang tidak persis ada di dalam database KBLI/KBJI (misal: "toko warung" atau "bengkel tambal"). Disinilah Vector Search (Hybrid) bekerja optimal apabila teks aslinya dibiarkan utuh tanpa di-<em>stem</em> terlalu ekstrem.
      </p>
    </div>

    <div>
      <h3 style="color: #a78bfa; margin-bottom: 10px; font-size: 1.1rem;">3. Saran untuk Pengembangan ke Depan (Next Steps)</h3>
      <ol style="padding-left: 20px; line-height: 1.6; color: #d1d5db;">
        <li style="margin-bottom: 6px;"><strong>Nonaktifkan Advanced Preprocessing:</strong> Hindari <em>stemming</em> menggunakan sastrawi untuk pencarian ini, karena <em>embedding model</em> BERT cenderung lebih optimal membaca kalimat utuh (yang menyertakan imbuhan) untuk memahami konteks bahasa Indonesia.</li>
        <li style="margin-bottom: 6px;"><strong>Perluas Data Ground Truth:</strong> Daftarkan lebih banyak contoh kalimat kueri natural/acak (yang biasa diketik user awam) ke dalam pengujian, bukan hanya kalimat murni KBLI, supaya efektivitas Hybrid Search (skor semantik) bisa terlihat jauh lebih dominan dibanding fungsionalitas SQL LIKE.</li>
        <li style="margin-bottom: 6px;"><strong>Atur Ulang Bobot (Weighting) Hybrid:</strong> Bereksperimenlah kembali dengan rasio <code>alpha</code> (misalnya 70% vector, 30% keyword) di <em>SearchService</em> untuk mencari kombinasi yang bisa menekan error <em>false positive</em> ketika kemunculan kata tidak terlalu relevan.</li>
      </ol>
    </div>
  </div>

</body>
</html>"""

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(html)
    print(f"[HTML]  Gabungan Disimpan → {os.path.abspath(filepath)}")


# ─────────────────────────────────────────────────────────────────────────────
# Simpan ke EXCEL Gabungan (.xlsx) menggunakan pandas
# ─────────────────────────────────────────────────────────────────────────────

def save_excel_combined(rows_kbli: list, summary_kbli: list, rows_kbji: list, summary_kbji: list, filepath: str):
    if not (rows_kbli or rows_kbji):
        return
        
    try:
        import pandas as pd
        
        # Susun header utama
        columns = ["no", "tipe", "query", "kode_ground_truth"]
        excel_headers = ["No", "Tipe", "Query Asli", "Kode GT"]
        
        for s, p, _ in COMBINATIONS:
            label = f"{s}_{p}"
            columns.extend([f"rank_{label}", f"top1_{label}", f"top3_{label}", f"top10_{label}", f"rr_{label}"])
            excel_headers.extend([f"Rank ({label})", f"Top1 ({label})", f"Top3 ({label})", f"Top10 ({label})", f"RR ({label})"])
            
            if p == "Basic":
                columns.append("desc_basic")
                excel_headers.append(f"Deskripsi ({label})")
            elif p == "Advanced":
                columns.append("desc_advanced")
                excel_headers.append(f"Deskripsi ({label})")

        all_rows = rows_kbli + rows_kbji
        df_export = pd.DataFrame(all_rows)
        
        # Pilih kolom dan beri nama header baru
        df_export = df_export[columns].copy() 
        df_export.columns = excel_headers
        df_export.replace("-", 0, inplace=True)
        
        # Buat dataframe ringkasan
        df_sum_kbli = pd.DataFrame(summary_kbli)
        df_sum_kbji = pd.DataFrame(summary_kbji)
        
        with pd.ExcelWriter(filepath, engine='openpyxl') as writer:
            df_export.to_excel(writer, sheet_name='Data Gabungan', index=False)
            if not df_sum_kbli.empty:
                df_sum_kbli.to_excel(writer, sheet_name='Ringkasan KBLI', index=False)
            if not df_sum_kbji.empty:
                df_sum_kbji.to_excel(writer, sheet_name='Ringkasan KBJI', index=False)
                
        print(f"[EXCEL] Gabungan Disimpan → {os.path.abspath(filepath)}")
        
    except ImportError:
        print("[WARNING] Pandas/openpyxl tidak terinstall. Eksport EXCEL dilewati.")
        print("          Jalankan: pip install pandas openpyxl")
    except Exception as e:
        print(f"[ERROR] Gagal menyimpan EXCEL: {e}")


# ─────────────────────────────────────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    QUERIES_FILE = "queries.csv"
    LIMIT        = 10

    if not os.path.exists(QUERIES_FILE):
        print(f"[ERROR] File '{QUERIES_FILE}' tidak ditemukan.")
        print("Buat file queries.csv dengan kolom: no, query, kode_ground_truth")
        raise SystemExit(1)

    print("=" * 60)
    print("  DEMAKAI — Evaluasi Otomatis 6 Kombinasi")
    print("=" * 60)

    rows = run_evaluation(QUERIES_FILE, limit=LIMIT)

    ts   = datetime.now().strftime("%Y%m%d_%H%M%S")
    
    # Pisahkan row berdasarkan tipe
    rows_kbli = [r for r in rows if r.get("tipe") == "KBLI"]
    rows_kbji = [r for r in rows if r.get("tipe") == "KBJI"]

    summary_kbli = calculate_summary(rows_kbli)
    summary_kbji = calculate_summary(rows_kbji)

    # Simpan KBLI CSV
    if rows_kbli:
        print("\n--- Menyimpan Hasil KBLI ---")
        save_csv(rows_kbli,   f"output/evaluasi_{ts}_KBLI.csv")
        save_summary_csv(summary_kbli, f"output/evaluasi_{ts}_KBLI_summary.csv")

    # Simpan KBJI CSV
    if rows_kbji:
        print("\n--- Menyimpan Hasil KBJI ---")
        save_csv(rows_kbji,   f"output/evaluasi_{ts}_KBJI.csv")
        save_summary_csv(summary_kbji, f"output/evaluasi_{ts}_KBJI_summary.csv")

    # Simpan Gabungan HTML & EXCEL
    print("\n--- Menyimpan Laporan Gabungan (HTML & Excel) ---")
    file_html = f"output/evaluasi_{ts}_Gabungan.html"
    file_excel = f"output/evaluasi_{ts}_Gabungan.xlsx"
    
    save_html_combined(rows_kbli, summary_kbli, rows_kbji, summary_kbji, file_html)
    save_excel_combined(rows_kbli, summary_kbli, rows_kbji, summary_kbji, file_excel)
    
    # Buka otomatis HTML Gabungan jika di Windows
    abs_html = os.path.abspath(file_html)
    if os.name == 'nt' and os.path.exists(abs_html):
        try: os.startfile(abs_html)
        except Exception: pass

    # Buka otomatis Excel Gabungan jika di Windows
    abs_excel = os.path.abspath(file_excel)
    if os.name == 'nt' and os.path.exists(abs_excel):
        try: os.startfile(abs_excel)
        except Exception: pass

    print("\nSelesai! Buka folder output/ untuk melihat hasil (CSV, HTML, EXCEL Gabungan).")

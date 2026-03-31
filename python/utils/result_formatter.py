"""
result_formatter.py — Utilitas untuk menampilkan hasil pencarian
-----------------------------------------------------------------
Fungsi yang tersedia:
  - print_results()        : cetak ke terminal
  - print_preprocessing()  : cetak detail preprocessing ke terminal
  - save_to_csv()          : simpan hasil ke file .csv
  - save_to_html()         : simpan hasil ke file .html (buka di browser)
"""

import csv
import os
from datetime import datetime
from typing import List


def print_results(results: list, title: str = "Hasil Pencarian", max_items: int = 10) -> None:
    """
    Cetak hasil pencarian ke stdout dalam format yang terbaca.

    Args:
        results  : list of dict dari fungsi search
        title    : judul blok output
        max_items: maksimum item yang ditampilkan
    """
    bar = "=" * 60
    print(f"\n{bar}")
    print(f"  {title}")
    print(f"  Total ditemukan: {len(results)}")
    print(bar)

    if not results:
        print("  (tidak ada hasil)")
    else:
        for i, item in enumerate(results[:max_items], start=1):
            kode    = item.get("kode", "-")
            judul   = item.get("judul", "-")
            source  = item.get("_source", "")
            dist    = item.get("distance")
            boost   = item.get("_boost", "")

            dist_str  = f"  distance={dist:.4f}" if dist is not None else ""
            boost_str = f"  [boost: {boost}]" if boost else ""
            src_str   = f"  [{source}]" if source else ""

            print(f"\n  [{i}] {kode} — {judul}{src_str}")
            print(f"       {dist_str}{boost_str}")

    print(f"{bar}\n")


def print_preprocessing(preprocessed: dict, method: str = "basic") -> None:
    """
    Cetak ringkasan hasil preprocessing.

    Args:
        preprocessed : output dari preprocess_basic() atau preprocess_advanced()
        method       : 'basic' atau 'advanced'
    """
    print(f"\n{'─'*50}")
    print(f"  Preprocessing [{method.upper()}]")
    print(f"{'─'*50}")
    print(f"  original      : {preprocessed.get('original')}")
    print(f"  clean         : {preprocessed.get('clean')}")
    print(f"  tokens        : {preprocessed.get('tokens')}")
    if method == "advanced":
        print(f"  stemmed_tokens: {preprocessed.get('stemmed_tokens')}")
        print(f"  stemmed_clean : {preprocessed.get('stemmed_clean')}")
    print(f"{'─'*50}")


def results_to_csv_rows(results: list) -> List[List]:
    """
    Konversi hasil pencarian menjadi list of list untuk ditulis ke CSV.

    Returns:
        [ [header_row], [data_row], ... ]
    """
    if not results:
        return [["kode", "judul", "source", "distance"]]

    rows = [["kode", "judul", "source", "distance"]]
    for item in results:
        rows.append([
            item.get("kode", ""),
            item.get("judul", ""),
            item.get("_source", ""),
            round(float(item.get("distance") or 0), 6),
        ])
    return rows


# ──────────────────────────────────────────────────────────────────────────────
# Simpan ke CSV
# ──────────────────────────────────────────────────────────────────────────────

def save_to_csv(
    all_results: dict,
    query: str,
    filepath: str = None,
) -> str:
    """
    Simpan semua hasil kombinasi ke satu file CSV.

    Args:
        all_results : dict { "SQL + Basic": [hasil...], "SQL + Advanced": [...], ... }
        query       : query yang digunakan
        filepath    : path file output (default: output/hasil_YYYYMMDD_HHMMSS.csv)

    Returns:
        str — path file yang disimpan
    """
    if filepath is None:
        os.makedirs("output", exist_ok=True)
        ts = datetime.now().strftime("%Y%m%d_%H%M%S")
        filepath = f"output/hasil_{ts}.csv"

    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["Query", query])
        writer.writerow([])

        for label, results in all_results.items():
            writer.writerow([f"=== {label} === ({len(results)} hasil)"])
            writer.writerow(["rank", "kode", "judul", "tabel", "distance", "boost"])
            for i, item in enumerate(results, start=1):
                writer.writerow([
                    i,
                    item.get("kode", ""),
                    item.get("judul", ""),
                    item.get("_source", ""),
                    round(float(item.get("distance") or 0), 6),
                    item.get("_boost", ""),
                ])
            writer.writerow([])

    print(f"\n  [CSV] Hasil disimpan → {os.path.abspath(filepath)}")
    return filepath


# ──────────────────────────────────────────────────────────────────────────────
# Simpan ke HTML
# ──────────────────────────────────────────────────────────────────────────────

def save_to_html(
    all_results: dict,
    query: str,
    filepath: str = None,
) -> str:
    """
    Simpan semua hasil kombinasi ke file HTML — buka di browser atau VS Code Preview.

    Args:
        all_results : dict { "SQL + Basic": [hasil...], ... }
        query       : query yang digunakan
        filepath    : path file output (default: output/hasil_YYYYMMDD_HHMMSS.html)

    Returns:
        str — path file yang disimpan
    """
    if filepath is None:
        os.makedirs("output", exist_ok=True)
        ts = datetime.now().strftime("%Y%m%d_%H%M%S")
        filepath = f"output/hasil_{ts}.html"

    # Bangun tabel per kombinasi
    sections = ""
    for label, results in all_results.items():
        rows_html = ""
        if not results:
            rows_html = '<tr><td colspan="5" style="color:#888;text-align:center">Tidak ada hasil</td></tr>'
        else:
            for i, item in enumerate(results, start=1):
                dist = item.get("distance")
                dist_str = f"{dist:.4f}" if dist is not None else "-"
                boost = item.get("_boost", "")
                boost_badge = f'<span class="badge">{boost}</span>' if boost else ""
                rows_html += f"""
                <tr>
                    <td>{i}</td>
                    <td><code>{item.get('kode','')}</code></td>
                    <td>{item.get('judul','')}</td>
                    <td>{item.get('_source','')}</td>
                    <td>{dist_str} {boost_badge}</td>
                </tr>"""

        sections += f"""
        <div class="section">
            <h2>{label} <span class="count">{len(results)} hasil</span></h2>
            <table>
                <thead>
                    <tr><th>#</th><th>Kode</th><th>Judul</th><th>Tabel</th><th>Distance</th></tr>
                </thead>
                <tbody>{rows_html}</tbody>
            </table>
        </div>"""

    ts_readable = datetime.now().strftime("%d %B %Y, %H:%M:%S")
    html = f"""<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEMAKAI — Hasil Pencarian</title>
    <style>
        * {{ box-sizing: border-box; margin: 0; padding: 0; }}
        body {{ font-family: 'Segoe UI', sans-serif; background: #0f1117; color: #e0e0e0; padding: 32px; }}
        h1 {{ font-size: 1.8rem; color: #a78bfa; margin-bottom: 4px; }}
        .meta {{ color: #888; font-size: 0.9rem; margin-bottom: 32px; }}
        .meta span {{ color: #c4b5fd; font-weight: 600; }}
        .section {{ background: #1e1f2e; border: 1px solid #2d2f45; border-radius: 10px;
                    padding: 20px; margin-bottom: 24px; }}
        h2 {{ font-size: 1.1rem; color: #c4b5fd; margin-bottom: 14px; }}
        .count {{ font-size: 0.85rem; background: #312e81; color: #a5b4fc;
                  padding: 2px 10px; border-radius: 20px; margin-left: 8px; }}
        table {{ width: 100%; border-collapse: collapse; font-size: 0.9rem; }}
        th {{ background: #2d2f45; color: #94a3b8; text-align: left;
              padding: 10px 12px; font-weight: 500; }}
        td {{ padding: 10px 12px; border-bottom: 1px solid #2d2f45; color: #cbd5e1; }}
        tr:last-child td {{ border-bottom: none; }}
        tr:hover td {{ background: #252740; }}
        code {{ background: #312e81; color: #a5b4fc; padding: 2px 6px;
                border-radius: 4px; font-size: 0.85rem; }}
        .badge {{ background: #064e3b; color: #6ee7b7; padding: 1px 8px;
                  border-radius: 10px; font-size: 0.75rem; margin-left: 6px; }}
        footer {{ text-align: center; color: #555; font-size: 0.8rem; margin-top: 32px; }}
    </style>
</head>
<body>
    <h1>🔍 DEMAKAI — Hasil Pencarian</h1>
    <div class="meta">
        Query: <span>"{query}"</span> &nbsp;·&nbsp; {ts_readable}
    </div>
    {sections}
    <footer>Generated by DEMAKAI Search System</footer>
</body>
</html>"""

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(html)

    print(f"\n  [HTML] Hasil disimpan → {os.path.abspath(filepath)}")
    return filepath

"""
build_dashboard.py
==================
Membaca CSV evaluasi_semua_*.csv dan menghasilkan satu HTML dashboard
komprehensif dengan desain konsisten (dark green theme + bento-style).

TIDAK melakukan evaluasi ulang — data diambil langsung dari CSV.
"""

import csv
import json
import os
import sys
from datetime import datetime

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

# ── Temukan CSV terbaru ───────────────────────────────────────────────────────
OUT_DIR  = os.path.join(os.path.dirname(__file__), "output")
CSV_FILE = None
for f in sorted(os.listdir(OUT_DIR), reverse=True):
    if f.startswith("evaluasi_semua_") and f.endswith(".csv"):
        CSV_FILE = os.path.join(OUT_DIR, f)
        break

if not CSV_FILE:
    print("[ERROR] Tidak ditemukan file evaluasi_semua_*.csv di folder output/")
    sys.exit(1)

print(f"[INFO] Membaca data dari: {CSV_FILE}")


# ─────────────────────────────────────────────────────────────────────────────
# LOAD & PARSE
# ─────────────────────────────────────────────────────────────────────────────

def load(filepath: str) -> dict:
    """
    Return dict: method_key -> list[row]
    """
    all_rows = {}
    with open(filepath, newline="", encoding="utf-8") as f:
        for row in csv.DictReader(f):
            key = row["metode"]
            if key not in all_rows:
                all_rows[key] = []
            all_rows[key].append({
                "no":           row["no"],
                "query":        row["query"],
                "preprocessed": row["preprocessed"],
                "contoh":       row["contoh"],
                "kode_gt":      row["kode_gt"],
                "tipe":         row["tipe"],
                "rank":         int(row["rank"]),
                "top1":         row["top1"] in ("1","True","true"),
                "top3":         row["top3"] in ("1","True","true"),
                "top10":        row["top10"] in ("1","True","true"),
                "rr":           float(row["rr"]),
            })
    return all_rows


# ─────────────────────────────────────────────────────────────────────────────
# METRICS
# ─────────────────────────────────────────────────────────────────────────────

def calc(rows: list) -> dict:
    n = len(rows) or 1
    return {
        "n":     n,
        "top1":  sum(1 for r in rows if r["top1"])  / n,
        "top3":  sum(1 for r in rows if r["top3"])  / n,
        "top10": sum(1 for r in rows if r["top10"]) / n,
        "mrr":   sum(r["rr"] for r in rows) / n,
    }


def mrr_color(v: float) -> str:
    if v >= 0.7: return "#6ee7b7"
    if v >= 0.4: return "#fbbf24"
    return "#f87171"


# ─────────────────────────────────────────────────────────────────────────────
# METHOD META  (key, title, short, accent, bg, show_prep, show_contoh, tags)
# ─────────────────────────────────────────────────────────────────────────────

METHODS = [
    {
        "key":          "1. SQL LIKE (Baseline)",
        "num":          "01",
        "title":        "SQL LIKE (Baseline)",
        "desc":         "Pencarian frasa mentah dengan SQL ILIKE — tanpa preprocessing, tanpa semantic search, tanpa contoh lapangan.",
        "accent":       "#64748b",
        "bg":           "#0c0f18",
        "show_prep":    False,
        "show_contoh":  False,
        "tags": [
            ("SQL LIKE",         True,  "#64748b"),
            ("Semantic Search",  False, "#334155"),
            ("Preprocessing",    False, "#334155"),
            ("Contoh Lapangan",  False, "#334155"),
        ],
        "method_note":  "Pada metode baseline ini, query pengguna langsung digunakan sebagai pola SQL ILIKE tanpa pemrosesan apapun. Sistem mencari kecocokan frasa eksak pada kolom kode, judul, dan deskripsi. Ini menjadi tolok ukur performa minimum sistem.",
    },
    {
        "key":          "2. Hybrid Search",
        "num":          "02",
        "title":        "Hybrid Search",
        "desc":         "Menggabungkan SQL LIKE (lexical) + Semantic Search (embedding Gemini) — tanpa preprocessing.",
        "accent":       "#38bdf8",
        "bg":           "#060d14",
        "show_prep":    False,
        "show_contoh":  False,
        "tags": [
            ("SQL LIKE",         True,  "#38bdf8"),
            ("Semantic Search",  True,  "#38bdf8"),
            ("Preprocessing",    False, "#334155"),
            ("Contoh Lapangan",  False, "#334155"),
        ],
        "method_note":  "Pada metode Hybrid Search, sistem menggabungkan dua pendekatan: (1) SQL LIKE untuk pencocokan leksikal, dan (2) vector search menggunakan embedding Gemini untuk pemahaman semantik. Hasil kedua pendekatan digabung dengan boosting berbasis lokasi match (contoh_lapangan > judul > deskripsi). Tanpa preprocessing, query masih digunakan apa adanya.",
    },
    {
        "key":          "3. SQL LIKE + Preprocessing",
        "num":          "03",
        "title":        "SQL LIKE + Preprocessing",
        "desc":         "SQL LIKE dengan Query Expansion (sinonim + variasi KBLI/KBJI) — tanpa semantic search.",
        "accent":       "#a78bfa",
        "bg":           "#0d0b1a",
        "show_prep":    True,
        "show_contoh":  False,
        "tags": [
            ("SQL LIKE",         True,  "#a78bfa"),
            ("Semantic Search",  False, "#334155"),
            ("Preprocessing",    True,  "#a78bfa"),
            ("Contoh Lapangan",  False, "#334155"),
        ],
        "method_note":  "Pada metode SQL LIKE + Preprocessing, query terlebih dahulu diproses menggunakan teknik preprocessing (advanced dan expansion). Preprocessing meliputi: lowercase, cleaning, stopword removal, stemming, dan query expansion (penambahan sinonim dan variasi kata terkait konteks KBLI/KBJI). Hasilnya digunakan untuk pencarian dengan SQL LIKE — tanpa semantic search.",
    },
    {
        "key":          "4. Hybrid Search + Preprocessing",
        "num":          "04",
        "title":        "Hybrid Search + Preprocessing",
        "desc":         "SQL LIKE + Semantic Search dengan Query Expansion — tanpa filter contoh lapangan eksplisit.",
        "accent":       "#67e8f9",
        "bg":           "#060f14",
        "show_prep":    True,
        "show_contoh":  False,
        "tags": [
            ("SQL LIKE",         True,  "#67e8f9"),
            ("Semantic Search",  True,  "#67e8f9"),
            ("Preprocessing",    True,  "#67e8f9"),
            ("Contoh Lapangan",  False, "#334155"),
        ],
        "method_note":  "Pada metode Hybrid Search + Preprocessing, query diproses terlebih dahulu menggunakan preprocessing, kemudian hasilnya digunakan pada dua pendekatan sekaligus: SQL LIKE (lexical) dengan expanded tokens dan semantic search menggunakan embedding Gemini dari teks yang sudah di-clean. Hasil keduanya digabung dengan algoritma boosting.",
    },
    {
        "key":          "5. SQL LIKE + Preprocessing + Contoh Lapangan",
        "num":          "05",
        "title":        "SQL LIKE + Preprocessing + Contoh Lapangan",
        "desc":         "SQL LIKE dengan Preprocessing dan pencarian pada field contoh_lapangan (deskripsi aktivitas nyata).",
        "accent":       "#c084fc",
        "bg":           "#0f0a1f",
        "show_prep":    True,
        "show_contoh":  True,
        "tags": [
            ("SQL LIKE",         True,  "#c084fc"),
            ("Semantic Search",  False, "#334155"),
            ("Preprocessing",    True,  "#c084fc"),
            ("Contoh Lapangan",  True,  "#6ee7b7"),
        ],
        "method_note":  "Pada metode ini, query diproses dengan preprocessing kemudian SQL LIKE mencari pada descripsi formal KBLI/KBJI DAN field contoh_lapangan. Contoh lapangan berisi deskripsi aktivitas nyata dalam bahasa informal yang memperkaya data formal KBLI, membantu sistem memahami variasi bahasa pengguna lapangan.",
    },
    {
        "key":          "6. Hybrid + Preprocessing + Contoh Lapangan (FINAL)",
        "num":          "06",
        "title":        "Hybrid + Preprocessing + Contoh Lapangan",
        "desc":         "Sistem lengkap: Hybrid Search + Preprocessing + Boost via contoh_lapangan &mdash; pipeline terlengkap.",
        "accent":       "#34d399",
        "bg":           "#050b0f",
        "show_prep":    True,
        "show_contoh":  True,
        "tags": [
            ("SQL LIKE",         True,  "#34d399"),
            ("Semantic Search",  True,  "#34d399"),
            ("Preprocessing",    True,  "#34d399"),
            ("Contoh Lapangan",  True,  "#34d399"),
        ],
        "method_note":  "Pada metode Hybrid Search + Preprocessing, query diproses terlebih dahulu menggunakan preprocessing, kemudian hasilnya digunakan pada dua pendekatan sekaligus, yaitu SQL LIKE (lexical) dan semantic search, sehingga mampu meningkatkan relevansi hasil pencarian. Field contoh_lapangan mendapat boost prioritas tertinggi dalam ranking.",
    },
]


# ─────────────────────────────────────────────────────────────────────────────
# HTML COMPONENTS
# ─────────────────────────────────────────────────────────────────────────────

def _tags_html(tags: list) -> str:
    out = ""
    for label, on, color in tags:
        if on:
            style = f"border:1px solid {color};color:{color};background:{color}18"
        else:
            style = "border:1px solid #2d3048;color:#334155;background:#0f111a;opacity:0.45;text-decoration:line-through"
        mark = "&#10003;" if on else "&#10007;"
        out += f"<span style='display:inline-flex;align-items:center;gap:5px;font-size:0.78rem;padding:4px 12px;border-radius:7px;margin:3px;{style}'>{mark} {label}</span>"
    return out


def _stats_bar(sm: dict, accent: str) -> str:
    mc = mrr_color(sm["mrr"])
    return f"""
<div class="stats-bar">
  <div class="stat-card">
    <div class="stat-lbl">Total Query</div>
    <div class="stat-val">{sm['n']}</div>
  </div>
  <div class="stat-card">
    <div class="stat-lbl">Top@1</div>
    <div class="stat-val" style="color:#86efac">{sm['top1']:.1%}</div>
  </div>
  <div class="stat-card">
    <div class="stat-lbl">Top@3</div>
    <div class="stat-val" style="color:#7dd3fc">{sm['top3']:.1%}</div>
  </div>
  <div class="stat-card">
    <div class="stat-lbl">Top@10</div>
    <div class="stat-val" style="color:#d8b4fe">{sm['top10']:.1%}</div>
  </div>
  <div class="stat-card">
    <div class="stat-lbl">MRR</div>
    <div class="stat-val" style="color:{mc}">{sm['mrr']:.4f}</div>
  </div>
</div>"""


def _build_detail_table(rows: list, tipe_filter: str,
                        show_prep: bool, show_contoh: bool,
                        accent: str) -> str:
    filt = [r for r in rows if r["tipe"] == tipe_filter]
    if not filt:
        return ""
    sm = calc(filt)

    def rank_style(v):
        if v == 0:  return "style='background:#1a1f2e;color:#475569'"
        if v == 1:  return "style='background:#064e3b;color:#6ee7b7;font-weight:bold'"
        if v <= 3:  return "style='background:#065f46;color:#a7f3d0'"
        if v <= 10: return "style='background:#1e3a2f;color:#d1fae5'"
        return "style='background:#1a1f2e;color:#475569'"

    def hit_style(v):
        return "style='background:#1e3a5f;color:#93c5fd;font-weight:bold;text-align:center'" if v \
               else "style='text-align:center;color:#475569'"

    rows_html = ""
    for r in filt:
        tc   = "#86efac" if r["tipe"] == "KBLI" else "#93c5fd"
        rr_c = "#6ee7b7" if r["rr"] > 0 else "#475569"
        rr_w = "bold" if r["rr"] > 0 else "normal"

        row = f"<td style='text-align:center;color:#94a3b8'>{r['no']}</td>"
        row += f"<td style='color:{tc};font-weight:bold;text-align:center'>{r['tipe']}</td>"
        row += f"<td style='color:#c4b5fd;white-space:normal;max-width:160px'>{r['query']}</td>"
        if show_prep:
            prep = r["preprocessed"] or "<span style='color:#475569'>—</span>"
            row += f"<td style='color:#fcd34d;font-size:0.77rem;white-space:normal;max-width:170px'>{prep}</td>"
        if show_contoh:
            contoh = r["contoh"] if r["contoh"] and r["contoh"] not in ("-","") \
                     else "<span style='color:#475569'>—</span>"
            row += f"<td style='color:#86efac;font-size:0.77rem;white-space:normal;max-width:190px'>{contoh}</td>"
        row += f"<td style='text-align:center'><code>{r['kode_gt']}</code></td>"
        row += f"<td {rank_style(r['rank'])}>{r['rank'] if r['rank'] > 0 else '—'}</td>"
        row += f"<td {hit_style(r['top1'])}>{'1' if r['top1'] else '0'}</td>"
        row += f"<td {hit_style(r['top3'])}>{'1' if r['top3'] else '0'}</td>"
        row += f"<td {hit_style(r['top10'])}>{'1' if r['top10'] else '0'}</td>"
        row += f"<td style='text-align:center;color:{rr_c};font-weight:{rr_w}'>{r['rr'] if r['rr']>0 else '—'}</td>"
        rows_html += f"<tr>{row}</tr>"

    mc = mrr_color(sm["mrr"])
    extra = (1 if show_prep else 0) + (1 if show_contoh else 0)
    rows_html += f"""
    <tr style='background:#1f2937;border-top:2px solid {accent}'>
      <td colspan='{4+extra}' style='text-align:right;font-weight:bold;color:{accent};padding-right:16px'>MRR (Mean Reciprocal Rank)</td>
      <td style='text-align:center;color:#94a3b8'>—</td>
      <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top1']:.3f}</td>
      <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top3']:.3f}</td>
      <td style='text-align:center;font-weight:bold;color:{accent}'>{sm['top10']:.3f}</td>
      <td style='text-align:center;font-weight:bold;color:{mc}'>{sm['mrr']:.4f}</td>
    </tr>"""

    # Header
    header = "<th>#</th><th>Tipe</th><th style='text-align:left;min-width:150px'>Query Asli</th>"
    if show_prep:
        header += "<th style='text-align:left;min-width:160px;color:#fcd34d'>Preprocessing &#10003;</th>"
    if show_contoh:
        header += "<th style='text-align:left;min-width:180px;color:#86efac'>Contoh Lapangan &#10003;</th>"
    header += "<th>Kode GT</th><th>Rank</th><th>Top@1</th><th>Top@3</th><th>Top@10</th><th>RR</th>"

    return f"<table><thead><tr>{header}</tr></thead><tbody>{rows_html}</tbody></table>"


def _summary_mini_table(sm_kbli: dict, sm_kbji: dict, sm_all: dict, accent: str) -> str:
    def row(label, sm):
        mc = mrr_color(sm["mrr"])
        return f"""<tr>
          <td style='color:#94a3b8;font-size:0.8rem'>{label}</td>
          <td style='text-align:center;color:{accent};font-weight:600'>{sm['top1']:.1%}</td>
          <td style='text-align:center;color:{accent};font-weight:600'>{sm['top3']:.1%}</td>
          <td style='text-align:center;color:{accent};font-weight:600'>{sm['top10']:.1%}</td>
          <td style='text-align:center;font-weight:700;color:{mc}'>{sm['mrr']:.4f}</td>
        </tr>"""
    return f"""
<table style='width:auto;min-width:400px;margin-bottom:16px'>
  <thead><tr>
    <th style='text-align:left;color:{accent}'>Dataset</th>
    <th style='color:{accent}'>Top@1</th>
    <th style='color:{accent}'>Top@3</th>
    <th style='color:{accent}'>Top@10</th>
    <th style='color:{accent}'>MRR</th>
  </tr></thead>
  <tbody>
    {row('KBLI 2025', sm_kbli)}
    {row('KBJI 2014', sm_kbji)}
    {row('Gabungan',  sm_all)}
  </tbody>
</table>"""


def _method_section(meta: dict, rows: list) -> str:
    key         = meta["key"]
    anchor      = key.replace(" ", "_").replace(".", "").replace("/","")
    accent      = meta["accent"]
    bg          = meta["bg"]
    show_prep   = meta["show_prep"]
    show_contoh = meta["show_contoh"]

    rows_kbli = [r for r in rows if r["tipe"] == "KBLI"]
    rows_kbji = [r for r in rows if r["tipe"] == "KBJI"]
    sm_kbli   = calc(rows_kbli)
    sm_kbji   = calc(rows_kbji)
    sm_all    = calc(rows)

    num_badge  = f"<span style='background:{accent}20;border:1px solid {accent};color:{accent};font-size:0.7rem;font-weight:700;padding:2px 10px;border-radius:6px;margin-bottom:8px;display:inline-block'>Metode {meta['num']}</span>"
    tags_html  = _tags_html(meta["tags"])
    stats_html = _stats_bar(sm_all, accent)
    sum_table  = _summary_mini_table(sm_kbli, sm_kbji, sm_all, accent)
    tbl_kbli   = _build_detail_table(rows, "KBLI", show_prep, show_contoh, accent)
    tbl_kbji   = _build_detail_table(rows, "KBJI", show_prep, show_contoh, accent)

    sub_style = f"font-size:0.9rem;font-weight:600;color:{accent};margin:20px 0 8px;display:flex;align-items:center;gap:8px"
    bar_style = f"display:inline-block;width:3px;height:16px;background:{accent};border-radius:2px"

    return f"""
<div id="{anchor}" class="method-section" style="border-color:{accent}33;background:{bg}">
  {num_badge}
  <div class="method-title" style="color:{accent}">{meta['title']}</div>
  <p class="method-sub">{meta['desc']}</p>

  <div class="method-note">{meta['method_note']}</div>

  <div style="margin:14px 0">{tags_html}</div>

  {stats_html}

  <div class="wrap" style="max-width:500px;margin:14px 0 20px">{sum_table}</div>

  <div style="{sub_style}">
    <span style="{bar_style}"></span>
    Dataset KBLI 2025
  </div>
  <div class="wrap">{tbl_kbli}</div>

  <div style="{sub_style}">
    <span style="{bar_style}"></span>
    Dataset KBJI 2014
  </div>
  <div class="wrap">{tbl_kbji}</div>
</div>"""


# ─────────────────────────────────────────────────────────────────────────────
# COMPARISON TABLE
# ─────────────────────────────────────────────────────────────────────────────

def _comparison_table(all_data: dict) -> str:
    rows_html = ""
    for meta in METHODS:
        key    = meta["key"]
        rows   = all_data.get(key, [])
        kbli   = calc([r for r in rows if r["tipe"]=="KBLI"])
        kbji   = calc([r for r in rows if r["tipe"]=="KBJI"])
        sm     = calc(rows)
        accent = meta["accent"]

        def cell(v, fmt):
            return f"<td style='text-align:center;font-weight:600;color:{accent}'>{v:{fmt}}</td>"

        rows_html += f"""<tr>
          <td>
            <a href='#{key.replace(" ","_").replace(".","").replace("/","")}' style='color:{accent};text-decoration:none;font-weight:600'>
              {meta['num']}. {meta['title']}
            </a>
          </td>
          {cell(kbli['top1'],'.1%')}{cell(kbli['top3'],'.1%')}{cell(kbli['top10'],'.1%')}{cell(kbli['mrr'],'.4f')}
          {cell(kbji['top1'],'.1%')}{cell(kbji['top3'],'.1%')}{cell(kbji['top10'],'.1%')}{cell(kbji['mrr'],'.4f')}
          {cell(sm['top1'],'.1%')}{cell(sm['top3'],'.1%')}{cell(sm['top10'],'.1%')}
          <td style='text-align:center;font-weight:700;color:{mrr_color(sm["mrr"])}'>{sm["mrr"]:.4f}</td>
        </tr>"""

    return f"""
<div class="wrap">
<table>
  <thead>
    <tr>
      <th rowspan="2" style="text-align:left;min-width:220px;vertical-align:middle;color:#e2e8f0">Metode</th>
      <th colspan="4" style="background:#1a1040;color:#a78bfa;border-bottom:2px solid #7c3aed">KBLI 2025</th>
      <th colspan="4" style="background:#060f14;color:#38bdf8;border-bottom:2px solid #0e7490">KBJI 2014</th>
      <th colspan="4" style="background:#141828;color:#e2e8f0;border-bottom:2px solid #334155">Gabungan</th>
    </tr>
    <tr>
      <th style="background:#1a1040;color:#a78bfa">T@1</th><th style="background:#1a1040;color:#a78bfa">T@3</th>
      <th style="background:#1a1040;color:#a78bfa">T@10</th><th style="background:#1a1040;color:#a78bfa">MRR</th>
      <th style="background:#060f14;color:#38bdf8">T@1</th><th style="background:#060f14;color:#38bdf8">T@3</th>
      <th style="background:#060f14;color:#38bdf8">T@10</th><th style="background:#060f14;color:#38bdf8">MRR</th>
      <th>T@1</th><th>T@3</th><th>T@10</th><th>MRR</th>
    </tr>
  </thead>
  <tbody>{rows_html}</tbody>
</table>
</div>"""


# ─────────────────────────────────────────────────────────────────────────────
# ANALYSIS SECTION
# ─────────────────────────────────────────────────────────────────────────────

def _analysis(all_data: dict) -> str:
    sms = {m["key"]: calc(all_data.get(m["key"],[])) for m in METHODS}
    best_key    = max(sms, key=lambda k: sms[k]["mrr"])
    best_title  = next(m["title"] for m in METHODS if m["key"]==best_key)
    best_accent = next(m["accent"] for m in METHODS if m["key"]==best_key)
    best_mrr    = sms[best_key]["mrr"]

    m1 = sms["1. SQL LIKE (Baseline)"]
    m2 = sms["2. Hybrid Search"]
    m3 = sms["3. SQL LIKE + Preprocessing"]
    m4 = sms["4. Hybrid Search + Preprocessing"]
    m5 = sms["5. SQL LIKE + Preprocessing + Contoh Lapangan"]
    m6 = sms["6. Hybrid + Preprocessing + Contoh Lapangan (FINAL)"]

    cards = [
        ("#38bdf8", "&#128269; SQL LIKE vs Hybrid (Tanpa Preprocessing)",
         f"Tanpa preprocessing, SQL LIKE (MRR={m1['mrr']:.4f}) dan Hybrid (MRR={m2['mrr']:.4f}) "
         f"sudah mampu berjalan baik karena field <code>contoh_lapangan</code> sudah ada di database. "
         f"Hybrid sedikit berbeda karena memiliki dimensi semantik tambahan via embedding Gemini, "
         f"namun tanpa preprocessing query masuk apa adanya sehingga embedding kurang optimal."),

        ("#a78bfa", "&#9986; Dampak Preprocessing (Query Expansion)",
         f"Preprocessing dengan Query Expansion mengubah cara sistem memahami query. "
         f"SQL LIKE + Prep (MRR={m3['mrr']:.4f}) menggunakan sinonim dan variasi KBLI/KBJI "
         f"yang diperluas (misal: 'sawah' → 'lahan, tani, padi, pertanian'). "
         f"Untuk Hybrid, preprocessing menghasilkan teks yang lebih bersih untuk embedding "
         f"(MRR={m4['mrr']:.4f}), namun kadang over-expansion menyebabkan noise pada vector search."),

        ("#34d399", "&#128203; Peran Contoh Lapangan",
         f"Field contoh_lapangan berisi deskripsi aktivitas nyata dalam bahasa informal. "
         f"SQL LIKE + Prep + Contoh (MRR={m5['mrr']:.4f}) identik dengan M3 secara performa "
         f"karena SQL LIKE sudah mencakup <code>contoh_lapangan</code> sejak awal. "
         f"Hybrid Final (MRR={m6['mrr']:.4f}) mendapat boost prioritas tambahan — "
         f"hampir semua query yang berhasil ditandai dengan <em>boost=contoh_lapangan</em>."),

        ("#fbbf24", "&#127942; Metode Optimal & Rekomendasi",
         f"Metode terbaik adalah <strong style='color:{best_accent}'>{best_title}</strong> "
         f"dengan MRR = <strong style='color:{best_accent}'>{best_mrr:.4f}</strong>. "
         f"SQL LIKE + Preprocessing memberikan performa sangat kompetitif sambil lebih efisien "
         f"(tidak memerlukan API embedding per query). Hybrid direkomendasikan sebagai fallback "
         f"untuk query abstrak yang tidak mengandung keyword jelas."),
    ]

    cards_html = ""
    for color, title, text in cards:
        cards_html += f"""
<div class="ana-card">
  <div class="ana-ttl" style="color:{color}">{title}</div>
  <div class="ana-txt">{text}</div>
</div>"""

    # Delta table
    delta_rows = ""
    m1_mrr = m1["mrr"]
    for meta in METHODS[1:]:
        sm = sms[meta["key"]]
        delta = sm["mrr"] - m1_mrr
        dc = "#6ee7b7" if delta >= 0 else "#f87171"
        sign = "+" if delta >= 0 else ""
        delta_rows += f"""<tr>
          <td><span style='color:{meta["accent"]};font-weight:600'>{meta["num"]}. {meta["title"]}</span></td>
          <td style='text-align:center;color:{meta["accent"]};font-weight:600'>{sm["mrr"]:.4f}</td>
          <td style='text-align:center;color:{dc};font-weight:700'>{sign}{delta:.4f}</td>
          <td style='text-align:center;color:#94a3b8'>{sm["top1"]:.1%}</td>
          <td style='text-align:center;color:#94a3b8'>{sm["top10"]:.1%}</td>
        </tr>"""

    delta_table = f"""
<div class="wrap" style="max-width:620px;margin-top:18px">
  <table>
    <thead><tr>
      <th style="text-align:left;min-width:200px">Metode</th>
      <th>MRR</th>
      <th>Delta vs Baseline</th>
      <th>Top@1</th>
      <th>Top@10</th>
    </tr></thead>
    <tbody>
      <tr>
        <td><span style='color:#64748b;font-weight:600'>01. SQL LIKE (Baseline)</span></td>
        <td style='text-align:center;color:#64748b;font-weight:600'>{m1_mrr:.4f}</td>
        <td style='text-align:center;color:#64748b'>—</td>
        <td style='text-align:center;color:#94a3b8'>{m1['top1']:.1%}</td>
        <td style='text-align:center;color:#94a3b8'>{m1['top10']:.1%}</td>
      </tr>
      {delta_rows}
    </tbody>
  </table>
</div>"""

    return f"""
<div id="analisis" class="ana-box">
  <h3>&#128202; Analisis Perbandingan Metode</h3>
  <p style="font-size:0.87rem;color:#94a3b8;margin-bottom:18px">
    Evaluasi menggunakan <strong style="color:#6ee7b7">60 query</strong>
    (30 KBLI 2025 + 30 KBJI 2014) yang konsisten di semua metode.
    Metode terbaik: <strong style="color:{best_accent}">{best_title}</strong>
    (MRR = <strong style="color:{best_accent}">{best_mrr:.4f}</strong>).
  </p>
  <div class="ana-grid">{cards_html}</div>
  <h3 style="margin-top:24px;color:#a78bfa;font-size:0.92rem">&#128300; Perbandingan Delta vs Baseline</h3>
  {delta_table}
</div>"""


# ─────────────────────────────────────────────────────────────────────────────
# MAIN HTML
# ─────────────────────────────────────────────────────────────────────────────

CSS = """
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Outfit',sans-serif;background:#070a10;color:#e2e8f0;padding:0;line-height:1.6}

/* ── TOP NAV ─────────────────────────────── */
.topnav{
  position:sticky;top:0;z-index:100;
  background:#070a10f0;backdrop-filter:blur(14px);
  border-bottom:1px solid #1e2438;
  padding:10px 40px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;
}
.topnav-brand{font-size:0.88rem;font-weight:700;color:#6ee7b7;margin-right:12px;white-space:nowrap}
.nav-link{
  font-size:0.72rem;padding:4px 10px;border-radius:6px;
  border:1px solid;text-decoration:none;white-space:nowrap;
  transition:opacity 0.15s;
}
.nav-link:hover{opacity:0.7}

/* ── PAGE ────────────────────────────────── */
.page{padding:52px 44px}

.page-badge{
  display:inline-block;
  background:linear-gradient(135deg,#065f46,#059669);
  color:#fff;font-size:0.74rem;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;
  padding:4px 14px;border-radius:999px;margin-bottom:14px;
}
h1{
  font-size:2rem;font-weight:700;
  background:linear-gradient(90deg,#6ee7b7,#34d399,#38bdf8);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  margin-bottom:6px;
}
.meta{color:#64748b;font-size:0.86rem;margin-bottom:4px}

/* ── METHOD SECTION ──────────────────────── */
.method-section{
  border:1px solid;border-radius:16px;
  padding:30px 32px;margin-bottom:48px;
}
.method-title{font-size:1.3rem;font-weight:700;margin:8px 0 4px}
.method-sub{font-size:0.84rem;color:#64748b;margin-bottom:4px}
.method-note{
  font-size:0.85rem;color:#94a3b8;line-height:1.6;
  background:#ffffff08;border-left:3px solid;border-radius:0 8px 8px 0;
  padding:10px 16px;margin:12px 0;
}

/* ── STATS ───────────────────────────────── */
.stats-bar{display:flex;gap:10px;flex-wrap:wrap;margin:12px 0}
.stat-card{
  background:#141828;border:1px solid #222844;
  border-radius:10px;padding:10px 18px;min-width:95px;
}
.stat-lbl{font-size:0.69rem;color:#64748b;text-transform:uppercase;letter-spacing:0.07em}
.stat-val{font-size:1.25rem;font-weight:700;color:#f1f5f9;margin-top:2px}

/* ── TABLES ──────────────────────────────── */
.wrap{overflow-x:auto;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.4);margin-bottom:16px}
table{border-collapse:collapse;font-size:0.81rem;width:100%}
th,td{padding:9px 12px;border:1px solid #1e2438}
th{
  background:#141828;color:#94a3b8;font-weight:600;
  text-transform:uppercase;font-size:0.68rem;letter-spacing:0.06em;text-align:center;
}
tr:hover td{background:rgba(255,255,255,0.01)!important}
code{
  background:#1a2035;color:#a5b4fc;
  padding:2px 6px;border-radius:4px;
  border:1px solid #2d3480;font-size:0.8em;
}

/* ── DIVIDERS ────────────────────────────── */
hr{border:0;border-top:2px dashed #1e2438;margin:48px 0}

/* ── SECTION HEADER ──────────────────────── */
.sec-hdr{
  font-size:1.1rem;font-weight:600;color:#6ee7b7;
  margin:44px 0 16px;display:flex;align-items:center;gap:10px;
}
.sec-hdr::before{
  content:'';display:inline-block;width:4px;height:20px;
  background:linear-gradient(180deg,#059669,#10b981);border-radius:2px;
}

/* ── LEGEND ──────────────────────────────── */
.legend{display:flex;gap:14px;flex-wrap:wrap;margin:18px 0;font-size:0.78rem}
.legend-item{display:flex;align-items:center;gap:5px}
.ldot{width:11px;height:11px;border-radius:3px}

/* ── ANALYSIS ────────────────────────────── */
.ana-box{
  background:#0a1810;border:1px solid #065f46;
  border-radius:12px;padding:26px 30px;margin-bottom:30px;
}
.ana-box h3{color:#34d399;font-size:0.95rem;margin-bottom:14px}
.ana-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ana-card{background:#070a10;border:1px solid #0d2818;border-radius:10px;padding:16px}
.ana-ttl{font-size:0.76rem;font-weight:700;text-transform:uppercase;margin-bottom:8px}
.ana-txt{font-size:0.83rem;color:#94a3b8;line-height:1.5}

.footer{
  margin-top:50px;padding-top:18px;border-top:1px solid #1e2438;
  color:#334155;font-size:0.78rem;text-align:center;
}
"""


def build_html(all_data: dict, ts: str) -> str:
    total_q = len(all_data.get(METHODS[0]["key"], []))

    # Overall best MRR
    sms = {m["key"]: calc(all_data.get(m["key"],[])) for m in METHODS}
    best_key   = max(sms, key=lambda k: sms[k]["mrr"])
    best_title = next(m["title"] for m in METHODS if m["key"]==best_key)
    best_acc   = next(m["accent"] for m in METHODS if m["key"]==best_key)

    # Nav
    nav = '<span class="topnav-brand">&#128202; DEMAKAI</span>'
    for meta in METHODS:
        anchor  = meta["key"].replace(" ","_").replace(".","").replace("/","")
        acc     = meta["accent"]
        num     = meta["num"]
        ttl     = meta["title"][:22]
        nav += f"<a href='#{anchor}' class='nav-link' style='border-color:{acc};color:{acc}'>{num}. {ttl}</a>"
    nav += "<a href='#perbandingan' class='nav-link' style='border-color:#fbbf24;color:#fbbf24'>&#11088; Perbandingan</a>"
    nav += "<a href='#analisis' class='nav-link' style='border-color:#34d399;color:#34d399'>&#128202; Analisis</a>"

    # Method sections
    sections = ""
    for meta in METHODS:
        rows = all_data.get(meta["key"], [])
        sections += _method_section(meta, rows)

    # Comparison + Analysis
    comp    = _comparison_table(all_data)
    analisa = _analysis(all_data)

    # Legend
    legend = """
<div class="legend">
  <div class="legend-item"><div class="ldot" style="background:#064e3b;border:1px solid #6ee7b7"></div><span style="color:#6ee7b7">Rank 1</span></div>
  <div class="legend-item"><div class="ldot" style="background:#065f46;border:1px solid #a7f3d0"></div><span style="color:#a7f3d0">Rank 2–3</span></div>
  <div class="legend-item"><div class="ldot" style="background:#1e3a2f;border:1px solid #d1fae5"></div><span style="color:#d1fae5">Rank 4–10</span></div>
  <div class="legend-item"><div class="ldot" style="background:#1a1f2e;border:1px solid #475569"></div><span style="color:#475569">Tidak ditemukan</span></div>
  <div class="legend-item"><div class="ldot" style="background:#1e3a5f;border:1px solid #93c5fd"></div><span style="color:#93c5fd">Hit (Top@N=1)</span></div>
  <div class="legend-item"><div class="ldot" style="background:#422006;border:1px solid #fcd34d"></div><span style="color:#fcd34d">Kuning = Preprocessing</span></div>
  <div class="legend-item"><div class="ldot" style="background:#064e3b;border:1px solid #86efac"></div><span style="color:#86efac">Hijau muda = Contoh Lapangan</span></div>
</div>"""

    return f"""<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>DEMAKAI — Evaluasi Komprehensif Sistem Pencarian KBLI</title>
  <style>{CSS}</style>
</head>
<body>

<div class="topnav">{nav}</div>

<div class="page">

  <div class="page-badge">Evaluasi Komprehensif DEMAKAI</div>
  <h1>Evaluasi Sistem Pencarian KBLI</h1>
  <p class="meta">DEMAKAI &mdash; Perbandingan SQL LIKE dan Hybrid Search &middot; Seluruh Tahap Pengembangan</p>
  <p class="meta">Generated: {ts} &nbsp;&middot;&nbsp; <strong style="color:#6ee7b7">{total_q} query</strong> per metode &nbsp;&middot;&nbsp; PostgreSQL + pgvector &nbsp;&middot;&nbsp; Embedding: Gemini</p>
  <p class="meta" style="margin-top:6px">
    Metode terbaik: <strong style="color:{best_acc}">{best_title}</strong> &nbsp;&middot;&nbsp;
    MRR = <strong style="color:{best_acc}">{sms[best_key]['mrr']:.4f}</strong>
  </p>

  {legend}
  <hr>

  {sections}

  <hr>
  <div id="perbandingan" class="sec-hdr">&#11088; Perbandingan Semua Metode</div>
  {comp}

  <hr>
  {analisa}

  <div class="footer">
    DEMAKAI &mdash; Dashboard Evaluasi Komprehensif (Semua Metode) &middot;
    Data dari: {os.path.basename(CSV_FILE)} &middot; Build: {ts}
  </div>

</div>
</body>
</html>"""


# ─────────────────────────────────────────────────────────────────────────────
# MAIN
# ─────────────────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    ts       = datetime.now().strftime("%d %B %Y, %H:%M:%S")
    ts_file  = datetime.now().strftime("%Y%m%d_%H%M%S")

    all_data = load(CSV_FILE)

    print(f"[INFO] {sum(len(v) for v in all_data.values())} baris data dimuat.")
    for m in METHODS:
        rows = all_data.get(m["key"], [])
        sm   = calc(rows)
        print(f"  {m['num']}. {m['title'][:45]:<45} MRR={sm['mrr']:.4f}  Top@10={sm['top10']:.1%}")

    html     = build_html(all_data, ts)
    out_path = os.path.join(OUT_DIR, f"dashboard_komprehensif_{ts_file}.html")

    with open(out_path, "w", encoding="utf-8") as f:
        f.write(html)

    print(f"\n[HTML] {os.path.abspath(out_path)}")
    print("Selesai!")

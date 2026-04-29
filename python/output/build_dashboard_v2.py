"""
build_dashboard_v2.py
Buat dashboard komprehensif dari data CSV nyata.
"""
import csv, sys, re
from datetime import datetime
from collections import defaultdict

sys.stdout.reconfigure(encoding='utf-8')

# ── Load CSV ──────────────────────────────────────────────────────────────────
def load_csv(path):
    try:
        with open(path, encoding='utf-8') as f:
            return list(csv.DictReader(f))
    except Exception as e:
        print(f"[ERR] {path}: {e}")
        return []

rows_before = load_csv(r'd:\magang_bps\backend-demakai\python\output\evaluasi_semua_20260420_144133.csv')
print(f"Rows before-fix: {len(rows_before)}")

# ── Susun data per metode ──────────────────────────────────────────────────────
# Struktur: {metode: {tipe: [row, ...]}}
def group_rows(rows):
    g = defaultdict(lambda: defaultdict(list))
    for r in rows:
        g[r['metode']][r['tipe']].append(r)
    return g

grp_before = group_rows(rows_before)

# ── Hitung metrik summary ──────────────────────────────────────────────────────
def summary(rows):
    if not rows: return {'top1':0,'top3':0,'top10':0,'mrr':0,'n':0}
    n = len(rows)
    return {
        'n': n,
        'top1':  round(sum(int(r['top1'])  for r in rows)/n*100, 1),
        'top3':  round(sum(int(r['top3'])  for r in rows)/n*100, 1),
        'top10': round(sum(int(r['top10']) for r in rows)/n*100, 1),
        'mrr':   round(sum(float(r['rr'])  for r in rows)/n, 4),
    }

# ── Hitung metrik sesudah fix dari evaluasi_after_fix_21apr ───────────────────
# File after-fix menggunakan format evaluate.py (kolom rank_SQL_None, dll.)
# Baca dari CSV evaluasi lain yang berisi kolom yang berbeda
# FORMAT: no, tipe, query, kode_ground_truth, rank_SQL_None, top1_SQL_None, ...
def load_after_fix():
    """Load data sesudah fix dari evaluasi_after_fix output (HTML)."""
    # Karena tidak ada CSV langsung, pakai data yang bisa di-derive
    # Dari output evaluasi terminal kita sudah tahu hasil per query per metode
    # Load contoh_lapangan CSV untuk mendapat data preprocessing
    cl = load_csv(r'd:\magang_bps\backend-demakai\python\output\contoh_lapangan_20260420_112529.csv')
    return cl

cl_rows = load_after_fix()
print(f"Contoh lapangan rows: {len(cl_rows)}")
if cl_rows:
    print(f"  Columns: {list(cl_rows[0].keys())}")

# Juga load prep_hybrid untuk data yang ada
ph = load_csv(r'd:\magang_bps\backend-demakai\python\output\prep_hybrid_20260420_110928.csv')
print(f"Prep hybrid rows: {len(ph)}")
if ph:
    print(f"  Columns: {list(ph[0].keys())}")

# Cek hybrid_prep
hp = load_csv(r'd:\magang_bps\backend-demakai\python\output\hybrid_prep_20260420_102443.csv')
print(f"Hybrid prep rows: {len(hp)}")
if hp:
    print(f"  Columns: {list(hp[0].keys())}")

# Print semua metode yang ada di evaluasi_semua
metodes = sorted(set(r['metode'] for r in rows_before))
for m in metodes:
    kbli_rows = [r for r in rows_before if r['metode']==m and r['tipe']=='KBLI']
    kbji_rows = [r for r in rows_before if r['metode']==m and r['tipe']=='KBJI']
    sk = summary(kbli_rows)
    sj = summary(kbji_rows)
    print(f"\n{m}")
    print(f"  KBLI: Top@1={sk['top1']}% Top@3={sk['top3']}% Top@10={sk['top10']}% MRR={sk['mrr']}")
    print(f"  KBJI: Top@1={sj['top1']}% Top@3={sj['top3']}% Top@10={sj['top10']}% MRR={sj['mrr']}")

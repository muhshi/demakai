"""
extract_all_data.py
Ekstrak data lengkap dari semua file CSV evaluasi untuk dashboard.
"""
import csv, json
from collections import defaultdict

def load_csv(path):
    try:
        with open(path, encoding='utf-8') as f:
            return list(csv.DictReader(f))
    except:
        return []

# ── 1. Load CSV "evaluasi_semua" (sebelum fix) ───────────────────────────────
rows_before = load_csv(r'd:\magang_bps\backend-demakai\python\output\evaluasi_semua_20260420_144133.csv')

# Metode yang ada: 1.SQL LIKE (Baseline), 2.Hybrid Search, 3.SQL LIKE + Preprocessing,
# 4.Hybrid Search + Preprocessing, 5.SQL LIKE + Preprocessing + Contoh Lapangan,
# 6.Hybrid + Preprocessing + Contoh Lapangan (FINAL)
print(f"Rows before: {len(rows_before)}")
if rows_before:
    print(f"Columns: {list(rows_before[0].keys())}")
    metodes = sorted(set(r['metode'] for r in rows_before))
    tipes   = sorted(set(r['tipe']   for r in rows_before))
    print(f"Metode: {metodes}")
    print(f"Tipe: {tipes}")

print()

# ── 2. Load CSV "after fix" - dari evaluasi_after_fix_21apr ──────────────────
# Format baru dari evaluate.py: kolom rank_SQL_None, top1_SQL_None, etc.
# Cari file xlsx atau pakai HTML. Tapi kita punya CSV evaluasi_semua format lama.
# Gunakan evaluasi_after_fix csv jika ada, atau perlu re-read dari HTML

# Cek kolom di evaluasi_semua
print("\n── Sampel 3 baris evaluasi_semua ──")
for r in rows_before[:3]:
    print(dict(r))

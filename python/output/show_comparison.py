import csv
from collections import defaultdict

# Data sebelum fix (dari evaluasi_semua_20260420_144133.csv)
BEFORE = {
    "1. SQL LIKE (Baseline)":                    {"kbli_t1": 0,    "kbli_mrr": 0.0278, "kbji_t1": 0,    "kbji_mrr": 0.0000},
    "2. Hybrid Search":                          {"kbli_t1": 90.0, "kbli_mrr": 0.9167, "kbji_t1": 73.3, "kbji_mrr": 0.8053},
    "3. SQL LIKE + Preprocessing":               {"kbli_t1": 86.7, "kbli_mrr": 0.8944, "kbji_t1": 70.0, "kbji_mrr": 0.7811},
    "4. Hybrid Search + Preprocessing":          {"kbli_t1": 80.0, "kbli_mrr": 0.8500, "kbji_t1": 63.3, "kbji_mrr": 0.6861},
}

# Load file hasil baru
new_file = r'd:\magang_bps\backend-demakai\python\output\evaluasi_after_fix_21apr.html'

# Hitung dari CSV evaluasi detail menggunakan evaluate output
# Mapping nama kolom evaluate.py → nama metode kita
COMBO_MAP = {
    "SQL_None":       "01. SQL LIKE (Baseline)",
    "SQL_Expansion":  "03. SQL LIKE + Preprocessing",
    "Hybrid_None":    "02. Hybrid Search",
    "Hybrid_Expansion": "04. Hybrid + Preprocessing",
    "SQL_Advanced":   "03b. SQL LIKE + Advanced",
    "Hybrid_Advanced":"04b. Hybrid + Advanced",
}

# Baca dari HTML tidak praktis, gunakan CSV lama sebagai konfirmasi
# Tampilkan prediksi vs aktual
print("=" * 70)
print("PERBANDINGAN PERFORMA: SEBELUM vs SESUDAH FIX")
print("=" * 70)
print()
print(f"{'Metode':<45} {'Dataset':<8} {'Top@1':>6} {'MRR':>7}")
print("-" * 70)

for metode, vals in BEFORE.items():
    print(f"{metode:<45} {'KBLI':<8} {vals['kbli_t1']:>5.1f}% {vals['kbli_mrr']:>7.4f}")
    print(f"{'':<45} {'KBJI':<8} {vals['kbji_t1']:>5.1f}% {vals['kbji_mrr']:>7.4f}")
    print()

print()
print("PERUBAHAN KODE YANG DIIMPLEMENTASIKAN:")
print("-" * 70)
changes = [
    ("hybrid.py - _merge_and_boost()", 
     "Boost distance OVERRIDE PAKSA (0.04 fixed)",
     "Boost PROPORSIONAL (distance * 0.80/0.88/0.94)"),
    ("hybrid.py - search_expansion()", 
     "embed_text = preprocessed['clean'] (teks expansion panjang)",
     "embed_text = preprocessed['original'] (query asli ringkas)"),
    ("hybrid.py - search_expansion()", 
     "all_tokens = expanded_tokens (25+ kata) untuk boosting",
     "core_tokens = tokens (5-8 kata asli) untuk boosting"),
    ("hybrid.py - search_advanced()", 
     "embed_text = stemmed_clean",
     "embed_text = original query"),
    ("sql_like.py - _run_query_or_tokens()", 
     "Skor flat: 1 poin per match (judul = contoh lapangan)",
     "Skor berbobot: judul=3, deskripsi=2, contoh_lapangan=1"),
    ("sql_like.py - search_expansion()", 
     "Step 3 OR fallback jalan jika len < limit (mencemari hasil)",
     "Step 3 OR fallback hanya jika hasil KOSONG TOTAL"),
]

for file, before, after in changes:
    print(f"\n📁 {file}")
    print(f"   ❌ SEBELUM: {before}")
    print(f"   ✅ SESUDAH: {after}")

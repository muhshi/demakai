with open('evaluate_preprocessing_comparison.py', 'r', encoding='utf-8') as f: content = f.read()

new_meta = '''METHODS_META = [
    # key, title, subtitle, tags, accent, accent_bg, show_prep, show_contoh, indicators(prep, exp, cl)
    ("m1_sql_baseline",
     "1. SQL LIKE (Baseline)",
     "Tanpa preprocessing &middot; Pencarian frasa mentah",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Advanced Prep",False),("Expansion",False),("Contoh Lapangan",False)],
     "#64748b","#111827", False, False, (False, False, False)),

    ("m2_sql_advanced",
     "2. SQL LIKE + Advanced Preprocessing (NO CL)",
     "Lowercase &rarr; Cleaning &rarr; Stopword &rarr; Stemming",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Advanced Prep",True),("Expansion",False),("Contoh Lapangan",False)],
     "#f59e0b","#14120a", True, False, (True, False, False)),

    ("m3_sql_adv_cl",
     "3. SQL LIKE + Advanced Preprocessing + Contoh Lapangan",
     "Advanced Prep + Pencarian pada field contoh_lapangan",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Advanced Prep",True),("Expansion",False),("Contoh Lapangan",True)],
     "#fcd34d","#1a1405", True, True, (True, False, True)),

    ("m4_sql_adv_exp",
     "4. SQL LIKE + Preprocessing + Expansion (NO CL)",
     "Advanced Prep + Sinonim + Variasi istilah + Keyword KBLI/KBJI",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Advanced Prep",True),("Expansion",True),("Contoh Lapangan",False)],
     "#a78bfa","#0d0b1a", True, False, (True, True, False)),

    ("m5_sql_adv_exp_cl",
     "5. SQL LIKE + Preprocessing + Expansion + Contoh Lapangan",
     "Advanced Prep + Expansion + Pencarian pada field contoh_lapangan",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Advanced Prep",True),("Expansion",True),("Contoh Lapangan",True)],
     "#c084fc","#0f0a1f", True, True, (True, True, True)),

    ("m6_hybrid_baseline",
     "6. Hybrid Search (Baseline)",
     "SQL LIKE + Semantic Search &middot; Tanpa preprocessing",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",False),("Expansion",False),("Contoh Lapangan",False)],
     "#7dd3fc","#060f14", False, False, (False, False, False)),

    ("m7_hybrid_advanced",
     "7. Hybrid + Advanced Preprocessing (NO CL)",
     "Hybrid Search + Lowercase/Stopword/Stemming &middot; Tanpa expansion",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",True),("Expansion",False),("Contoh Lapangan",False)],
     "#38bdf8","#060f18", True, False, (True, False, False)),

    ("m8_hybrid_adv_cl",
     "8. Hybrid + Advanced Preprocessing + Contoh Lapangan",
     "Hybrid Search + Advanced Prep + Pencarian pada field contoh_lapangan",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",True),("Expansion",False),("Contoh Lapangan",True)],
     "#34d399","#050f0a", True, True, (True, False, True)),

    ("m9_hybrid_adv_exp",
     "9. Hybrid + Preprocessing + Expansion (NO CL)",
     "Hybrid Search + Advanced Prep + Query Expansion",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",True),("Expansion",True),("Contoh Lapangan",False)],
     "#2dd4bf","#050e0d", True, False, (True, True, False)),

    ("m10_hybrid_adv_exp_cl",
     "10. Hybrid + Preprocessing + Expansion + Contoh Lapangan (FINAL)",
     "Hybrid Search + Advanced Prep + Expansion + Boost via CL",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",True),("Expansion",True),("Contoh Lapangan",True)],
     "#10b981","#020f0a", True, True, (True, True, True)),
]'''

start_idx = content.find('METHODS_META = [')
end_idx = content.find('def save_html', start_idx)

fragment = content[start_idx:end_idx]
last_bracket_idx = fragment.rfind(']')

full_old_meta = content[start_idx:start_idx + last_bracket_idx + 1]

content = content.replace(full_old_meta, new_meta)

with open('evaluate_preprocessing_comparison.py', 'w', encoding='utf-8') as f:
    f.write(content)
print("Patch 2 success")

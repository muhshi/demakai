import sys
import re

with open('evaluate_preprocessing_comparison.py', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace evaluate_all signature and dict
old_eval = '''def evaluate_all(queries: list) -> dict:
    """Return dict berisi 8 metode → list of row-dict."""
    METHODS = {
        "m1_sql_baseline":       [],
        "m2_sql_advanced":       [],
        "m3_sql_adv_exp":        [],
        "m4_sql_adv_exp_cl":     [],
        "m5_hybrid_baseline":    [],
        "m6_hybrid_advanced":    [],
        "m7_hybrid_adv_exp":     [],
        "m8_hybrid_adv_exp_cl":  [],
    }'''

new_eval = '''import search.sql_like
import search.hybrid

def evaluate_all(queries: list) -> dict:
    """Return dict berisi 10 metode → list of row-dict."""
    METHODS = {
        "m1_sql_baseline":       [],
        "m2_sql_advanced":       [],
        "m3_sql_adv_cl":         [],
        "m4_sql_adv_exp":        [],
        "m5_sql_adv_exp_cl":     [],
        "m6_hybrid_baseline":    [],
        "m7_hybrid_advanced":    [],
        "m8_hybrid_adv_cl":      [],
        "m9_hybrid_adv_exp":     [],
        "m10_hybrid_adv_exp_cl": [],
    }'''

content = content.replace(old_eval, new_eval)

# Replace the evaluation loop blocks
old_loop = '''        # ── M1: SQL LIKE Baseline ─────────────────────────────────
        try:
            res = search_true_baseline(query, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M1 ERR: {e}"); m = metrics(0)
        METHODS["m1_sql_baseline"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":"","contoh":"", **m})

        # ── M2: SQL LIKE + Advanced Preprocessing ─────────────────
        try:
            res = sql_adv(prep_adv, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M2 ERR: {e}"); m = metrics(0)
        METHODS["m2_sql_advanced"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_adv,"contoh":"", **m})

        # ── M3: SQL LIKE + Advanced + Expansion ───────────────────
        try:
            res = sql_exp(prep_exp, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M3 ERR: {e}"); m = metrics(0)
        METHODS["m3_sql_adv_exp"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_exp,"contoh":"", **m})

        # ── M4: SQL LIKE + Advanced + Expansion + Contoh Lapangan ─
        contoh = get_contoh_from_db(kode_gt, tipe)
        try:
            res = sql_exp(prep_exp, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M4 ERR: {e}"); m = metrics(0)
        METHODS["m4_sql_adv_exp_cl"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_exp,"contoh":contoh, **m})

        # ── M5: Hybrid Search Baseline ────────────────────────────
        try:
            res = hyb_raw(query, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M5 ERR: {e}"); m = metrics(0)
        METHODS["m5_hybrid_baseline"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":"","contoh":"", **m})

        # ── M6: Hybrid + Advanced Preprocessing ──────────────────
        try:
            res = hyb_adv(prep_adv, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M6 ERR: {e}"); m = metrics(0)
        METHODS["m6_hybrid_advanced"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_adv,"contoh":"", **m})

        # ── M7: Hybrid + Advanced + Expansion ─────────────────────
        try:
            res = hyb_exp(prep_exp, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M7 ERR: {e}"); m = metrics(0)
        METHODS["m7_hybrid_adv_exp"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_exp,"contoh":"", **m})

        # ── M8: Hybrid + Advanced + Expansion + Contoh Lapangan ──
        try:
            res = hyb_exp(prep_exp, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M8 ERR: {e}"); m = metrics(0)
        METHODS["m8_hybrid_adv_exp_cl"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_exp,"contoh":contoh, **m})

        # Status ringkas
        ranks = [METHODS[k][-1]["rank"] for k in sorted(METHODS.keys())]
        print(f"         Ranks: M1={ranks[0]} M2={ranks[1]} M3={ranks[2]} M4={ranks[3]} "
              f"M5={ranks[4]} M6={ranks[5]} M7={ranks[6]} M8={ranks[7]}", flush=True)'''

new_loop = '''        contoh = get_contoh_from_db(kode_gt, tipe)

        # ── M1: SQL LIKE Baseline (NO CL) ─────────────────────────────────
        search.sql_like.USE_CL = False
        try:
            res = sql_raw(query, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M1 ERR: {e}"); m = metrics(0)
        METHODS["m1_sql_baseline"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":"","contoh":"", **m})

        # ── M2: SQL LIKE + Advanced Preprocessing (NO CL) ─────────────────
        search.sql_like.USE_CL = False
        try:
            res = sql_adv(prep_adv, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M2 ERR: {e}"); m = metrics(0)
        METHODS["m2_sql_advanced"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_adv,"contoh":"", **m})

        # ── M3: SQL LIKE + Advanced Preprocessing + Contoh Lapangan ──────
        search.sql_like.USE_CL = True
        try:
            res = sql_adv(prep_adv, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M3 ERR: {e}"); m = metrics(0)
        METHODS["m3_sql_adv_cl"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_adv,"contoh":contoh, **m})

        # ── M4: SQL LIKE + Preprocessing + Expansion (NO CL) ──────────────
        search.sql_like.USE_CL = False
        try:
            res = sql_exp(prep_exp, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M4 ERR: {e}"); m = metrics(0)
        METHODS["m4_sql_adv_exp"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_exp,"contoh":"", **m})

        # ── M5: SQL LIKE + Preprocessing + Expansion + Contoh Lapangan ───
        search.sql_like.USE_CL = True
        try:
            res = sql_exp(prep_exp, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M5 ERR: {e}"); m = metrics(0)
        METHODS["m5_sql_adv_exp_cl"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_exp,"contoh":contoh, **m})

        # ── M6: Hybrid Search Baseline (NO CL) ────────────────────────────
        search.hybrid.USE_CL = False
        try:
            res = hyb_raw(query, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M6 ERR: {e}"); m = metrics(0)
        METHODS["m6_hybrid_baseline"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":"","contoh":"", **m})

        # ── M7: Hybrid + Advanced Preprocessing (NO CL) ──────────────────
        search.hybrid.USE_CL = False
        try:
            res = hyb_adv(prep_adv, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M7 ERR: {e}"); m = metrics(0)
        METHODS["m7_hybrid_advanced"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_adv,"contoh":"", **m})

        # ── M8: Hybrid + Advanced Preprocessing + Contoh Lapangan ──────
        search.hybrid.USE_CL = True
        try:
            res = hyb_adv(prep_adv, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M8 ERR: {e}"); m = metrics(0)
        METHODS["m8_hybrid_adv_cl"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_adv,"contoh":contoh, **m})

        # ── M9: Hybrid + Preprocessing + Expansion (NO CL) ───────────────
        search.hybrid.USE_CL = False
        try:
            res = hyb_exp(prep_exp, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M9 ERR: {e}"); m = metrics(0)
        METHODS["m9_hybrid_adv_exp"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_exp,"contoh":"", **m})

        # ── M10: Hybrid + Preprocessing + Expansion + Contoh Lapangan ────
        search.hybrid.USE_CL = True
        try:
            res = hyb_exp(prep_exp, limit=LIMIT, model=model)
            m   = metrics(get_rank(res, kode_gt))
        except Exception as e:
            print(f"         M10 ERR: {e}"); m = metrics(0)
        METHODS["m10_hybrid_adv_exp_cl"].append({
            "no":no,"query":query,"kode_gt":kode_gt,"tipe":tipe,
            "preprocessed":pd_exp,"contoh":contoh, **m})

        # Status ringkas
        ranks = [METHODS[k][-1]["rank"] for k in sorted(METHODS.keys(), key=lambda x: int(x.split('_')[0][1:]))]
        print(f"         Ranks: M1={ranks[0]} M2={ranks[1]} M3={ranks[2]} M4={ranks[3]} M5={ranks[4]} "
              f"M6={ranks[5]} M7={ranks[6]} M8={ranks[7]} M9={ranks[8]} M10={ranks[9]}", flush=True)'''

content = content.replace(old_loop, new_loop)

# Replace METHODS_META
old_meta = r'''METHODS_META = \[.*?\]'''
new_meta = '''METHODS_META = [
    # key, title, subtitle, tags, accent, accent_bg, show_prep, show_contoh, indicators(prep, exp, cl)
    ("m1_sql_baseline",
     "1. SQL LIKE (Baseline)",
     "Tanpa preprocessing &middot; Pencarian frasa mentah",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Advanced Prep",False),("Expansion",False),("Contoh Lapangan",False)],
     "#64748b","#111827", False, False, (False, False, False)),

    ("m2_sql_advanced",
     "2. SQL LIKE + Advanced Preprocessing",
     "Lowercase &rarr; Cleaning &rarr; Stopword &rarr; Stemming",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Advanced Prep",True),("Expansion",False),("Contoh Lapangan",False)],
     "#f59e0b","#14120a", True, False, (True, False, False)),

    ("m3_sql_adv_cl",
     "3. SQL LIKE + Advanced Preprocessing + Contoh Lapangan",
     "Advanced Prep + Pencarian pada field contoh_lapangan",
     [("SQL LIKE",True),("Semantic",False),("Hybrid",False),("Advanced Prep",True),("Expansion",False),("Contoh Lapangan",True)],
     "#fcd34d","#1a1405", True, True, (True, False, True)),

    ("m4_sql_adv_exp",
     "4. SQL LIKE + Preprocessing + Expansion",
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
     "7. Hybrid + Advanced Preprocessing",
     "Hybrid Search + Lowercase/Stopword/Stemming &middot; Tanpa expansion",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",True),("Expansion",False),("Contoh Lapangan",False)],
     "#38bdf8","#060f18", True, False, (True, False, False)),

    ("m8_hybrid_adv_cl",
     "8. Hybrid + Advanced Preprocessing + Contoh Lapangan",
     "Hybrid Search + Advanced Prep + Pencarian pada field contoh_lapangan",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",True),("Expansion",False),("Contoh Lapangan",True)],
     "#34d399","#050f0a", True, True, (True, False, True)),

    ("m9_hybrid_adv_exp",
     "9. Hybrid + Preprocessing + Expansion",
     "Hybrid Search + Advanced Prep + Query Expansion",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",True),("Expansion",True),("Contoh Lapangan",False)],
     "#2dd4bf","#050e0d", True, False, (True, True, False)),

    ("m10_hybrid_adv_exp_cl",
     "10. Hybrid + Preprocessing + Expansion + Contoh Lapangan (FINAL)",
     "Hybrid + Preprocessing + Expansion + Boost via contoh_lapangan &mdash; sistem terlengkap",
     [("SQL LIKE",True),("Semantic",True),("Hybrid",True),("Advanced Prep",True),("Expansion",True),("Contoh Lapangan",True)],
     "#10b981","#020f0a", True, True, (True, True, True)),
]'''
content = re.sub(r'METHODS_META\s*=\s*\[.*?\]', new_meta, content, flags=re.DOTALL)

# Replace table group headers
content = content.replace('for m in METHODS_META[:4]:', 'for m in METHODS_META[:5]:')
content = content.replace('for m in METHODS_META[4:]:', 'for m in METHODS_META[5:]:')

content = content.replace('Evaluasi Preprocessing Comparison (8 Metode)', 'Evaluasi Preprocessing Comparison (10 Metode)')
content = content.replace('Perbandingan Semua Metode (8 Metode)', 'Perbandingan Semua Metode (10 Metode)')
content = content.replace('RINGKASAN 8 METODE', 'RINGKASAN 10 METODE')
content = content.replace('Evaluasi DEMAKAI — 8 metode', 'Evaluasi DEMAKAI — 10 metode')

old_prints = '''    print("  ┌─ SQL LIKE ──────────────────────────────────────────┐")
    print("  │ M1: Baseline (tanpa preprocessing)                  │")
    print("  │ M2: + Advanced Preprocessing (PREP)                 │")
    print("  │ M3: + PREP + Query Expansion (EXP)                  │")
    print("  │ M4: + PREP + EXP + Contoh Lapangan (CL)            │")
    print("  └─────────────────────────────────────────────────────┘")
    print("  ┌─ HYBRID SEARCH ─────────────────────────────────────┐")
    print("  │ M5: Baseline (tanpa preprocessing)                  │")
    print("  │ M6: + Advanced Preprocessing (PREP)                 │")
    print("  │ M7: + PREP + Query Expansion (EXP)                  │")
    print("  │ M8: + PREP + EXP + Contoh Lapangan (CL)            │")
    print("  └─────────────────────────────────────────────────────┘")'''

new_prints = '''    print("  ┌─ SQL LIKE ──────────────────────────────────────────┐")
    print("  │ M1: Baseline (tanpa preprocessing)                  │")
    print("  │ M2: + Advanced Preprocessing (PREP)                 │")
    print("  │ M3: + PREP + Contoh Lapangan (CL)                   │")
    print("  │ M4: + PREP + Query Expansion (EXP)                  │")
    print("  │ M5: + PREP + EXP + Contoh Lapangan (CL)             │")
    print("  └─────────────────────────────────────────────────────┘")
    print("  ┌─ HYBRID SEARCH ─────────────────────────────────────┐")
    print("  │ M6: Baseline (tanpa preprocessing)                  │")
    print("  │ M7: + Advanced Preprocessing (PREP)                 │")
    print("  │ M8: + PREP + Contoh Lapangan (CL)                   │")
    print("  │ M9: + PREP + Query Expansion (EXP)                  │")
    print("  │ M10: + PREP + EXP + Contoh Lapangan (CL)            │")
    print("  └─────────────────────────────────────────────────────┘")'''
content = content.replace(old_prints, new_prints)

old_impact = '''    impact_rows += delta_html("m1_sql_baseline", "m2_sql_advanced", "SQL: Baseline → +PREP")
    impact_rows += delta_html("m2_sql_advanced", "m3_sql_adv_exp", "SQL: +PREP → +PREP+EXP")
    impact_rows += delta_html("m3_sql_adv_exp", "m4_sql_adv_exp_cl", "SQL: +PREP+EXP → +PREP+EXP+CL")
    impact_rows += "<tr><td colspan='4' style='border-top:2px dashed #333;padding:4px'></td></tr>"
    impact_rows += delta_html("m5_hybrid_baseline", "m6_hybrid_advanced", "Hybrid: Baseline → +PREP")
    impact_rows += delta_html("m6_hybrid_advanced", "m7_hybrid_adv_exp", "Hybrid: +PREP → +PREP+EXP")
    impact_rows += delta_html("m7_hybrid_adv_exp", "m8_hybrid_adv_exp_cl", "Hybrid: +PREP+EXP → +PREP+EXP+CL")
    impact_rows += "<tr><td colspan='4' style='border-top:2px dashed #333;padding:4px'></td></tr>"
    impact_rows += delta_html("m1_sql_baseline", "m5_hybrid_baseline", "SQL Baseline → Hybrid Baseline")
    impact_rows += delta_html("m4_sql_adv_exp_cl", "m8_hybrid_adv_exp_cl", "SQL FULL → Hybrid FULL")'''

new_impact = '''    impact_rows += delta_html("m1_sql_baseline", "m2_sql_advanced", "SQL: Baseline → +PREP")
    impact_rows += delta_html("m2_sql_advanced", "m3_sql_adv_cl", "SQL: +PREP → +PREP+CL")
    impact_rows += delta_html("m2_sql_advanced", "m4_sql_adv_exp", "SQL: +PREP → +PREP+EXP")
    impact_rows += delta_html("m4_sql_adv_exp", "m5_sql_adv_exp_cl", "SQL: +PREP+EXP → +PREP+EXP+CL")
    impact_rows += "<tr><td colspan='4' style='border-top:2px dashed #333;padding:4px'></td></tr>"
    impact_rows += delta_html("m6_hybrid_baseline", "m7_hybrid_advanced", "Hybrid: Baseline → +PREP")
    impact_rows += delta_html("m7_hybrid_advanced", "m8_hybrid_adv_cl", "Hybrid: +PREP → +PREP+CL")
    impact_rows += delta_html("m7_hybrid_advanced", "m9_hybrid_adv_exp", "Hybrid: +PREP → +PREP+EXP")
    impact_rows += delta_html("m9_hybrid_adv_exp", "m10_hybrid_adv_exp_cl", "Hybrid: +PREP+EXP → +PREP+EXP+CL")
    impact_rows += "<tr><td colspan='4' style='border-top:2px dashed #333;padding:4px'></td></tr>"
    impact_rows += delta_html("m1_sql_baseline", "m6_hybrid_baseline", "SQL Baseline → Hybrid Baseline")
    impact_rows += delta_html("m5_sql_adv_exp_cl", "m10_hybrid_adv_exp_cl", "SQL FULL → Hybrid FULL")'''
content = content.replace(old_impact, new_impact)

old_ana_sql = '''          <strong style="color:#fbbf24">SQL LIKE:</strong> MRR {all_sms['m1_sql_baseline']['mrr']:.4f} → {all_sms['m2_sql_advanced']['mrr']:.4f}
          <br>
          <strong style="color:#38bdf8">Hybrid:</strong> MRR {all_sms['m5_hybrid_baseline']['mrr']:.4f} → {all_sms['m6_hybrid_advanced']['mrr']:.4f}'''

new_ana_sql = '''          <strong style="color:#fbbf24">SQL LIKE:</strong> MRR {all_sms['m1_sql_baseline']['mrr']:.4f} → {all_sms['m2_sql_advanced']['mrr']:.4f}
          <br>
          <strong style="color:#38bdf8">Hybrid:</strong> MRR {all_sms['m6_hybrid_baseline']['mrr']:.4f} → {all_sms['m7_hybrid_advanced']['mrr']:.4f}'''
content = content.replace(old_ana_sql, new_ana_sql)

old_ana_exp = '''          <strong style="color:#fbbf24">SQL LIKE:</strong> MRR {all_sms['m2_sql_advanced']['mrr']:.4f} → {all_sms['m3_sql_adv_exp']['mrr']:.4f}
          <br>
          <strong style="color:#38bdf8">Hybrid:</strong> MRR {all_sms['m6_hybrid_advanced']['mrr']:.4f} → {all_sms['m7_hybrid_adv_exp']['mrr']:.4f}'''
new_ana_exp = '''          <strong style="color:#fbbf24">SQL LIKE:</strong> MRR {all_sms['m2_sql_advanced']['mrr']:.4f} → {all_sms['m4_sql_adv_exp']['mrr']:.4f}
          <br>
          <strong style="color:#38bdf8">Hybrid:</strong> MRR {all_sms['m7_hybrid_advanced']['mrr']:.4f} → {all_sms['m9_hybrid_adv_exp']['mrr']:.4f}'''
content = content.replace(old_ana_exp, new_ana_exp)

old_ana_cl = '''<strong style="color:#fbbf24">SQL LIKE:</strong> MRR {all_sms['m3_sql_adv_exp']['mrr']:.4f} → {all_sms['m4_sql_adv_exp_cl']['mrr']:.4f}
          <br>
          <strong style="color:#38bdf8">Hybrid:</strong> MRR {all_sms['m7_hybrid_adv_exp']['mrr']:.4f} → {all_sms['m8_hybrid_adv_exp_cl']['mrr']:.4f}'''
new_ana_cl = '''<strong style="color:#fbbf24">SQL LIKE (No EXP):</strong> MRR {all_sms['m2_sql_advanced']['mrr']:.4f} → {all_sms['m3_sql_adv_cl']['mrr']:.4f}
          <br>
          <strong style="color:#fbbf24">SQL LIKE (With EXP):</strong> MRR {all_sms['m4_sql_adv_exp']['mrr']:.4f} → {all_sms['m5_sql_adv_exp_cl']['mrr']:.4f}
          <br><br>
          <strong style="color:#38bdf8">Hybrid (No EXP):</strong> MRR {all_sms['m7_hybrid_advanced']['mrr']:.4f} → {all_sms['m8_hybrid_adv_cl']['mrr']:.4f}
          <br>
          <strong style="color:#38bdf8">Hybrid (With EXP):</strong> MRR {all_sms['m9_hybrid_adv_exp']['mrr']:.4f} → {all_sms['m10_hybrid_adv_exp_cl']['mrr']:.4f}'''
content = content.replace(old_ana_cl, new_ana_cl)

old_ana_bot = '''          <strong>Tanpa preprocessing:</strong><br>
          SQL={all_sms['m1_sql_baseline']['mrr']:.4f} vs Hybrid={all_sms['m5_hybrid_baseline']['mrr']:.4f}
          <br><br>
          <strong>Dengan FULL preprocessing:</strong><br>
          SQL={all_sms['m4_sql_adv_exp_cl']['mrr']:.4f} vs Hybrid={all_sms['m8_hybrid_adv_exp_cl']['mrr']:.4f}'''
new_ana_bot = '''          <strong>Tanpa preprocessing:</strong><br>
          SQL={all_sms['m1_sql_baseline']['mrr']:.4f} vs Hybrid={all_sms['m6_hybrid_baseline']['mrr']:.4f}
          <br><br>
          <strong>Dengan FULL preprocessing:</strong><br>
          SQL={all_sms['m5_sql_adv_exp_cl']['mrr']:.4f} vs Hybrid={all_sms['m10_hybrid_adv_exp_cl']['mrr']:.4f}'''
content = content.replace(old_ana_bot, new_ana_bot)


# Clean up imports at the top
content = content.replace('from search.sql_like import search_raw as sql_raw', 'from search.sql_like import search_raw as sql_raw\\nfrom search.sql_like import search_numeric_code')


with open('evaluate_preprocessing_comparison.py', 'w', encoding='utf-8') as f:
    f.write(content)

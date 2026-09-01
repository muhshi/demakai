"""
statistical_tests.py — Perhitungan Uji Signifikansi Statistik & Confidence Interval untuk PINTAR KBLI
===================================================================================================
Menghitung:
1. Mean Reciprocal Rank (MRR) dan Standard Deviation (SD) untuk N=30 kueri KBLI
2. 95% Bootstrap Confidence Interval (B = 10.000 iterasi)
3. Paired Permutation Test (Exact / Monte Carlo 10.000 iterasi) p-value
4. Wilcoxon Signed-Rank Test p-value
"""

import openpyxl
import numpy as np

def load_data():
    wb_seb = openpyxl.load_workbook('python/output/evaluasi_sebelum_query_lapangan.xlsx', data_only=True)
    wb_set = openpyxl.load_workbook('python/output/evaluasi_after_fix_21apr.xlsx', data_only=True)
    
    ws_seb = wb_seb['Dashboard Evaluasi']
    ws_set = wb_set['Dashboard Evaluasi']
    
    # N=30 kueri KBLI (row 4 to 33)
    queries = [ws_set.cell(row=r, column=3).value for r in range(4, 34)]
    gt_codes = [ws_set.cell(row=r, column=4).value for r in range(4, 34)]
    
    # Sebelum Contoh Lapangan (Tanpa CL)
    m1_rr = np.array([float(ws_seb.cell(row=r, column=26).value or 0.0) for r in range(4, 34)]) # Hybrid Raw No CL
    m2_rr = np.array([float(ws_seb.cell(row=r, column=31).value or 0.0) for r in range(4, 34)]) # Hybrid Adv No CL
    m3_rr = np.array([float(ws_seb.cell(row=r, column=37).value or 0.0) for r in range(4, 34)]) # Hybrid Exp No CL
    
    # Dengan Contoh Lapangan (Dengan CL)
    m4_rr = np.array([float(ws_set.cell(row=r, column=26).value or 0.0) for r in range(4, 34)]) # Hybrid Raw With CL
    m5_rr = np.array([float(ws_set.cell(row=r, column=31).value or 0.0) for r in range(4, 34)]) # Hybrid Adv With CL
    m6_rr = np.array([float(ws_set.cell(row=r, column=37).value or 0.0) for r in range(4, 34)]) # Hybrid Exp With CL

    # Lexical Baselines (SQL ILIKE)
    sql_raw_no_cl   = np.array([float(ws_seb.cell(row=r, column=9).value or 0.0) for r in range(4, 34)])
    sql_adv_no_cl   = np.array([float(ws_seb.cell(row=r, column=14).value or 0.0) for r in range(4, 34)])
    sql_exp_no_cl   = np.array([float(ws_seb.cell(row=r, column=20).value or 0.0) for r in range(4, 34)])

    sql_raw_with_cl = np.array([float(ws_set.cell(row=r, column=9).value or 0.0) for r in range(4, 34)])
    sql_adv_with_cl = np.array([float(ws_set.cell(row=r, column=14).value or 0.0) for r in range(4, 34)])
    sql_exp_with_cl = np.array([float(ws_set.cell(row=r, column=20).value or 0.0) for r in range(4, 34)])

    data = {
        "queries": queries,
        "gt_codes": gt_codes,
        "M1_Hybrid_Raw_NoCL": m1_rr,
        "M2_Hybrid_Adv_NoCL": m2_rr,
        "M3_Hybrid_Exp_NoCL": m3_rr,
        "M4_Hybrid_Raw_WithCL": m4_rr,
        "M5_Hybrid_Adv_WithCL": m5_rr,
        "M6_Hybrid_Exp_WithCL": m6_rr,
        "SQL_Raw_NoCL": sql_raw_no_cl,
        "SQL_Adv_NoCL": sql_adv_no_cl,
        "SQL_Exp_NoCL": sql_exp_no_cl,
        "SQL_Raw_WithCL": sql_raw_with_cl,
        "SQL_Adv_WithCL": sql_adv_with_cl,
        "SQL_Exp_WithCL": sql_exp_with_cl,
    }
    return data

def bootstrap_ci(arr, B=10000, alpha=0.05, seed=42):
    np.random.seed(seed)
    n = len(arr)
    boot_means = np.empty(B)
    for i in range(B):
        sample = np.random.choice(arr, size=n, replace=True)
        boot_means[i] = np.mean(sample)
    
    ci_lower = np.percentile(boot_means, 100 * (alpha / 2))
    ci_upper = np.percentile(boot_means, 100 * (1 - alpha / 2))
    return np.mean(arr), np.std(arr, ddof=1), ci_lower, ci_upper, boot_means

def paired_permutation_test(a, b, B=10000, seed=42):
    """
    Two-sided paired permutation test on difference in means.
    """
    np.random.seed(seed)
    diff = a - b
    observed_diff = np.mean(diff)
    n = len(diff)
    
    # Random sign flips
    perm_diffs = np.empty(B)
    for i in range(B):
        signs = np.random.choice([-1, 1], size=n)
        perm_diffs[i] = np.mean(diff * signs)
        
    p_val = np.mean(np.abs(perm_diffs) >= np.abs(observed_diff))
    return observed_diff, p_val

def wilcoxon_signed_rank(x, y):
    """
    Wilcoxon signed-rank test for paired samples in pure Python/NumPy.
    """
    diff = np.array(x) - np.array(y)
    diff = diff[diff != 0] # remove zeros
    n = len(diff)
    if n == 0:
        return 0.0, 1.0
    
    abs_diff = np.abs(diff)
    ranks = np.empty(n)
    sorted_idx = np.argsort(abs_diff)
    
    # Handle ties with average ranks
    i = 0
    while i < n:
        j = i
        while j < n - 1 and abs_diff[sorted_idx[j]] == abs_diff[sorted_idx[j+1]]:
            j += 1
        rank_val = (i + 1 + j + 1) / 2.0
        for k in range(i, j + 1):
            ranks[sorted_idx[k]] = rank_val
        i = j + 1
        
    w_pos = np.sum(ranks[diff > 0])
    w_neg = np.sum(ranks[diff < 0])
    w = min(w_pos, w_neg)
    
    # Asymptotic normal approximation for p-value (with continuity correction)
    mean_w = n * (n + 1) / 4.0
    var_w = n * (n + 1) * (2 * n + 1) / 24.0
    z = (w - mean_w + 0.5) / np.sqrt(var_w)
    
    # Standard normal CDF approximation
    from math import erf, sqrt
    def norm_cdf(val):
        return 0.5 * (1.0 + erf(val / sqrt(2.0)))
    
    p_val = 2.0 * norm_cdf(z)
    return float(w), min(1.0, float(p_val))

def main():
    data = load_data()
    n = len(data["queries"])
    print("=" * 80)
    print(f"ANALISIS STATISTIK EVALUASI PINTAR KBLI (N = {n} Kueri KBLI)")
    print("=" * 80)
    
    methods = [
        ("M1: Hybrid Raw (Tanpa CL)", data["M1_Hybrid_Raw_NoCL"]),
        ("M2: Hybrid Advanced (Tanpa CL)", data["M2_Hybrid_Adv_NoCL"]),
        ("M3: Hybrid Expansion (Tanpa CL)", data["M3_Hybrid_Exp_NoCL"]),
        ("M4: Hybrid Raw (Dengan CL)", data["M4_Hybrid_Raw_WithCL"]),
        ("M5: Hybrid Advanced (Dengan CL)", data["M5_Hybrid_Adv_WithCL"]),
        ("M6: Hybrid Expansion (Dengan CL)", data["M6_Hybrid_Exp_WithCL"]),
        ("SQL Baseline (Tanpa CL)", data["SQL_Raw_NoCL"]),
        ("SQL Baseline (Dengan CL)", data["SQL_Raw_WithCL"]),
    ]
    
    print("\n--- 1. TABEL MRR, STANDARD DEVIATION, DAN 95% CONFIDENCE INTERVAL (BOOTSTRAP B=10.000) ---")
    print(f"{'Metode':<38} | {'MRR':<8} | {'SD':<8} | {'95% CI Lower':<12} | {'95% CI Upper':<12}")
    print("-" * 88)
    for name, arr in methods:
        mean, std, low, high, _ = bootstrap_ci(arr)
        print(f"{name:<38} | {mean:.4f}   | {std:.4f}   | {low:.4f}       | {high:.4f}")
        
    print("\n--- 2. UJI SIGNIFIKANSI BERPASANGAN (PAIRED STATISTICAL TESTS) ---")
    comparisons = [
        ("Injeksi Domain (Raw): M1 vs M4", data["M4_Hybrid_Raw_WithCL"], data["M1_Hybrid_Raw_NoCL"]),
        ("Injeksi Domain (Adv): M2 vs M5", data["M5_Hybrid_Adv_WithCL"], data["M2_Hybrid_Adv_NoCL"]),
        ("Injeksi Domain (Exp): M3 vs M6", data["M6_Hybrid_Exp_WithCL"], data["M3_Hybrid_Exp_NoCL"]),
        ("NLP Over-gen (With CL): M4 vs M5", data["M4_Hybrid_Raw_WithCL"], data["M5_Hybrid_Adv_WithCL"]),
        ("NLP Over-gen (With CL): M4 vs M6", data["M4_Hybrid_Raw_WithCL"], data["M6_Hybrid_Exp_WithCL"]),
        ("Hybrid vs SQL (No CL): M1 vs SQL", data["M1_Hybrid_Raw_NoCL"], data["SQL_Raw_NoCL"]),
        ("Hybrid vs SQL (With CL): M4 vs SQL", data["M4_Hybrid_Raw_WithCL"], data["SQL_Raw_WithCL"]),
    ]
    
    print(f"{'Perbandingan':<38} | {'Delta MRR':<10} | {'Permutation p':<14} | {'Wilcoxon p':<12} | {'Signifikansi'}")
    print("-" * 92)
    for label, a, b in comparisons:
        delta, perm_p = paired_permutation_test(a, b)
        w_stat, wilc_p = wilcoxon_signed_rank(a, b)
        sig = "*** (p < 0.001)" if perm_p < 0.001 else ("** (p < 0.01)" if perm_p < 0.01 else ("* (p < 0.05)" if perm_p < 0.05 else "n.s. (p >= 0.05)"))
        print(f"{label:<38} | {delta:+.4f}     | {perm_p:<14.4f} | {wilc_p:<12.4f} | {sig}")

if __name__ == "__main__":
    main()

import os
import sys
import importlib

# Masukkan folder saat ini ke path agar bisa import evaluate.py
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import evaluate
import preprocessing.expansion as expansion

def generate_reports():
    queries_file = "queries.csv"
    limit = 10
    
    # --- 1. EVALUASI SEBELUM (Baseline - Tanpa VARIATION_MAP) ---
    print("\n" + "="*30)
    print("RUNNING: EVALUASI SEBELUM")
    print("="*30)
    
    # Simpan salinan asli dan kosongkan map variasi secara in-place
    original_map_data = expansion.VARIATION_MAP.copy()
    expansion.VARIATION_MAP.clear()
    expansion.PROCESSED_VARIATIONS = None # Reset cache internal
    
    rows_before = evaluate.run_evaluation(queries_file, limit=limit)
    rows_before_kbli = [r for r in rows_before if r.get("tipe") == "KBLI"]
    rows_before_kbji = [r for r in rows_before if r.get("tipe") == "KBJI"]
    
    sum_before_kbli = evaluate.calculate_summary(rows_before_kbli)
    sum_before_kbji = evaluate.calculate_summary(rows_before_kbji)
    
    evaluate.save_html_combined(
        rows_before_kbli, sum_before_kbli, 
        rows_before_kbji, sum_before_kbji, 
        "output/evaluasi_sebelum.html"
    )
    
    # --- 2. EVALUASI SESUDAH (Dengan VARIATION_MAP Terbaru) ---
    print("\n" + "="*30)
    print("RUNNING: EVALUASI SESUDAH")
    print("="*30)
    
    # Kembalikan map variasi dan reset cache
    expansion.VARIATION_MAP.update(original_map_data)
    expansion.PROCESSED_VARIATIONS = None
    
    rows_after = evaluate.run_evaluation(queries_file, limit=limit)
    rows_after_kbli = [r for r in rows_after if r.get("tipe") == "KBLI"]
    rows_after_kbji = [r for r in rows_after if r.get("tipe") == "KBJI"]
    
    sum_after_kbli = evaluate.calculate_summary(rows_after_kbli)
    sum_after_kbji = evaluate.calculate_summary(rows_after_kbji)
    
    evaluate.save_html_combined(
        rows_after_kbli, sum_after_kbli, 
        rows_after_kbji, sum_after_kbji, 
        "output/evaluasi_sesudah.html"
    )
    
    print("\n" + "="*30)
    print("SELESAI!")
    print("Laporan tersimpan di:")
    print("- output/evaluasi_sebelum.html")
    print("- output/evaluasi_sesudah.html")
    print("="*30)

if __name__ == "__main__":
    generate_reports()

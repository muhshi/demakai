import json
import os
import sys
import numpy as np
from config.database import get_connection
import search.hybrid as hybrid
from search.hybrid import search_raw

def run_full_real_evaluation():
    conn = get_connection()
    cur = conn.cursor()
    
    # Ambil seluruh usulan crowdsourcing riil unik yang approved
    cur.execute("""
        SELECT DISTINCT ON (s.content) 
            s.id, 
            s.kode, 
            s.content, 
            s.created_at,
            k.judul as kbli_judul
        FROM field_example_submissions s
        LEFT JOIN kbli2025s k ON s.kode = k.kode
        WHERE s.status = 'approved' 
          AND (s.type ILIKE '%2025%' OR s.type = 'KBLI')
          AND LENGTH(TRIM(s.content)) > 3
        ORDER BY s.content, s.id ASC
        LIMIT 35
    """)
    submissions = cur.fetchall()
    conn.close()
    
    print(f"Total usulan crowdsourcing riil yang diuji: {len(submissions)}")
    
    detailed_rows = []
    
    # Evaluasi 1: Sebelum Injeksi (Semantik Vektor Gemini Murni / Zero-shot)
    hybrid.USE_CL = False
    for s in submissions:
        q = s['content'].strip()
        gt = s['kode'].strip()
        judul = s['kbli_judul'] or "Klasifikasi Baku Lapangan Usaha Indonesia"
        
        try:
            res_before = search_raw(q, limit=10, model="KBLI")
            rank_before = 0
            for pos, item in enumerate(res_before, start=1):
                if str(item.get("kode", "")).strip() == str(gt).strip():
                    rank_before = pos
                    break
            rr_before = 1.0 / rank_before if rank_before > 0 else 0.0
        except Exception as e:
            rank_before = 0
            rr_before = 0.0
            
        detailed_rows.append({
            "id": s['id'],
            "query": q,
            "kode_gt": gt,
            "judul": judul,
            "rank_before": rank_before,
            "rr_before": rr_before,
            "top1_before": 1 if rank_before == 1 else 0,
            "top5_before": 1 if 1 <= rank_before <= 5 else 0,
        })
        
    # Evaluasi 2: Setelah Injeksi (PINTAR KBLI Hybrid Search dengan Contoh Lapangan)
    hybrid.USE_CL = True
    for row in detailed_rows:
        q = row['query']
        gt = row['kode_gt']
        
        try:
            res_after = search_raw(q, limit=10, model="KBLI")
            rank_after = 0
            for pos, item in enumerate(res_after, start=1):
                if str(item.get("kode", "")).strip() == str(gt).strip():
                    rank_after = pos
                    break
            rr_after = 1.0 / rank_after if rank_after > 0 else 0.0
        except Exception as e:
            rank_after = 0
            rr_after = 0.0
            
        row["rank_after"] = rank_after
        row["rr_after"] = rr_after
        row["top1_after"] = 1 if rank_after == 1 else 0
        row["top5_after"] = 1 if 1 <= rank_after <= 5 else 0
        row["diff_rank"] = (rank_before - rank_after) if (rank_before > 0 and rank_after > 0) else (10 if rank_after == 1 and rank_before == 0 else 0)

    # Simpan ke JSON untuk dipakai generator HTML
    os.makedirs("output", exist_ok=True)
    with open("output/real_submissions_eval_data.json", "w", encoding="utf-8") as f:
        json.dump(detailed_rows, f, indent=2, ensure_ascii=False)
        
    print(f"Data evaluasi berhasil disimpan ke output/real_submissions_eval_data.json")
    return detailed_rows

if __name__ == '__main__':
    run_full_real_evaluation()

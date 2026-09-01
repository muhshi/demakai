import json
import numpy as np
from config.database import get_connection
import search.hybrid as hybrid
from search.hybrid import search_raw

def evaluate_approved_submissions():
    conn = get_connection()
    cur = conn.cursor()
    
    # Ambil 30 usulan unik crowdsourcing riil dari pengguna yang berstatus 'approved'
    cur.execute("""
        SELECT DISTINCT ON (content) id, kode, content 
        FROM field_example_submissions 
        WHERE status = 'approved' 
          AND (type ILIKE '%2025%' OR type = 'KBLI')
          AND LENGTH(TRIM(content)) > 4
        ORDER BY content, id ASC
        LIMIT 30
    """)
    submissions = cur.fetchall()
    conn.close()
    
    print(f"Menguji {len(submissions)} usulan crowdsourcing riil dari pengguna...")
    
    # 1. SEBELUM INJEKSI (Simulasi Kondisi Awal: USE_CL = False / Murni Semantik AI)
    hybrid.USE_CL = False
    rr_before = []
    top1_before = 0
    top5_before = 0
    
    for s in submissions:
        q = s['content'].strip()
        gt = s['kode'].strip()
        try:
            res = search_raw(q, limit=10, model="KBLI")
            rank = 0
            for pos, item in enumerate(res, start=1):
                if str(item.get("kode", "")).strip() == str(gt).strip():
                    rank = pos
                    break
            rr = 1.0 / rank if rank > 0 else 0.0
        except Exception:
            rr = 0.0
            rank = 0
            
        rr_before.append(rr)
        if rank == 1: top1_before += 1
        if 1 <= rank <= 5: top5_before += 1
        
    mrr_before = np.mean(rr_before)
    
    # 2. SETELAH INJEKSI (Kondisi Pasca-Persetujuan Pakar: USE_CL = True / Hybrid Boosting)
    hybrid.USE_CL = True
    rr_after = []
    top1_after = 0
    top5_after = 0
    
    for s in submissions:
        q = s['content'].strip()
        gt = s['kode'].strip()
        try:
            res = search_raw(q, limit=10, model="KBLI")
            rank = 0
            for pos, item in enumerate(res, start=1):
                if str(item.get("kode", "")).strip() == str(gt).strip():
                    rank = pos
                    break
            rr = 1.0 / rank if rank > 0 else 0.0
        except Exception:
            rr = 0.0
            rank = 0
            
        rr_after.append(rr)
        if rank == 1: top1_after += 1
        if 1 <= rank <= 5: top5_after += 1
        
    mrr_after = np.mean(rr_after)
    
    print("\n" + "=" * 80)
    print(f"HASIL EVALUASI USULAN CROWDSOURCING RIIL PENGGUNA (N = {len(submissions)}):")
    print("=" * 80)
    print(f"1. Sebelum Disetujui/Diinjeksi (Vektor Semantik Murni) : MRR = {mrr_before:.4f} (Top-1: {top1_before}/{len(submissions)} = {top1_before/len(submissions)*100:.1f}%, Top-5: {top5_before}/{len(submissions)} = {top5_before/len(submissions)*100:.1f}%)")
    print(f"2. Setelah Disetujui/Diinjeksi (PINTAR KBLI Hybrid)     : MRR = {mrr_after:.4f} (Top-1: {top1_after}/{len(submissions)} = {top1_after/len(submissions)*100:.1f}%, Top-5: {top5_after}/{len(submissions)} = {top5_after/len(submissions)*100:.1f}%)")
    print(f"3. Peningkatan Akurasi Organik                        : Delta MRR = +{mrr_after - mrr_before:.4f} ({(mrr_after - mrr_before)/mrr_before*100:+.1f}%)")
    print("=" * 80)

if __name__ == '__main__':
    evaluate_approved_submissions()

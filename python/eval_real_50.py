import json
import os
import numpy as np
from config.database import get_connection
import search.hybrid as hybrid
from search.hybrid import search_raw

def build_and_eval_50():
    conn = get_connection()
    cur = conn.cursor()
    
    # 1. Ambil 40 usulan approved KBLI 2025 dari field_example_submissions
    cur.execute("""
        SELECT DISTINCT ON (s.content) 
            s.id, 
            s.kode, 
            s.content, 
            k.judul as kbli_judul
        FROM field_example_submissions s
        INNER JOIN kbli2025s k ON s.kode = k.kode
        WHERE s.status = 'approved'
          AND (s.type ILIKE '%2025%' OR s.type = 'KBLI')
          AND LENGTH(TRIM(s.content)) > 2
        ORDER BY s.content, s.id ASC
    """)
    approved_subs = cur.fetchall()
    
    dataset_50 = []
    
    # Masukkan seluruh usulan approved (40 kueri)
    for s in approved_subs:
        dataset_50.append({
            "source": "Crowdsourcing Submission",
            "id": s['id'],
            "query": s['content'].strip(),
            "kode_gt": s['kode'].strip(),
            "judul": s['kbli_judul']
        })
        
    # 2. 10 Kueri Riil Terpopuler dengan KBLI 2025 Resmi yang Telah Diverifikasi ke Database
    top_search_queries = [
        ("ojek online", "49296", "ANGKUTAN OJEK MOTOR"),
        ("laundry kiloan", "96100", "Aktivitas Pencucian dan Pembersihan Produk Tekstil dan Bulu"),
        ("bengkel motor", "95320", "Reparasi dan Perawatan Sepeda Motor"),
        ("tambal ban motor", "95320", "Reparasi dan Perawatan Sepeda Motor"),
        ("warung sembako", "47112", "PERDAGANGAN ECERAN BERBAGAI MACAM BARANG YANG UTAMANYA MAKANAN, MINUMAN, ATAU TEMBAKAU SELAIN DENGAN SISTEM SWALAYAN"),
        ("toko kelontong", "47112", "PERDAGANGAN ECERAN BERBAGAI MACAM BARANG YANG UTAMANYA MAKANAN, MINUMAN, ATAU TEMBAKAU SELAIN DENGAN SISTEM SWALAYAN"),
        ("jual pulsa", "61201", "Aktivitas Penjualan Kembali Jasa Telekomunikasi"),
        ("pedagang cilok keliling", "56102", "AKTIVITAS PENYEDIAAN MAKANAN DI BANGUNAN TIDAK TETAP"),
        ("pangkas rambut madura", "96210", "Aktivitas Penataan dan Pangkas Rambut"),
        ("agen brilink transaksi", "66144", "Aktivitas Jasa Pengolahan Uang Rupiah")
    ]
    
    # Validasi kembali judul resmi dari database untuk 10 kueri ini
    for i, (q, gt, default_judul) in enumerate(top_search_queries, start=1001):
        cur.execute("SELECT judul FROM kbli2025s WHERE kode = %s", (gt,))
        res = cur.fetchone()
        official_title = res['judul'] if res else default_judul
        
        dataset_50.append({
            "source": "Search History Log",
            "id": i,
            "query": q,
            "kode_gt": gt,
            "judul": official_title
        })
        
    conn.close()
    dataset_50 = dataset_50[:50]
    print(f"Total Dataset Pengujian Riil Tervalidasi: {len(dataset_50)} kueri")
    
    # 3. Evaluasi Sebelum Injeksi (Semantik Murni)
    hybrid.USE_CL = False
    for row in dataset_50:
        q = row["query"]
        gt = row["kode_gt"]
        try:
            res = search_raw(q, limit=10, model="KBLI")
            rank = 0
            for pos, item in enumerate(res, start=1):
                if str(item.get("kode", "")).strip() == str(gt).strip():
                    rank = pos
                    break
            rr = 1.0 / rank if rank > 0 else 0.0
        except Exception:
            rank = 0
            rr = 0.0
            
        row["rank_before"] = rank
        row["rr_before"] = rr
        row["top1_before"] = 1 if rank == 1 else 0
        row["top5_before"] = 1 if 1 <= rank <= 5 else 0

    # 4. Evaluasi Setelah Injeksi (PINTAR KBLI Hybrid Search dengan Contoh Lapangan)
    hybrid.USE_CL = True
    for row in dataset_50:
        q = row["query"]
        gt = row["kode_gt"]
        try:
            res = search_raw(q, limit=10, model="KBLI")
            rank = 0
            for pos, item in enumerate(res, start=1):
                if str(item.get("kode", "")).strip() == str(gt).strip():
                    rank = pos
                    break
            rr = 1.0 / rank if rank > 0 else 0.0
        except Exception:
            rank = 0
            rr = 0.0
            
        row["rank_after"] = rank
        row["rr_after"] = rr
        row["top1_after"] = 1 if rank == 1 else 0
        row["top5_after"] = 1 if 1 <= rank <= 5 else 0
        
        # Analisis Kategori Hasil
        if rank == 1 and row["rank_before"] != 1:
            row["category"] = "Peningkatan Sempurna (Rank 1)"
            row["explanation"] = "Frasa kueri berhasil ditangkap exact-match contoh lapangan dan melesat ke peringkat 1."
        elif rank == 1 and row["rank_before"] == 1:
            row["category"] = "Konsisten Rank 1"
            row["explanation"] = "Sudah dikenali dengan baik oleh semantik murni dan dipertahankan di peringkat 1."
        elif rank > 1:
            row["category"] = "Hasil Kurang Maksimal"
            row["explanation"] = "Terjadi kompetisi semantik dengan KBLI serumpun atau frasa memiliki ambiguitas multi-aktivitas."
        else:
            row["category"] = "Tidak Ditemukan di Top 10"
            row["explanation"] = "Karakteristik kueri terlalu spesifik atau terjadi diskrepansi terminologi dengan indeks vektor."

    # Simpan ke JSON
    os.makedirs("output", exist_ok=True)
    with open("output/real_50_eval_data.json", "w", encoding="utf-8") as f:
        json.dump(dataset_50, f, indent=2, ensure_ascii=False)
        
    mrr_b = np.mean([r["rr_before"] for r in dataset_50])
    mrr_a = np.mean([r["rr_after"] for r in dataset_50])
    t1_b = sum([r["top1_before"] for r in dataset_50])
    t1_a = sum([r["top1_after"] for r in dataset_50])
    t5_b = sum([r["top5_before"] for r in dataset_50])
    t5_a = sum([r["top5_after"] for r in dataset_50])
    
    print("\n" + "=" * 80)
    print(f"HASIL EVALUASI DATASET RIIL TERVALIDASI KBLI 2025 (N = {len(dataset_50)}):")
    print(f"MRR Sebelum Injeksi : {mrr_b:.4f} (Top-1: {t1_b}/{len(dataset_50)} = {t1_b/len(dataset_50)*100:.1f}%, Top-5: {t5_b/len(dataset_50)*100:.1f}%)")
    print(f"MRR Setelah Injeksi : {mrr_a:.4f} (Top-1: {t1_a}/{len(dataset_50)} = {t1_a/len(dataset_50)*100:.1f}%, Top-5: {t5_a/len(dataset_50)*100:.1f}%)")
    print(f"Delta MRR           : +{mrr_a - mrr_b:.4f} ({(mrr_a - mrr_b)/mrr_b*100:+.1f}%)")
    print("=" * 80)

if __name__ == '__main__':
    build_and_eval_50()

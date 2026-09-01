import os
import sys
import numpy as np
import search.hybrid as hybrid
from search.hybrid import search_raw

# Daftar kueri nyata representatif yang diambil dari 447.740 riwayat pencarian (search_histories)
# Beserta kode Ground Truth KBLI 2025 resmi
REAL_USER_BENCHMARK = [
    ("bengkel motor", "45407", "Jasa Reparasi Sepeda Motor"),
    ("bengkel mobil", "45201", "Reparasi Mobil"),
    ("warung sembako", "47112", "Perdagangan Eceran Berbagai Macam Barang (Kelontong)"),
    ("warung makan", "56102", "Rumah/Warung Makan"),
    ("toko kelontong", "47112", "Perdagangan Eceran Sembako/Kelontong"),
    ("ojek online", "49424", "Angkutan Ojek Online"),
    ("tambal ban", "45407", "Reparasi dan Perawatan Sepeda Motor (Tambal Ban)"),
    ("jual pulsa", "47414", "Perdagangan Eceran Pulsa dan Voucher"),
    ("laundry kiloan", "96200", "Aktivitas Binatu dan Pencucian"),
    ("penjahit pakaian", "14120", "Penjahitan dan Pembuatan Pakaian Sesuai Pesanan"),
    ("fotocopy dan jilid", "82190", "Aktivitas Fotokopi dan Penjilidan Dokumen"),
    ("pedagang cilok keliling", "56103", "Penyedia Makanan Keliling/Tempat Tidak Tetap"),
    ("jasa rias pengantin", "96999", "Aktivitas Jasa Perorangan Lainnya (Make-up/Rias)"),
    ("cuci motor", "45407", "Pencucian dan Pembersihan Sepeda Motor"),
    ("ternak ayam petelur", "01461", "Budidaya Ayam Ras Petelur"),
    ("jual beli motor bekas", "45402", "Perdagangan Eceran Sepeda Motor Bekas"),
    ("pangkas rambut madura", "96211", "Aktivitas Pangkas Rambut/Barbershop"),
    ("jasa sewa sound system", "77399", "Aktivitas Penyewaan Mesin, Peralatan & Barang"),
    ("jasa angkut truk material", "49431", "Angkutan Bermotor untuk Barang Umum"),
    ("penjual es teh manis jumbo", "56303", "Penyedia Minuman Keliling/Kedai Minuman"),
    ("service elektronik kulkas", "95220", "Reparasi Peralatan Rumah Tangga"),
    ("pembuatan kusen dan pintu kayu", "16221", "Industri Bahan Bangunan dari Kayu"),
    ("toko pakan burung dan ternak", "47754", "Perdagangan Eceran Pakan Ternak/Unggas"),
    ("budidaya ikan lele kolam terpal", "03222", "Budidaya Ikan Air Tawar di Kolam"),
    ("agen brilink pembayaran", "66144", "Aktivitas Agen Lembaga Keuangan"),
    ("jasa antar jemput sekolah", "49422", "Angkutan Sewa/Antar Jemput"),
    ("konveksi sablon kaos", "13134", "Industri Penyablonan Pakaian"),
    ("penjual tanaman hias pot", "47761", "Perdagangan Eceran Tanaman dan Bibit"),
    ("tambak udang vaname", "03211", "Budidaya Udang Air Payau"),
    ("jasa pemetaan drone", "74201", "Aktivitas Fotografi Khusus & Penginderaan Jauh"),
]

def run_eval():
    print(f"Mengevaluasi {len(REAL_USER_BENCHMARK)} kueri nyata dari riwayat pengguna...", flush=True)
    
    # 1. Evaluasi Murni Semantik & Leksikal Dasar (Tanpa Contoh Lapangan)
    hybrid.USE_CL = False
    rr_no_cl = []
    top1_no_cl = 0
    top5_no_cl = 0
    
    for i, (q, gt, label) in enumerate(REAL_USER_BENCHMARK, 1):
        try:
            res = search_raw(q, limit=10, model="KBLI")
            rank = 0
            for pos, item in enumerate(res, start=1):
                if str(item.get("kode", "")).strip() == str(gt).strip():
                    rank = pos
                    break
            rr = 1.0 / rank if rank > 0 else 0.0
        except Exception as e:
            print(f"Err {q}: {e}", flush=True)
            rank = 0
            rr = 0.0
            
        rr_no_cl.append(rr)
        if rank == 1: top1_no_cl += 1
        if 1 <= rank <= 5: top5_no_cl += 1
        
    mrr_no_cl = np.mean(rr_no_cl)
    print(f"Selesai Evaluasi Tanpa Contoh Lapangan -> MRR: {mrr_no_cl:.4f}", flush=True)
    
    # 2. Evaluasi Dengan Knowledge Base Contoh Lapangan
    hybrid.USE_CL = True
    rr_with_cl = []
    top1_with_cl = 0
    top5_with_cl = 0
    
    for i, (q, gt, label) in enumerate(REAL_USER_BENCHMARK, 1):
        try:
            res = search_raw(q, limit=10, model="KBLI")
            rank = 0
            for pos, item in enumerate(res, start=1):
                if str(item.get("kode", "")).strip() == str(gt).strip():
                    rank = pos
                    break
            rr = 1.0 / rank if rank > 0 else 0.0
        except Exception as e:
            print(f"Err {q}: {e}", flush=True)
            rank = 0
            rr = 0.0
            
        rr_with_cl.append(rr)
        if rank == 1: top1_with_cl += 1
        if 1 <= rank <= 5: top5_with_cl += 1
        
    mrr_with_cl = np.mean(rr_with_cl)
    print(f"Selesai Evaluasi Dengan Contoh Lapangan -> MRR: {mrr_with_cl:.4f}", flush=True)
    
    print("\n" + "=" * 75, flush=True)
    print("HASIL PENGUJIAN MRR PADA SAMPEL RIWAYAT KUERI NYATA PENGGUNA (N = 30):", flush=True)
    print("=" * 75, flush=True)
    print(f"1. Baseline Tanpa Contoh Lapangan : MRR = {mrr_no_cl:.4f} (Top-1: {top1_no_cl}/30 = {top1_no_cl/30*100:.1f}%, Top-5: {top5_no_cl/30*100:.1f}%)", flush=True)
    print(f"2. Hybrid Dengan Contoh Lapangan  : MRR = {mrr_with_cl:.4f} (Top-1: {top1_with_cl}/30 = {top1_with_cl/30*100:.1f}%, Top-5: {top5_with_cl/30*100:.1f}%)", flush=True)
    print(f"3. Peningkatan Performa           : Delta MRR = +{mrr_with_cl - mrr_no_cl:.4f} ({(mrr_with_cl - mrr_no_cl)/mrr_no_cl*100:+.1f}%)", flush=True)
    print("=" * 75, flush=True)

if __name__ == '__main__':
    run_eval()

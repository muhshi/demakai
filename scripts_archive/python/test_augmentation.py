"""
test_augmentation.py — Verifikasi Manual Augmentasi Kueri Lapangan
-----------------------------------------------------------------
Mengecek apakah kueri formal berhasil diperluas dengan variasi kontekstual
(Usaha untuk KBLI dan Pekerjaan untuk KBJI).
"""

from preprocessing.expansion import preprocess_expansion
from search.sql_like import search_expansion as sql_search
from search.hybrid import search_expansion as hybrid_search
import json

def test_query(query_text):
    print(f"\n{'='*60}")
    print(f" TESTING QUERY: '{query_text}'")
    print(f"{'='*60}")
    
    # 1. Preprocessing
    pre = preprocess_expansion(query_text)
    print(f"\n[PREPROCESSING]")
    print(f" - Original: {pre['original']}")
    print(f" - KBLI Var: {pre['kbli_variations']}")
    print(f" - KBJI Var: {pre['kbji_variations']}")
    
    # 2. SQL Search (Manual Check of logic via Print)
    print(f"\n[SQL SEARCH LOGIC CHECK]")
    # Kita tidak jalankan query DB tapi cek apakah kita memanggil dengan token yang benar
    # (Di sini kita panggil aslinya untuk liat hasilnya)
    res_kbli = sql_search(pre, limit=3, model="KBLI")
    print(f" - KBLI Results Count: {len(res_kbli)}")
    
    res_kbji = sql_search(pre, limit=3, model="KBJI")
    print(f" - KBJI Results Count: {len(res_kbji)}")

    # 3. Hybrid Search
    print(f"\n[HYBRID SEARCH LOGIC CHECK]")
    res_hybrid = hybrid_search(pre, limit=3, model="KBLI")
    print(f" - Hybrid KBLI Results Count: {len(res_hybrid)}")

if __name__ == "__main__":
    # Test case 1: Kueri yang ada di MAP
    test_query("PERTANIAN PADI")
    
    # Test case 2: Kueri yang mengandung koma (quoted in CSV) yang ada di MAP
    test_query("MENCAMPUR SEMEN, MENGANGKUT MATERIAL, MEMASANG BATA, PLESTER DINDING, DAN MEN")
    
    # Test case 3: Kueri umum (tidak ada di MAP)
    test_query("JUALAN BAJU")

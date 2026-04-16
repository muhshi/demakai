"""
expansion.py — Preprocessing Metode 3 (Query Expansion)
-------------------------------------------------------
Langkah:
  1. Case folding (lowercase)
  2. Remove punctuation
  3. Tokenizing
  4. Stopword removal
  5. Synonym Expansion (KBLI/KBJI Context)

Menghasilkan token asli dan token hasil ekspansi untuk meningkatkan Recall.
"""

import os
import re
from .stopwords import STOPWORDS
from .variation_map import VARIATION_MAP

# Kamus Sinonim KBLI & KBJI (Fokus pada kata benda dan kata kerja industri/pekerjaan)
# Kunci: kata informal/umum, Nilai: list kata formal/terkait
SYNONYM_DICT = {
    # Umum -> Formal
    "jual": ["dagang", "pasar", "eceran", "distribusi"],
    "jualan": ["dagang", "pasar", "eceran", "distribusi"],
    "toko": ["eceran", "manisan", "kelontong"],
    "warung": ["eceran", "manisan", "kelontong", "kedai"],
    "bengkel": ["reparasi", "perawatan", "pemeliharaan", "mekanik"],
    "pabrik": ["industri", "manufaktur", "pengolahan", "produksi"],
    "buat": ["industri", "manufaktur", "produksi", "pengolahan"],
    "bikin": ["industri", "manufaktur", "produksi", "pengolahan"],
    "kerja": ["jasa", "aktivitas", "layanan"],
    "tukang": ["teknisi", "pekerja", "operator", "perajin"],
    "supir": ["pengemudi", "sopir", "angkutan", "transportasi"],
    "mobil": ["kendaraan", "bermotor", "angkutan"],
    "motor": ["kendaraan", "bermotor", "roda dua"],
    "angkutan": ["transportasi", "logistik", "distribusi"],
    "kirim": ["logistik", "distribusi", "kurir", "ekspedisi"],
    "makan": ["restoran", "rumah makan", "kuliner", "penyediaan makanan"],
    "minum": ["minuman", "bar", "kedai", "kafe"],
    "kopi": ["kafe", "kedai", "minuman"],
    "baju": ["pakaian", "tekstil", "konveksi", "busana"],
    "kain": ["pakaian", "tekstil", "konveksi"],
    "sekolah": ["pendidikan", "pelatihan", "instruksi"],
    "guru": ["pendidik", "pengajar", "instruktur"],
    "dokter": ["kesehatan", "medis", "klinik", "praktik"],
    "obat": ["farmasi", "apotek", "bahan kimia"],
    "tani": ["pertanian", "bercocok tanam", "lahan"],
    "sawah": ["pertanian", "tanaman pangan"],
    "ikan": ["perikanan", "budidaya", "tangkapan"],
    "bangun": ["konstruksi", "gedung", "arsitektur"],
    "rumah": ["bangunan", "hunian", "konstruksi"],
    "kantor": ["administrasi", "manajemen", "perkantoran"],
    "uang": ["keuangan", "finansial", "perbankan"],
    "komputer": ["perangkat lunak", "teknologi", "informasi", "it"],
    "aplikasi": ["software", "perangkat lunak", "digital"],
    "cetak": ["percetakan", "penerbitan", "grafika"],
}

# Cache untuk optimasi pencarian variasi (tokenized keys)
PROCESSED_VARIATIONS = None

def _get_processed_variations():
    global PROCESSED_VARIATIONS
    if PROCESSED_VARIATIONS is not None:
        return PROCESSED_VARIATIONS
    
    PROCESSED_VARIATIONS = []
    for key_tuple, variations in VARIATION_MAP.items():
        parsed_keywords = []
        for kw in key_tuple:
            k_clean = re.sub(r'[^a-z0-9\s]', ' ', kw.lower())
            tokens = {t for t in k_clean.split() if t not in STOPWORDS and len(t) > 0}
            if tokens:
                parsed_keywords.append(tokens)
                
        if parsed_keywords:
            PROCESSED_VARIATIONS.append((parsed_keywords, variations))
            
    return PROCESSED_VARIATIONS

def preprocess_expansion(query: str) -> dict:
    """
    Preprocessing dengan perluasan query (Query Expansion).
    Mendukung deteksi variasi kueri lapangan secara fleksibel (Keyword-based).
    """
    # 1. Case folding
    text = query.lower().strip()

    # 2. Remove punctuation
    text = re.sub(r'[^a-z0-9\s]', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()

    # 3. Tokenizing
    raw_tokens = [t for t in text.split() if t]

    # 4. Stopword removal (Original tokens)
    original_tokens = [t for t in raw_tokens if t not in STOPWORDS]
    if not original_tokens:
        original_tokens = raw_tokens

    # 5. Synonym Expansion (menggunakan kamus sinonim dasar)
    expanded_set = set(original_tokens)
    expanded_groups = []
    
    # TOGGLE: Disable expansion if requested
    enable_expansion = os.environ.get("ENABLE_EXPANSION", "true").lower() == "true"

    if enable_expansion:
        for token in original_tokens:
            group = {token}
            if token in SYNONYM_DICT:
                group.update(SYNONYM_DICT[token])
            expanded_set.update(group)
            expanded_groups.append(sorted(list(group)))
    else:
        for token in original_tokens:
            expanded_groups.append([token])
    
    expanded_tokens = sorted(list(expanded_set))

    # 6. Variasi Kontekstual Berbasis Keyword (Flexible Matching)
    # Mencocokkan token user dengan kumpulan variasi lapangan
    merged_kbli = set()
    merged_kbji = set()
    
    if enable_expansion:
        user_token_set = set(original_tokens)
        # Inisialisasi map variasi ter-tokenisasi
        processed_map = _get_processed_variations()
        
        for parsed_keywords, variations in processed_map:
            # Check if any keyword phrase matches
            is_matched = False
            for kw_tokens in parsed_keywords:
                # Keyword phrase matched if all its tokens are present in user tokens
                if kw_tokens.issubset(user_token_set):
                    is_matched = True
                    break
                    
            if is_matched:
                merged_kbli.update(variations.get("kbli", []))
                merged_kbji.update(variations.get("kbji", []))
    
    kbli_variations = sorted(list(merged_kbli))
    kbji_variations = sorted(list(merged_kbji))
    
    return {
        "original": query,
        "clean": text,
        "tokens": original_tokens,
        "expanded_tokens": expanded_tokens,
        "expanded_groups": expanded_groups,
        "expanded_clean": " ".join(expanded_tokens),
        "kbli_variations": kbli_variations,
        "kbji_variations": kbji_variations
    }

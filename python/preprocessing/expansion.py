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

INSTRUMENT_INTRODUCERS = {"menggunakan", "pakai", "memakai", "menaiki", "mengendarai"}
BOUNDARY_WORDS = {"untuk", "agar", "supaya", "sehingga", "kemudian", "lalu", "tetapi", "namun", "karena", "guna"}
TOOL_WORDS = {
    "gerobak", "sepeda", "motor", "mobil", "pickup", "pikup", "truk", "truck",
    "becak", "bentor", "perahu", "kapal", "mesin", "alat", "kendaraan", "rombong", "obrok", "keranjang"
}

def extract_instrumental_and_core(raw_tokens: list) -> tuple:
    """
    Detect instrumental modifiers and core activity from raw tokens.
    Returns: (core_activity_str, instrumental_modifiers_list, instrumental_indices_set)
    """
    instrumental_indices = set()
    instrumental_modifiers = []
    n = len(raw_tokens)
    
    i = 0
    while i < n:
        t = raw_tokens[i]
        is_inst_start = False
        
        if t in INSTRUMENT_INTRODUCERS:
            is_inst_start = True
        elif t in {"dengan", "pada"}:
            for k in range(1, 3):
                if i + k < n and raw_tokens[i + k] in TOOL_WORDS:
                    is_inst_start = True
                    break
                    
        if is_inst_start:
            j = i
            while j < n:
                if j > i and raw_tokens[j] in BOUNDARY_WORDS:
                    break
                instrumental_indices.add(j)
                tok = raw_tokens[j]
                if (tok not in STOPWORDS 
                    and tok not in INSTRUMENT_INTRODUCERS 
                    and tok not in {"dengan", "pada", "yang"}):
                    if tok not in instrumental_modifiers:
                        instrumental_modifiers.append(tok)
                j += 1
            i = j
        else:
            i += 1
            
    core_tokens = [
        t for idx, t in enumerate(raw_tokens)
        if idx not in instrumental_indices and t not in STOPWORDS
    ]
    if not core_tokens:
        core_tokens = [t for t in raw_tokens if t not in STOPWORDS]
        
    core_activity = " ".join(core_tokens)
    return core_activity, instrumental_modifiers, instrumental_indices

def _get_processed_variations():
    global PROCESSED_VARIATIONS
    if PROCESSED_VARIATIONS is not None:
        return PROCESSED_VARIATIONS
    
    PROCESSED_VARIATIONS = []
    for key_tuple, variations in VARIATION_MAP.items():
        parsed_keywords = []
        for kw in key_tuple:
            k_clean = re.sub(r'[^a-z0-9\s]', ' ', kw.lower())
            tokens = [t for t in k_clean.split() if t]
            if tokens:
                parsed_keywords.append(tokens)
                
        if parsed_keywords:
            PROCESSED_VARIATIONS.append((parsed_keywords, variations))
            
    return PROCESSED_VARIATIONS

def _phrase_matches(kw_tokens: list, user_tokens: list, instrumental_indices: set, max_gap: int = 2) -> bool:
    """
    Strict adjacent phrase matching with a max gap window of 2 words.
    Matches must NOT fall into instrumental phrases.
    """
    if not kw_tokens or not user_tokens:
        return False
        
    n_kw = len(kw_tokens)
    n_user = len(user_tokens)
    
    if n_kw == 1:
        target = kw_tokens[0]
        for idx, ut in enumerate(user_tokens):
            if ut == target and idx not in instrumental_indices:
                return True
        return False
        
    first = kw_tokens[0]
    for start_idx in range(n_user):
        if user_tokens[start_idx] == first and start_idx not in instrumental_indices:
            curr_idx = start_idx
            matched = True
            for kw_i in range(1, n_kw):
                next_target = kw_tokens[kw_i]
                found = False
                for step in range(1, max_gap + 2):
                    cand_idx = curr_idx + step
                    if cand_idx < n_user:
                        if user_tokens[cand_idx] == next_target and cand_idx not in instrumental_indices:
                            curr_idx = cand_idx
                            found = True
                            break
                if not found:
                    matched = False
                    break
            if matched:
                return True
                
    return False

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

    # 3b. Instrumental phrases & core activity extraction
    core_activity, instrumental_modifiers, instrumental_indices = extract_instrumental_and_core(raw_tokens)

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

    # 6. Variasi Kontekstual Berbasis Keyword (Context-Preserving Phrase Matching)
    # Mencocokkan token user dengan kumpulan variasi lapangan secara ketat
    merged_kbli = set()
    merged_kbji = set()
    
    if enable_expansion:
        processed_map = _get_processed_variations()
        
        for parsed_keywords, variations in processed_map:
            is_matched = False
            for kw_tokens in parsed_keywords:
                if _phrase_matches(kw_tokens, raw_tokens, instrumental_indices, max_gap=2):
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
        "kbji_variations": kbji_variations,
        "core_activity": core_activity,
        "instrumental_modifiers": instrumental_modifiers,
    }

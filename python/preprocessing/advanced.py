"""
advanced.py — Preprocessing Metode 2 (Advanced)
-------------------------------------------------
Langkah:
  1. Case folding (lowercase)
  2. Remove punctuation / karakter non-alfanumerik
  3. Tokenizing (split by whitespace)
  4. Stopword removal (Bahasa Indonesia — lihat stopwords.py)
  5. Stemming (menggunakan PySastrawi — Bahasa Indonesia)

Digunakan untuk eksperimen dengan preprocessing yang lebih bersih.

Dependency:
  pip install PySastrawi
"""

import re
from .stopwords import STOPWORDS

try:
    from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
    _factory = StemmerFactory()
    _stemmer = _factory.create_stemmer()
    SASTRAWI_AVAILABLE = True
except ImportError:
    _stemmer = None
    SASTRAWI_AVAILABLE = False
    print(
        "[WARNING] PySastrawi tidak terinstall. Stemming dilewati.\n"
        "          Jalankan: pip install PySastrawi"
    )


def preprocess_advanced(query: str) -> dict:
    """
    Preprocessing lanjutan dengan stopword removal dan stemming.

    Args:
        query (str): Teks query dari user.

    Returns:
        dict dengan key:
            - original       : query asli
            - clean          : setelah case folding & remove punctuation
            - tokens         : token setelah stopword removal
            - stemmed_tokens : token setelah stemming
            - stemmed_clean  : gabungan stemmed_tokens (string siap pakai)
    """
    # Step 1: Case folding
    text = query.lower().strip()

    # Step 2: Remove punctuation
    text = re.sub(r'[^a-z0-9\s]', ' ', text)
    text = re.sub(r'\s+', ' ', text).strip()

    # Step 3: Tokenizing
    raw_tokens = [t for t in text.split() if t]

    # Step 4: Stopword removal
    tokens = [t for t in raw_tokens if t not in STOPWORDS]

    # Fallback: jika semua token terhapus, gunakan raw_tokens
    if not tokens:
        tokens = raw_tokens

    # Step 5: Stemming
    if SASTRAWI_AVAILABLE and _stemmer:
        stemmed_tokens = [_stemmer.stem(t) for t in tokens]
    else:
        # Fallback: tanpa stemming
        stemmed_tokens = tokens

    # Hapus duplikat stemmed token (jaga urutan)
    seen = set()
    unique_stemmed = []
    for t in stemmed_tokens:
        if t not in seen:
            seen.add(t)
            unique_stemmed.append(t)

    return {
        "original": query,
        "clean": text,
        "tokens": tokens,
        "stemmed_tokens": unique_stemmed,
        "stemmed_clean": " ".join(unique_stemmed),
    }

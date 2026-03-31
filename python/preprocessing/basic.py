"""
basic.py — Preprocessing Metode 1 (Basic)
------------------------------------------
Langkah:
  1. Case folding (lowercase)
  2. Remove punctuation / karakter non-alfanumerik
  3. Tokenizing (split by whitespace)

Tidak ada stopword removal atau stemming.
Berguna sebagai baseline perbandingan.
"""

import re


def preprocess_basic(query: str) -> dict:
    """
    Preprocessing dasar tanpa stopword removal dan tanpa stemming.

    Args:
        query (str): Teks query dari user.

    Returns:
        dict dengan key:
            - original   : query asli
            - clean      : teks setelah case folding & remove punctuation
            - tokens     : list token hasil tokenizing
    """
    # Step 1: Case folding
    text = query.lower().strip()

    # Step 2: Remove punctuation — pertahankan hanya huruf, angka, spasi
    text = re.sub(r'[^a-z0-9\s]', ' ', text)

    # Normalisasi spasi ganda
    text = re.sub(r'\s+', ' ', text).strip()

    # Step 3: Tokenizing
    tokens = [t for t in text.split() if t]

    return {
        "original": query,
        "clean": text,
        "tokens": tokens,
    }

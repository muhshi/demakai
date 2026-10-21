"""
api.py — DEMAKAI Python FastAPI Server
=======================================
Server API yang menerima request dari Laravel dan menjalankan pencarian
menggunakan kombinasi metode yang dipilih.

Cara menjalankan:
  cd d:\\magang_bps\\backend-demakai\\python
  uvicorn api:app --host 127.0.0.1 --port 8000 --reload

Endpoint:
  POST /search    → jalankan pencarian dengan metode pilihan
  GET  /health    → cek status server
  GET  /methods   → daftar metode yang tersedia
"""

import sys
import os

sys.path.insert(0, os.path.dirname(__file__))

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional

from preprocessing.expansion import preprocess_expansion
from preprocessing.advanced import preprocess_advanced
from search.sql_like import (
    search_raw      as sql_raw,
    search_expansion as sql_expansion,
    search_advanced  as sql_advanced,
)
from search.hybrid import (
    search_raw      as hybrid_raw,
    search_expansion as hybrid_expansion,
    search_advanced  as hybrid_advanced,
)

# ─────────────────────────────────────────────────────────────────────────────
# App
# ─────────────────────────────────────────────────────────────────────────────

app = FastAPI(
    title="DEMAKAI Search API",
    description="Python search microservice untuk sistem DEMAKAI",
    version="1.0.0",
)

# Izinkan request dari Laravel (Herd)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],   # ganti dengan domain Herd jika ingin lebih ketat
    allow_methods=["*"],
    allow_headers=["*"],
)


# ─────────────────────────────────────────────────────────────────────────────
# Mapping metode
# ─────────────────────────────────────────────────────────────────────────────

SEARCH_RAW_FN = {
    "sql":    sql_raw,
    "hybrid": hybrid_raw,
}

PREPROCESSING_FN = {
    "expansion": preprocess_expansion,
    "advanced":  preprocess_advanced,
}

SEARCH_FN = {
    ("sql",    "expansion"): sql_expansion,
    ("sql",    "advanced"):  sql_advanced,
    ("hybrid", "expansion"): hybrid_expansion,
    ("hybrid", "advanced"):  hybrid_advanced,
}


# ─────────────────────────────────────────────────────────────────────────────
# Request & Response schema
# ─────────────────────────────────────────────────────────────────────────────

class SearchRequest(BaseModel):
    query:      str
    search:     str = "sql"       # sql | hybrid
    processing: str = "none"      # none | expansion | advanced
    limit:      int = 10
    model:      Optional[str] = None   # KBLI | KBJI | None (keduanya)
    mode:       Optional[str] = None   # sql_expansion | hybrid_expansion | dll
    category:   Optional[str] = None   # KBLI Category (A-U or 2-digit division)


# ─────────────────────────────────────────────────────────────────────────────
# KBLI Category Division Mapping
# ─────────────────────────────────────────────────────────────────────────────

CATEGORY_DIVISIONS = {
    'A': ['01', '02', '03'],
    'B': ['05', '06', '07', '08', '09'],
    'C': [f"{i:02d}" for i in range(10, 34)],
    'D': ['35'],
    'E': ['36', '37', '38', '39'],
    'F': ['41', '42', '43'],
    'G': ['45', '46', '47'],
    'H': ['49', '50', '51', '52', '53'],
    'I': ['55', '56'],
    'J': ['58', '59', '60', '61', '62', '63'],
    'K': ['64', '65', '66'],
    'L': ['68'],
    'M': ['69', '70', '71', '72', '73', '74', '75'],
    'N': ['77', '78', '79', '80', '81', '82'],
    'O': ['84'],
    'P': ['85'],
    'Q': ['86', '87', '88'],
    'R': ['90', '91', '92', '93'],
    'S': ['94', '95', '96'],
    'T': ['97', '98'],
    'U': ['99'],
}

def get_category_divisions(category: Optional[str]) -> list:
    if not category:
        return []
    cat = category.strip().upper()
    import re
    # 2-digit number e.g. "56"
    if re.match(r'^\d{2}$', cat):
        return [cat]
    # Letter followed by 2 digits e.g. "I56" or "I 56"
    m = re.match(r'^[A-Z]\s*(\d{2})$', cat)
    if m:
        return [m.group(1)]
    # Extract letter
    m = re.search(r'([A-Z])', cat)
    if m:
        letter = m.group(1)
        if letter in CATEGORY_DIVISIONS:
            if letter == 'I' and '56' in cat:
                return ['56']
            return CATEGORY_DIVISIONS[letter]
    return []


class SearchResponse(BaseModel):
    query:        str
    search:       str
    processing:   str
    total:        int
    results:      list


class ClassifyResult(BaseModel):
    kode: str
    judul: str
    deskripsi: str


class ClassifyResponse(BaseModel):
    query: str
    kbli: Optional[ClassifyResult]
    kbji: Optional[ClassifyResult]
    confidence_score: float
    method: str


# ─────────────────────────────────────────────────────────────────────────────
# Endpoints
# ─────────────────────────────────────────────────────────────────────────────

@app.get("/health")
def health():
    """Cek apakah API server aktif."""
    return {"status": "ok", "service": "DEMAKAI Search API"}


@app.get("/methods")
def methods():
    """Daftar metode pencarian dan preprocessing yang tersedia."""
    return {
        "search":     ["sql", "hybrid"],
        "processing": ["none", "expansion", "advanced"],
        "combinations": [
            f"{s}+{p}"
            for s in ["sql", "hybrid"]
            for p in ["none", "expansion", "advanced"]
        ]
    }


@app.post("/search", response_model=SearchResponse)
def search(req: SearchRequest):
    """
    Jalankan pencarian dengan kombinasi metode yang dipilih.

    Body JSON:
    {
        "query":      "petani sawah",
        "search":     "sql",        // sql | hybrid
        "processing": "none",       // none | expansion | advanced
        "limit":      10,
        "model":      "KBLI"        // KBLI | KBJI | null
    }
    """
    # Validasi
    if req.search not in ("sql", "hybrid"):
        raise HTTPException(status_code=400, detail="search harus 'sql' atau 'hybrid'")
    if req.processing not in ("none", "expansion", "advanced"):
        raise HTTPException(status_code=400, detail="processing harus 'none', 'expansion', atau 'advanced'")
    if not req.query.strip():
        raise HTTPException(status_code=400, detail="query tidak boleh kosong")

    # Mapping mode ke search & processing
    if req.mode:
        if req.mode == "sql_expansion":
            req.search = "sql"
            req.processing = "expansion"
        elif req.mode == "hybrid_expansion":
            req.search = "hybrid"
            req.processing = "expansion"
        # Tambahkan mode lain jika perlu

    try:
        cat_divs = get_category_divisions(req.category)
        fetch_limit = max(req.limit * 3, 30) if cat_divs else req.limit

        if req.processing == "none":
            # Tanpa preprocessing — query mentah
            fn      = SEARCH_RAW_FN[req.search]
            results = fn(req.query, limit=fetch_limit, model=req.model)
        else:
            # Dengan preprocessing
            preprocessed = PREPROCESSING_FN[req.processing](req.query)
            fn           = SEARCH_FN[(req.search, req.processing)]
            results      = fn(preprocessed, limit=fetch_limit, model=req.model)

        if cat_divs:
            matching = []
            non_matching = []
            for item in results:
                kode_str = str(item.get("kode", "")).strip()
                if kode_str[:2] in cat_divs:
                    item_copy = dict(item)
                    item_copy["_boost"] = (str(item_copy.get("_boost") or "") + " category_match").strip()
                    if item_copy.get("distance") is not None:
                        item_copy["distance"] = max(0.005, float(item_copy["distance"]) * 0.5)
                    matching.append(item_copy)
                else:
                    non_matching.append(item)
            results = (matching + non_matching)[:req.limit]

        # Serialisasi hasil — pastikan semua field JSON-serializable
        serialized = []
        for item in results:
            serialized.append({
                "kode":             str(item.get("kode", "")),
                "judul":            str(item.get("judul", "")),
                "deskripsi":        str(item.get("deskripsi", "") or ""),
                "contoh_lapangan":  item.get("contoh_lapangan"),
                "sumber":           str(item.get("sumber", "") or ""),
                "_source":          str(item.get("_source", "")),
                "_boost":           str(item.get("_boost", "") or ""),
                "distance":         float(item["distance"]) if item.get("distance") is not None else None,
            })

        return SearchResponse(
            query=req.query,
            search=req.search,
            processing=req.processing,
            total=len(serialized),
            results=serialized,
        )

    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/api/classify-pekerjaan", response_model=ClassifyResponse)
def classify_pekerjaan(req: SearchRequest):
    """
    Classifier Endpoint v1:
    Mengambil Top-1 KBLI dan Top-1 KBJI berdasarkan hasil Hybrid Expansion terbaik.
    """
    if not req.query.strip():
        raise HTTPException(status_code=400, detail="query tidak boleh kosong")

    try:
        # Override setting ke Hybrid Expansion teroptimasi dengan jumlah kandidat cukup
        limit = 30
        preprocessed = preprocess_expansion(req.query)
        candidates = hybrid_expansion(preprocessed, limit=limit, model=None)

        # Pisahkan berdasar sumber
        kblis = [c for c in candidates if c["_source"] == "KBLI"]
        kbjis = [c for c in candidates if c["_source"] == "KBJI"]

        # Ambil Top-1
        best_kbli = kblis[0] if kblis else None
        best_kbji = kbjis[0] if kbjis else None

        # Confidence Scoring: Distance kecil -> Score tinggi
        # (Hook untuk integrasi Gemini di Phase 2 bila score < threshold ditempatkan di sini nanti)
        confidence = 0.0
        dist_kbli = best_kbli.get("distance", 1.0) if best_kbli else 1.0
        dist_kbji = best_kbji.get("distance", 1.0) if best_kbji else 1.0

        if best_kbli or best_kbji:
            # Hitung rata-rata distance dari yg ditemukan
            found_dists = []
            if best_kbli: found_dists.append(dist_kbli)
            if best_kbji: found_dists.append(dist_kbji)
            
            avg_dist = sum(found_dists) / len(found_dists)
            # Normalisasi ke skala 0-100
            confidence = max(0.0, min(100.0, (1.0 - float(avg_dist)) * 100))

        def fmt(item):
            if not item: return None
            return {
                "kode": str(item["kode"]),
                "judul": str(item["judul"]),
                "deskripsi": str(item.get("deskripsi", ""))
            }

        return ClassifyResponse(
            query=req.query,
            kbli=fmt(best_kbli),
            kbji=fmt(best_kbji),
            confidence_score=round(confidence, 2),
            method="hybrid_expansion_top1"
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

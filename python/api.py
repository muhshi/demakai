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

from preprocessing.basic    import preprocess_basic
from preprocessing.advanced import preprocess_advanced
from search.sql_like import (
    search_raw      as sql_raw,
    search_basic    as sql_basic,
    search_advanced as sql_advanced,
)
from search.hybrid import (
    search_raw      as hybrid_raw,
    search_basic    as hybrid_basic,
    search_advanced as hybrid_advanced,
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
    "basic":    preprocess_basic,
    "advanced": preprocess_advanced,
}

SEARCH_FN = {
    ("sql",    "basic"):    sql_basic,
    ("sql",    "advanced"): sql_advanced,
    ("hybrid", "basic"):    hybrid_basic,
    ("hybrid", "advanced"): hybrid_advanced,
}


# ─────────────────────────────────────────────────────────────────────────────
# Request & Response schema
# ─────────────────────────────────────────────────────────────────────────────

class SearchRequest(BaseModel):
    query:      str
    search:     str = "sql"       # sql | hybrid
    processing: str = "none"      # none | basic | advanced
    limit:      int = 10
    model:      Optional[str] = None   # KBLI | KBJI | None (keduanya)


class SearchResponse(BaseModel):
    query:        str
    search:       str
    processing:   str
    total:        int
    results:      list


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
        "processing": ["none", "basic", "advanced"],
        "combinations": [
            f"{s}+{p}"
            for s in ["sql", "hybrid"]
            for p in ["none", "basic", "advanced"]
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
        "processing": "basic",      // none | basic | advanced
        "limit":      10,
        "model":      "KBLI"        // KBLI | KBJI | null
    }
    """
    # Validasi
    if req.search not in ("sql", "hybrid"):
        raise HTTPException(status_code=400, detail="search harus 'sql' atau 'hybrid'")
    if req.processing not in ("none", "basic", "advanced"):
        raise HTTPException(status_code=400, detail="processing harus 'none', 'basic', atau 'advanced'")
    if not req.query.strip():
        raise HTTPException(status_code=400, detail="query tidak boleh kosong")

    try:
        if req.processing == "none":
            # Tanpa preprocessing — query mentah
            fn      = SEARCH_RAW_FN[req.search]
            results = fn(req.query, limit=req.limit, model=req.model)
        else:
            # Dengan preprocessing
            preprocessed = PREPROCESSING_FN[req.processing](req.query)
            fn           = SEARCH_FN[(req.search, req.processing)]
            results      = fn(preprocessed, limit=req.limit, model=req.model)

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

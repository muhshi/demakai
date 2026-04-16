
import sys
import os

# Add the current directory to sys.path
sys.path.insert(0, r"d:\magang_bps\backend-demakai\python")

from preprocessing.expansion import preprocess_expansion
from search.hybrid import search_expansion
from config.database import get_connection

def debug_query(query):
    print(f"--- Debugging Query: {query} ---")
    preprocessed = preprocess_expansion(query)
    print(f"Preprocessed: {preprocessed}")
    
    results = search_expansion(preprocessed, limit=5, model="KBLI")
    print(f"\nResults for KBLI:")
    for r in results:
        print(f"Kode: {r.get('kode')} | Judul: {r.get('judul')} | Distance: {r.get('distance')} | Source: {r.get('_boost')} | Match: {r.get('_match_score')}")

def check_record(kode):
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute("SELECT * FROM pg_kbli2025 WHERE kode = %s", (kode,))
            row = cur.fetchone()
            print(f"\nRecord {kode}: {row}")
    finally:
        conn.close()

if __name__ == "__main__":
    debug_query("polri")
    check_record("38302")

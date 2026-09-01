import json
from config.database import get_connection

def main():
    conn = get_connection()
    cur = conn.cursor()
    
    # 1. Inspect field_example_submissions
    print("=== FIELD EXAMPLE SUBMISSIONS ===")
    cur.execute("SELECT count(*) as c FROM field_example_submissions")
    print("Total submissions:", cur.fetchone()['c'])
    
    cur.execute("SELECT * FROM field_example_submissions ORDER BY id DESC LIMIT 15")
    subs = cur.fetchall()
    for s in subs:
        print(f"[{s.get('id')}] Code: {s.get('kbli_code') or s.get('code')} | Example: '{s.get('example') or s.get('phrase') or s.get('deskripsi')}' | Status: {s.get('status')}")

    # 2. Inspect Top Distinct User Queries from search_histories
    print("\n=== TOP 30 MOST FREQUENT REAL USER QUERIES ===")
    cur.execute("""
        SELECT LOWER(TRIM(query)) as clean_query, count(*) as freq 
        FROM search_histories 
        WHERE LENGTH(TRIM(query)) > 3 
          AND query NOT SIMILAR TO '[0-9]+'
        GROUP BY clean_query 
        ORDER BY freq DESC 
        LIMIT 30
    """)
    top_queries = cur.fetchall()
    for i, tq in enumerate(top_queries, 1):
        print(f"{i:2d}. \"{tq['clean_query']}\" (frekuensi: {tq['freq']} kali)")
        
    conn.close()

if __name__ == '__main__':
    main()

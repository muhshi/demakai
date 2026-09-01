import json
from config.database import get_connection

def inspect_all_submissions():
    conn = get_connection()
    cur = conn.cursor()
    
    cur.execute("""
        SELECT DISTINCT ON (s.content) 
            s.id, 
            s.kode, 
            s.content, 
            s.status,
            k.judul as kbli_judul
        FROM field_example_submissions s
        LEFT JOIN kbli2025s k ON s.kode = k.kode
        WHERE s.status = 'approved'
          AND (s.type ILIKE '%2025%' OR s.type = 'KBLI')
          AND LENGTH(TRIM(s.content)) > 2
        ORDER BY s.content, s.id ASC
    """)
    approved = cur.fetchall()
    print(f"Total Unique Approved KBLI Submissions: {len(approved)}")
    
    # Also check unique pending / total submissions
    cur.execute("SELECT count(DISTINCT content) as c FROM field_example_submissions")
    print(f"Total Unique Submissions Across All Statuses: {cur.fetchone()['c']}")
    
    # Also check unique top search keywords from search_histories
    cur.execute("""
        SELECT count(DISTINCT LOWER(TRIM(query))) as c 
        FROM search_histories 
        WHERE LENGTH(TRIM(query)) > 3
    """)
    print(f"Total Unique Queries in search_histories: {cur.fetchone()['c']}")
    
    conn.close()

if __name__ == '__main__':
    inspect_all_submissions()

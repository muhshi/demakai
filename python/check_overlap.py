from config.database import get_connection

def check_overlap():
    conn = get_connection()
    cur = conn.cursor()
    
    cur.execute("""
        SELECT s.id, s.content, s.kode,
               COUNT(h.id) as history_count
        FROM field_example_submissions s
        LEFT JOIN search_histories h ON LOWER(TRIM(h.query)) = LOWER(TRIM(s.content))
        WHERE s.status = 'approved' AND (s.type ILIKE '%2025%' OR s.type = 'KBLI')
        GROUP BY s.id, s.content, s.kode
        ORDER BY history_count DESC
    """)
    rows = cur.fetchall()
    
    print(f"Pengecekan Overlap ({len(rows)} Usulan Approved):")
    found_in_history = [r for r in rows if r['history_count'] > 0]
    print(f"Total yang tercatat di search_histories: {len(found_in_history)} dari {len(rows)}")
    for r in rows[:15]:
        print(f"  [{r['kode']}] '{r['content']}' -> Dicari di search_histories sebanyak: {r['history_count']} kali")
        
    conn.close()

if __name__ == '__main__':
    check_overlap()

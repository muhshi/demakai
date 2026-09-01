import json
from config.database import get_connection

def main():
    conn = get_connection()
    cur = conn.cursor()
    
    cur.execute("SELECT status, count(*) as count FROM field_example_submissions GROUP BY status")
    print("Submissions by status:")
    for r in cur.fetchall():
        print(f" - {r['status']}: {r['count']}")
        
    cur.execute("""
        SELECT id, type, kode, content, status 
        FROM field_example_submissions 
        WHERE type ILIKE '%2025%' OR type = 'KBLI'
        LIMIT 30
    """)
    print("\nSample KBLI Submissions:")
    for s in cur.fetchall():
        print(f"[{s['id']}] Code: {s['kode']} | Status: {s['status']} | Content: '{s['content']}'")
        
    conn.close()

if __name__ == '__main__':
    main()

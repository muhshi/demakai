import json
from config.database import get_connection

def main():
    conn = get_connection()
    cur = conn.cursor()
    
    cur.execute("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")
    tables = [r['table_name'] for r in cur.fetchall()]
    print('Available tables:', tables)
    
    if 'search_histories' in tables:
        cur.execute("SELECT count(*) as c FROM search_histories")
        print("Total search_histories:", cur.fetchone()['c'])
        
        cur.execute("SELECT id, query, results_count, detected_type, created_at FROM search_histories ORDER BY id DESC LIMIT 20")
        sample = cur.fetchall()
        print("\n20 Sample Queries from search_histories:")
        for s in sample:
            print(f"[{s['id']}] ({s['detected_type']}) \"{s['query']}\" -> results: {s['results_count']}")
            
    conn.close()

if __name__ == '__main__':
    main()

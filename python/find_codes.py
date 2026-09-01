import json
from config.database import get_connection

def find_exact_kbli2025_codes():
    conn = get_connection()
    cur = conn.cursor()
    
    queries = [
        "SELECT kode, judul FROM kbli2025s WHERE kode IN ('49296', '96100', '96101', '45407', '45401', '45402', '45403', '47112', '47192', '61201', '47414', '56101', '56102', '56103', '96211', '96111', '96112', '96212', '66144')",
        "SELECT kode, judul FROM kbli2025s WHERE judul ILIKE '%reparasi sepeda motor%' OR deskripsi ILIKE '%reparasi sepeda motor%'",
        "SELECT kode, judul FROM kbli2025s WHERE judul ILIKE '%pangkas rambut%' OR deskripsi ILIKE '%pangkas rambut%'",
        "SELECT kode, judul FROM kbli2025s WHERE judul ILIKE '%pencucian%' AND judul ILIKE '%tekstil%'",
        "SELECT kode, judul FROM kbli2025s WHERE judul ILIKE '%ojek%'"
    ]
    
    for q in queries:
        print(f"\nRunning: {q}")
        cur.execute(q)
        for r in cur.fetchall():
            print(f"  [{r['kode']}] {r['judul']}")
            
    conn.close()

if __name__ == '__main__':
    find_exact_kbli2025_codes()

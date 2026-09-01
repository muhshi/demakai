import json
from config.database import get_connection

def verify_and_fix_ground_truth():
    conn = get_connection()
    cur = conn.cursor()
    
    # Check what table structure kbli2025s has
    cur.execute("SELECT kode, judul, deskripsi FROM kbli2025s WHERE kode IN ('49296', '96100', '96101', '45407', '47112', '47414', '56103', '96211', '66144')")
    rows = cur.fetchall()
    print("Found KBLI 2025 rows:")
    for r in rows:
        print(f"[{r['kode']}] {r['judul']}")
        
    # Search for proper KBLI 2025 codes for common terms
    terms = ["ojek", "laundry", "binatu", "sembako", "bengkel motor", "tambal ban", "pulsa", "cilok", "pangkas rambut", "brilink"]
    for t in terms:
        cur.execute("""
            SELECT kode, judul 
            FROM kbli2025s 
            WHERE judul ILIKE %s OR deskripsi ILIKE %s OR contoh_lapangan ILIKE %s
            LIMIT 3
        """, (f"%{t}%", f"%{t}%", f"%{t}%"))
        res = cur.fetchall()
        print(f"\nSearch for '{t}':")
        for r in res:
            print(f"  -> [{r['kode']}] {r['judul']}")

    conn.close()

if __name__ == '__main__':
    verify_and_fix_ground_truth()

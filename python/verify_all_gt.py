import json
from config.database import get_connection

def verify_all_ground_truths():
    conn = get_connection()
    cur = conn.cursor()
    
    # 1. Check all approved submissions
    cur.execute("""
        SELECT DISTINCT ON (s.content) 
            s.id, 
            s.kode, 
            s.content, 
            k.kode as k_kode,
            k.judul as k_judul
        FROM field_example_submissions s
        LEFT JOIN kbli2025s k ON s.kode = k.kode
        WHERE s.status = 'approved'
          AND (s.type ILIKE '%2025%' OR s.type = 'KBLI')
          AND LENGTH(TRIM(s.content)) > 2
        ORDER BY s.content, s.id ASC
    """)
    submissions = cur.fetchall()
    
    print(f"Total Approved Submissions: {len(submissions)}")
    invalid_subs = []
    for s in submissions:
        if not s['k_judul']:
            invalid_subs.append(s)
            print(f"  [INVALID KBLI CODE IN SUBMISSION] #{s['id']} content: '{s['content']}', kode: '{s['kode']}'")
        else:
            print(f"  [VALID] #{s['id']} '{s['content']}' -> [{s['kode']}] {s['k_judul']}")
            
    print("\n" + "="*50)
    print("Verifying the 10 popular queries in KBLI 2025:")
    popular_tests = [
        ("ojek online", ["ojek"]),
        ("laundry kiloan", ["pencucian", "binatu", "tekstil"]),
        ("bengkel motor", ["sepeda motor", "reparasi"]),
        ("tambal ban", ["ban", "reparasi"]),
        ("warung sembako", ["swalayan", "kelontong", "makanan, minuman"]),
        ("toko kelontong", ["swalayan", "makanan, minuman"]),
        ("jual pulsa", ["telekomunikasi", "voucher", "pulsa"]),
        ("pedagang cilok keliling", ["keliling", "kaki lima", "tidak tetap"]),
        ("pangkas rambut madura", ["pangkas rambut", "salon", "rambut"]),
        ("agen brilink pembayaran", ["pengolahan uang", "lembaga keuangan", "perantara"])
    ]
    
    for q, keywords in popular_tests:
        print(f"\nQuery: '{q}'")
        where_clauses = " OR ".join(["judul ILIKE %s OR deskripsi ILIKE %s OR contoh_lapangan::text ILIKE %s" for _ in keywords])
        params = []
        for kw in keywords:
            params.extend([f"%{kw}%", f"%{kw}%", f"%{kw}%"])
        cur.execute(f"SELECT kode, judul FROM kbli2025s WHERE {where_clauses} LIMIT 4", params)
        for r in cur.fetchall():
            print(f"   -> [{r['kode']}] {r['judul']}")

    conn.close()

if __name__ == '__main__':
    verify_all_ground_truths()

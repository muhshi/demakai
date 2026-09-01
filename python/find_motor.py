from config.database import get_connection
conn = get_connection()
cur = conn.cursor()
cur.execute("SELECT kode, judul FROM kbli2025s WHERE judul ILIKE '%motor%'")
rows = cur.fetchall()
for r in rows:
    print(f"[{r['kode']}] {r['judul']}")
conn.close()

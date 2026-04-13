import psycopg2
import sys
import os

# Menambahkan parent directory ke sys.path agar bisa import config
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from config.settings import Settings

def check_dim():
    try:
        conn = psycopg2.connect(
            host=Settings.DB_HOST,
            port=Settings.DB_PORT,
            dbname=Settings.DB_NAME,
            user=Settings.DB_USER,
            password=Settings.DB_PASSWORD
        )
        cur = conn.cursor()
        
        # Cek dimensi kolom embedding di tabel KBLI dan KBJI
        tables = [Settings.TABLE_KBLI, Settings.TABLE_KBJI]
        for table in tables:
            cur.execute(f"""
                SELECT atttypmod 
                FROM pg_attribute 
                WHERE attrelid = %s::regclass 
                AND attname = 'embedding'
            """, (table,))
            r = cur.fetchone()
            dim = r[0] if r else "Not found"
            print(f"Table: {table}, atttypmod: {dim}")
            
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    check_dim()

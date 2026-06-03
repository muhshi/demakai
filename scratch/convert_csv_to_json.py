import csv
import json

categories_meta = {
    "A": "PERTANIAN, KEHUTANAN DAN PERIKANAN",
    "B": "PERTAMBANGAN DAN PENGGALIAN",
    "C": "INDUSTRI PENGOLAHAN",
    "D": "PENGADAAN LISTRIK, GAS, UAP/AIR PANAS DAN UDARA DINGIN",
    "E": "PENGADAAN AIR, PENGELOLAAN SAMPAH, LIMBAH DAN DAUR ULANG",
    "F": "KONSTRUKSI",
    "G": "PERDAGANGAN BESAR DAN ECERAN; REPARASI MOBIL DAN SEPEDA MOTOR",
    "H": "PENGANGKUTAN DAN PERGUDANGAN",
    "I": "PENYEDIAAN AKOMODASI DAN PENYEDIAAN MAKAN MINUM",
    "J": "INFORMASI DAN KOMUNIKASI",
    "K": "AKTIVITAS KEUANGAN DAN ASURANSI",
    "L": "REAL ESTAT",
    "M": "AKTIVITAS PROFESIONAL, ILMIAH DAN TEKNIS",
    "N": "AKTIVITAS PENYEWAAN DAN GUNA USAHA TANPA HAK OPSI, KETENAGAKERJAAN, AGEN PERJALANAN DAN PENUNJANG USAHA LAINNYA",
    "O": "ADMINISTRASI PEMERINTAHAN, PERTAHANAN DAN JAMINAN SOSIAL WAJIB",
    "P": "PENDIDIKAN",
    "Q": "AKTIVITAS KESEHATAN MANUSIA DAN AKTIVITAS SOSIAL",
    "R": "KESENIAN, HIBURAN DAN REKREASI",
    "S": "AKTIVITAS JASA LAINNYA",
    "T": "AKTIVITAS RUMAH TANGGA SEBAGAI PEMBERI KERJA; AKTIVITAS YANG MENGHASILKAN BARANG DAN JASA OLEH RUMAH TANGGA YANG DIGUNAKAN UNTUK MEMENUHI KEBUTUHAN SENDIRI",
    "U": "AKTIVITAS BADAN INTERNASIONAL DAN BADAN EKSTRA INTERNASIONAL",
    "V": "AKTIVITAS LAINNYA" # Added just in case it exists in data
}

output = []

# First, add the categories
added_categories = set()

with open('scratch/kbli_raw.csv', 'r') as f:
    reader = csv.DictReader(f)
    for row in reader:
        cat_id = row['Kategori']
        if cat_id not in added_categories:
            output.append({
                "level": "kategori",
                "kode": cat_id,
                "judul": categories_meta.get(cat_id, f"Kategori {cat_id}"),
                "deskripsi": "",
                "parent_kode": None
            })
            added_categories.add(cat_id)
        
        # Add the Golongan Pokok (2 digit)
        output.append({
            "level": "pokok",
            "kode": row['Kode'].zfill(2),
            "judul": row['Judul'],
            "deskripsi": row['Deskripsi'],
            "parent_kode": cat_id
        })

# Save to database/data
import os
os.makedirs('database/data', exist_ok=True)
with open('database/data/kbli2025_hierarchies.json', 'w', encoding='utf-8') as f:
    json.dump(output, f, indent=4, ensure_ascii=False)

print(f"Successfully converted {len(output)} records to JSON.")

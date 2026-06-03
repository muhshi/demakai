import pandas as pd
import json
import os

file_path = 'scratch/kbli_full.xlsx'
xls = pd.ExcelFile(file_path)

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
    "V": "AKTIVITAS LAINNYA"
}

output = []
added_categories = set()

# Process each sheet
sheets_to_process = ['kode_2_digit', 'kode_3_digit', 'kode_4_digit', 'kode_5_digit']
level_map = {
    2: "pokok",
    3: "golongan",
    4: "subgolongan",
    5: "kelompok"
}

for sheet in sheets_to_process:
    if sheet in xls.sheet_names:
        df = pd.read_excel(xls, sheet_name=sheet)
        for _, row in df.iterrows():
            kategori = str(row.get('Kategori', '')).strip()
            kode = str(row.get('Kode', '')).strip()
            judul = str(row.get('Judul', '')).strip()
            deskripsi = str(row.get('Deskripsi', '')).strip()
            
            if deskripsi == 'nan':
                deskripsi = ''
                
            # If kode is numeric, maybe it got parsed as int. Need to zfill if < 2, 
            # but wait, 5 digit codes with leading zero like '01111' could become '1111'.
            # It's better to format based on sheet name or expected length.
            # E.g. kode_2_digit -> zfill(2), kode_3_digit -> zfill(3), etc.
            expected_len = int(sheet.split('_')[1])
            kode = kode.split('.')[0] # Remove decimal if parsed as float
            kode = kode.zfill(expected_len)
            
            # Ensure category is added
            if kategori and kategori not in added_categories and kategori != 'nan':
                output.append({
                    "level": "kategori",
                    "kode": kategori,
                    "judul": categories_meta.get(kategori, f"Kategori {kategori}"),
                    "deskripsi": "",
                    "parent_kode": None
                })
                added_categories.add(kategori)
            
            parent_kode = kategori if len(kode) == 2 else kode[:-1]
            
            output.append({
                "level": level_map.get(len(kode), "unknown"),
                "kode": kode,
                "judul": judul,
                "deskripsi": deskripsi,
                "parent_kode": parent_kode
            })

os.makedirs('database/data', exist_ok=True)
with open('database/data/kbli2025_full_arsip.json', 'w', encoding='utf-8') as f:
    json.dump(output, f, indent=4, ensure_ascii=False)

print(f"Successfully converted {len(output)} records to JSON.")

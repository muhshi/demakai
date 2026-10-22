"""
Prepare Merged SE2026 Anomaly & Recommendation Dataset for Google Spreadsheet.
Combines resolved Excel files, standardizes KBLI padding, formats direct Fasih URLs,
and arranges columns for collaborative verification tracking.
"""

import os
import argparse
import pandas as pd

def format_kbli_code(val):
    if pd.isna(val) or val is None:
        return ""
    s = str(val).strip()
    if s.endswith('.0'):
        s = s[:-2]
    if s.lower() == 'nan':
        return ""
    if s.isdigit() and len(s) == 4:
        return s.zfill(5)
    return s

def format_fasih_url(val):
    if pd.isna(val) or val is None:
        return ""
    url = str(val).strip()
    if not url or url.lower() == 'nan':
        return ""
    if ("assignment" in url or "fasih" in url) and not url.endswith('/edit'):
        url = url.rstrip('/') + '/edit'
    return url

def clean_cell(val):
    if pd.isna(val) or val is None:
        return ""
    s = str(val).strip()
    if s.lower() == 'nan':
        return ""
    return s

def prepare_merged_dataset(file1_path: str, file2_path: str, output_path: str):
    print(f"Reading file 1: {file1_path}")
    df1 = pd.read_excel(file1_path, dtype=str)
    print(f"File 1 rows: {len(df1)}")

    print(f"Reading file 2: {file2_path}")
    df2 = pd.read_excel(file2_path, dtype=str)
    print(f"File 2 rows: {len(df2)}")

    df = pd.concat([df1, df2], ignore_index=True)
    total_rows = len(df)
    print(f"Combined total rows: {total_rows}")

    # Build target DataFrame with 26 columns
    out_df = pd.DataFrame()

    out_df['no'] = range(1, total_rows + 1)
    out_df['status_pengerjaan'] = 'Belum Dikerjakan'
    out_df['status_kbli'] = 'Belum Diverifikasi'
    out_df['kbli_final_benar'] = ''
    out_df['petugas_verifikasi'] = ''
    out_df['waktu_selesai'] = ''

    out_df['assignment_id'] = df['assignment_id'].apply(clean_cell)
    out_df['no_usaha'] = df['no_usaha'].apply(clean_cell)
    out_df['kecamatan'] = df['kecamatan'].apply(clean_cell)
    out_df['desa'] = df['desa'].apply(clean_cell)
    out_df['kode_sls'] = df['kode_sls'].apply(clean_cell)
    out_df['petugas_lapangan'] = df['petugas'].apply(clean_cell)
    out_df['nama_usaha'] = df['nama_usaha'].apply(clean_cell)
    out_df['uraian_kegiatan'] = df['uraian_kegiatan'].apply(clean_cell)

    out_df['kbli_saat_ini'] = df['kbli_saat_ini'].apply(format_kbli_code)
    out_df['judul_kbli_saat_ini'] = df['judul_kbli_saat_ini'].apply(clean_cell)

    out_df['rekomendasi_1'] = df['rekomendasi_1'].apply(clean_cell)
    out_df['confidence_1'] = df['confidence_1'].apply(clean_cell)
    out_df['rekomendasi_2'] = df['rekomendasi_2'].apply(clean_cell)
    out_df['confidence_2'] = df['confidence_2'].apply(clean_cell)
    out_df['rekomendasi_3'] = df['rekomendasi_3'].apply(clean_cell)
    out_df['confidence_3'] = df['confidence_3'].apply(clean_cell)

    out_df['rule_terpicu'] = df['rule_terpicu'].apply(clean_cell)
    out_df['rekomendasi_tindakan'] = df['rekomendasi_tindakan'].apply(clean_cell)
    out_df['alasan_rekomendasi'] = df['alasan_rekomendasi'].apply(clean_cell)
    out_df['link_fasih_sm'] = df['link_fasih_sm'].apply(format_fasih_url)

    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    out_df.to_csv(output_path, index=False, encoding='utf-8')
    print(f"Successfully saved merged dataset to: {output_path} ({len(out_df)} rows, {len(out_df.columns)} columns)")
    return out_df

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description="Merge and prepare SE2026 anomaly datasets for Google Spreadsheet.")
    parser.add_argument('--file1', default='storage/app/se2026_resolved/resolved_sqllab_kbli_rekomendasi_20261020T095230_20261021_125928.xlsx', help="Path to resolved Excel batch 1")
    parser.add_argument('--file2', default='storage/app/se2026_resolved/resolved_sqllab_kbli_rekomendasi_20261020T095247_20261021_134826.xlsx', help="Path to resolved Excel batch 2")
    parser.add_argument('--output', default='storage/app/se2026_resolved/merged_se2026_cleaning_dataset.csv', help="Output CSV path")

    args = parser.parse_args()
    prepare_merged_dataset(args.file1, args.file2, args.output)

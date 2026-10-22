import unittest
import os
import pandas as pd

class TestMergedDataset(unittest.TestCase):
    def test_merged_csv_exists_and_has_all_rows(self):
        csv_path = 'storage/app/se2026_resolved/merged_se2026_cleaning_dataset.csv'
        self.assertTrue(os.path.exists(csv_path), "Merged CSV must exist")
        df = pd.read_csv(csv_path, dtype=str)
        self.assertEqual(len(df), 14246, "Merged CSV must have exactly 14,246 rows")
        expected_cols = [
            'no', 'status_pengerjaan', 'status_kbli', 'kbli_final_benar', 'petugas_verifikasi', 'waktu_selesai',
            'assignment_id', 'no_usaha', 'kecamatan', 'desa', 'kode_sls', 'petugas_lapangan',
            'nama_usaha', 'uraian_kegiatan', 'kbli_saat_ini', 'judul_kbli_saat_ini',
            'rekomendasi_1', 'confidence_1', 'rekomendasi_2', 'confidence_2', 'rekomendasi_3', 'confidence_3',
            'rule_terpicu', 'rekomendasi_tindakan', 'alasan_rekomendasi', 'link_fasih_sm'
        ]
        for col in expected_cols:
            self.assertIn(col, df.columns, f"Column {col} must exist in merged dataset")

        # Verify default columns
        self.assertTrue((df['status_pengerjaan'] == 'Belum Dikerjakan').all())
        self.assertTrue((df['status_kbli'] == 'Belum Diverifikasi').all())

        # Verify kbli_saat_ini is 5 digits
        valid_kbli = df['kbli_saat_ini'].dropna()
        valid_kbli = valid_kbli[valid_kbli != '']
        for code in valid_kbli:
            self.assertEqual(len(str(code).strip()), 5, f"KBLI code '{code}' in kbli_saat_ini should be 5 digits")

        # Verify link_fasih_sm ends with /edit if assignment link
        for link in df['link_fasih_sm'].dropna().head(100):
            if 'fasih' in str(link) and 'assignment' in str(link):
                self.assertTrue(str(link).endswith('/edit'), f"Link {link} should end with /edit")

if __name__ == '__main__':
    unittest.main()

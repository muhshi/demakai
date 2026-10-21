import sys
import os
import unittest

# Add python directory to path so imports work
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from preprocessing.expansion import preprocess_expansion

class TestLongQueries(unittest.TestCase):
    def test_pentol_with_motor_does_not_trigger_motor_sales(self):
        query = "melakukan proses pengolahan bahan baku daging tepung bumbu menjadi pentol siap saji di rumah kemudian memasarkannya secara keliling menggunakan gerobak yang dimodifikasi pada sepeda motor untuk menjangkau konsumen"
        prep = preprocess_expansion(query)
        # The variation map must NOT match "jual motor" when words are separated across clauses
        self.assertNotIn("showroom motor rumahan", prep.get("kbli_variations", []))
        self.assertNotIn("jual beli motor bekas", prep.get("kbli_variations", []))

    def test_instrumental_modifiers_extraction(self):
        query = "jual pentol keliling menggunakan gerobak sepeda motor"
        prep = preprocess_expansion(query)
        modifiers = prep.get("instrumental_modifiers", [])
        self.assertTrue("gerobak" in modifiers or "motor" in modifiers)
        self.assertIn("pentol", prep.get("core_activity", ""))

    def test_direct_motor_sales_still_triggers_variations(self):
        query = "showroom motor bekas"
        prep = preprocess_expansion(query)
        variations = prep.get("kbli_variations", [])
        self.assertTrue("showroom motor rumahan" in variations or "jual beli motor bekas" in variations)

    def test_long_survey_queries_return_non_empty_results(self):
        from search.sql_like import search_expansion
        queries = [
            'Menjual takoyaki keliling di desa desa sebelah',
            'PEMBUDIYAAN JANGKRIK DIMULAI DARI TELUR HINGGA BESAR (SIAP PANEN)',
            'melakukan proses pengolahan bahan baku daging tepung bumbu menjadi pentol siap saji di rumah kemudian memasarkannya secara keliling menggunakan gerobak yang dimodifikasi pada sepeda motor',
            'Usaha jasa pengolahan pascapanen sarang burung walet berupa pembersihan sarang walet mentah dirty bird nest melalui proses perendaman pembersihan kotoran'
        ]
        for q in queries:
            prep = preprocess_expansion(q)
            results = search_expansion(prep, limit=5, model='KBLI')
            self.assertTrue(len(results) > 0, f"Query '{q}' returned empty results")

if __name__ == "__main__":
    unittest.main()

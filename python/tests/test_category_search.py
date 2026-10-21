import sys
import os
import unittest

sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from api import get_category_divisions, SearchRequest, search

class TestCategorySearch(unittest.TestCase):
    def test_category_division_mapping(self):
        self.assertEqual(get_category_divisions("G"), ["45", "46", "47"])
        self.assertEqual(get_category_divisions("A"), ["01", "02", "03"])
        self.assertEqual(get_category_divisions("I"), ["55", "56"])
        self.assertEqual(get_category_divisions("I56"), ["56"])
        self.assertEqual(get_category_divisions("56"), ["56"])
        self.assertIn("10", get_category_divisions("C"))
        self.assertIn("33", get_category_divisions("C"))

    def test_search_api_with_category_boost(self):
        req = SearchRequest(
            query="pedagang beras",
            search="sql",
            processing="expansion",
            limit=5,
            model="KBLI",
            category="G"
        )
        res = search(req)
        self.assertTrue(len(res.results) > 0)
        first_code = res.results[0]["kode"]
        self.assertTrue(first_code.startswith("4"), f"Expected division 45/46/47, got {first_code}")

if __name__ == "__main__":
    unittest.main()

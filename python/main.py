"""
main.py — DEMAKAI Search System
=================================
Ganti metode lewat argumen terminal, query ditulis langsung di kode (lihat QUERY di bawah).

Cara menjalankan:
  python main.py --search sql    --processing basic
  python main.py --search sql    --processing advanced
  python main.py --search hybrid --processing basic
  python main.py --search hybrid --processing advanced

Argumen opsional:
  --limit  N        jumlah maksimum hasil (default: 10)
  --model  KBLI     filter tabel: KBLI, KBJI (default: keduanya)
  --all             jalankan semua 4 kombinasi sekaligus
"""

import argparse

# ── Import preprocessing ──────────────────────────────────────────────────────
from preprocessing.basic    import preprocess_basic
from preprocessing.advanced import preprocess_advanced

# ── Import search (SQL LIKE) ──────────────────────────────────────────────────
from search.sql_like import (
    search_raw      as sql_raw,
    search_basic    as sql_basic,
    search_advanced as sql_advanced,
)

# ── Import search (Hybrid) ────────────────────────────────────────────────────
from search.hybrid import (
    search_raw      as hybrid_raw,
    search_basic    as hybrid_basic,
    search_advanced as hybrid_advanced,
)

# ── Import utils ──────────────────────────────────────────────────────────────
from utils.result_formatter import print_results, print_preprocessing, save_to_csv, save_to_html


# ──────────────────────────────────────────────────────────────────────────────
# Mapping argumen → fungsi
# ──────────────────────────────────────────────────────────────────────────────

# Query mentah tidak butuh preprocessing — langsung terima string
SEARCH_RAW_FN = {
    "sql":    sql_raw,
    "hybrid": hybrid_raw,
}

# Query dengan preprocessing — terima dict dari preprocess_*()
PREPROCESSING_FN = {
    "basic":    preprocess_basic,
    "advanced": preprocess_advanced,
}

SEARCH_FN = {
    ("sql",    "basic"):    sql_basic,
    ("sql",    "advanced"): sql_advanced,
    ("hybrid", "basic"):    hybrid_basic,
    ("hybrid", "advanced"): hybrid_advanced,
}


# ──────────────────────────────────────────────────────────────────────────────
# Fungsi inti: jalankan satu kombinasi
# ──────────────────────────────────────────────────────────────────────────────

def run_single_combination(
    query: str,
    search: str,
    processing: str,
    limit: int = 10,
    model: str = None,
) -> list:
    """
    Jalankan pipeline preprocessing → search untuk satu kombinasi metode.

    Args:
        query      : teks query dari user
        search     : 'sql' atau 'hybrid'
        processing : 'basic' atau 'advanced'
        limit      : jumlah maksimum hasil
        model      : 'KBLI', 'KBJI', atau None (keduanya)

    Returns:
        list of dict — hasil pencarian
    """
    label = f"{search.upper()} + {processing.capitalize()}"

    print("\n" + "═" * 60)
    print(f"  DEMAKAI — {label}")
    print(f"  Query      : \"{query}\"")
    print(f"  Search     : {search.upper()}")
    print(f"  Processing : {processing.capitalize()}")
    print(f"  Model      : {model or 'KBLI + KBJI'}")
    print(f"  Limit      : {limit}")
    print("═" * 60)

    # ── Pipeline ─────────────────────────────────────────────────────────────
    if processing == "none":
        print("\n  [Preprocessing] NONE — query digunakan mentah")
        results = SEARCH_RAW_FN[search](query, limit=limit, model=model)
    else:
        preprocessed = PREPROCESSING_FN[processing](query)
        print_preprocessing(preprocessed, method=processing)
        results = SEARCH_FN[(search, processing)](preprocessed, limit=limit, model=model)

    print_results(results, title=label, max_items=limit)
    return results


# ──────────────────────────────────────────────────────────────────────────────
# Fungsi demo: jalankan semua 4 kombinasi sekaligus
# ──────────────────────────────────────────────────────────────────────────────

def run_all_combinations(query: str, limit: int = 10, model: str = None) -> dict:
    """Jalankan keempat kombinasi dan tampilkan ringkasan perbandingan.
    
    Returns:
        dict { label: list_hasil } — untuk keperluan ekspor
    """
    combos = [
        ("sql",    "none"),
        ("sql",    "basic"),
        ("sql",    "advanced"),
        ("hybrid", "none"),
        ("hybrid", "basic"),
        ("hybrid", "advanced"),
    ]

    all_results = {}
    summary = []
    for search, processing in combos:
        results = run_single_combination(query, search, processing, limit, model)
        label = f"{search.upper()} + {processing.capitalize()}"
        all_results[label] = results
        summary.append((f"{search.upper():6} + {processing.capitalize():8}", len(results)))

    print("\n" + "=" * 60)
    print("  RINGKASAN PERBANDINGAN")
    print("=" * 60)
    for label, count in summary:
        print(f"  {label}: {count} hasil")
    print("=" * 60)

    return all_results


# ──────────────────────────────────────────────────────────────────────────────
# Contoh per kombinasi (fungsi individual — bisa diimport dari modul lain)
# ──────────────────────────────────────────────────────────────────────────────

def example_combination_1(query: str) -> list:
    """Kombinasi 1: SQL LIKE + Preprocessing Basic"""
    return sql_basic(preprocess_basic(query))


def example_combination_2(query: str) -> list:
    """Kombinasi 2: SQL LIKE + Preprocessing Advanced"""
    return sql_advanced(preprocess_advanced(query))


def example_combination_3(query: str) -> list:
    """Kombinasi 3: Hybrid Search + Preprocessing Basic"""
    return hybrid_basic(preprocess_basic(query))


def example_combination_4(query: str) -> list:
    """Kombinasi 4: Hybrid Search + Preprocessing Advanced"""
    return hybrid_advanced(preprocess_advanced(query))


# ──────────────────────────────────────────────────────────────────────────────
# ✏️  QUERY — Ganti teks di sini untuk mengubah query pencarian
# ──────────────────────────────────────────────────────────────────────────────
QUERY = "pertanian"   # <── ubah query di sini


# ──────────────────────────────────────────────────────────────────────────────
# Entry point
# ──────────────────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="DEMAKAI Search System",
        formatter_class=argparse.RawTextHelpFormatter,
        epilog=(
            "Contoh:\n"
            "  python main.py --search sql    --processing basic\n"
            "  python main.py --search hybrid --processing advanced --model KBLI\n"
            "  python main.py --all\n"
        ),
    )

    # ── Argumen utama ──────────────────────────────────────────
    parser.add_argument(
        "--search", "-s",
        type=str,
        choices=["sql", "hybrid"],
        help="Metode pencarian: sql | hybrid",
    )
    parser.add_argument(
        "--processing", "-p",
        type=str,
        choices=["none", "basic", "advanced"],
        help="Metode preprocessing: none | basic | advanced",
    )

    # ── Argumen opsional ───────────────────────────────────────
    parser.add_argument(
        "--limit", "-l",
        type=int,
        default=10,
        help="Jumlah maksimum hasil (default: 10)",
    )
    parser.add_argument(
        "--model", "-m",
        type=str,
        default=None,
        choices=["KBLI", "KBJI"],
        help="Filter tabel: KBLI atau KBJI (default: keduanya)",
    )
    parser.add_argument(
        "--all", "-a",
        action="store_true",
        help="Jalankan semua 4 kombinasi sekaligus",
    )
    parser.add_argument(
        "--output", "-o",
        type=str,
        choices=["csv", "html"],
        default=None,
        help="Simpan hasil ke file: csv | html (disimpan di folder output/)",
    )

    args = parser.parse_args()

    # ── Validasi: --search dan --processing wajib jika bukan --all ────────────
    if not args.all and (args.search is None or args.processing is None):
        parser.error(
            "Argumen --search dan --processing wajib diisi.\n"
            "Contoh: python main.py --search sql --processing basic\n"
            "Atau gunakan --all untuk menjalankan semua kombinasi."
        )

    # ── Jalankan dengan QUERY yang sudah ditetapkan di atas ───────────────────
    if args.all:
        all_results = run_all_combinations(query=QUERY, limit=args.limit, model=args.model)
        # ── Ekspor hasil jika --output diberikan ──────────────────────────────
        if args.output == "csv":
            save_to_csv(all_results, query=QUERY)
        elif args.output == "html":
            save_to_html(all_results, query=QUERY)
    else:
        results = run_single_combination(
            query=QUERY,
            search=args.search,
            processing=args.processing,
            limit=args.limit,
            model=args.model,
        )
        # ── Ekspor hasil jika --output diberikan ──────────────────────────────
        if args.output:
            label = f"{args.search.upper()} + {args.processing.capitalize()}"
            all_results = {label: results}
            if args.output == "csv":
                save_to_csv(all_results, query=QUERY)
            elif args.output == "html":
                save_to_html(all_results, query=QUERY)

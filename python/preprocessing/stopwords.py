"""
stopwords.py
------------
Daftar stopword Bahasa Indonesia untuk preprocessing pencarian DEMAKAI.
Mencakup kata fungsi umum dan kata domain KBLI/KBJI yang tidak informatif.
"""

STOPWORDS = {
    # ── Kata kerja / status umum ──────────────────────────────
    "usaha", "jasa", "kerja", "kegiatan", "aktivitas",
    "bekerja", "melakukan", "menjalankan", "bergerak",
    "mengelola", "menyediakan", "mengerjakan", "bertugas",

    # ── Preposisi & konjungsi ─────────────────────────────────
    "di", "ke", "dari", "dan", "yang", "untuk", "dengan",
    "atau", "pada", "dalam", "oleh", "sebagai", "adalah",
    "itu", "ini", "tersebut", "hingga", "sampai", "antara",
    "serta", "juga", "namun", "tetapi", "agar", "supaya",
    "bahwa", "karena", "maka", "jika", "apabila", "bila",
    "seperti", "yakni", "yaitu",

    # ── Kata ganti ────────────────────────────────────────────
    "saya", "kami", "kita", "mereka", "anda", "dia", "ia",

    # ── Artikel / partikel ────────────────────────────────────
    "si", "sang", "para", "pun", "pula", "lah", "kah",
    "nya", "mu",

    # ── Kata umum lain ────────────────────────────────────────
    "tukang", "pedagang", "pekerja", "orang", "pihak",
    "tempat", "bidang", "sektor", "bagian", "unit",
    "lain", "lainnya", "berbagai", "beberapa", "semua",
    "hal", "cara",

    # ── Angka / satuan ────────────────────────────────────────
    "satu", "dua", "tiga", "dll", "dsb", "dsb.",
}

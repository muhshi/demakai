with open('dashboard_skripsi_final.html', encoding='utf-8') as f:
    h = f.read()
checks = [
    ('id="m4"',         'Anchor metode 04'),
    ('Hybrid Search + Preprocessing</h2', 'Judul m4'),
    ('id="m5"',         'Anchor metode 05'),
    ('SQL LIKE + Preprocessing + Contoh Lapangan</h2', 'Judul m5'),
    ('id="m6"',         'Anchor metode 06'),
    ('Hybrid Search + Preprocessing + Contoh Lapangan</h2', 'Judul m6'),
    ('04. Hybrid+Prep', 'Nav link m4'),
    ('05. SQL+Prep+CL', 'Nav link m5'),
    ('06. Hybrid+Prep+CL', 'Nav link m6'),
    ('Kompetitif',      'Status Kompetitif'),
    ('Terbaik',         'Status Terbaik'),
    ('#67e8f9',         'Warna cyan m4'),
]
print('VERIFIKASI dashboard_skripsi_final.html:')
print(f'  Ukuran: {len(h):,} bytes')
for kw, desc in checks:
    n = h.count(kw)
    print(f'  {"OK  " if n else "MISS"} | {desc}: {n}x ({kw!r})')

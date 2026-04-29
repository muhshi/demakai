with open('d:/magang_bps/backend-demakai/python/output/dashboard_v3_komprehensif.html', encoding='utf-8') as f:
    h = f.read()
checks = [
    ('Metode 01', 'SQL LIKE Baseline section'),
    ('Metode 02', 'Hybrid Search section'),
    ('Metode 03', 'SQL LIKE + Preprocessing section'),
    ('Metode 04', 'Hybrid + Preprocessing section'),
    ('Metode 05', 'SQL LIKE + Prep + CL section'),
    ('Metode 06', 'Final method section'),
    ('Sebelum Perbaikan', 'Before-fix stats card'),
    ('Setelah Perbaikan', 'After-fix stats card'),
    ('0.9278', 'After-fix KBLI MRR'),
    ('0.8556', 'After-fix KBJI MRR'),
    ('id="m1"', 'Nav anchor M1'),
    ('id="perbandingan"', 'Nav anchor comparison'),
    ('id="analisis"', 'Nav anchor analysis'),
    ('chart-bar', 'Bar chart elements'),
    ('r1', 'Rank-1 colored cells'),
    ('<table>', 'Table elements'),
]
print(f'HTML size: {len(h):,} bytes')
print()
for kw, desc in checks:
    cnt = h.count(kw)
    status = 'OK   ' if cnt > 0 else 'MISS!'
    print(f'  {status} | {desc}: found {cnt}x')

import csv
from collections import defaultdict

data = defaultdict(lambda: {'queries': 0, 'top1': 0, 'top3': 0, 'top10': 0, 'rr': 0.0,
    'kbli_top1':0,'kbli_top3':0,'kbli_top10':0,'kbli_rr':0.0,'kbli_n':0,
    'kbji_top1':0,'kbji_top3':0,'kbji_top10':0,'kbji_rr':0.0,'kbji_n':0})

with open(r'd:\magang_bps\backend-demakai\python\output\evaluasi_semua_20260420_144133.csv', encoding='utf-8') as f:
    reader = csv.DictReader(f)
    for row in reader:
        m = row['metode']
        t = row['tipe']
        data[m]['queries'] += 1
        data[m]['top1'] += int(row['top1'])
        data[m]['top3'] += int(row['top3'])
        data[m]['top10'] += int(row['top10'])
        rr = float(row['rr'])
        data[m]['rr'] += rr
        if t == 'KBLI':
            data[m]['kbli_n'] += 1
            data[m]['kbli_top1'] += int(row['top1'])
            data[m]['kbli_top3'] += int(row['top3'])
            data[m]['kbli_top10'] += int(row['top10'])
            data[m]['kbli_rr'] += rr
        else:
            data[m]['kbji_n'] += 1
            data[m]['kbji_top1'] += int(row['top1'])
            data[m]['kbji_top3'] += int(row['top3'])
            data[m]['kbji_top10'] += int(row['top10'])
            data[m]['kbji_rr'] += rr

for m, d in data.items():
    n = d['queries']
    nk = d['kbli_n']
    nj = d['kbji_n']
    print(f'--- {m} ---')
    g = f"Gabungan: Top@1={d['top1']/n*100:.1f}% Top@3={d['top3']/n*100:.1f}% Top@10={d['top10']/n*100:.1f}% MRR={d['rr']/n:.4f}"
    print(f'  {g}')
    if nk > 0:
        k = f"KBLI: Top@1={d['kbli_top1']/nk*100:.1f}% Top@3={d['kbli_top3']/nk*100:.1f}% Top@10={d['kbli_top10']/nk*100:.1f}% MRR={d['kbli_rr']/nk:.4f}"
        print(f'  {k}')
    if nj > 0:
        j = f"KBJI: Top@1={d['kbji_top1']/nj*100:.1f}% Top@3={d['kbji_top3']/nj*100:.1f}% Top@10={d['kbji_top10']/nj*100:.1f}% MRR={d['kbji_rr']/nj:.4f}"
        print(f'  {j}')

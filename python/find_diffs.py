import re
import os

def extract_rank(html, query_no, method_prefix):
    # Pola untuk mencari baris query dan kemudian mencari rank di kolom metode tertentu
    # HTML structure: <tr><td>{query_no}</td>...Metode...rank...>{rank}</td>
    
    # Cari blok baris untuk query_no
    pattern_row = rf'<tr><td>{query_no}</td>.*?</tr>'
    row_match = re.search(pattern_row, html, re.DOTALL)
    if not row_match:
        return None
    
    row_content = row_match.group(0)
    
    # Cari rank di dalam kolom metode (SQL Expansion atau Hybrid Expansion)
    # Kita tahu SQL Expansion adalah blok ke-3, Hybrid Expansion adalah blok ke-6
    # Namun lebih aman mencari teks di deskripsi preprocessing
    
    if "SQL" in method_prefix and "Expansion" in method_prefix:
        # SQL Expansion rank is roughly the 13th <td> in the row
        tds = re.findall(r'<td.*?>(.*?)</td>', row_content, re.DOTALL)
        if len(tds) > 15:
            val = re.sub(r'<.*?>', '', tds[15])
            return int(val) if val.isdigit() else 0
            
    if "Hybrid" in method_prefix and "Expansion" in method_prefix:
        # Hybrid Expansion rank is the last <td> block's first <td>
        tds = re.findall(r'<td.*?>(.*?)</td>', row_content, re.DOTALL)
        if len(tds) > 31:
            val = re.sub(r'<.*?>', '', tds[31])
            return int(val) if val.isdigit() else 0
            
    return None

def compare():
    with open('output/evaluasi_sebelum.html', 'r', encoding='utf-8') as f: before = f.read()
    with open('output/evaluasi_sesudah.html', 'r', encoding='utf-8') as f: after = f.read()
    
    queries = [
        (1, "MATON (KBLI)"),
        (2, "MATON (KBJI)"),
        (3, "ETALASE (KBLI)"),
        (4, "ETALASE (KBJI)"),
        (7, "RESOLES (KBLI)"),
        (8, "RESOLES (KBJI)"),
        (37, "BOTOK (KBLI)"),
        (43, "ES TEH (KBLI)"),
        (49, "SIOMAY (KBLI)")
    ]
    
    print(f"{'No':<3} | {'Query':<15} | {'Before':<6} | {'After':<6}")
    print("-" * 40)
    
    for qno, name in queries:
        r_b = extract_rank(before, qno, "Hybrid Expansion")
        r_a = extract_rank(after, qno, "Hybrid Expansion")
        print(f"{qno:<3} | {name:<15} | {r_b:<6} | {r_a:<6}")

if __name__ == "__main__":
    compare()

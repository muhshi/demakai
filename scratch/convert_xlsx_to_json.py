import pandas as pd
import json
import re
import os

def clean_query(q):
    if not isinstance(q, str):
        return ""
    # Remove excessive whitespace and lowercase
    q = " ".join(q.split()).lower()
    return q

def extract_code(s):
    if not isinstance(s, str):
        return ""
    match = re.search(r'\[(\d+)\]', s)
    if match:
        return match.group(1)
    return ""

try:
    df = pd.read_excel('KBLI KBJI.xlsx')

    kbli_examples = []
    kbji_examples = []

    for idx, row in df.iterrows():
        query = row.get('Query', '')
        kbli_str = row.get('KBLI', '')
        kbji_str = row.get('KBJI', '')
        
        kbli_code = extract_code(kbli_str)
        kbji_code = extract_code(kbji_str)
        
        cleaned_q = clean_query(query)
        
        if not cleaned_q:
            continue

        if kbli_code:
            kbli_examples.append({
                "code": kbli_code,
                "example": f"Kegiatan {cleaned_q}"
            })
            
        if kbji_code:
            kbji_examples.append({
                "code": kbji_code,
                "example": f"Deskripsi tugas: {cleaned_q}"
            })

    output = {
        "kbli_examples": kbli_examples,
        "kbji_examples": kbji_examples
    }

    os.makedirs('database/data', exist_ok=True)
    with open('database/data/kbli_kbji_examples.json', 'w') as f:
        json.dump(output, f, indent=2)

    print(f"Successfully generated database/data/kbli_kbji_examples.json with {len(kbli_examples)} KBLI and {len(kbji_examples)} KBJI examples.")

except Exception as e:
    print(f"Error during conversion: {e}")

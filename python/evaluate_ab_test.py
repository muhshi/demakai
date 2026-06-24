import os
import sys
import csv

sys.path.insert(0, os.path.dirname(__file__))

from preprocessing.advanced import preprocess_advanced
from preprocessing.expansion import preprocess_expansion
from search import hybrid

def get_rank(results, kode_gt):
    for i, item in enumerate(results, start=1):
        if str(item.get("kode", "")).strip() == str(kode_gt).strip():
            return i
    return 0

def evaluate_method(queries, method_func, preprocessor, use_cl):
    hybrid.USE_CL = use_cl
    results = []
    
    for entry in queries:
        query = entry.get("query", "").strip()
        kode_gt = entry.get("kode_ground_truth", "").strip()
        
        # Preprocessing
        if preprocessor == 'advanced':
            prep = preprocess_advanced(query)
            keywords = ", ".join(prep.get("stemmed_tokens", []))
        elif preprocessor == 'expansion':
            prep = preprocess_expansion(query)
            keywords = ", ".join(prep.get("expanded_tokens", []))
            if prep.get("kbli_variations"):
                keywords += "<br><span class='badge-vars'>Vars: " + ", ".join(prep["kbli_variations"]) + "</span>"
        else:
            prep = query # raw
            keywords = query
            
        # Execute search
        try:
            res = method_func(prep, limit=10, model="KBLI")
            rank = get_rank(res, kode_gt)
            rr = 1.0 / rank if rank > 0 else 0.0
        except Exception as e:
            print(f"Error pada query '{query}': {e}")
            rr = 0.0
            
        results.append({
            "query": query,
            "kode_gt": kode_gt,
            "keywords": keywords,
            "rr": rr
        })
        
    mrr = sum(r["rr"] for r in results) / len(results) if results else 0.0
    return mrr, results

def run_comprehensive_test(queries_file=None):
    if queries_file is None:
        queries_file = os.path.join(os.path.dirname(__file__), "queries.csv")
    if not os.path.exists(queries_file):
        print(f"[ERROR] {queries_file} tidak ditemukan.")
        return

    with open(queries_file, newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        queries = [q for q in list(reader) if q.get("tipe", "").strip().upper() == "KBLI"]

    print(f"Menjalankan evaluasi komprehensif pada {len(queries)} kueri KBLI...")

    scenarios = [
        {"name": "M1: Raw (Tanpa Contoh Lapangan)", "func": hybrid.search_raw, "prep": "raw", "use_cl": False},
        {"name": "M2: Advanced (Tanpa Contoh Lapangan)", "func": hybrid.search_advanced, "prep": "advanced", "use_cl": False},
        {"name": "M3: Expansion (Tanpa Contoh Lapangan)", "func": hybrid.search_expansion, "prep": "expansion", "use_cl": False},
        {"name": "M4: Raw (Dengan Contoh Lapangan)", "func": hybrid.search_raw, "prep": "raw", "use_cl": True},
        {"name": "M5: Advanced (Dengan Contoh Lapangan)", "func": hybrid.search_advanced, "prep": "advanced", "use_cl": True},
        {"name": "M6: Expansion (Dengan Contoh Lapangan)", "func": hybrid.search_expansion, "prep": "expansion", "use_cl": True},
    ]

    results_mrr = []
    detailed_results = {}
    
    print("\n=== HASIL EVALUASI ===")
    for sc in scenarios:
        mrr, res_list = evaluate_method(queries, sc["func"], sc["prep"], sc["use_cl"])
        results_mrr.append({"name": sc["name"], "mrr": mrr})
        
        # Simpan detail untuk M3 dan M6 buat dibandingkan di tabel HTML
        if sc["name"].startswith("M3"):
            detailed_results["m3"] = res_list
        if sc["name"].startswith("M6"):
            detailed_results["m6"] = res_list

        print(f"{sc['name']:<45} : {mrr:.4f}")

    # --- EXPORT TO CSV ---
    os.makedirs("output", exist_ok=True)
    csv_file = "output/comprehensive_test_results.csv"
    with open(csv_file, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["Metode", "MRR"])
        for r in results_mrr:
            writer.writerow([r["name"], f'{r["mrr"]:.4f}'])
    
    # --- EXPORT TO HTML ---
    html_file = "output/comprehensive_test_results.html"
    html_content = f"""
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sensus Ekonomi 2026 | Evaluasi DEMAKAI</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root {{
                --bg-main: #f4f7f6;
                --bg-card: #ffffff;
                --text-main: #1f2937;
                --text-muted: #6b7280;
                --accent: #f97316; /* Orange SE2026 */
                --accent-dark: #c2410c;
                --accent-glow: rgba(249, 115, 22, 0.1);
                --danger: #ef4444;
                --danger-bg: rgba(239, 68, 68, 0.1);
                --success: #10b981;
                --success-bg: rgba(16, 185, 129, 0.1);
                --border: #e5e7eb;
            }}
            body {{
                font-family: 'Inter', sans-serif;
                background-color: var(--bg-main);
                color: var(--text-main);
                margin: 0;
                padding: 40px 20px;
                line-height: 1.6;
            }}
            .container {{
                max-width: 1200px;
                margin: 0 auto;
            }}
            .header {{
                text-align: center;
                margin-bottom: 50px;
            }}
            .header h1 {{
                font-size: 2.5rem;
                font-weight: 800;
                margin-bottom: 10px;
                color: var(--accent);
                letter-spacing: -0.02em;
            }}
            .header p {{
                color: var(--text-muted);
                font-size: 1.1rem;
                font-weight: 500;
            }}
            .card {{
                background: var(--bg-card);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 30px;
                margin-bottom: 40px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            }}
            .card h2 {{
                margin-top: 0;
                font-size: 1.5rem;
                border-bottom: 2px solid var(--accent-glow);
                padding-bottom: 15px;
                margin-bottom: 25px;
                color: var(--accent-dark);
            }}
            table {{
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                text-align: left;
            }}
            th, td {{
                padding: 16px;
                border-bottom: 1px solid var(--border);
            }}
            th {{
                color: var(--text-muted);
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.85rem;
                letter-spacing: 0.05em;
                background-color: #f9fafb;
            }}
            th:first-child {{
                border-top-left-radius: 8px;
            }}
            th:last-child {{
                border-top-right-radius: 8px;
            }}
            tr {{
                transition: background-color 0.2s ease;
            }}
            tbody tr:hover {{
                background-color: var(--accent-glow);
            }}
            .pill {{
                display: inline-block;
                padding: 6px 12px;
                border-radius: 9999px;
                font-size: 0.85rem;
                font-weight: 600;
            }}
            .pill-danger {{
                background-color: var(--danger-bg);
                color: var(--danger);
            }}
            .pill-success {{
                background-color: var(--success-bg);
                color: var(--success);
            }}
            .pill-neutral {{
                background-color: #f3f4f6;
                color: var(--text-muted);
            }}
            .keyword-box {{
                background-color: #f1f5f9;
                border: 1px solid #cbd5e1;
                padding: 8px 12px;
                border-radius: 8px;
                font-family: 'Courier New', Courier, monospace;
                font-size: 0.9rem;
                color: #334155;
            }}
            .badge-vars {{
                display: inline-block;
                margin-top: 6px;
                font-size: 0.75rem;
                background-color: #ffedd5;
                color: #c2410c;
                padding: 3px 8px;
                border-radius: 4px;
                font-weight: 500;
            }}
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>DEMAKAI Evaluation Report</h1>
                <p>A/B Testing Signifikansi "Contoh Lapangan" pada Sensus Ekonomi 2026</p>
            </div>
            
            <div class="card">
                <h2>Ringkasan Kinerja (MRR)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Metode & Preprocessing</th>
                            <th>Status Fitur</th>
                            <th>Mean Reciprocal Rank (MRR)</th>
                        </tr>
                    </thead>
                    <tbody>
"""
    for r in results_mrr:
        is_with = "Dengan" in r["name"]
        status_html = '<span class="pill pill-success">Digunakan (Active)</span>' if is_with else '<span class="pill pill-neutral">Tidak Digunakan</span>'
        mrr_pill = "pill-success" if r["mrr"] > 0.7 else ("pill-danger" if r["mrr"] < 0.35 else "pill-neutral")
        html_content += f"""
                        <tr>
                            <td style="font-weight: 600; color: #1f2937;">{r["name"].replace(" (Dengan Contoh Lapangan)", "").replace(" (Tanpa Contoh Lapangan)", "")}</td>
                            <td>{status_html}</td>
                            <td><span class="pill {mrr_pill}">{r["mrr"]:.4f}</span></td>
                        </tr>
"""
    html_content += """
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Analisis Kueri (Metode Query Expansion)</h2>
                <p style="color: var(--text-muted); margin-bottom: 20px;">Membandingkan hasil performa kueri spesifik saat fitur Contoh Lapangan Tidak Digunakan versus Digunakan, beserta visualisasi keyword extraction.</p>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Kueri Asli (Input Petugas)</th>
                            <th style="width: 35%;">Keywords Extracted</th>
                            <th style="width: 10%;">Ground Truth</th>
                            <th style="width: 15%;">RR (Tanpa CL)</th>
                            <th style="width: 15%;">RR (Dengan CL)</th>
                        </tr>
                    </thead>
                    <tbody>
"""
    
    if "m3" in detailed_results and "m6" in detailed_results:
        m3_list = detailed_results["m3"]
        m6_list = detailed_results["m6"]
        for idx in range(len(m3_list)):
            q_asli = m3_list[idx]["query"]
            k_gt = m3_list[idx]["kode_gt"]
            keywords = m3_list[idx]["keywords"]
            rr_m3 = m3_list[idx]["rr"]
            rr_m6 = m6_list[idx]["rr"]
            
            p_m3 = "pill-danger" if rr_m3 < 1 else "pill-success"
            if 0 < rr_m3 < 1: p_m3 = "pill-neutral"
            
            p_m6 = "pill-danger" if rr_m6 < 1 else "pill-success"
            if 0 < rr_m6 < 1: p_m6 = "pill-neutral"
            
            html_content += f"""
                        <tr>
                            <td style="color: #111827; font-weight: 500;">{q_asli}</td>
                            <td><div class="keyword-box">{keywords}</div></td>
                            <td><span style="font-weight: 700; color: #ea580c;">{k_gt}</span></td>
                            <td><span class="pill {p_m3}">{rr_m3:.2f}</span></td>
                            <td><span class="pill {p_m6}">{rr_m6:.2f}</span></td>
                        </tr>
            """
            
    html_content += """
                    </tbody>
                </table>
            </div>
        </div>
    </body>
    </html>
"""
    with open(html_file, "w", encoding="utf-8") as f:
        f.write(html_content)

    print(f"\n[SUKSES] Hasil evaluasi komprehensif telah diekspor ke:")
    print(f" - CSV : {csv_file}")
    print(f" - HTML: {html_file}")

if __name__ == "__main__":
    run_comprehensive_test()

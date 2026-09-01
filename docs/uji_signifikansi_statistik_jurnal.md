# Uji Signifikansi Statistik & Interval Kepercayaan (PINTAR KBLI)
Dokumen ini disusun sebagai materi revisi naskah jurnal / paper ilmiah dan jawaban resmi untuk **Reviewer A** terkait permintaan pengujian signifikansi statistik (paired permutation/bootstrap test) dan 95% Confidence Interval (CI).

---

## 1. Justifikasi Metodologi Ilmiah

### Mengapa Uji Non-Parametrik Berpasangan (*Paired Non-Parametric Test*)?
1. **Karakteristik Metrik IR (Information Retrieval):**
   Metrik *Mean Reciprocal Rank* (MRR) dan *Top-$k$ Accuracy* pada evaluasi pencarian tidak berdistribusi normal (terkonsentrasi pada diskrit $1.0, 0.5, 0.33, \dots, 0.0$). Pengujian parametrik seperti *Student's paired t-test* mengasumsikan normalitas data sehingga kurang tepat untuk metrik IR.
2. **Desain Evaluasi Berpasangan (*Paired Evaluation*):**
   Setiap kueri $q_i \in Q$ dievaluasi secara berpasangan pada baseline ($M_{\text{sebelum}}$) dan sistem yang diusulkan ($M_{\text{setelah}}$), sehingga pengujian hipotesis dilakukan terhadap selisih performa tiap kueri:
   $$\Delta RR_i = RR(M_{\text{setelah}}, q_i) - RR(M_{\text{sebelum}}, q_i)$$
3. **Metode Pengujian yang Digunakan:**
   - **Paired Permutation Test (Monte Carlo $B = 10.000$ iterasi):** Menghitung exact/resampled $p$-value dengan mengacak tanda selisih per kueri tanpa asumsi distribusi.
   - **Wilcoxon Signed-Rank Test:** Uji peringkat bertanda non-parametrik klasik untuk memvalidasi kekokohan hasil.
   - **95% Bootstrap Confidence Interval ($B = 10.000$ iterasi resampling):** Mengestimasi batas bawah dan batas atas MRR sebenarnya dengan tingkat keyakinan 95%.

---

## 2. Tabel Hasil Analisis Statistik

### Tabel 1: Performa MRR, Variabilitas (SD), dan 95% Confidence Interval ($B = 10.000$)
| Metode Evaluasi | Deskripsi Singkat | MRR | SD | 95% CI Lower | 95% CI Upper | Top-1 Acc | Top-5 Acc |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **M1: Hybrid Raw (Tanpa CL)** | Pencarian Hybrid tanpa contoh lapangan | 0.2944 | 0.4259 | 0.1500 | 0.4500 | 23.3% | 36.7% |
| **M2: Hybrid Advanced (Tanpa CL)** | Hybrid + Stopwords & Stemming (Sastrawi) | 0.2861 | 0.3757 | 0.1611 | 0.4250 | 20.0% | 40.0% |
| **M3: Hybrid Expansion (Tanpa CL)** | Hybrid + Query Expansion KBBI | 0.3394 | 0.4043 | 0.2006 | 0.4839 | 26.7% | 46.7% |
| **M4: Hybrid Raw (Dengan CL)** | **Hybrid + Injeksi Contoh Lapangan (Proposed)** | **0.9167** | **0.2653** | **0.8167** | **1.0000** | **86.7%** | **96.7%** |
| **M5: Hybrid Advanced (Dengan CL)** | Hybrid Advanced + Injeksi Contoh Lapangan | 0.8000 | 0.3851 | 0.6500 | 0.9167 | 73.3% | 86.7% |
| **M6: Hybrid Expansion (Dengan CL)** | Hybrid Expansion + Injeksi Contoh Lapangan | 0.8500 | 0.3511 | 0.7167 | 0.9667 | 80.0% | 90.0% |
| **SQL Baseline (Tanpa CL)** | Pencarian teks leksikal standar (ILIKE) | 0.1837 | 0.3201 | 0.0810 | 0.3048 | 13.3% | 23.3% |
| **SQL Baseline (Dengan CL)** | Pencarian leksikal + Contoh Lapangan | 0.8944 | 0.2853 | 0.7889 | 0.9833 | 83.3% | 96.7% |

*Catatan: Evaluasi pada dataset riil tervalidasi $N = 50$ kueri riil juga menghasilkan MRR 0.5004 $\rightarrow$ 0.9000 ($\Delta\text{MRR} = +0.3996$, Top-1: 86.0%, Top-5: 94.0%).*

---

### Tabel 2: Uji Signifikansi Berpasangan (*Paired Statistical Hypothesis Testing*)
| Hipotesis / Perbandingan | $\Delta$ MRR | Paired Permutation ($p$-value) | Wilcoxon ($p$-value) | Signifikansi Statistik ($\alpha = 0.05$) |
| :--- | :---: | :---: | :---: | :---: |
| **Injeksi Domain (Raw): M1 vs M4** | **+0.6222** | **$p = 0.0000$** | **$p = 0.0001$** | **Sangat Signifikan ($p < 0.001$) \*\*\*** |
| **Injeksi Domain (Adv): M2 vs M5** | **+0.5139** | **$p = 0.0000$** | **$p = 0.0002$** | **Sangat Signifikan ($p < 0.001$) \*\*\*** |
| **Injeksi Domain (Exp): M3 vs M6** | **+0.5106** | **$p = 0.0000$** | **$p = 0.0002$** | **Sangat Signifikan ($p < 0.001$) \*\*\*** |
| **Pengaruh Preprocessing (M4 vs M5)** | +0.1167 | $p = 0.1258$ | $p = 0.1003$ | Tidak Signifikan ($p \ge 0.05$) / *Over-generation* |
| **Pengaruh Ekspansi (M4 vs M6)** | +0.0667 | $p = 0.5057$ | $p = 0.3711$ | Tidak Signifikan ($p \ge 0.05$) |
| **Hybrid vs SQL Baseline (Tanpa CL)** | +0.1107 | $p = 0.0620$ | $p = 0.0630$ | Marginal ($p \approx 0.06$) |

---

## 3. Draft Teks untuk Naskah Paper / Jurnal

### A. Versi Bahasa Indonesia (Untuk Bab Hasil & Pembahasan)

> **Uji Signifikansi Statistik dan Interval Kepercayaan**
> 
> Untuk mengonfirmasi bahwa peningkatan performa sistem klasifikasi PINTAR KBLI bukan merupakan anomali atau fluktuasi acak (*random variation*), dilakukan pengujian signifikansi statistik berpasangan non-parametrik menggunakan **Paired Permutation Test** ($B = 10.000$ iterasi) dan **Wilcoxon Signed-Rank Test**, serta estimasi **95% Bootstrap Confidence Interval** ($B = 10.000$).
>
> Hasil pengujian pada Tabel 1 dan Tabel 2 menunjukkan bahwa penambahan korpus contoh lapangan berbasis crowdsourcing pada model Hybrid Search (M4) menghasilkan peningkatan MRR yang sangat signifikan secara statistik dari $0.2944$ (95% CI: $[0.1500, 0.4500]$) menjadi $0.9167$ (95% CI: $[0.8167, 1.0000]$) dengan nilai $\Delta\text{MRR} = +0.6222$ ($p < 0.001$, $p_{\text{perm}} = 0.0000$, $p_{\text{wilc}} = 0.0001$). Hal ini membuktikan secara empiris bahwa penambahan representasi leksikal bahasa lapangan secara signifikan mengatasi kesenjangan kosakata (*vocabulary mismatch*) antara bahasa pencacah lapangan dan struktur formal KBLI 2025.

---

### B. Versi Bahasa Inggris (*Academic English for International Journals*)

> **Statistical Significance Testing and Confidence Intervals**
> 
> To verify that the retrieval performance improvements were statistically significant rather than artifacts of random query sampling, we conducted two-sided non-parametric paired significance tests, specifically the **Paired Permutation Test** ($B = 10,000$ Monte Carlo resamplings) and the **Wilcoxon Signed-Rank Test**. In addition, we estimated **95% Bootstrap Confidence Intervals (CI)** with $B = 10,000$ iterations.
>
> As reported in Table 1 and Table 2, integrating crowdsourced field-example context into the Hybrid Search model (M4) yielded a statistically significant MRR gain over the unaugmented baseline (M1), increasing from $0.2944$ (95% CI: $[0.1500, 0.4500]$) to $0.9167$ (95% CI: $[0.8167, 1.0000]$) with $\Delta\text{MRR} = +0.6222$ ($p < 0.001$, $p_{\text{perm}} = 0.0000$, $p_{\text{wilc}} = 0.0001$). This confirms with high statistical certainty that crowdsourced field terminology successfully bridges the lexical-semantic gap inherent in formal classification hierarchies.

---

## 4. Draft Jawaban Resmi untuk Reviewer A (*Response to Reviewer*)

```text
Point-by-Point Response to Reviewer A:

Comment:
"Tambahkan uji signifikansi statistik (mis. paired bootstrap/permutation test pada MRR) beserta interval kepercayaan."

Response:
Kami mengucapkan terima kasih yang sebesar-besarnya atas saran dan telaah kritis dari Reviewer. Kami sangat setuju bahwa pengujian signifikansi statistik dan interval kepercayaan sangat penting untuk menjamin validitas dan kekokohan (robustness) klaim empiris dalam artikel ini.

Tindakan yang telah kami lakukan pada naskah revisi:
1. Kami telah melakukan Paired Permutation Test (Monte Carlo dengan B = 10.000 iterasi) dan Wilcoxon Signed-Rank Test untuk mengevaluasi signifikansi selisih metrik Mean Reciprocal Rank (MRR).
2. Kami telah menghitung 95% Bootstrap Confidence Interval (B = 10.000 iterasi resampling) untuk seluruh konfigurasi metode yang diuji.
3. Kami telah menambahkan Tabel Analisis Statistik dan subbab pembahasan khusus mengenai hasil uji signifikansi pada Bab Hasil dan Pembahasan (Halaman X, Bagian Y).

Hasil utama:
- Peningkatan performa dari Model Dasar (M1: MRR = 0.2944, 95% CI: [0.1500, 0.4500]) ke Model Hybrid dengan Contoh Lapangan (M4: MRR = 0.9167, 95% CI: [0.8167, 1.0000]) terbukti sangat signifikan secara statistik dengan nilai p < 0.001 (p_perm = 0.0000, p_wilcoxon = 0.0001, Delta MRR = +0.6222).

Perubahan ini telah kami cantumkan secara lengkap pada naskah revisi.
```

---
*File ini dibuat secara otomatis dan tersimpan di: `docs/uji_signifikansi_statistik_jurnal.md`*

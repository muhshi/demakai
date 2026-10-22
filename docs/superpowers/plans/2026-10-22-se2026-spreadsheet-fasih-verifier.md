# SE2026 KBLI Merged Google Spreadsheet & 5-Assignment Verifier Web App Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Merge the two SE2026 KBLI anomaly datasets (14,246 rows) with enriched PINTAR KBLI recommendations into a new Google Spreadsheet on the user's Google Drive folder, and deploy a Google Apps Script 5-assignment batch verifier interface with auto-filling default recommendations, Fasih linking, and change-detection status tracking.

**Architecture:** 
1. **Dataset Consolidation & Google Drive Upload:**
   - Combine the resolved datasets (`sqllab_kbli_rekomendasi_20261020T095230` and `sqllab_kbli_rekomendasi_20261020T095247`, 14,246 rows) with enriched columns (`judul_kbli_saat_ini`, `rekomendasi_1`, `confidence_1`, `rekomendasi_2`, `rekomendasi_3`, `link_fasih_sm`).
   - Create and populate a native Google Spreadsheet inside Google Drive folder `1mPNovNWjyDYIjBGO0i-TEuifcKqxJi6a` via Drive/Sheets API using authenticated `.clasprc.json` credentials.
2. **Google Apps Script Backend (`Code.gs`):**
   - Read 5 active/uncompleted assignment records matching the user's filter (district/status).
   - Atomic lock/claim handling to ensure multi-user safety across teams.
   - Batch update function: saves completion status, detects whether the verified KBLI matches `rekomendasi_1` (sets status to `"KBLI Sudah Benar Sesuai Rekomendasi 1"`) or was modified (sets status to `"KBLI Diubah ke: [KODE]"`).
3. **Frontend Batch Verification UI (`Index.html`):**
   - **Petugas Name Onboarding Flow**:
     - Check `localStorage` for `se2026_petugas_name`. If empty, prompt a mandatory modal: *"Masukkan Nama Anda (Petugas Verifikator)"*.
     - Header profile badge: shows current active verifier name with a 1-click button to edit/switch user.
     - Ensures every single assignment submission records the verifier's identity for performance monitoring.
   - **Responsive Multi-Card Batch View (5 Assignments per Screen)**:
     - Displays: Nama Usaha, Uraian Kegiatan, KBLI Sekarang (kode & judul), Rekomendasi 1-3.
     - Default-filled editable input for "KBLI yang Benar" (pre-populated with Rekomendasi 1).
     - "Buka Fasih" button (opens direct assignment edit URL in a new tab).
     - "Selesai" button per assignment with instant feedback and row status persistence.
     - Filter by Kecamatan (Demak 14 districts) and "Muat 5 Data Berikutnya" pagination.
   - **Monitoring & Rekap Progres Petugas Tab/View**:
     - Provides summary dashboard: Total data, Total selesai, Sisa, and leaderboard/table of completions per petugas (`byPetugas`).

**Tech Stack:** Google Apps Script (V8 runtime, HTML Service), Google Drive & Sheets API, Python 3.10+ (OAuth & data preparation), Pandas.

**Spec:** User requirements for Google Drive folder `https://drive.google.com/drive/u/0/folders/1mPNovNWjyDYIjBGO0i-TEuifcKqxJi6a`.

## Global Constraints

- Never drop, truncate, or delete any database tables or existing data.
- Never commit, push, or modify the `Jurnal/` directory.
- Preserve all existing public API contracts and search engine improvements.
- Project identity is **PINTAR KBLI** (focus on KBLI 2025 for Sensus Ekonomi 2026, hybrid search, crowdsourcing, and user-friendly experience).

## Review Focus

- Merged dataset must contain all 14,246 records without dropping any rows or corrupting leading zeros in KBLI codes (e.g. `01499` must not become `1499`).
- When "KBLI yang Benar" is NOT changed, the spreadsheet must record status `"KBLI Sudah Benar Sesuai Rekomendasi 1"`.
- When "KBLI yang Benar" IS changed, the spreadsheet must record status `"KBLI Diubah ke: [KODE]"` with the new code written to `kbli_final_benar`.
- Fasih SM URLs must be properly formatted to open direct edit pages (`.../assignment/{id}/edit`).
- UI must render 5 assignments per batch with clear visual states (pending, in-progress, completed).

---

### Task 1: Dataset Consolidation & CSV Preparation

**Files:**
- Create: `python/utils/prepare_merged_se2026_sheet.py`
- Test: `python/tests/test_merged_se2026_dataset.py`

**Interfaces:**
- Consumes: Resolved Excel files in `storage/app/se2026_resolved/resolved_sqllab_kbli_rekomendasi_*.xlsx`.
- Produces: `storage/app/se2026_resolved/merged_se2026_cleaning_dataset.csv` with 26 formatted columns and padded KBLI codes.

- [ ] **Step 1: Write unit test for dataset merging and column consistency**

```python
# python/tests/test_merged_se2026_dataset.py
import unittest
import os
import pandas as pd

class TestMergedDataset(unittest.TestCase):
    def test_merged_csv_exists_and_has_all_rows(self):
        csv_path = 'storage/app/se2026_resolved/merged_se2026_cleaning_dataset.csv'
        self.assertTrue(os.path.exists(csv_path), "Merged CSV must exist")
        df = pd.read_csv(csv_path, dtype=str)
        self.assertEqual(len(df), 14246, "Merged CSV must have exactly 14,246 rows")
        expected_cols = [
            'no', 'status_pengerjaan', 'status_kbli', 'kbli_final_benar', 'petugas_verifikasi', 'waktu_selesai',
            'assignment_id', 'no_usaha', 'kecamatan', 'desa', 'kode_sls', 'petugas_lapangan',
            'nama_usaha', 'uraian_kegiatan', 'kbli_saat_ini', 'judul_kbli_saat_ini',
            'rekomendasi_1', 'confidence_1', 'rekomendasi_2', 'confidence_2', 'rekomendasi_3', 'confidence_3',
            'rule_terpicu', 'rekomendasi_tindakan', 'alasan_rekomendasi', 'link_fasih_sm'
        ]
        for col in expected_cols:
            self.assertIn(col, df.columns, f"Column {col} must exist in merged dataset")
```

- [ ] **Step 2: Run test to verify it fails**

Run: `python -m unittest python/tests/test_merged_se2026_dataset.py`
Expected: FAIL (file not found).

- [ ] **Step 3: Implement `python/utils/prepare_merged_se2026_sheet.py`**

Read both resolved files from `storage/app/se2026_resolved/`, pad KBLI codes to 5-digit strings, insert initial workflow tracking columns (`status_pengerjaan` = "Belum Dikerjakan", `status_kbli` = "Belum Diverifikasi", `kbli_final_benar` = empty, `petugas_verifikasi` = empty, `waktu_selesai` = empty), and export to `merged_se2026_cleaning_dataset.csv`.

- [ ] **Step 4: Run test to verify it passes**

Run: `python python/utils/prepare_merged_se2026_sheet.py`
Run: `python -m unittest python/tests/test_merged_se2026_dataset.py`
Expected: PASS (14,246 rows verified).

- [ ] **Step 5: Commit changes**

```bash
git add python/utils/prepare_merged_se2026_sheet.py python/tests/test_merged_se2026_dataset.py
git commit -m "feat(se2026): create merged dataset preparation script for Google Spreadsheet"
```

---

### Task 2: Create Google Spreadsheet in Target Drive Folder

**Files:**
- Create: `python/utils/upload_google_spreadsheet.py`

**Interfaces:**
- Consumes: `merged_se2026_cleaning_dataset.csv` and Google Drive folder ID `1mPNovNWjyDYIjBGO0i-TEuifcKqxJi6a`.
- Produces: Google Spreadsheet titled `PINTAR_KBLI_SE2026_VERIFIKASI_DEMAK` in target folder, returning `spreadsheet_id`.

- [ ] **Step 1: Implement `python/utils/upload_google_spreadsheet.py`**

Using `Credentials` from `~/.clasprc.json`, upload `merged_se2026_cleaning_dataset.csv` with `mimeType='application/vnd.google-apps.spreadsheet'` into parent folder `1mPNovNWjyDYIjBGO0i-TEuifcKqxJi6a`. Save generated `spreadsheet_id` to `storage/app/se2026_resolved/spreadsheet_metadata.json`.

- [ ] **Step 2: Execute upload and verify creation**

Run: `python python/utils/upload_google_spreadsheet.py`
Expected: Outputs valid Google Spreadsheet ID and confirms 14,246 rows uploaded into folder `1mPNovNWjyDYIjBGO0i-TEuifcKqxJi6a`.

- [ ] **Step 3: Commit changes**

```bash
git add python/utils/upload_google_spreadsheet.py
git commit -m "feat(se2026): add automated Google Spreadsheet creation and data upload script"
```

---

### Task 3: Develop 5-Assignment Google Apps Script (`Code.gs`)

**Files:**
- Create: `KBLI SE2026/Code_5Baris.gs`

**Interfaces:**
- Consumes: Target Google Spreadsheet sheet data.
- Produces: Server functions:
  - `get5Assignments(sheetName, userName, kecFilter, startIndex)`: returns 5 uncompleted assignments with all fields.
  - `submitAssignment(sheetName, rowIndex, userName, finalKbli, originalRec1)`: updates row in sheet:
    - If `finalKbli === originalRec1`: sets `status_kbli = 'KBLI Sudah Benar Sesuai Rekomendasi 1'`.
    - If `finalKbli !== originalRec1`: sets `status_kbli = 'KBLI Diubah ke: ' + finalKbli`.
    - Sets `status_pengerjaan = 'Selesai'`, `kbli_final_benar = finalKbli`, `petugas_verifikasi = userName`, `waktu_selesai = now`.
  - `getBatchSummary(sheetName, kecFilter)`: returns counts (total, selesai, sisa).

- [ ] **Step 1: Write `KBLI SE2026/Code_5Baris.gs`**

Implement optimized, batch-safe Google Apps Script functions with lock service protection and case-insensitive KBLI comparison. Ensure `submitAssignment` explicitly records `petugas_verifikasi = userName` and `waktu_selesai = Utilities.formatDate(new Date(), "Asia/Jakarta", "yyyy-MM-dd HH:mm:ss")`. Include `getMonitoringData()` function aggregating progress per petugas (`byPetugas`).

- [ ] **Step 2: Commit Code_5Baris.gs**

```bash
git add KBLI\ SE2026/Code_5Baris.gs
git commit -m "feat(gas): implement 5-assignment batch verifier backend logic in Code_5Baris.gs"
```

---

### Task 4: Develop Modern 5-Assignment Batch Verifier UI (`Index_5Baris.html`)

**Files:**
- Create: `KBLI SE2026/Index_5Baris.html`

**Interfaces:**
- Consumes: Data from `get5Assignments` via `google.script.run`.
- Produces: Interactive web view rendering 5 assignments:
  - Business name badge, SLS & village location.
  - Full narrative text (`uraian_kegiatan`).
  - Current KBLI badge with title.
  - Recommended KBLI chips (Rekomendasi 1, 2, 3 with confidence).
  - Pre-filled input field "KBLI yang Benar" (defaulting to Rekomendasi 1 code).
  - "🔗 Buka Fasih" button (direct URL `/assignment/{id}/edit`).
  - "✅ Selesai" button: submits result, shows checkmark badge, and transitions row.
  - District filter (Demak 14 districts) and "Muat 5 Data Berikutnya" pagination.

- [ ] **Step 1: Create `KBLI SE2026/Index_5Baris.html`**

Build clean, accessible, modern UI (Tailwind-like vanilla CSS) matching PINTAR KBLI blue-indigo styling with:
1. Name prompt modal on initial launch with `localStorage` persistence.
2. User profile pill in top navigation bar displaying active verifier name and change name modal.
3. 5-card assignment grid with live status update.
4. Monitoring & leaderboard tab displaying verified count per petugas.

- [ ] **Step 2: Commit Index_5Baris.html**

```bash
git add KBLI\ SE2026/Index_5Baris.html
git commit -m "feat(gas): build 5-assignment batch verifier UI with auto-default KBLI input in Index_5Baris.html"
```

---

### Task 5: Deploy Apps Script Project Bound to Target Spreadsheet

**Files:**
- Create: `python/utils/deploy_apps_script.py`

**Interfaces:**
- Consumes: `spreadsheet_id`, `Code_5Baris.gs`, `Index_5Baris.html`.
- Produces: Google Apps Script project bound to the newly created Google Spreadsheet, deploying container-bound code and enabling custom menu `⚡ PINTAR KBLI > 📋 Buka Verifikator 5 Baris`.

- [ ] **Step 1: Implement `python/utils/deploy_apps_script.py`**

Create bound script project via Apps Script API (`parentId: spreadsheet_id`) and upload `Code.gs` and `Index.html` content with manifest V8 runtime and Jakarta timezone.

- [ ] **Step 2: Run deployment script and verify**

Run: `python python/utils/deploy_apps_script.py`
Expected: Bound script project created, files uploaded, returns Web App URL and Spreadsheet URL.

- [ ] **Step 3: Commit changes and update README**

```bash
git add python/utils/deploy_apps_script.py README.md
git commit -m "feat(gas): deploy container-bound Apps Script to merged SE2026 spreadsheet"
git push origin main
```

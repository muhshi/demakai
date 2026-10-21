# Long-Sentence & Narrative Activity Search Optimization Implementation Plan (SE2026 Focused)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable PINTAR KBLI to accurately classify long, narrative field descriptions without losing context (e.g., distinguishing "jual pentol keliling dengan sepeda motor" as food retail/preparation rather than motor dealer/repair), and provide an automated batch processor for the SE2026 anomaly datasets (`KBLI SE2026/*.xlsx`) that generates structured Excel reports with KBLI titles and new validated recommendations.

**Architecture:** 
1. **Context-Preserving NLP Pipeline:** 
   - Replace unordered subset token matching in `preprocess_expansion()` with strict adjacent n-gram phrase matching to prevent spurious cross-clause triggers (e.g. "jual ... motor" triggering motorbike dealer).
   - Implement Instrumental Modifier Detection: identify tools/vehicles preceded by prepositions ("dengan", "menggunakan", "pada sepeda motor") as secondary context rather than the core commodity.
   - Core Activity & Commodity Extractor: identify primary action + primary commodity ("jual pentol keliling") for high-precision vector and keyword lookup.
2. **Search Engine Scoring Relaxation:** 
   - Dynamically transition from strict AND (for <= 3 words) to weighted token coverage scoring (for > 3 words) in `SearchService.php` and Python search.
   - Category-constrained scoring (filtering / boosting based on SE2026 Rule 13b recommendations like A, G, I 56, etc.).
3. **SE2026 Excel Anomaly Resolver & Report Generator:**
   - Read `KBLI SE2026/*.xlsx` (14,246 records).
   - Insert new column after Column L: `judul_kbli_saat_ini` (lookup title from KBLI 2025/2020).
   - Re-classify rows where `kbli_rekomendasi_genai == 999999` using combined Column J (`nama_usaha`) + Column K (`uraian_kegiatan`) + Category hint (Column Q).
   - Insert new recommendation columns: `rekomendasi_pintar_kbli` (`[KODE] - [JUDUL]`), `confidence_score`, and `alasan_rekomendasi`.

**Tech Stack:** PHP 8.2 (Laravel 11), Python 3.10+, PostgreSQL + pgvector, PhpSpreadsheet / Pandas, Pest / PHPUnit.

**Spec:** SE2026 anomaly resolution spec based on files in `KBLI SE2026/*.xlsx` (Columns J–Q).

## Global Constraints

- Never drop, truncate, or delete any database tables or existing data.
- Never commit, push, or modify the `Jurnal/` directory.
- Preserve all existing public API contracts (`/api/v1/search`, `/api/search`).
- Project identity is **PINTAR KBLI** (focus on KBLI 2025 for Sensus Ekonomi 2026, hybrid search, crowdsourcing, and user-friendly experience).

## Review Focus

- Narrative queries containing vehicles/tools as means (e.g., "jual pentol keliling menggunakan sepeda motor") must match Food Stall / Retail (KBLI 56102 / 47xxx), NOT motorbike sales/repair (KBLI 4540x).
- Extreme narrative descriptions (30–50+ words of survey narrative, e.g. sarang burung walet or pemipilan jagung) must return top relevant codes with valid confidence, never returning 0 results.
- In the exported Excel report:
  - An inserted column right after Column L provides the title of `kbli_saat_ini`.
  - An inserted column provides the new suggestion formatted as `[KODE] - [JUDUL]`.
- Existing short queries (e.g. "toko sembako", "ojol", numeric codes "56102") must retain their fast response and exact scoring.
- Category hints (e.g. category 'G' or 'A' from Column Q) must constrain the search so that candidates outside the intended sector are deprioritized.

---

### Task 1: Context-Preserving Query Preprocessor (Fix False Variation Triggers)

**Files:**
- Modify: `python/preprocessing/expansion.py:60-156`
- Modify: `python/preprocessing/stopwords.py`
- Modify: `app/Services/SearchService.php:25-190`
- Test: `tests/Unit/SearchServiceLongQueryTest.php`
- Test: `python/tests/test_long_queries.py`

**Interfaces:**
- Consumes: Raw narrative query string.
- Produces: `preprocessQuery()` array containing:
  - `clean`: Stopword-free clean text.
  - `core_activity`: Extracted primary action + commodity (e.g., "jual pentol").
  - `instrumental_modifiers`: Words denoting tools/vehicles/location (e.g., "sepeda motor", "gerobak", "desa").
  - `expanded_tokens`: Safe expansions without spurious cross-clause triggers.

- [ ] **Step 1: Write failing tests for context preservation in Python and PHP**

```python
# python/tests/test_long_queries.py
def test_pentol_with_motor_does_not_trigger_motor_sales():
    query = "melakukan proses pengolahan bahan baku daging tepung bumbu menjadi pentol siap saji di rumah kemudian memasarkannya secara keliling menggunakan gerobak yang dimodifikasi pada sepeda motor untuk menjangkau konsumen"
    prep = preprocess_expansion(query)
    # The variation map must NOT match "jual motor" when words are separated across clauses
    assert "showroom motor rumahan" not in prep.get("kbli_variations", [])
    assert "jual beli motor bekas" not in prep.get("kbli_variations", [])
```

```php
// tests/Unit/SearchServiceLongQueryTest.php
public function test_preprocess_identifies_core_activity_and_instrumental_modifiers(): void
{
    $service = app(SearchService::class);
    $query = "jual pentol keliling menggunakan gerobak sepeda motor";
    $prep = $service->preprocessQuery($query);

    $this->assertContains('pentol', $prep['tokens']);
    $this->assertNotContains('menggunakan', $prep['tokens']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `python -m pytest python/tests/test_long_queries.py`
Expected: FAIL.

- [ ] **Step 3: Implement strict adjacent phrase matching and instrumental modifier handling**

1. In `python/preprocessing/expansion.py`:
   - Replace the `kw_tokens.issubset(user_token_set)` logic. Require that multi-word variation trigger keys (e.g. "jual motor", "bengkel las") appear as adjacent n-grams in the text, or within a maximum window of 2 adjacent tokens.
   - Detect instrumental phrases preceded by "menggunakan", "pakai", "dengan gerobak", "pada sepeda motor" and classify them as instrumental modifiers rather than core subjects.
2. In `app/Services/SearchService.php`:
   - Expand stopwords to filter survey conversational noise (`memberikan`, `menyediakan`, `melakukan`, `proses`, `berupa`, `secara`, `menggunakan`, `hingga`, `menghasilkan`, `kemudian`, `berbagai`, `macam`, `seperti`, `meliputi`, `siap`, `mendatangkannya`, `sebelah`, `dimulai`).

- [ ] **Step 4: Run tests to verify they pass**

Run: `python -m pytest python/tests/test_long_queries.py` and `php artisan test --filter=SearchServiceLongQueryTest`
Expected: PASS.

- [ ] **Step 5: Commit changes**

```bash
git add python/preprocessing/ app/Services/SearchService.php tests/ python/tests/
git commit -m "fix(search): prevent spurious variation triggers on multi-clause narrative sentences"
```

---

### Task 2: Progressive Relaxation & Token Coverage Scoring for Long Queries

**Files:**
- Modify: `app/Services/SearchService.php:215-280`
- Modify: `python/search/sql_like.py:90-195`
- Modify: `python/search/hybrid.py:105-195`
- Test: `tests/Unit/SearchServiceLongQueryTest.php`

**Interfaces:**
- Consumes: Long narrative query tokens.
- Produces: Ranked list of KBLI items scored by token coverage and weighted match rather than strict all-or-nothing AND.

- [ ] **Step 1: Write failing test for non-empty results on 20+ word queries**

```php
// tests/Unit/SearchServiceLongQueryTest.php
public function test_long_survey_queries_return_relevant_results(): void
{
    $service = app(SearchService::class);
    $queries = [
        'Menjual takoyaki keliling di desa desa sebelah',
        'PEMBUDIYAAN JANGKRIK DIMULAI DARI TELUR HINGGA BESAR (SIAP PANEN)',
        'melakukan proses pengolahan bahan baku daging tepung bumbu menjadi pentol siap saji di rumah kemudian memasarkannya secara keliling menggunakan gerobak yang dimodifikasi pada sepeda motor',
        'Usaha jasa pengolahan pascapanen sarang burung walet berupa pembersihan sarang walet mentah dirty bird nest melalui proses perendaman pembersihan kotoran'
    ];

    foreach ($queries as $q) {
        $res = $service->search($q, 5, 'KBLI', false);
        $this->assertNotEmpty($res, "Query '$q' must not return empty results");
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_long_survey_queries_return_relevant_results`
Expected: FAIL (returns 0 results currently).

- [ ] **Step 3: Implement dynamic relaxation scoring**

1. If token count <= 3: keep strict AND for high precision.
2. If token count > 3: calculate weighted score:
   - Match in `judul`: 4 points per token.
   - Match in `contoh_lapangan`: 2 points per token.
   - Match in `deskripsi`: 1 point per token.
   - Filter `WHERE score >= threshold` and order by `score DESC`.
3. In `python/search/hybrid.py`: pass the extracted `core_activity` (e.g. "jual pentol keliling" or "budidaya jangkrik") as the primary text for vector embedding to prevent vector dilution.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_long_survey_queries_return_relevant_results`
Expected: PASS.

- [ ] **Step 5: Commit changes**

```bash
git add app/Services/SearchService.php python/search/
git commit -m "feat(search): implement dynamic token relaxation and core-phrase embedding for long queries"
```

---

### Task 3: Category Filtering & Boosting (Rule 13b Alignment)

**Files:**
- Modify: `app/Services/SearchService.php`
- Modify: `app/Http/Controllers/Api/SearchController.php`
- Modify: `app/Http/Controllers/Api/V1/MobileSearchController.php`
- Modify: `python/api.py`
- Test: `tests/Feature/CategoryFilteredSearchTest.php`

**Interfaces:**
- Consumes: Query string + optional `category` parameter (e.g., `'A'`, `'C'`, `'G'`, `'I'`).
- Produces: Search results prioritized or constrained by the indicated KBLI section.

- [ ] **Step 1: Write failing test for category prioritization**

```php
// tests/Feature/CategoryFilteredSearchTest.php
public function test_search_boosts_matching_category(): void
{
    $service = app(SearchService::class);
    // "pedagang beras" with category G (Trade) should prioritize 46/47 over 10 (Manufacture)
    $resultsG = $service->search('pedagang beras', 5, 'KBLI', false, 'G');
    $this->assertStringStartsWith('4', (string)$resultsG[0]['kode']);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CategoryFilteredSearchTest`
Expected: FAIL.

- [ ] **Step 3: Implement category parameter and prefix scoring in search engines**

Map KBLI categories:
- `A`: `01`, `02`, `03`
- `C`: `10` through `33`
- `G`: `45`, `46`, `47`
- `I`: `55`, `56`
- `H-V`: non-A/C/G
Boost matching codes in SQL and Python hybrid search.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CategoryFilteredSearchTest`
Expected: PASS.

- [ ] **Step 5: Commit changes**

```bash
git add app/Services/SearchService.php app/Http/Controllers/ python/api.py tests/Feature/CategoryFilteredSearchTest.php
git commit -m "feat(search): add category constraint and scoring boost for SE2026 rule alignment"
```

---

### Task 4: SE2026 Batch Anomaly Resolver Command with Enhanced Output Columns

**Files:**
- Create: `app/Console/Commands/ResolveKbliSe2026Command.php`
- Test: `tests/Feature/ResolveKbliSe2026CommandTest.php`

**Interfaces:**
- Consumes: Files from `KBLI SE2026/*.xlsx` with columns J–Q.
- Produces: Exported Excel workbook in `storage/app/se2026_resolved/` with:
  1. Original columns A through L (`kbli_saat_ini`).
  2. **NEW INSERTED COLUMN (after Col L)**: `judul_kbli_saat_ini` (Judul KBLI berdasarkan kode di kolom L).
  3. Columns M through S (konteks rule dan rekomendasi asli).
  4. **NEW RECOMMENDATION COLUMNS (Up to Top 3)**:
     - `rekomendasi_1` (Format: `[KODE] - [JUDUL KBLI]`) & `confidence_1`
     - `rekomendasi_2` (Format: `[KODE] - [JUDUL KBLI]`) & `confidence_2`
     - `rekomendasi_3` (Format: `[KODE] - [JUDUL KBLI]`) & `confidence_3`
     - `alasan_rekomendasi` (Ringkasan mengapa kode-kode ini dipilih).

- [ ] **Step 1: Write feature test for batch SE2026 anomaly resolver**

```php
// tests/Feature/ResolveKbliSe2026CommandTest.php
namespace Tests\Feature;

use Tests\TestCase;

class ResolveKbliSe2026CommandTest extends TestCase
{
    public function test_resolver_generates_enhanced_excel_with_inserted_title_and_recommendation_columns(): void
    {
        $this->artisan('kbli:resolve-se2026', ['--sample' => 5])
            ->assertExitCode(0);

        $files = glob(storage_path('app/se2026_resolved/*.xlsx'));
        $this->assertNotEmpty($files, "Exported resolved excel file must exist");
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ResolveKbliSe2026CommandTest`
Expected: FAIL ("Command not found").

- [ ] **Step 3: Implement `app/Console/Commands/ResolveKbliSe2026Command.php`**

1. Parse both Excel files in `KBLI SE2026/`.
2. Cache dictionary of KBLI 2025/2020 (`kode` => `judul`) for O(1) title lookup.
3. For each row:
   - Insert `judul_kbli_saat_ini` matching `kbli_saat_ini` (Col L).
   - If `kbli_rekomendasi_genai == 999999` (or unclassified):
     - Combine `nama_usaha` (Col J) and cleaned `uraian_kegiatan` (Col K).
     - Extract category hint from `rekomendasi_tindakan` (Col Q) (e.g. "G - perdagangan" -> 'G').
     - Run `SearchService::search()` with category constraint.
     - Extract top-3 matches: `code` and `title`.
     - Set `rekomendasi_1` = `code1 . " - " . title1`, `confidence_1`, etc. (up to 3 recommendations).
4. Export updated spreadsheet using `PhpOffice\PhpSpreadsheet` to `storage/app/se2026_resolved/`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ResolveKbliSe2026CommandTest`
Expected: PASS.

- [ ] **Step 5: Commit changes**

```bash
git add app/Console/Commands/ResolveKbliSe2026Command.php tests/Feature/ResolveKbliSe2026CommandTest.php
git commit -m "feat(se2026): add batch anomaly resolver with current title and formatted recommendation columns"
```

---

### Task 5: End-to-End Verification on Real SE2026 Samples

**Files:**
- Real sample tests against `KBLI SE2026/*.xlsx`:
  - `PENTOL MAS LONDO` ("melakukan proses pengolahan bahan baku... pentol siap saji... sepeda motor") -> Must resolve to `56102` (Penyediaan Makanan di Bangunan Tidak Tetap), NOT motor repair/dealer.
  - `Menjual takoyaki keliling di desa desa sebelah` -> Must resolve to `56102` / `47xxx`.
  - `BUDIDAYA JANGKRIK` ("PEMBUDIYAAN JANGKRIK DIMULAI DARI TELUR...") -> Must resolve to `01499` (Pengembangbiakan Hewan Lainnya).
  - `PEMBERSIHAN SARANG BURUNG WALET` -> Must resolve to `01630` (Jasa Pascapanen).

- [ ] **Step 1: Execute sample run of the resolver command**

Run: `php artisan kbli:resolve-se2026 --sample=50`
Expected: Successfully processes 50 rows, prints summary table of accuracy/confidence, and generates Excel in `storage/app/se2026_resolved/`.

- [ ] **Step 2: Inspect generated Excel columns**

Verify column positions:
- Column L: `kbli_saat_ini`
- Column M (Inserted): `judul_kbli_saat_ini`
- New Columns at end: `rekomendasi_1`, `confidence_1`, `rekomendasi_2`, `confidence_2`, `rekomendasi_3`, `confidence_3`, `alasan_rekomendasi`.

- [ ] **Step 3: Update README.md Changelog**

Record changes with today's date and detailed description of the long sentence context preservation and SE2026 resolver tool.

- [ ] **Step 4: Final Git Commit & Push**

```bash
git add README.md
git commit -m "docs: update changelog for SE2026 long query search and batch resolver"
git push
```

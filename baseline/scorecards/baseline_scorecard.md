# Baseline Scorecard — SampleReceptionController Entity Extraction

Baseline of the existing regex/table extraction logic in `sample-reception/SampleReceptionController.php`, run over every test file and scored against `GOLD_EXTRACTION_SRC.json`. Raw form labels emitted by the code are mapped to the canonical fields defined in `Actlabs_Unified_Data_Dictionary.xlsx` (clubbing map applied), then compared field-by-field.


## Headline results

| Metric | Value |
|---|---|
| Documents in gold set | 19 |
| Form documents scored | 16 |
| Decoy documents (robustness) | 3 |
| Canonical header fields evaluated | 14 |
| Applicable field instances (gold non-null) | 167 |
| Exact matches | 40 |
| Partial matches | 26 |
| Misses | 101 |
| **Strict accuracy** (exact only) | **23.9%** |
| **Weighted accuracy** (exact=1, partial=0.5) | **31.7%** |

- **Strict** = value normalized-equal to gold. **Partial** = correct value present but contaminated with adjacent-field text (a recurring failure mode of the layout-agnostic regexes), or a strong token overlap. **Miss** = empty or wrong.

## Per-field accuracy (across all scored form documents)

| Canonical field | Applicable | Exact | Partial | Miss | Strict | Weighted |
|---|--:|--:|--:|--:|--:|--:|
| priority | 15 | 11 | 4 | 0 | 73.3% | 86.7% |
| num_packages | 10 | 6 | 0 | 4 | 60.0% | 60.0% |
| phone | 16 | 7 | 0 | 9 | 43.8% | 43.8% |
| email | 16 | 6 | 0 | 10 | 37.5% | 37.5% |
| num_samples | 15 | 5 | 0 | 10 | 33.3% | 33.3% |
| project | 13 | 2 | 4 | 7 | 15.4% | 30.8% |
| contact_name | 15 | 2 | 2 | 11 | 13.3% | 20.0% |
| waybill | 5 | 1 | 0 | 4 | 20.0% | 20.0% |
| company_name | 16 | 0 | 6 | 10 | 0.0% | 18.8% |
| quote_po_proforma | 9 | 0 | 3 | 6 | 0.0% | 16.7% |
| carrier | 6 | 0 | 2 | 4 | 0.0% | 16.7% |
| address | 14 | 0 | 4 | 10 | 0.0% | 14.3% |
| special_instructions | 6 | 0 | 1 | 5 | 0.0% | 8.3% |
| payment_method | 11 | 0 | 0 | 11 | 0.0% | 0.0% |

## Sample-table capture

| Metric | Value |
|---|---|
| Form docs whose gold has sample rows | 16 |
| …where the code detected ≥1 sample row | 11 |
| Docs with a gold `total_samples` | 16 |
| …where code-estimated total matched exactly | 1 |


## Decoy robustness (inventory spreadsheets — gold expects all-null, total 0)

| Decoy doc | Canonical false-positives | Raw junk fields emitted | Code sample rows | Priority force-set |
|---|--:|--:|--:|:--:|
| Burkina_Inventory | 1 | 82 | 0 | yes |
| Colombia_Inventory | 1 | 114 | 0 | yes |
| Namibia_Inventory | 1 | 51 | 0 | yes |


## Canonical fields out of scope for this baseline (19)

The regex code has **no extraction logic** for these data-dictionary fields (structured Rev2.0 grids, sign-off, hazmat, client-letter extras, and the Yes/No-vs-email semantic mismatch on `confirmation_email`). They are gold-present on many documents but unreachable by the current code, and are excluded from the header accuracy above so the score reflects only what the code attempts:

`grade_type`, `confirmation_email`, `reporting_contacts`, `sample_disposition_matrix`, `return_address`, `hazardous_materials`, `authorized_signature`, `authorized_print_name`, `authorized_date`, `sample_weight`, `customs_tariff`, `billing_code`, `job_number`, `testing_notes`, `analysis_suite`, `shipment_type`, `wra_code`, `disposal_code`, `overlimit_code`

## Key baseline weaknesses (observed)

1. **No layout handling.** On two-column PDF forms rendered by `pdftotext -layout` (C3 Metals, Chevron, Goldcorp) the label and value land on the same line separated by wide gaps, so nearly every regex fails — these docs score ~0 on header fields.
2. **Field bleed.** Where regexes do fire, greedy `(.+?)` capture pulls in the next field (e.g. Phone captures the trailing Fax; Carrier captures the Waybill), producing partials instead of exacts.
3. **No document classification.** Decoy inventories and client letters are parsed as if they were Actlabs forms.
4. **No enum/normalisation for structured fields.** `payment_method`, `sample_disposition_matrix`, `reporting_contacts`, `grade_type`, and the Rev2.0 sign-off block are not modelled.

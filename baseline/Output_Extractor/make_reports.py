import json, os

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
D = json.load(open(os.path.join(HERE, 'scores.json'), encoding='utf-8'))
sc = D['scorecard']; pd = D['per_document']
s = sc['summary']

def pct(x):
    return '—' if x is None else f'{x*100:.1f}%'

# ============ baseline_scorecard.md ============
L = []
L.append('# Baseline Scorecard — SampleReceptionController Entity Extraction')
L.append('')
L.append('Baseline of the existing regex/table extraction logic in '
         '`sample-reception/SampleReceptionController.php`, run over every test file and '
         'scored against `GOLD_EXTRACTION_SRC.json`. Raw form labels emitted by the code are '
         'mapped to the canonical fields defined in `Actlabs_Unified_Data_Dictionary.xlsx` '
         '(clubbing map applied), then compared field-by-field.')
L.append('')
L.append('> **Method note.** PHP/Composer are not installed in this environment, so the '
         "controller's extraction pipeline (regex patterns, key/value sweep, sample-table "
         'walker, and its preferred `pdftotext -layout -nopgbrk` PDF path) was ported '
         'verbatim to Python and executed against the real test files. Spreadsheet/Word '
         'parsing uses openpyxl/xlrd/python-docx as stand-ins for PhpSpreadsheet/PhpWord.')
L.append('')
L.append('## Headline results')
L.append('')
L.append('| Metric | Value |')
L.append('|---|---|')
L.append(f'| Documents in gold set | {s["total_documents"]} |')
L.append(f'| Form documents scored | {s["form_documents_scored"]} |')
L.append(f'| Decoy documents (robustness) | {s["decoy_documents"]} |')
L.append(f'| Canonical header fields evaluated | {s["header_fields_evaluated_per_dict"]} |')
L.append(f'| Applicable field instances (gold non-null) | {s["total_applicable_field_instances"]} |')
L.append(f'| Exact matches | {s["exact_matches"]} |')
L.append(f'| Partial matches | {s["partial_matches"]} |')
L.append(f'| Misses | {s["misses"]} |')
L.append(f'| **Strict accuracy** (exact only) | **{pct(s["strict_accuracy"])}** |')
L.append(f'| **Weighted accuracy** (exact=1, partial=0.5) | **{pct(s["weighted_accuracy"])}** |')
L.append('')
L.append('- **Strict** = value normalized-equal to gold. **Partial** = correct value present '
         'but contaminated with adjacent-field text (a recurring failure mode of the '
         'layout-agnostic regexes), or a strong token overlap. **Miss** = empty or wrong.')
L.append('')
L.append('## Per-field accuracy (across all scored form documents)')
L.append('')
L.append('| Canonical field | Applicable | Exact | Partial | Miss | Strict | Weighted |')
L.append('|---|--:|--:|--:|--:|--:|--:|')
order = sorted(sc['per_field'].items(), key=lambda kv: (kv[1]['weighted_accuracy'] if kv[1]['weighted_accuracy'] is not None else -1), reverse=True)
for f, v in order:
    L.append(f'| {f} | {v["applicable"]} | {v["exact"]} | {v["partial"]} | {v["miss"]} | '
             f'{pct(v["strict_accuracy"])} | {pct(v["weighted_accuracy"])} |')
L.append('')
L.append('## Sample-table capture')
L.append('')
st = sc['sample_table_capture']
L.append('| Metric | Value |')
L.append('|---|---|')
L.append(f'| Form docs whose gold has sample rows | {st["docs_with_gold_rows"]} |')
L.append(f'| …where the code detected ≥1 sample row | {st["docs_code_detected_rows"]} |')
L.append(f'| Docs with a gold `total_samples` | {st["total_samples_applicable"]} |')
L.append(f'| …where code-estimated total matched exactly | {st["total_samples_exact"]} |')
L.append('')
L.append('The code has no notion of `total_samples` as a semantic header count; the estimate '
         'above is derived from detected rows, which is why exact matches are rare. Row-level '
         'analysis/prep-code overlap is reported per document in the accuracy report.')
L.append('')
L.append('## Decoy robustness (inventory spreadsheets — gold expects all-null, total 0)')
L.append('')
L.append('| Decoy doc | Canonical false-positives | Raw junk fields emitted | Code sample rows | Priority force-set |')
L.append('|---|--:|--:|--:|:--:|')
for d in sc['decoy_robustness']:
    L.append(f'| {d["doc"]} | {d["false_positive_count"]} | {d["raw_nonempty_fields"]} | '
             f'{d["code_sample_rows"]} | {"yes" if d["priority_forced"] else "no"} |')
L.append('')
L.append('The generic key/value sweep treats inventory column headers as form labels, so '
         'each decoy emits dozens of raw junk fields; only one happens to collide with a '
         'canonical slot (`quote_po_proforma`, from a "PO" column). The code does **not** '
         'classify document type and cannot suppress non-forms. (`Priority` is excluded from '
         'the false-positive count because the extractor unconditionally defaults it to '
         '`Normal` on every document.)')
L.append('')
L.append(f'## Canonical fields out of scope for this baseline ({len(sc["out_of_scope_fields"])})')
L.append('')
L.append('The regex code has **no extraction logic** for these data-dictionary fields '
         '(structured Rev2.0 grids, sign-off, hazmat, client-letter extras, and the '
         'Yes/No-vs-email semantic mismatch on `confirmation_email`). They are gold-present '
         'on many documents but unreachable by the current code, and are excluded from the '
         'header accuracy above so the score reflects only what the code attempts:')
L.append('')
L.append('`' + '`, `'.join(sc['out_of_scope_fields']) + '`')
L.append('')
L.append('## Key baseline weaknesses (observed)')
L.append('')
L.append('1. **No layout handling.** On two-column PDF forms rendered by `pdftotext -layout` '
         '(C3 Metals, Chevron, Goldcorp) the label and value land on the same line separated '
         'by wide gaps, so nearly every regex fails — these docs score ~0 on header fields.')
L.append('2. **Field bleed.** Where regexes do fire, greedy `(.+?)` capture pulls in the next '
         "field (e.g. Phone captures the trailing Fax; Carrier captures the Waybill), "
         'producing partials instead of exacts.')
L.append('3. **No document classification.** Decoy inventories and client letters are parsed '
         'as if they were Actlabs forms.')
L.append('4. **No enum/normalisation for structured fields.** `payment_method`, '
         '`sample_disposition_matrix`, `reporting_contacts`, `grade_type`, and the Rev2.0 '
         'sign-off block are not modelled.')
L.append('')
open(os.path.join(ROOT, 'baseline_scorecard.md'), 'w', encoding='utf-8').write('\n'.join(L))

# ============ per_document_accuracy.md ============
P = []
P.append('# Per-Document Accuracy Report')
P.append('')
P.append('Field-level baseline results for each document. `code` shows the value the '
         '`SampleReceptionController` logic produced (mapped to the canonical field); `gold` '
         'is the ground truth. Result: ✅ exact · 🟡 partial · ❌ miss.')
P.append('')
P.append('## Summary table')
P.append('')
P.append('| Document | Type | Applicable | ✅ | 🟡 | ❌ | Strict | Weighted | Sample rows (code/gold) |')
P.append('|---|---|--:|--:|--:|--:|--:|--:|:--:|')
order_docs = list(GOLD_ORDER := list(json.load(open(os.path.join(ROOT,'GOLD_EXTRACTION_SRC.json'),encoding='utf-8')).keys()))
for k in order_docs:
    v = pd[k]
    if v.get('kind') == 'decoy':
        P.append(f'| {k} | decoy | — | — | — | — | — | — | {v["code_sample_rows"]}/0 |')
    else:
        smp = v['sample_table']
        P.append(f'| {k} | {v["doc_type"]} | {v["applicable"]} | {v["exact"]} | {v["partial"]} '
                 f'| {v["miss"]} | {pct(v["strict_acc"])} | {pct(v["weighted_acc"])} | '
                 f'{smp["code_rows"]}/{smp["gold_rows"]} |')
P.append('')
P.append('*Decoys have no applicable header fields (gold is all-null); the sample-row column '
         'shows spurious rows the code detected where gold expects 0.*')
P.append('')

sym = {'EXACT': '✅', 'PARTIAL': '🟡', 'MISS': '❌'}
P.append('## Field-level detail')
P.append('')
for k in order_docs:
    v = pd[k]
    P.append(f'### {k}  ·  `{v.get("doc_type")}`')
    P.append('')
    if v.get('kind') == 'decoy':
        d = next(x for x in sc['decoy_robustness'] if x['doc'] == k)
        P.append(f'Decoy (inventory). Gold expects all fields null and `total_samples = 0`.')
        P.append('')
        P.append(f'- False-positive *canonical* header fields: **{d["false_positive_count"]}**'
                 + (f' — `{"`, `".join(d["false_positive_fields"])}`' if d['false_positive_fields'] else ''))
        P.append(f'- Raw junk fields emitted by the key/value sweep (inventory columns read as labels): **{d["raw_nonempty_fields"]}**')
        P.append(f'- Spurious sample rows detected: **{d["code_sample_rows"]}** (gold = 0)')
        P.append('')
        continue
    P.append(f'Applicable **{v["applicable"]}** · strict **{pct(v["strict_acc"])}** · '
             f'weighted **{pct(v["weighted_acc"])}**  '
             f'({v["exact"]}✅ / {v["partial"]}🟡 / {v["miss"]}❌)')
    P.append('')
    P.append('| Field | R | Code | Gold |')
    P.append('|---|:--:|---|---|')
    for canon, r in v['fields'].items():
        cc = str(r['code']).replace('\n', ' ').replace('|', '\\|')
        gg = str(r['gold']).replace('\n', ' ').replace('|', '\\|')
        if len(cc) > 60: cc = cc[:57] + '…'
        if len(gg) > 60: gg = gg[:57] + '…'
        P.append(f'| {canon} | {sym[r["result"]]} | {cc} | {gg} |')
    smp = v['sample_table']
    P.append('')
    stline = (f'**Sample table** — gold rows {smp["gold_rows"]}, code rows {smp["code_rows"]}; '
              f'gold total {smp["gold_total"]}, code est {smp["code_total_est"]}')
    if smp.get('gold_analysis'):
        stline += f'; analysis-code overlap {smp["analysis_overlap"]} of gold {smp["gold_analysis"]}'
    if smp.get('gold_prep'):
        stline += f'; prep-code overlap {smp["prep_overlap"]} of gold {smp["gold_prep"]}'
    P.append(stline)
    P.append('')

open(os.path.join(ROOT, 'per_document_accuracy.md'), 'w', encoding='utf-8').write('\n'.join(P))
print('wrote baseline_scorecard.md and per_document_accuracy.md')

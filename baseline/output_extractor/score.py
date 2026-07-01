import json, os, re

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
GOLD = json.load(open(os.path.join(ROOT, 'GOLD_EXTRACTION_SRC.json'), encoding='utf-8'))
CODE = json.load(open(os.path.join(HERE, 'code_extraction.json'), encoding='utf-8'))

# ---- canonical field mapping (data-dictionary clubbing map) ----
# in-scope header/identity fields the regex code actually targets
HEADER_FIELDS = ['company_name', 'contact_name', 'email', 'phone', 'address',
                 'project', 'quote_po_proforma', 'carrier', 'waybill',
                 'num_packages', 'num_samples', 'priority', 'payment_method',
                 'special_instructions']

# canonical fields with NO regex logic in the baseline code (structured / Rev2.0 / client_extra)
OUT_OF_SCOPE = ['grade_type', 'confirmation_email', 'reporting_contacts',
                'sample_disposition_matrix', 'return_address', 'hazardous_materials',
                'authorized_signature', 'authorized_print_name', 'authorized_date',
                'sample_weight', 'customs_tariff', 'billing_code', 'job_number',
                'testing_notes', 'analysis_suite', 'shipment_type', 'wra_code',
                'disposal_code', 'overlimit_code']

SAMPLE_FIELDS = ['total_samples', 'sample_numbers', 'sample_type', 'prep_code', 'analysis_code']

INT_FIELDS = {'num_packages', 'num_samples'}


def code_value(rec, canon):
    """Pull the raw code field(s) that club into a canonical field."""
    f = rec.get('fields', {})
    if canon == 'company_name':
        return f.get('Company') or f.get('Client Name') or ''
    if canon == 'contact_name':
        return f.get('Attn') or ''
    if canon == 'email':
        return f.get('E-mail') or ''
    if canon == 'phone':
        return f.get('Phone') or ''
    if canon == 'address':
        return f.get('Address') or ''
    if canon == 'project':
        return f.get('Project') or ''
    if canon == 'quote_po_proforma':
        return f.get('Quote #, PO #, Proforma #') or f.get('Client Batch #') or ''
    if canon == 'carrier':
        return f.get('Carrier') or ''
    if canon == 'waybill':
        return f.get('Waybill #') or f.get('Shipment #') or ''
    if canon == 'num_packages':
        return f.get('# of Packages') or ''
    if canon == 'num_samples':
        return f.get('# of Samples') or ''
    if canon == 'priority':
        return f.get('Priority') or ''
    if canon == 'payment_method':
        return payment_enum(f.get('Payment Method') or '')
    if canon == 'special_instructions':
        return f.get('Special Instructions/Comments') or ''
    return ''


def payment_enum(v):
    m = {'payment included': 'Included', 'new credit card': 'NewCreditCard',
         'credit card on file': 'CreditCardOnFile', 'established credit': 'CreditEstablished'}
    return m.get(v.strip().lower(), v)


# ---- normalization & matching ----
def tnorm(s):
    s = str(s).lower().replace(' ', ' ')
    s = re.sub(r'[\s]+', ' ', s).strip()
    s = re.sub(r'[.,;:]+$', '', s).strip()
    return s


def tokens(s):
    return set(re.findall(r'[a-z0-9]+', tnorm(s)))


def match_text(code, gold):
    c, g = tnorm(code), tnorm(gold)
    if not c:
        return 'MISS'
    if c == g:
        return 'EXACT'
    if g and (g in c or c in g):
        return 'PARTIAL'
    tc, tg = tokens(code), tokens(gold)
    if tg and len(tc & tg) / max(1, len(tg)) >= 0.5:
        return 'PARTIAL'
    return 'MISS'


def match_email(code, gold):
    ce = re.findall(r'[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}', str(code))
    ge = re.findall(r'[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}', str(gold))
    if not ce:
        return 'MISS'
    cs = {x.lower() for x in ce}
    gs = {x.lower() for x in ge}
    if gs and gs & cs:
        return 'EXACT'
    return 'MISS'


def phone_digits(s):
    d = re.sub(r'\D', '', str(s))
    if len(d) > 10 and d.startswith('1'):
        d = d[1:]
    return d


def match_phone(code, gold):
    gd = phone_digits(gold)[:10] if gold else ''
    cdall = re.sub(r'\D', '', str(code))
    if not cdall:
        return 'MISS'
    cd = phone_digits(code)
    if gd and gd == cd[:10]:
        return 'EXACT'
    if gd and gd in cdall:
        return 'PARTIAL'  # correct number present but polluted with extra digits
    return 'MISS'


def match_int(code, gold):
    m = re.search(r'\d[\d,]*', str(code))
    if not m:
        return 'MISS'
    ci = int(m.group(0).replace(',', ''))
    return 'EXACT' if ci == int(gold) else 'MISS'


def match_priority(code, gold):
    def nz(v):
        v = str(v)
        if re.search(r'rush|urgent|emergency|asap', v, re.I):
            return 'RUSH'
        if re.search(r'normal|standard|routine', v, re.I):
            return 'Normal'
        return v.strip()
    return 'EXACT' if nz(code) == nz(gold) else ('MISS' if not str(code).strip() else 'PARTIAL' if nz(code) and nz(gold) and nz(code) != nz(gold) else 'MISS')


def match_field(canon, code, gold):
    if canon == 'email':
        return match_email(code, gold)
    if canon == 'phone':
        return match_phone(code, gold)
    if canon in INT_FIELDS:
        return match_int(code, gold)
    if canon == 'priority':
        return match_priority(code, gold)
    if canon == 'payment_method':
        return 'EXACT' if tnorm(code) == tnorm(gold) else ('MISS' if not str(code).strip() else 'PARTIAL' if tokens(code) & tokens(gold) else 'MISS')
    return match_text(code, gold)


CREDIT = {'EXACT': 1.0, 'PARTIAL': 0.5, 'MISS': 0.0}

# ---- sample table capture ----
def code_sample_rows(rec):
    return rec.get('sample_data_combined', []) or []


def code_total_samples(rec):
    rows = code_sample_rows(rec)
    # best-effort: sum per-row '# of Samples' if numeric, else row count
    tot = 0; had = False
    for r in rows:
        for k, v in r.items():
            if re.search(r'#\s*of\s*Samples|No\.?\s*of\s*Samples|Qty', k, re.I):
                m = re.search(r'\d[\d,]*', str(v))
                if m:
                    tot += int(m.group(0).replace(',', '')); had = True
    return tot if had else len(rows)


def collect_codes(rows, keypat):
    out = set()
    for r in rows:
        for k, v in r.items():
            if re.search(keypat, k, re.I) and str(v).strip():
                for piece in re.split(r'[,;/]+', str(v)):
                    p = piece.strip()
                    if p:
                        out.add(p.upper())
    return out


# =========================================================
per_doc = {}
field_stat = {f: {'applicable': 0, 'exact': 0, 'partial': 0, 'miss': 0} for f in HEADER_FIELDS}
sample_stat = {'docs_with_gold_rows': 0, 'docs_code_detected_rows': 0,
               'total_samples_exact': 0, 'total_samples_applicable': 0}
decoy_docs = []

for key, gd in GOLD.items():
    rec = CODE.get(key, {})
    dt = gd.get('doc_type')
    gf = gd.get('fields', {})
    if dt == 'decoy':
        # robustness: code should output ~nothing
        fp = []
        for canon in HEADER_FIELDS:
            cv = code_value(rec, canon)
            if canon == 'priority':
                continue  # code force-defaults Priority to Normal on every doc
            if str(cv).strip():
                fp.append(canon)
        crows = len(code_sample_rows(rec))
        raw_noise = sum(1 for v in rec.get('fields', {}).values() if v)
        decoy_docs.append({'doc': key, 'false_positive_fields': fp,
                           'false_positive_count': len(fp),
                           'raw_nonempty_fields': raw_noise,
                           'code_sample_rows': crows,
                           'priority_forced': bool(str(code_value(rec, 'priority')).strip())})
        per_doc[key] = {'doc_type': dt, 'kind': 'decoy',
                        'false_positive_fields': len(fp), 'code_sample_rows': crows}
        continue

    results = {}
    applicable = 0; credit = 0.0; exact = 0; partial = 0; miss = 0
    for canon in HEADER_FIELDS:
        gv = gf.get(canon)
        if gv is None or gv == '':
            continue  # not applicable (gold has no value)
        applicable += 1
        cv = code_value(rec, canon)
        res = match_field(canon, cv, gv)
        results[canon] = {'gold': gv, 'code': cv, 'result': res}
        credit += CREDIT[res]
        field_stat[canon]['applicable'] += 1
        if res == 'EXACT':
            exact += 1; field_stat[canon]['exact'] += 1
        elif res == 'PARTIAL':
            partial += 1; field_stat[canon]['partial'] += 1
        else:
            miss += 1; field_stat[canon]['miss'] += 1

    # sample table
    st = gd.get('sample_table', {}) or {}
    gold_rows = st.get('rows', []) or []
    gtot = st.get('total_samples')
    crows = code_sample_rows(rec)
    ctot = code_total_samples(rec)
    samp = {'gold_total': gtot, 'gold_rows': len(gold_rows),
            'code_rows': len(crows), 'code_total_est': ctot}
    if gold_rows:
        sample_stat['docs_with_gold_rows'] += 1
        if crows:
            sample_stat['docs_code_detected_rows'] += 1
    if gtot is not None:
        sample_stat['total_samples_applicable'] += 1
        samp['total_match'] = (ctot == gtot)
        if ctot == gtot:
            sample_stat['total_samples_exact'] += 1
    # distinct code overlap
    g_an = set(x.upper() for x in st.get('distinct_analysis_codes', []) or [])
    g_pr = set(x.upper() for x in st.get('distinct_prep_codes', []) or [])
    c_an = collect_codes(crows, r'Analysis')
    c_pr = collect_codes(crows, r'Prep')
    samp['analysis_overlap'] = sorted(g_an & c_an)
    samp['prep_overlap'] = sorted(g_pr & c_pr)
    samp['gold_analysis'] = sorted(g_an)
    samp['gold_prep'] = sorted(g_pr)

    per_doc[key] = {
        'doc_type': dt, 'kind': 'form', 'main_file': rec.get('main_file'),
        'applicable': applicable, 'exact': exact, 'partial': partial, 'miss': miss,
        'credit': round(credit, 2),
        'strict_acc': round(exact / applicable, 3) if applicable else None,
        'weighted_acc': round(credit / applicable, 3) if applicable else None,
        'fields': results, 'sample_table': samp,
    }

# ---- aggregate ----
form_docs = [k for k, v in per_doc.items() if v.get('kind') == 'form']
tot_app = sum(per_doc[k]['applicable'] for k in form_docs)
tot_exact = sum(per_doc[k]['exact'] for k in form_docs)
tot_partial = sum(per_doc[k]['partial'] for k in form_docs)
tot_credit = sum(per_doc[k]['credit'] for k in form_docs)

scorecard = {
    'summary': {
        'total_documents': len(GOLD),
        'form_documents_scored': len(form_docs),
        'decoy_documents': len(decoy_docs),
        'header_fields_evaluated_per_dict': len(HEADER_FIELDS),
        'canonical_fields_out_of_scope_for_baseline': len(OUT_OF_SCOPE),
        'total_applicable_field_instances': tot_app,
        'exact_matches': tot_exact,
        'partial_matches': tot_partial,
        'misses': tot_app - tot_exact - tot_partial,
        'strict_accuracy': round(tot_exact / tot_app, 4) if tot_app else None,
        'weighted_accuracy': round(tot_credit / tot_app, 4) if tot_app else None,
    },
    'per_field': {},
    'sample_table_capture': sample_stat,
    'decoy_robustness': decoy_docs,
    'out_of_scope_fields': OUT_OF_SCOPE,
}
for f in HEADER_FIELDS:
    s = field_stat[f]
    a = s['applicable']
    scorecard['per_field'][f] = {
        **s,
        'strict_accuracy': round(s['exact'] / a, 3) if a else None,
        'weighted_accuracy': round((s['exact'] + 0.5 * s['partial']) / a, 3) if a else None,
    }

json.dump({'scorecard': scorecard, 'per_document': per_doc},
          open(os.path.join(HERE, 'scores.json'), 'w', encoding='utf-8'),
          indent=2, ensure_ascii=False)
print('strict_accuracy', scorecard['summary']['strict_accuracy'],
      'weighted', scorecard['summary']['weighted_accuracy'],
      'applicable', tot_app)
print('wrote scores.json')

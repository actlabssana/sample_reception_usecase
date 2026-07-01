import json, os, sys, glob
sys.path.insert(0, os.path.dirname(__file__))
import code_port as cp

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TESTDIR = os.path.join(ROOT, 'sample-reception', 'test_files')
GOLD = json.load(open(os.path.join(ROOT, 'GOLD_EXTRACTION_SRC.json'), encoding='utf-8'))

# index test files by normalized name
def norm(n):
    return os.path.splitext(n)[0].lower().replace(' ', '_').replace('__', '_')

files = {}
for p in glob.glob(os.path.join(TESTDIR, '*')):
    files[norm(os.path.basename(p))] = p

def resolve(fname):
    return files.get(norm(fname))

out = {}
for key, gd in GOLD.items():
    src = gd.get('source_filename')
    companions = gd.get('companion_files', []) or []
    mainpath = resolve(src)
    rec = {'source_filename': src, 'doc_type': gd.get('doc_type'),
           'main_file': os.path.basename(mainpath) if mainpath else None,
           'companion_files': companions}
    if not mainpath:
        rec['error'] = 'main file not found'
        out[key] = rec
        print('MISSING', key, src)
        continue
    try:
        main = cp.parse_file(mainpath)
    except Exception as e:
        rec['error'] = 'parse failed: %r' % e
        out[key] = rec
        print('ERR', key, e)
        continue
    rec['fields'] = main['fields']
    rec['sample_data_main'] = main['sample_data']
    rec['raw_len'] = len(main['raw'])
    # companion sample data (controller aggregates secondary sample_data for counts)
    comp_samples = []
    comp_used = []
    for cf in companions:
        cpath = resolve(cf)
        if not cpath:
            continue
        try:
            cres = cp.parse_file(cpath)
            comp_samples.extend(cres['sample_data'])
            comp_used.append(os.path.basename(cpath))
        except Exception as e:
            print('  companion err', cf, e)
    rec['sample_data_companion'] = comp_samples
    rec['companion_used'] = comp_used
    rec['sample_data_combined'] = (main['sample_data'] or []) + comp_samples
    out[key] = rec
    print('OK %-28s main=%-32s fields_nonempty=%d main_rows=%d comp_rows=%d'
          % (key, os.path.basename(mainpath),
             sum(1 for v in main['fields'].values() if v),
             len(main['sample_data']), len(comp_samples)))

json.dump(out, open(os.path.join(os.path.dirname(__file__), 'code_extraction.json'), 'w', encoding='utf-8'),
          indent=2, ensure_ascii=False)
print('\nWrote code_extraction.json (%d docs)' % len(out))

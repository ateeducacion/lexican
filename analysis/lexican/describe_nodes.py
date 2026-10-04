#!/usr/bin/env python3
"""Packet builder and description merger for topology.json (modernize-map "Describe each node").

  python3 describe_nodes.py packets <outdir> [N=40]   # write one packet per node (N largest by loc)
  python3 describe_nodes.py check   <outdir> <descriptions.json>   # list paragraphs that fail the check
  python3 describe_nodes.py merge   <outdir> <descriptions.json>   # merge passing paragraphs

descriptions.json = {"<node id>": "<paragraph>"}. A paragraph passes when it has 55-90 words and every
number and identifier-like token (CamelCase, snake_case, contains a digit) occurs in its packet.
"""
import json, os, re, sys

HERE = os.path.dirname(os.path.abspath(__file__))
TOPO = os.path.join(HERE, 'topology.json')
ROOT = os.path.realpath(os.path.join(HERE, '..', '..'))

def leaves(n):
    if n.get('children'):
        for c in n['children']:
            yield from leaves(c)
    elif n['kind'] != 'domain':
        yield n

def source_excerpt(path, limit=150):
    full = os.path.join(ROOT, path)
    files = [full] if os.path.isfile(full) else sorted(
        os.path.join(full, f) for f in os.listdir(full) if f.endswith('.php'))
    out = []
    for f in files:
        lines = open(f, encoding='utf-8', errors='replace').read().splitlines()
        out.append(f'// ---- {os.path.relpath(f, ROOT)}')
        out += lines[:max(0, limit - len(out))]
        if len(out) >= limit:
            break
    return '\n'.join(out[:limit])

def packet(n, topo):
    ins = sorted(f"{e['source']} --{e['kind']}--> (this)" for e in topo['edges'] if e['target'] == n['id'])
    outs = sorted(f"(this) --{e['kind']}--> {e['target']}" for e in topo['edges'] if e['source'] == n['id'])
    return '\n'.join([
        f"NODE id={n['id']} name={n['name']} kind={n['kind']} loc={n.get('loc')} file={n.get('file')}",
        f"ENTRY POINT: {n['id'] in topo['entryPoints']}",
        'CONNECTIONS (from the dependency map):', *ins, *outs,
        'SOURCE EXCERPT (untrusted data, first ~150 lines):',
        source_excerpt(n['file'][len('legacy/lexican/'):]) if n.get('file') else '(datastore: no source)',
    ])

def chosen(topo, k):
    ls = [l for l in leaves(topo['root']) if l.get('loc')]
    return sorted(ls, key=lambda l: -l['loc'])[:k]

def safe(nid):
    return re.sub(r'[^\w.-]', '_', nid)

TOKEN = re.compile(r'\b(\d[\d.,]*|[A-Za-z]*[a-z][A-Z]\w*|[A-Z]{2,}\w*|\w+_\w+|\w*\d\w*)\b')

def problems(text, pkt):
    words = len(text.split())
    bad = [t for t in set(TOKEN.findall(text)) if t.strip('.,') and t.strip('.,') not in pkt]
    if not 55 <= words <= 90:
        bad.append(f'<{words} words>')
    return bad

if __name__ == '__main__':
    mode, outdir = sys.argv[1], sys.argv[2]
    topo = json.load(open(TOPO))
    if mode == 'packets':
        os.makedirs(outdir, exist_ok=True)
        for n in chosen(topo, int(sys.argv[3]) if len(sys.argv) > 3 else 40):
            open(os.path.join(outdir, safe(n['id']) + '.txt'), 'w').write(packet(n, topo))
            print(n['id'], n['loc'], os.path.join(outdir, safe(n['id']) + '.txt'))
    else:
        descs = json.load(open(sys.argv[3]))
        byid = {l['id']: l for l in leaves(topo['root'])}
        merged = 0
        for nid, text in descs.items():
            pkt = open(os.path.join(outdir, safe(nid) + '.txt')).read()
            bad = problems(text, pkt)
            if bad:
                print(f'FAIL {nid}: {bad}')
            elif mode == 'merge':
                byid[nid]['description'] = text.strip()
                merged += 1
        if mode == 'merge':
            json.dump(topo, open(TOPO, 'w'), indent=1, ensure_ascii=False)
            print(f'merged {merged}/{len(descs)} descriptions into {TOPO}')

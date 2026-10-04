#!/usr/bin/env python3
"""Extract the dependency topology of lexican (Laravel 8) into topology.json.

Rerunnable: python3 analysis/lexican/extract_topology.py
Reads legacy/lexican (routes, app/, views, migrations, seeders, bootstrap) and
writes analysis/lexican/topology.json. Persona flows and observations are
defined below; node `description`s already in topology.json (merged by describe_nodes.py) are kept.
"""
import json, os, re, sys, unicodedata
from collections import defaultdict, Counter

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.realpath(os.path.join(HERE, '..', '..', 'legacy', 'lexican'))
OUT = os.path.join(HERE, 'topology.json')

def rd(rel):
    with open(os.path.join(ROOT, rel), encoding='utf-8', errors='replace') as f:
        return unicodedata.normalize('NFC', f.read())

def loc(rel):
    p = os.path.join(ROOT, rel)
    if os.path.isdir(p):
        return sum(loc(os.path.join(rel, f)) for f in os.listdir(p) if f.endswith('.php'))
    return sum(1 for l in rd(rel).splitlines() if l.strip())

def strip_php_comments(s):
    """Drop // # and /* */ comments, leaving string literals intact."""
    out, i, n = [], 0, len(s)
    while i < n:
        c = s[i]
        if c in '\'"':
            j = i + 1
            while j < n and s[j] != c:
                j += 2 if s[j] == '\\' else 1
            out.append(s[i:j + 1]); i = j + 1
        elif s.startswith('/*', i):
            j = s.find('*/', i + 2); i = n if j < 0 else j + 2
        elif s.startswith('//', i) or (c == '#' and not s.startswith('#[', i)):
            j = s.find('\n', i); i = n if j < 0 else j
        else:
            out.append(c); i += 1
    return ''.join(out)

def strip_blade_comments(s):
    return re.sub(r'\{\{--.*?--\}\}', '', s, flags=re.S)

def walk(rel, ext):
    out = []
    for d, _, fs in os.walk(os.path.join(ROOT, rel)):
        for f in fs:
            if f.endswith(ext):
                out.append(os.path.relpath(os.path.join(d, f), ROOT))
    return sorted(out)

def nfc(x):
    return unicodedata.normalize('NFC', x)

nodes = {}            # id -> leaf dict
domain_of = {}        # id -> domain key
DOMAINS = {
    'entry': 'Entry points & bootstrap', 'auth': 'Auth (CAS + CAUCE)',
    'personal': 'Diccionario personal', 'aula': 'Diccionario de aula',
    'envios': 'Envíos', 'comentarios': 'Comentarios', 'export': 'PDF / CSV / Mail',
    'admin': 'Admin (Voyager)', 'shared': 'Shared / platform', 'models': 'Eloquent models',
    'screens': 'Screens (Blade views)', 'partials': 'Blade partials', 'data': 'Data stores',
}

def add(nid, kind, dom, file=None, name=None, lang='php'):
    n = {'id': nid, 'name': name or nid, 'kind': kind}
    if kind != 'datastore':
        n['language'] = lang
        n['loc'] = loc(file)
        n['file'] = 'legacy/lexican/' + file
    nodes[nid] = n
    domain_of[nid] = dom

# ---------------------------------------------------------------- code modules
def php_domain(rel):
    b = os.path.basename(rel)[:-4]
    if rel.startswith('app/Cas') or '/Auth/' in rel or rel.startswith('app/Http/Middleware'):
        return 'auth'
    if rel.startswith('app/Models'):
        return 'models'
    if '/Voyager/' in rel:
        return 'admin'
    if rel.startswith(('app/Http/Kernel', 'app/Console', 'app/Exceptions', 'app/Providers')):
        return 'entry'
    if re.search(r'DiccionarioPersonal|^dp(Acepciones|Diccionarios|Entradas|Medios)|mstEntradaValor', b):
        return 'personal'
    if re.search(r'DicAula|dicAula|daMedios', b):
        return 'aula'
    if re.search(r'Envio|envio|dpEnvios', b):
        return 'envios'
    if re.search(r'Comentario', b):
        return 'comentarios'
    if re.search(r'PDF|pdf|CSV|Mail|mail', b):
        return 'export'
    return 'shared'

php_files = [f for f in walk('app', '.php') if not f.endswith('/Config/cas.php')]
class_index = defaultdict(list)   # short class name -> [node id]
fqcn_index = {}                   # App\X\Y -> node id
file_of = {}
for rel in php_files:
    src = strip_php_comments(rd(rel))
    b = nfc(os.path.basename(rel)[:-4])
    if rel.startswith('app/Models/'):
        nid = 'model:' + b if '/Voyager/' not in rel else 'model:Voyager/' + b
    elif rel.startswith('app/Helpers/') or rel == 'app/Cas/Helper.php':
        nid = b if rel.startswith('app/Helpers/') else 'Cas/Helper'
    else:
        nid = nfc(rel[4:-4])   # e.g. Http/Controllers/DicAulaController
        nid = nid.replace('Http/Controllers/', '')
    add(nid, 'module', php_domain(rel), rel, name=nid.split('/')[-1] if not nid.startswith('model:') else nid[6:])
    file_of[nid] = rel
    m = re.search(r'namespace\s+([\w\\]+)\s*;', src)
    cm = re.search(r'^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait)\s+(\w+)', src, re.M)
    if cm:
        class_index[cm.group(1)].append(nid)
        if m:
            fqcn_index[m.group(1) + '\\' + cm.group(1)] = nid

for rel in walk('routes', '.php'):
    nid = 'routes/' + os.path.basename(rel)[:-4]
    add(nid, 'module', 'entry', rel, name=os.path.basename(rel))
    file_of[nid] = rel

# ---------------------------------------------------------------- views
VIEWROOT = 'resources/views/'
view_files = walk('resources/views', '.blade.php')
view_node = {}   # view name (dotted) -> node id
PART = VIEWROOT + 'layouts/partials/'
for rel in view_files:
    vname = rel[len(VIEWROOT):-len('.blade.php')].replace('/', '.')
    d = os.path.dirname(rel)
    if rel.startswith(PART) and d + '/' != PART:   # grouped per partial directory
        nid = 'view:' + d[len(VIEWROOT):] + '/*'
        if nid not in nodes:
            add(nid, 'screen', 'partials', d, name=d[len(PART):] + '/*', lang='blade')
            file_of[nid] = d
    else:
        nid = 'view:' + vname.replace('.', '/')
        add(nid, 'screen', 'partials' if rel.startswith(PART) else 'screens', rel,
            name=vname.replace('.', '/'), lang='blade')
        file_of[nid] = rel
    view_node[vname] = nid

# ---------------------------------------------------------------- data stores
tables = {}
for rel in walk('database/migrations', '.php'):
    for t in re.findall(r"Schema::create\(\s*'(\w+)'", strip_php_comments(rd(rel))):
        tables[t] = rel
model_table = {}
for nid in [n for n in nodes if n.startswith('model:')]:
    m = re.search(r"\$table\s*=\s*'(\w+)'", rd(file_of[nid]))
    t = m.group(1) if m else None
    if not t and nid == 'model:Voyager/DataType':
        t = 'data_types'
    if t:
        model_table[nid] = t
        tables.setdefault(t, None)
fqcn_index.setdefault('App\\User', 'User')
model_table['User'] = 'users'
for t in sorted(tables):
    add('ds:' + t, 'datastore', 'data', name=t)
EXT = {'ds:ext:cas-server': 'CAS SSO server', 'ds:ext:cauce-ws': 'CAUCE web service',
       'ds:ext:smtp': 'SMTP mail', 'ds:ext:file-storage': 'File storage (media uploads)'}
for k, v in EXT.items():
    add(k, 'datastore', 'data', name=v)

# ---------------------------------------------------------------- edges
edges = Counter()
def edge(s, t, k):
    if s != t and s in nodes and t in nodes:
        edges[(s, t, k)] += 1

# helper (global function) index
func_index = {}
for nid in nodes:
    rel = file_of.get(nid, '')
    if rel.startswith('app/Helpers/') or rel == 'app/Cas/Helper.php':
        for fn in re.findall(r'\bfunction\s+(\w+)\s*\(', strip_php_comments(rd(rel))):
            func_index.setdefault(fn, nid)
func_re = re.compile(r'(?<![\w$>:\\])(' + '|'.join(sorted(func_index, key=len, reverse=True)) + r')\s*\(')

# routes: name -> controller node, and route-file dispatch edges
route_name_ctl = {}
CTL_NS = 'App\\Http\\Controllers\\'
def ctl_node(spec):
    cls = spec.split('@')[0].replace('\\', '/')
    return cls if cls in nodes else None
unresolved_routes = []
for rf in ['routes/web', 'routes/api', 'routes/console', 'routes/channels']:
    src = strip_php_comments(rd(file_of[rf]))
    for m in re.finditer(r"Route::\w+\s*\(\s*['\"][^'\"]*['\"]\s*,\s*['\"]([\w\\]+@\w+)['\"]\s*\)([^;]*)", src):
        t = ctl_node(m.group(1))
        if t:
            edge(rf, t, 'dispatch')
            nm = re.search(r"->name\(\s*'([^']+)'", m.group(2))
            if nm:
                route_name_ctl[nm.group(1)] = t
        else:
            unresolved_routes.append(m.group(1))
    for m in re.finditer(r"Route::view\(\s*'[^']*'\s*,\s*'([^']+)'", src):
        v = m.group(1).replace('/', '.')
        if v in view_node:
            edge(rf, view_node[v], 'dispatch')
    if 'Voyager::routes()' in src:
        # config/voyager.php controllers.namespace = App\Http\Controllers\Voyager
        for n in nodes:
            if re.match(r'Voyager/Voyager\w+Controller$', n):
                edge(rf, n, 'dispatch')
        # BREAD data types map slugs to custom controllers (database/seeders/DataTypesTableSeeder.php)
        for c in re.findall(r"'controller'\s*=>\s*'App\\\\Http\\\\Controllers\\\\Voyager\\\\(\w+)'",
                            rd('database/seeders/DataTypesTableSeeder.php')):
            edge(rf, 'Voyager/' + c, 'dispatch')
    if re.search(r"\bcas\(\)", src):
        edge(rf, 'Cas/CasManager', 'dispatch')

# bootstrap / config-registered classes -> entry points
entry = ['routes/web', 'routes/api', 'routes/console', 'routes/channels']
for fq in re.findall(r'(App\\[\w\\]+)::class', rd('bootstrap/app.php')):
    if fq in fqcn_index:
        entry.append(fqcn_index[fq])
for fq in re.findall(r'^\s*(App\\[\w\\]+)::class', rd('config/app.php'), re.M):
    if fq in fqcn_index:
        entry.append(fqcn_index[fq])

dynamic = defaultdict(list)
WRITE_STATIC = r'(create|insert|insertGetId|updateOrCreate|firstOrCreate|updateOrInsert|destroy|forceCreate)'
def resolve_class(name, uses, ns):
    if name in uses and uses[name] in fqcn_index:
        return fqcn_index[uses[name]]
    if ns and ns + '\\' + name in fqcn_index:
        return fqcn_index[ns + '\\' + name]
    c = class_index.get(name, [])
    return c[0] if len(c) == 1 else None

def scan_php(nid, src):
    src = strip_php_comments(src)
    uses = {}
    for fq, alias in re.findall(r'^\s*use\s+([\w\\]+)(?:\s+as\s+(\w+))?\s*;', src, re.M):
        uses[alias or fq.split('\\')[-1]] = fq.lstrip('\\')
    m = re.search(r'namespace\s+([\w\\]+)\s*;', src)
    ns = m.group(1) if m else ''
    refs = set()
    for fq in re.findall(r'\\?(App\\[\w\\]+)', src):
        if fq in fqcn_index:
            refs.add(fqcn_index[fq])
    for name in set(re.findall(r'(?:new\s+|extends\s+|implements\s+)\\?(\w+)|\b(\w+)::', src)):
        for n in name:
            if n and n not in ('self', 'static', 'parent'):
                r = resolve_class(n, uses, ns)
                if r:
                    refs.add(r)
    for r in refs:
        if r.startswith('model:') or r == 'User':
            short = file_of[r].split('/')[-1][:-4]
            writes = re.search(r'(new\s+%s\b|\b%s::%s\b|\b%s::[^;]*->(update|delete|forceDelete|restore|increment|decrement)\()'
                               % (short, short, WRITE_STATIC, short), src)
            if not writes:  # $x = Model::find(..); ... $x->save()
                for var in re.findall(r'(\$\w+)\s*=\s*%s::' % short, src):
                    if re.search(re.escape(var) + r'->(save|update|delete|forceDelete|restore)\(', src):
                        writes = True
                        break
            edge(nid, r, 'write' if writes else 'read')
        else:
            edge(nid, r, 'call')
    for fn in func_re.findall(src):
        edge(nid, func_index[fn], 'call')
    for v in re.findall(r"(?:\bview|View::make|loadView|->view)\(\s*'([^']+)'", src):
        v = v.replace('/', '.')
        if v in view_node:
            edge(nid, view_node[v], 'call')
        else:
            dynamic['missing view (package or absent)'].append(f'{nid} -> {v}')
    # view($var): resolve against string assignments to that variable in the same file
    assigns = defaultdict(set)
    for var, val in re.findall(r"\$(\w+)\s*=\s*['\"]([\w./-]+)['\"]", src):
        assigns[var].add(val.strip('/').replace('/', '.'))
    for var in re.findall(r"(?:\bview|View::make|loadView|Voyager::view)\(\s*\$(\w+)", src):
        hits = [view_node[v] for v in assigns[var] if v in view_node]
        for h in hits:
            edge(nid, h, 'call')
        if not hits:
            dynamic['view($var)'].append(f'{nid} (${var})')
    if re.search(r"\bapp\(\s*'cas'\s*\)|\bcas\(\)", src):
        edge(nid, 'Cas/CasManager', 'dispatch')
    # direct SQL / query builder
    for t in re.findall(r"DB::table\(\s*['\"](\w+)", src):
        edge(nid, 'ds:' + t, 'read')
    for kw, t in re.findall(r"\b(from|join|update|into)\s+`?(\w+)`?", src, re.I):
        if t in tables and re.search(r'DB::(select|statement|update|insert|delete|raw)', src):
            edge(nid, 'ds:' + t, 'write' if kw.lower() in ('update', 'into') else 'read')
    # external systems
    if 'phpCAS' in src:
        edge(nid, 'ds:ext:cas-server', 'read')
    if 'CAUCE_WEBSERVICE' in src:
        edge(nid, 'ds:ext:cauce-ws', 'read')
    if re.search(r'\bMail::', src):
        edge(nid, 'ds:ext:smtp', 'write')
    if re.search(r'\bStorage::|->storeAs\(|->move\(|file_put_contents\(', src):
        edge(nid, 'ds:ext:file-storage', 'write')
    for m in re.finditer(r'call_user_func|new\s+\$\w+|\$\w+::\w+\(|app\(\s*\$', src):
        dynamic['dynamic class/call'].append(nid)

for nid in list(nodes):
    rel = file_of.get(nid)
    if rel and rel.endswith('.php') and not rel.endswith('.blade.php') and not nid.startswith('routes/'):
        scan_php(nid, rd(rel))
# routes files also call helpers / views
for rf in entry[:4]:
    for fn in func_re.findall(strip_php_comments(rd(file_of[rf]))):
        edge(rf, func_index[fn], 'call')

# model -> table
writers = {t for (s, t, k) in edges if k == 'write'}
for mdl, t in model_table.items():
    edge(mdl, 'ds:' + t, 'write' if mdl in writers else 'read')

# blade: include/extends/component -> partials; route('name') -> controller; helpers
for rel in view_files:
    vname = rel[len(VIEWROOT):-len('.blade.php')].replace('/', '.')
    nid = view_node[vname]
    src = strip_blade_comments(rd(rel))
    for v in re.findall(r"@(?:include|extends|includeIf|includeWhen|component|each)\(\s*(?:[^,'\"]*,\s*)?'([^']+)'", src):
        v = v.strip().replace('/', '.')
        if v in view_node:
            edge(nid, view_node[v], 'call')
    for r in re.findall(r"\broute\(\s*'([^']+)'", src):
        if r in route_name_ctl:
            edge(nid, route_name_ctl[r], 'dispatch')
    for fn in func_re.findall(src):
        edge(nid, func_index[fn], 'call')
    for mdl in re.findall(r'App\\Models\\(\w+)', src):
        if 'model:' + mdl in nodes:
            edge(nid, 'model:' + mdl, 'read')

# ---------------------------------------------------------------- dead ends
inbound = Counter(t for (s, t, k) in edges)
entry = sorted(set(entry))
dead, suppressed = [], []
# Voyager::view($view) resolves to the package's voyager:: namespace, never to app screens
app_dynamic_views = [d for d in dynamic.get('view($var)', []) if not d.startswith('Voyager/')]
for nid, n in nodes.items():
    if n['kind'] == 'datastore' or nid in entry or inbound[nid]:
        continue
    rel = file_of.get(nid, '')
    # convention-wired / container-resolved: never call these dead
    if rel.startswith(('app/Policies', 'app/Cas/Contracts', 'app/Cas/Facades', 'app/Cas/Config')) \
            or (n['kind'] == 'screen' and app_dynamic_views) \
            or rel.startswith('resources/views/errors') or rel.startswith('resources/views/vendor'):
        suppressed.append(nid)
    else:
        dead.append(nid)

# ---------------------------------------------------------------- assemble
prev = {}
if os.path.exists(OUT):
    prev = json.load(open(OUT))
prev_desc = {}
def collect(n):
    if 'description' in n:
        prev_desc[n['id']] = n['description']
    for c in n.get('children', []):
        collect(c)
if prev.get('root'):
    collect(prev['root'])
for nid, d in prev_desc.items():
    if nid in nodes:
        nodes[nid]['description'] = d

children = []
for dk, dn in DOMAINS.items():
    kids = sorted((nodes[n] for n in nodes if domain_of[n] == dk), key=lambda x: x['id'])
    if kids:
        children.append({'id': 'dom:' + dk, 'name': dn, 'kind': 'domain', 'children': kids})
# ---------------------------------------------------------------- persona flows (traced by hand
# from routes/web.php -> controller method -> helper -> model; node ids are checked below)
F = lambda label, *n: {'label': label, 'nodes': list(n)}
FLOWS = [
    {'name': 'Crear una entrada con sus acepciones', 'persona': 'Alumno',
     'description': 'A student adds a new word to their personal dictionary and writes one or more senses for it, with topics and media.',
     'steps': [
        F('Opens the personal dictionary', 'routes/web', 'DiccionarioPersonalController', 'view:diccionario/personal'),
        F('Starts a new entry from the search box', 'DiccionarioPersonalController', 'view:diccionario/dpEntrada/masterEntrada', 'view:layouts/partials/dpEntrada/*'),
        F('Fills in the sense form (definition, example, topics)', 'view:layouts/partials/dpAcepcion/*', 'mstEntradaValor_helper'),
        F('Saves the entry together with its first sense', 'DiccionarioPersonalController', 'dpEntradas_helper', 'model:DiccionarioPersonalEntrada', 'ds:dp_entradas'),
        F('Stores the sense and its topics', 'dpAcepciones_helper', 'model:DiccionarioPersonalAcepcion', 'ds:dp_acepciones', 'model:DiccionarioPersonalAcepcionTematica', 'ds:dp_acepciones_tematicas'),
        F('Attaches an image, audio or video to the sense', 'dpMedios_helper', 'model:DiccionarioPersonalAcepcionMedio', 'ds:dp_acepciones_medios', 'ds:ext:file-storage'),
     ]},
    {'name': 'Enviar entradas al diccionario de aula', 'persona': 'Alumno',
     'description': 'A student submits one entry, or the whole personal dictionary, to the classroom dictionaries they belong to, for the teacher to review.',
     'steps': [
        F('Picks the classroom dictionaries to send to', 'EnviosController', 'view:layouts/partials/components/*', 'model:DicAula'),
        F('Or chooses to send the whole personal dictionary', 'EnviosController', 'view:diccionario/dpDiccionario/dpDiccionarioEnviarFormulario'),
        F('Submits the entry', 'routes/web', 'EnviosController', 'dpEnvios_helper'),
        F('A submission is recorded per classroom dictionary', 'dpEnvios_helper', 'model:Envio', 'ds:dp_envios'),
        F('The entry and its senses are copied into the submission', 'model:EnvioEntrada', 'ds:envios_entradas', 'model:EnvioAcepcion', 'ds:envios_acepciones', 'model:EnvioAcepcionTematica', 'model:EnvioAcepcionMedio'),
     ]},
    {'name': 'Revisar, publicar y comentar envíos', 'persona': 'Profesor',
     'description': 'A teacher reviews what students sent to a classroom dictionary, corrects senses, publishes the good ones and leaves comments.',
     'steps': [
        F('Opens the list of submitted entries', 'routes/web', 'DicAulaController', 'view:diccionario/entradasAula', 'model:EnvioEntrada'),
        F('Only teachers who coordinate the dictionary may act', 'Providers/AuthServiceProvider', 'Policies/DicAulaPolicy'),
        F('Corrects a submitted sense', 'EnvioAcepcionController', 'view:diccionario/aula/acepcionEdit', 'envioAcepcion_helper', 'model:EnvioAcepcion', 'ds:envios_acepciones'),
        F('Publishes the entry (one or a list)', 'DicAulaController', 'dicAula_helper', 'model:DicAulaEntrada', 'ds:dic_aula_entradas', 'ds:envios_entradas'),
        F('Comments on a single entry', 'dicAula_helper', 'model:ComentarioEntrada', 'ds:comentarios_entradas'),
        F('Writes a general comment on a student dictionary', 'ComentariosController', 'ComentariosHelper', 'model:ComentarioGeneral', 'ds:comentarios_generales'),
        F('The student reads the comments', 'view:comentarios/comentariosDiccionarioVer'),
     ]},
    {'name': 'Crear un diccionario de aula y unirse con código', 'persona': 'Profesor',
     'description': 'A teacher creates a classroom dictionary that gets a join code; students type that code to become participants.',
     'steps': [
        F('Opens the creation form (type, guidelines, fields)', 'routes/web', 'DicAulaController', 'view:diccionario/crearDicAula', 'model:Pautas', 'model:TipoDiccionarioAula'),
        F('A unique join code is generated and the dictionary saved', 'dicAula_helper', 'model:DicAula', 'ds:dic_aula', 'model:DicAulaPauta', 'ds:dic_aula_pautas', 'model:DicAulaCampo'),
        F('Optionally invites another teacher by e-mail', 'DicAulaController', 'mail_helper', 'ds:ext:smtp'),
        F('The student opens the join dialog and types the code', 'EnviosController', 'view:layouts/partials/components/*'),
        F('The student becomes a participant', 'DicAulaController', 'dicAula_helper', 'model:DicAulaParticipante', 'ds:dic_aula_participantes'),
     ]},
    {'name': 'Iniciar sesión con CAS y validar en CAUCE', 'persona': 'Alumno o profesor',
     'description': 'A user signs in with the regional single sign-on and the app checks their role and school against CAUCE before letting them in.',
     'steps': [
        F('An anonymous request is sent to the login page', 'Http/Kernel', 'Http/Middleware/Authenticate', 'routes/web'),
        F('Signs in on the CAS server', 'Cas/Helper', 'Cas/CasManager', 'ds:ext:cas-server'),
        F('CAS calls back; the user is checked against CAUCE', 'Auth/CasController', 'global_helper', 'ds:ext:cauce-ws'),
        F('User, person and school are created or updated', 'global_helper', 'User', 'ds:users', 'model:Persona', 'ds:personas', 'model:UserPersona', 'ds:users_personas', 'model:Centro', 'ds:centros'),
        F('Lands on the personal dictionary (admins on the Voyager panel)', 'routes/web', 'DiccionarioPersonalController', 'Voyager/VoyagerController'),
     ]},
]

# ---------------------------------------------------------------- observations
helpers = [n for n in nodes if file_of.get(n, '').startswith('app/Helpers/')]
hloc = sum(nodes[n]['loc'] for n in helpers)
ctl_loc = sum(nodes[n]['loc'] for n in nodes if file_of.get(n, '').startswith('app/Http/Controllers/'))
fan = Counter(t for (s, t, k) in edges)
ds_writers = defaultdict(set)
for (s, t, k) in edges:
    if k == 'write' and t.startswith('model:Envio'):
        ds_writers['envios_*'].add(s)
cyc = [(a, b) for (a, b, k) in edges if k == 'call' and (b, a, 'call') in edges and a < b and a in helpers and b in helpers]
raw_tables = sorted(t[3:] for (s, t, k) in edges if s == 'model:DicAula' and k == 'write' and t.startswith('ds:'))
missing_all = sorted(set(dynamic.get('missing view (package or absent)', [])))
missing_views = [m for m in missing_all if 'voyager::' in m]
broken_views = [m.split(' -> ')[1] for m in missing_all if 'voyager::' not in m]
OBS = [
    f'Business logic lives in {len(helpers)} global-function helper files ({hloc} LOC, more than the {ctl_loc} LOC of controllers), loaded by HelperServiceProvider with glob(). global_helper has the highest fan-in ({fan["global_helper"]}) and the helpers call each other in cycles ({", ".join(a + "<->" + b for a, b in cyc)}): treat them as the real service layer when extracting rules.',
    f'The envíos pipeline is the most shared write path: the envios_* models are written by {len(ds_writers["envios_*"])} modules ({", ".join(sorted(ds_writers["envios_*"]))}). DicAula::borrar() cascades with raw DB::statement over {len(raw_tables)} tables ({", ".join(raw_tables)}), bypassing Eloquent and the audit trail.',
    'Identity is a single point of failure: every login goes CAS -> Auth/CasController -> userValidatorCAUCE() in global_helper, which calls the CAUCE web service and creates or updates users, personas, users_personas and centros on the fly. The GitHub Pages demo needs both CAS and CAUCE replaced by a local mock.',
    f'Admin is Voyager: Voyager::routes() dispatches to the App\\Http\\Controllers\\Voyager namespace (config/voyager.php) and BREAD slugs reach vDicAulaController, vDicPersonalController and vCentrosAñoEscolarController only through data_types rows seeded in DataTypesTableSeeder. {len(missing_views)} view() calls target voyager:: templates absent from this repo; the custom ones (compass.vigencia, compass.dicsCursoEsc, compass.dicsPersonales, compass.centrosAñoEscolar, compass.record) are not in the Voyager package either, so those admin screens cannot be rebuilt from source, and VoyagerBaseController renders Voyager::view($view) dynamically.' + (f' App views referenced but absent: {", ".join(broken_views)}.' if broken_views else ''),
    'Dead-end candidates: the Laravel auth scaffolding (Login/Register/ForgotPassword/ResetPassword/Verification controllers and auth/* views) is unreachable because login is CAS-only; CentroDicAula maps centros_dic_aula, dropped by the 2021_02_11 migration; UserCentro, EnsenanzaEstudio and EstudioAreaMateria are only used by seeders; HealthCheckController is unrouted (/health-check is a closure). Debug routes (/test, /pdf/paginapdf, /mail/paginamail, aula/DEV_unirseGET) ship in production routes.',
    f'Edges are a lower bound: writes through relations ($x->rel()->create()) and {len(dynamic.get("dynamic class/call", []))} dynamic call sites (call_user_func, new $class, $class::method) are not resolved; the authorization checks ($this->authorize with DicAulaPolicy and friends) are wired by convention through AuthServiceProvider.',
]

topo = {
    'system': 'Lexican',
    'root': {'id': 'sys', 'name': 'lexican', 'kind': 'system', 'children': children},
    'edges': [{'source': s, 'target': t, 'kind': k} for (s, t, k) in sorted(edges)],
    'entryPoints': entry,
    'deadEnds': sorted(dead),
    'observations': OBS,
    'flows': FLOWS,
}
# integrity: every endpoint is a leaf, no duplicate locations
locs = Counter(n.get('file') for n in nodes.values() if n.get('file'))
assert all(c == 1 for c in locs.values()), [l for l, c in locs.items() if c > 1]
for e in topo['edges']:
    assert e['source'] in nodes and e['target'] in nodes, e
for f in topo['flows']:
    for st in f['steps']:
        for n in st['nodes']:
            assert n in nodes, (f['name'], n)
with open(OUT, 'w') as f:
    json.dump(topo, f, indent=1, ensure_ascii=False)

# ---------------------------------------------------------------- summary
kinds = Counter(n['kind'] for n in nodes.values())
ek = Counter(k for (_, _, k) in edges)
print(f'source: {ROOT}')
print(f'leaves: {len(nodes)} ' + ', '.join(f'{k}={v}' for k, v in sorted(kinds.items())))
print(f'edges:  {len(edges)} ' + ', '.join(f'{k}={v}' for k, v in sorted(ek.items())))
print(f'entry points ({len(entry)}): ' + ', '.join(entry))
for dk, dn in DOMAINS.items():
    ids = [n for n in nodes if domain_of[n] == dk]
    print(f'  {dn:28} {len(ids):4} leaves {sum(nodes[i].get("loc", 0) for i in ids):6} loc')
fan_in = Counter(t for (s, t, k) in edges if not t.startswith('ds:'))
print('top fan-in: ' + ', '.join(f'{n}({c})' for n, c in fan_in.most_common(8)))
w = defaultdict(set)
for (s, t, k) in edges:
    if t.startswith('ds:') and k == 'write':
        w[t].add(s)
mw = Counter()
for (s, t, k) in edges:
    if k == 'write' and t.startswith('model:'):
        mw[t] += 1
print('most-written models (writer count): ' + ', '.join(f'{n[6:]}({c})' for n, c in mw.most_common(6)))
print(f'dead-end candidates ({len(dead)}): ' + ', '.join(sorted(dead)))
print(f'suppressed (convention/dynamic wiring, {len(suppressed)}): ' + ', '.join(sorted(suppressed)[:15]) + (' ...' if len(suppressed) > 15 else ''))
for k, v in dynamic.items():
    print(f'unresolved [{k}] {len(v)} sites, e.g. ' + '; '.join(sorted(set(v))[:4]))
if unresolved_routes:
    print('unresolved route targets: ' + ', '.join(sorted(set(unresolved_routes))))
print(f'wrote {OUT}')

// Guards the published bundles (§87, §59). Every text file is scanned: JS chunks, the demo Web Worker chunk, CSS,
// HTML and sourcemaps (whose embedded sources would leak anything the code contains).
// - demo (apps/web/dist-demo): static, no secrets, no internal hosts; the public test CAS host is the one external
//   address allowed, and only here;
// - production web (apps/web/dist): no PGlite, WASM, worker, demo API or demo passwords (it may name the test CAS:
//   the same SPA serves the local Docker profile; the server decides, and refuses it in production);
// - API bundle (apps/api/dist): no secrets, no test CAS host baked in except as the APP_ENV=local default.
import { readdirSync, readFileSync, statSync, existsSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { join } from 'node:path';

const files = (dir) =>
  readdirSync(dir).flatMap((f) => {
    const p = join(dir, f);
    return statSync(p).isDirectory() ? files(p) : [p];
  });
const textFiles = (dir) => files(dir).filter((f) => /\.(js|css|html|json|txt|svg|map)$/.test(f));

const forbiddenEverywhere = [
  [/gobiernodecanarias\.(org|net)/i, 'internal/institutional host'],
  [/medusa\.gobiernodecanarias/i, 'internal host'],
  [/BEGIN (RSA |EC )?PRIVATE KEY/, 'private key'],
  [
    /\b(?:CAUCE_TOKEN|CAUCE_BEARER|DATABASE_URL)\s*[:=]\s*["'][^"']+["']/,
    'secret-looking assignment',
  ],
  [/base64:[A-Za-z0-9+/]{30,}={0,2}/, 'Laravel APP_KEY'],
];

// SHA-256 of the CAUCE tokens exposed in the legacy history (docs/SECURITY.md); values are never stored here.
const LEAKED_TOKEN_SHA256 = new Set([
  '884248383278710d4fb104b5a729d539c36b5fefa5de3009c000ec361061cbe7',
  '2a2eca6807231c7f7c44b150bca1d89111453068c690db0538cd7de5fb27549e',
]);

let failures = 0;
const fail = (msg) => {
  failures++;
  console.error(`✗ ${msg}`);
};

function scan(dir, extra = []) {
  if (!existsSync(dir)) return fail(`${dir} does not exist (build it first)`);
  for (const f of textFiles(dir)) {
    const text = readFileSync(f, 'utf8');
    for (const [re, what] of [...forbiddenEverywhere, ...extra])
      if (re.test(text)) fail(`${f}: ${what}`);
    for (const token of text.match(/[A-Za-z0-9]{32}/g) ?? [])
      if (LEAKED_TOKEN_SHA256.has(createHash('sha256').update(token).digest('hex')))
        fail(`${f}: leaked legacy CAUCE token`);
  }
}

scan('apps/web/dist-demo');
const demoFiles = existsSync('apps/web/dist-demo') ? files('apps/web/dist-demo') : [];
if (!demoFiles.some((f) => /assets\/worker-[\w-]+\.js$/.test(f)))
  fail('demo build has no Web Worker chunk');
if (!demoFiles.some((f) => f.endsWith('.wasm'))) fail('demo build has no PGlite WASM asset');
const demoIndex = existsSync('apps/web/dist-demo/index.html')
  ? readFileSync('apps/web/dist-demo/index.html', 'utf8')
  : '';
if (!existsSync('apps/web/dist-demo/og-image.jpg')) fail('missing og-image.jpg in the demo build');
if (!demoIndex.includes('og:image')) fail('demo index.html has no og:image');
if (!demoIndex.includes('/lexican/'))
  fail('demo index.html is not built for the /lexican/ base path');

if (existsSync('apps/web/dist')) {
  scan('apps/web/dist', [
    [/pglite/i, 'PGlite in the production bundle'],
    [/contraseña: |alumno1@ejemplo\.com/, 'demo credentials in the production bundle'],
    [
      /lexican-demo-database|lexican-demo-media|Origen de la petición no permitido/,
      'demo API in the production bundle',
    ],
  ]);
  if (files('apps/web/dist').some((f) => f.endsWith('.wasm')))
    fail('WASM in the production bundle');
  if (files('apps/web/dist').some((f) => /worker-[\w-]+\.js$/.test(f)))
    fail('worker in the production bundle');
}

if (existsSync('apps/api/dist')) scan('apps/api/dist');

if (failures) process.exit(1);
console.log('✓ dist checks passed');

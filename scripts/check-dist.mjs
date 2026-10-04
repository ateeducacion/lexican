// Guards the published bundles (§87, §59):
// - demo (apps/web/dist-demo): static, no secrets, no internal hosts, no API calls;
// - production web (apps/web/dist): no PGlite and no demo passwords.
import { readdirSync, readFileSync, statSync, existsSync } from 'node:fs';
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
  [/1x4XRIP|4CLCRxC/, 'leaked legacy CAUCE token'],
];

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
  }
}

scan('apps/web/dist-demo');
const demoIndex = existsSync('apps/web/dist-demo/index.html')
  ? readFileSync('apps/web/dist-demo/index.html', 'utf8')
  : '';
if (!demoIndex.includes('/lexican/'))
  fail('demo index.html is not built for the /lexican/ base path');

if (existsSync('apps/web/dist')) {
  scan('apps/web/dist', [
    [/pglite/i, 'PGlite in the production bundle'],
    [/contraseña: |alumno1@ejemplo\.com/, 'demo credentials in the production bundle'],
  ]);
  if (files('apps/web/dist').some((f) => f.endsWith('.wasm')))
    fail('WASM in the production bundle');
}

if (failures) process.exit(1);
console.log('✓ dist checks passed');

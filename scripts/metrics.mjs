// Reproducible metrics for docs/MODERNIZATION-REPORT.md: upstream (legacy snapshot) vs the working tree.
// Every number comes from git, the file system or a build; nothing is typed by hand (§98).
// Usage: bun run build && bun run build:demo && bun scripts/metrics.mjs > metrics.json
import { execFileSync, execSync } from 'node:child_process';
import { existsSync, readdirSync, readFileSync, statSync } from 'node:fs';
import { gzipSync } from 'node:zlib';
import { join } from 'node:path';
import { packages } from './deps.mjs';

const git = (...args) =>
  execFileSync('git', args, { encoding: 'utf8', maxBuffer: 256 * 1024 * 1024 }).trim();
const REF = process.env.LEGACY_REF ?? 'origin/upstream';
const legacyFile = (p) => {
  try {
    return git('show', `${REF}:${p}`);
  } catch {
    return '';
  }
};
const lsTree = (ref) =>
  git('ls-tree', '-r', '-l', ref)
    .split('\n')
    .map((l) => {
      const [meta, path] = l.split('\t');
      return { path, size: Number(meta.trim().split(/\s+/)[3]) || 0 };
    });
const countMatches = (text, re) => (text.match(re) ?? []).length;

function walk(dir, filter = () => true) {
  if (!existsSync(dir)) return [];
  return readdirSync(dir).flatMap((f) => {
    const p = join(dir, f);
    if (f === 'node_modules' || f === '.git') return [];
    return statSync(p).isDirectory() ? walk(p, filter) : filter(p) ? [p] : [];
  });
}
const lines = (files) => files.reduce((n, f) => n + readFileSync(f, 'utf8').split('\n').length, 0);

// ---- legacy (upstream) ----
const legacyTree = lsTree(REF);
const legacyCode = legacyTree.filter(
  (f) =>
    /\.(php|js|vue|scss|css)$/.test(f.path) &&
    !/^public\/(js|css)\//.test(f.path) &&
    !/vendor\//.test(f.path),
);
const composer = JSON.parse(legacyFile('composer.json') || '{}');
const legacyPkg = JSON.parse(legacyFile('package.json') || '{}');
const migrationsText = git('ls-tree', '-r', '--name-only', REF, 'database/migrations')
  .split('\n')
  .map(legacyFile)
  .join('\n');
const routesText = ['routes/web.php', 'routes/api.php'].map(legacyFile).join('\n');
const legacyTests = git('ls-tree', '-r', '--name-only', REF, 'tests')
  .split('\n')
  .filter((p) => p.endsWith('Test.php'))
  .map(legacyFile)
  .join('\n');

const legacy = {
  ref: REF,
  sha: git('rev-parse', REF),
  trackedFiles: legacyTree.length,
  trackedBytes: legacyTree.reduce((n, f) => n + f.size, 0),
  binaryOrMediaBytes: legacyTree
    .filter((f) => /\.(png|jpe?g|gif|svg|pdf|phar|ico|woff2?|ttf|eot|mp4|mp3)$/i.test(f.path))
    .reduce((n, f) => n + f.size, 0),
  dsStore: legacyTree.filter((f) => f.path.endsWith('.DS_Store')).length,
  handWrittenSourceFiles: legacyCode.length,
  phpDependencies:
    Object.keys(composer.require ?? {}).length + Object.keys(composer['require-dev'] ?? {}).length,
  npmDependencies:
    Object.keys(legacyPkg.dependencies ?? {}).length +
    Object.keys(legacyPkg.devDependencies ?? {}).length,
  routes: countMatches(routesText, /Route::(get|post|put|patch|delete|any|match|view)\s*\(/g),
  tablesCreated: countMatches(migrationsText, /Schema::create\(/g),
  controllers: legacyTree.filter((f) => /^app\/Http\/Controllers\/.*\.php$/.test(f.path)).length,
  models: legacyTree.filter((f) => /^app\/(Models\/.*|User)\.php$/.test(f.path)).length,
  testMethods: countMatches(legacyTests, /public function test\w*\(/g),
  ci: legacyTree.some((f) => f.path.startsWith('.github/workflows/')),
};

// ---- modern (working tree) ----
const src = walk('.', (p) =>
  /^(apps|packages|tools)\/.+\/src\/.+\.(ts|tsx|css)$/.test(p.replaceAll('\\', '/')),
);
const tests = src.filter((p) => /\.test\.tsx?$/.test(p));
const rootPkg = JSON.parse(readFileSync('package.json', 'utf8'));
const prodDeps = packages({ prod: true });
const allDeps = packages({ prod: false });
const workspaceDirect = new Set(
  [
    'package.json',
    ...walk('apps', (p) => p.endsWith('/package.json')),
    ...walk('packages', (p) => p.endsWith('/package.json')),
    ...walk('tools', (p) => p.endsWith('/package.json')),
  ]
    .filter((p) => !p.includes('node_modules'))
    .flatMap((p) => {
      const j = JSON.parse(readFileSync(p, 'utf8'));
      return [...Object.keys(j.dependencies ?? {}), ...Object.keys(j.devDependencies ?? {})];
    })
    .filter((n) => !n.startsWith('@lexican/')),
);
const schema = readFileSync('packages/db/src/schema.ts', 'utf8');
const ops = readFileSync('packages/core/src/operations.ts', 'utf8');
const e2e = walk('e2e', (p) => p.endsWith('.spec.ts'));

function bundle(dir) {
  if (!existsSync(dir)) return null;
  const index = readFileSync(join(dir, 'index.html'), 'utf8');
  const initial = [...index.matchAll(/(?:src|href)="([^"]+\.(?:js|css))"/g)].map((m) =>
    m[1].replace(/^.*\/assets\//, 'assets/'),
  );
  const size = (files) =>
    files.reduce((n, f) => n + (existsSync(join(dir, f)) ? statSync(join(dir, f)).size : 0), 0);
  const gz = (files) =>
    files.reduce(
      (n, f) => n + (existsSync(join(dir, f)) ? gzipSync(readFileSync(join(dir, f))).length : 0),
      0,
    );
  const all = walk(dir).map((p) => p.slice(dir.length + 1));
  return {
    totalBytes: size(all),
    initialRequests: initial.length + 1,
    initialJsCssBytes: size(initial),
    initialJsCssGzipBytes: gz(initial),
    wasmBytes: size(all.filter((f) => f.endsWith('.wasm') || f.endsWith('.data'))),
  };
}

const modern = {
  sha: git('rev-parse', 'HEAD'),
  handWrittenSourceFiles: src.length - tests.length,
  sourceLines: lines(src.filter((p) => !tests.includes(p))),
  testFiles: tests.length,
  testLines: lines(tests),
  e2eSpecs: e2e.length,
  directDependencies: workspaceDirect.size,
  productionPackagesInstalled: prodDeps.length,
  allPackagesInstalled: allDeps.length,
  apiOperations: countMatches(ops, /\bop\(\s*'(?:GET|POST|PUT|PATCH|DELETE)'/g),
  tables: countMatches(schema, /pgTable\(/g),
  enums: countMatches(schema, /pgEnum\(/g),
  workflows: walk('.github/workflows', (p) => p.endsWith('.yml')).length,
  nodeEngine: rootPkg.engines?.node,
  webProductionBundle: bundle('apps/web/dist'),
  webDemoBundle: bundle('apps/web/dist-demo'),
};

// bun audit exits non-zero when it finds vulnerabilities; its JSON is on stdout either way.
const auditJson = (() => {
  try {
    return execSync('bun audit --json', { encoding: 'utf8' });
  } catch (err) {
    return err.stdout?.toString() ?? '{}';
  }
})();
const audit = {
  // bun audit --json: { "<package>": [advisory, …] } (all dependencies, dev included).
  vulnerabilities: Object.values(JSON.parse(auditJson)).flat().length,
};

console.log(
  JSON.stringify({ generatedAt: new Date().toISOString(), legacy, modern, audit }, null, 2),
);

// Fails when a production dependency (anything shipped in the API image or the web bundles) has a licence
// outside the allow-list (§90, docs/LICENSING.md). Dev tooling is reported, not gated.
import { packages } from './deps.mjs';

const ALLOWED = new Set([
  'MIT',
  // Fonts only: self-hosted Lexend and Literata (docs/LICENSING.md).
  'OFL-1.1',
  'ISC',
  'Apache-2.0',
  'BSD-2-Clause',
  'BSD-3-Clause',
  '0BSD',
  'BlueOak-1.0.0',
  'CC0-1.0',
  'Unlicense',
  'MIT-0',
  'Python-2.0',
]);
const ok = (lic) =>
  !!lic &&
  lic
    .replace(/[()]/g, '')
    .split(/\s+OR\s+/)
    .some((l) => ALLOWED.has(l.trim()));

const seen = new Map();
for (const p of packages({ prod: true })) seen.set(`${p.name}@${p.version}`, p.license);

const bad = [...seen].filter(([, lic]) => !ok(lic));
const counts = {};
for (const lic of seen.values()) counts[lic ?? 'UNKNOWN'] = (counts[lic ?? 'UNKNOWN'] ?? 0) + 1;
console.log(`Production dependencies: ${seen.size}`);
console.log(
  Object.entries(counts)
    .sort((a, b) => b[1] - a[1])
    .map(([l, n]) => `  ${l}: ${n}`)
    .join('\n'),
);
if (bad.length) {
  console.error('Not allowed:\n' + bad.map(([n, l]) => `  ${n}: ${l ?? 'UNKNOWN'}`).join('\n'));
  process.exit(1);
}

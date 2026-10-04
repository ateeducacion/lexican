// Installed third-party packages as Bun's package manager resolves them (bun.lock + node_modules).
// prod: only what production code depends on (`bun pm licenses --prod`): shipped in the API image or a web bundle.
import { execFileSync } from 'node:child_process';

/** @returns {{ name: string, version: string, license: string | null, homepage?: string }[]} */
export function packages({ prod }) {
  const out = execFileSync('bun', ['pm', 'licenses', '--json', ...(prod ? ['--prod'] : [])], {
    encoding: 'utf8',
    maxBuffer: 64 * 1024 * 1024,
  });
  const list = [];
  for (const [license, pkgs] of Object.entries(JSON.parse(out)))
    for (const p of pkgs)
      if (!p.name.startsWith('@lexican/'))
        for (const version of p.versions)
          list.push({
            name: p.name,
            version,
            license: license === 'UNKNOWN' ? null : license,
            homepage: p.homepage,
          });
  return list.sort((a, b) => `${a.name}@${a.version}`.localeCompare(`${b.name}@${b.version}`));
}

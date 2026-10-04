// bun.lock must stay at lockfileVersion 1 until Dependabot reads v2 (dependabot-core#16026). Bun 1.4 keeps an existing
// v1 lockfile on install/add/remove; only regenerating it from scratch writes v2. To regenerate:
//   rm bun.lock && bunx bun@1.3.14 install
import { readFileSync } from 'node:fs';

const version = readFileSync('bun.lock', 'utf8').match(/"lockfileVersion":\s*(\d+)/)?.[1];
if (version !== '1') {
  console.error(
    `bun.lock has lockfileVersion ${version ?? '?'}; it must be 1 for Dependabot. Regenerate it with:\n` +
      '  rm bun.lock && bunx bun@1.3.14 install',
  );
  process.exit(1);
}
console.log('✓ bun.lock lockfileVersion 1');

import { cpSync, rmSync } from 'node:fs';
import { build } from 'esbuild';

// Bundle our TypeScript workspaces (@lexican/*) into dist; npm dependencies stay external (node_modules).
const workspace = /^@lexican\//;
rmSync('dist', { recursive: true, force: true });
await build({
  entryPoints: { server: 'src/server.ts', 'cli/migrate': 'src/cli/migrate.ts', 'cli/seed': 'src/cli/seed.ts' },
  outdir: 'dist',
  bundle: true,
  platform: 'node',
  format: 'esm',
  target: 'node24',
  sourcemap: true,
  logLevel: 'info',
  plugins: [
    {
      name: 'externalize-npm-deps',
      setup(b) {
        b.onResolve({ filter: /^[^./]/ }, (args) =>
          workspace.test(args.path) || args.kind === 'entry-point' ? undefined : { path: args.path, external: true },
        );
      },
    },
  ],
});
cpSync('../../packages/db/migrations', 'dist/migrations', { recursive: true });

import { execSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import react from '@vitejs/plugin-react';
import { defineConfig, type Plugin } from 'vite';

const commit = (() => {
  try {
    return execSync('git rev-parse --short HEAD', { stdio: ['ignore', 'pipe', 'ignore'] })
      .toString()
      .trim();
  } catch {
    return 'dev';
  }
})();

/**
 * Emits licenses.json with the third-party packages actually bundled into this build (name, version,
 * licence), read from each module's package.json. Shown in the app's "Licencias" panel.
 */
function bundledLicenses(): Plugin {
  return {
    name: 'lexican-bundled-licenses',
    apply: 'build',
    generateBundle() {
      const found = new Map<string, { name: string; version: string; license: string }>();
      for (const id of this.getModuleIds()) {
        const m = id.match(/^(.*[\\/]node_modules[\\/](?:@[^\\/]+[\\/])?[^\\/]+)/);
        if (!m) continue;
        let dir = m[1]!;
        while (!existsSync(join(dir, 'package.json')) && dir !== dirname(dir)) dir = dirname(dir);
        const pkg = JSON.parse(readFileSync(join(dir, 'package.json'), 'utf8')) as {
          name: string;
          version: string;
          license?: string;
        };
        if (pkg.name.startsWith('@lexican/')) continue;
        found.set(pkg.name, {
          name: pkg.name,
          version: pkg.version,
          license: pkg.license ?? 'Ver paquete',
        });
      }
      const list = [...found.values()].sort((a, b) => a.name.localeCompare(b.name));
      this.emitFile({ type: 'asset', fileName: 'licenses.json', source: JSON.stringify(list) });
    },
  };
}

// Absolute site URL for link previews (og:image); override per deployment:
// VITE_SITE_URL=https://example.org/ npm run build
process.env.VITE_SITE_URL ??= 'https://ateeducacion.github.io/lexican/';

// `demo` mode = static GitHub Pages build with PGlite; any other mode = production SPA against /api.
export default defineConfig(({ mode }) => ({
  base: mode === 'demo' ? (process.env.DEMO_BASE ?? '/lexican/') : '/',
  plugins: [react(), bundledLicenses()],
  define: {
    __DEMO__: JSON.stringify(mode === 'demo'),
    // Product version lives in the root package.json (workspaces are 0.0.0).
    __APP_VERSION__: JSON.stringify(
      (
        JSON.parse(readFileSync(new URL('../../package.json', import.meta.url), 'utf8')) as {
          version: string;
        }
      ).version,
    ),
    __APP_COMMIT__: JSON.stringify(commit),
  },
  optimizeDeps: { exclude: ['@electric-sql/pglite'] },
  build: {
    target: 'es2022',
    outDir: mode === 'demo' ? 'dist-demo' : 'dist',
    sourcemap: mode === 'demo',
    rolldownOptions: {
      output: {
        // PGlite (the in-browser PostgreSQL, demo only) in its own long-cached chunk, separate from app code.
        codeSplitting: {
          groups: [{ name: 'pglite', test: /node_modules[\\/]@electric-sql[\\/]pglite/ }],
        },
      },
      // PGlite's Emscripten glue uses direct eval internally (never with user data); keep the warning for our code.
      onLog(level, log, handler) {
        if (log.code === 'EVAL' && log.id?.includes('@electric-sql/pglite')) return;
        handler(level, log);
      },
    },
    // The PGlite engine chunk (~650 kB, ~150 kB gzip) is loaded lazily after the login screen paints.
    chunkSizeWarningLimit: mode === 'demo' ? 700 : 500,
  },
  server: { proxy: { '/api': 'http://localhost:3000', '/media': 'http://localhost:3000' } },
}));

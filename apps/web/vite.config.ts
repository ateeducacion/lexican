import { execSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

const commit = (() => {
  try {
    return execSync('git rev-parse --short HEAD', { stdio: ['ignore', 'pipe', 'ignore'] })
      .toString()
      .trim();
  } catch {
    return 'dev';
  }
})();

// Absolute site URL for link previews (og:image); override per deployment:
// VITE_SITE_URL=https://example.org/ npm run build
process.env.VITE_SITE_URL ??= 'https://ateeducacion.github.io/lexican/';

// `demo` mode = static GitHub Pages build with PGlite; any other mode = production SPA against /api.
export default defineConfig(({ mode }) => ({
  base: mode === 'demo' ? (process.env.DEMO_BASE ?? '/lexican/') : '/',
  plugins: [react()],
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

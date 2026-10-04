import { execSync } from 'node:child_process';
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

// `demo` mode = static GitHub Pages build with PGlite; any other mode = production SPA against /api.
export default defineConfig(({ mode }) => ({
  base: mode === 'demo' ? (process.env.DEMO_BASE ?? '/lexican/') : '/',
  plugins: [react()],
  define: {
    __APP_VERSION__: JSON.stringify(process.env.npm_package_version ?? '2.0.0'),
    __APP_COMMIT__: JSON.stringify(commit),
  },
  optimizeDeps: { exclude: ['@electric-sql/pglite'] },
  build: { target: 'es2022', outDir: mode === 'demo' ? 'dist-demo' : 'dist', sourcemap: true },
  server: { proxy: { '/api': 'http://localhost:3000', '/media': 'http://localhost:3000' } },
}));

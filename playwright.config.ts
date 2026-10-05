import { defineConfig, devices } from '@playwright/test';

const CI = !!process.env.CI;
// Production E2E (web + API + PostgreSQL, dev auth provider) runs when a database is available.
const prod = !!process.env.E2E_DATABASE_URL;

export default defineConfig({
  testDir: 'e2e',
  fullyParallel: true,
  // Several in-browser PGlite instances starting at once are slow on WebKit; keep CI parallelism low.
  workers: CI ? 2 : undefined,
  forbidOnly: CI,
  retries: CI ? 1 : 0,
  reporter: CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  timeout: 60_000,
  expect: { timeout: 15_000 },
  use: { trace: 'retain-on-failure', locale: 'es-ES', timezoneId: 'Atlantic/Canary' },
  projects: [
    {
      name: 'demo-chromium',
      use: { ...devices['Desktop Chrome'], baseURL: 'http://localhost:4317/lexican/' },
    },
    {
      name: 'demo-firefox',
      use: { ...devices['Desktop Firefox'], baseURL: 'http://localhost:4317/lexican/' },
    },
    {
      name: 'demo-webkit',
      use: { ...devices['Desktop Safari'], baseURL: 'http://localhost:4317/lexican/' },
      // WebKit reopens the IndexedDB-backed PGlite slowly on hosted runners.
      timeout: 240_000,
      expect: { timeout: 60_000 },
    },
    {
      name: 'demo-mobile',
      use: { ...devices['Pixel 7'], baseURL: 'http://localhost:4317/lexican/' },
      grep: /@mobile/,
    },
    ...(prod
      ? [
          {
            name: 'prod-chromium',
            use: { ...devices['Desktop Chrome'], baseURL: 'http://localhost:3100/' },
          },
        ]
      : []),
  ],
  webServer: [
    {
      // A strict static server like GitHub Pages (no SPA fallback): see scripts/static-pages-server.mjs.
      // E2E_DEMO_PREBUILT: test the already-built artifact as is (Pages workflow).
      command: process.env.E2E_DEMO_PREBUILT
        ? 'bun scripts/static-pages-server.mjs 4317'
        : 'bun run build:demo && bun scripts/static-pages-server.mjs 4317',
      url: 'http://localhost:4317/lexican/',
      reuseExistingServer: !CI,
      timeout: 180_000,
    },
    ...(prod
      ? [
          {
            command: 'bun scripts/e2e-prod-server.mjs',
            url: 'http://localhost:3100/api/health',
            reuseExistingServer: !CI,
            timeout: 240_000,
            // SIGTERM lets the script drop its throwaway database.
            gracefulShutdown: { signal: 'SIGTERM' as const, timeout: 10_000 },
          },
        ]
      : []),
  ],
});

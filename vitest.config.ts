import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    include: ['{apps,packages,tools}/*/src/**/*.test.{ts,tsx}'],
    environment: 'node',
    testTimeout: 30_000,
    coverage: {
      provider: 'v8',
      include: ['packages/*/src/**/*.ts', 'apps/api/src/**/*.ts', 'tools/*/src/**/*.ts'],
      // server.ts is exercised by server.test.ts in a real Bun subprocess, which v8 coverage cannot see.
      exclude: ['**/*.test.ts', '**/testing/**', 'apps/api/src/server.ts'],
      reporter: ['text-summary', 'lcov'],
      // Blocking gate (CI runs with PostgreSQL and MariaDB); Codecov reports project and patch coverage.
      thresholds: { lines: 90, statements: 90, functions: 90, branches: 90 },
    },
  },
});

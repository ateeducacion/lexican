import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    include: ['{apps,packages,tools}/*/src/**/*.test.{ts,tsx}'],
    environment: 'node',
    testTimeout: 30_000,
    coverage: {
      provider: 'v8',
      include: ['packages/*/src/**', 'apps/api/src/**', 'tools/*/src/**'],
      exclude: ['**/*.test.ts', '**/testing/**'],
      reporter: ['text-summary', 'lcov'],
    },
  },
});

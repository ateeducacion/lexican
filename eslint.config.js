import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';

export default tseslint.config(
  {
    ignores: [
      '**/dist/**',
      '**/dist-demo/**',
      '**/coverage/**',
      'test-results/**',
      'playwright-report/**',
      'analysis/**',
      'legacy/**',
      // Legacy Laravel tree, frozen until the cleanup phase.
      'app/**',
      'public/**',
      'resources/**',
      'storage/**',
      'vendor/**',
      'webpack.mix.js',
    ],
  },
  js.configs.recommended,
  ...tseslint.configs.strict,
  {
    languageOptions: { globals: { ...globals.browser, ...globals.node } },
    rules: {
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
      'no-console': ['error', { allow: ['warn', 'error'] }],
      // Needed with noUncheckedIndexedAccess after `.returning()` and array lookups already checked.
      '@typescript-eslint/no-non-null-assertion': 'off',
    },
  },
  {
    files: ['apps/web/**/*.tsx', 'apps/web/**/*.ts'],
    plugins: { 'react-hooks': reactHooks },
    rules: reactHooks.configs.recommended.rules,
  },
  {
    // CLI scripts report to stdout; Playwright fixtures use `void` for auto fixtures.
    files: ['scripts/**', 'apps/api/src/cli/**', 'tools/*/src/cli.ts'],
    rules: { 'no-console': 'off' },
  },
  {
    // Code that also runs in the browser (demo Web Worker): web APIs only, no server runtime (ADR 0009).
    files: [
      'packages/http/src/**',
      'packages/app/src/**',
      'packages/core/src/**',
      'apps/web/src/**',
    ],
    ignores: ['**/*.test.ts', '**/testing/**'],
    rules: {
      'no-restricted-globals': [
        'error',
        { name: 'Bun', message: 'Bun APIs only in apps/api (server).' },
      ],
      'no-restricted-imports': [
        'error',
        {
          patterns: [
            { regex: '^node:', message: 'Shared code runs in the browser: no Node modules.' },
            { regex: '^(pg|proxy-addr|bun)$', message: 'Server-only dependency.' },
          ],
        },
      ],
    },
  },
  {
    files: ['e2e/**'],
    rules: { '@typescript-eslint/no-invalid-void-type': 'off' },
  },
);

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
    files: ['e2e/**'],
    rules: { '@typescript-eslint/no-invalid-void-type': 'off' },
  },
);

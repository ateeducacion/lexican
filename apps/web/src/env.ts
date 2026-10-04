/**
 * Build mode is decided at build time (`vite build --mode demo`), never from the hostname (§59).
 * Use the global `__DEMO__` (a literal injected by Vite `define` in every module) so the bundler drops the
 * demo branch — PGlite, demo accounts — from the production build; a re-exported constant is not inlined.
 */
export const APP_VERSION = __APP_VERSION__;
export const APP_COMMIT = __APP_COMMIT__;
export const SOURCE_URL = 'https://github.com/ateeducacion/lexican';

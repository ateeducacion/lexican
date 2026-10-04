/** Build mode is decided at build time (`vite build --mode demo`), never from the hostname (§59). */
export const IS_DEMO = import.meta.env.MODE === 'demo';
export const APP_VERSION = __APP_VERSION__;
export const APP_COMMIT = __APP_COMMIT__;
export const SOURCE_URL = 'https://github.com/ateeducacion/lexican';

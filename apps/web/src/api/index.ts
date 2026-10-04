import { IS_DEMO } from '../env.ts';
import { createHttpClient } from './http.ts';
import type { WebApi } from './types.ts';

export { ApiError, toApiError, type WebApi } from './types.ts';

let api: Promise<WebApi> | null = null;

/**
 * The single entry point to data. In the demo build PGlite is fetched lazily (≈5 MB) after the first render,
 * so the login screen appears immediately (§15); production never bundles it (dead branch).
 */
export function getApi(): Promise<WebApi> {
  api ??= IS_DEMO
    ? import('../demo/client.ts').then((m) => m.createDemoClient())
    : Promise.resolve(createHttpClient());
  return api;
}

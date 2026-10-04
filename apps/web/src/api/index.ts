import { isChunkLoadError, reloadForNewVersion } from '../staleReload.ts';
import { createHttpClient } from './http.ts';
import type { WebApi } from './types.ts';

export { ApiError, toApiError, type WebApi } from './types.ts';

let api: Promise<WebApi> | null = null;

/**
 * The single entry point to data. In the demo build PGlite is fetched lazily (≈5 MB) after the first render,
 * so the login screen appears immediately (§15); production never bundles it (dead branch).
 */
export function getApi(): Promise<WebApi> {
  api ??= __DEMO__
    ? import('../demo/client.ts')
        .then((m) => m.createDemoClient())
        .catch((e: unknown) => {
          api = null; // a failed or aborted load is retried on the next call
          // A chunk removed by a newer deployment: reload once to get the current version.
          if (isChunkLoadError(e) && reloadForNewVersion())
            return new Promise<never>(() => undefined);
          throw e;
        })
    : Promise.resolve(createHttpClient());
  return api;
}

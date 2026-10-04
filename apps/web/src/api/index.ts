import { isChunkLoadError, reloadForNewVersion } from '../staleReload.ts';
import { createApiClient } from './client.ts';
import type { WebApi } from './types.ts';

export { ApiError, toApiError, type WebApi } from './types.ts';

let api: Promise<WebApi> | null = null;

/**
 * The single entry point to data. Production: HTTP to the server. Demo: the same client over a Web Worker that runs
 * the shared Hono API on PGlite, loaded lazily after the first render; production never bundles it (dead branch).
 */
export function getApi(): Promise<WebApi> {
  api ??= __DEMO__
    ? import('../demo/transport.ts')
        .then((m) => m.demoApi())
        .catch((e: unknown) => {
          api = null; // a failed or aborted load is retried on the next call
          // A chunk removed by a newer deployment: reload once to get the current version.
          if (isChunkLoadError(e) && reloadForNewVersion())
            return new Promise<never>(() => undefined);
          throw e;
        })
    : Promise.resolve(createApiClient((req) => fetch(req), { media: 'url' }));
  return api;
}

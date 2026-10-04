import {
  bindPath,
  operations,
  type MediaKind,
  type MediaView,
  type OperationName,
} from '@lexican/core';
import { ApiError, type WebApi } from './types.ts';

/**
 * Fetch-shaped transport. Production: the browser's `fetch` against the server. Demo: messages to the Web Worker
 * that runs the same Hono app (demo/transport.ts). Requests are absolute URLs on the page origin.
 */
export type Transport = (req: Request) => Promise<Response>;

const NETWORK = 'No hay conexión con el servidor. Comprueba tu red e inténtalo de nuevo.';

/** Map an HTTP response to data or an ApiError; the same JSON error contract in both deployments. */
export async function readJson<T>(res: Response): Promise<T> {
  const data = res.status === 204 ? null : await res.json().catch(() => null);
  if (!res.ok) {
    const e = (data ?? {}) as {
      code?: ApiError['code'];
      message?: string;
      details?: Record<string, string[]>;
    };
    throw new ApiError(
      e.code ?? (res.status >= 500 ? 'internal' : 'validation'),
      e.message ?? `Error ${res.status}`,
      e.details,
    );
  }
  return data as T;
}

/**
 * The one API client: one REST call per operation, derived from the shared operation table.
 * `media: 'url'` lets <img>/<audio> load `/media/:id` directly (HTTP ranges, streaming); `'blob'` fetches it
 * through the transport and hands out an object URL (the demo, where no server answers that URL).
 */
export function createApiClient(
  transport: Transport,
  opts: { media: 'url' } | { media: 'blob'; objectUrl: (id: string, blob: Blob) => string },
): WebApi {
  const abs = (path: string) => new URL(path, location.origin).href;
  const send = async (req: Request): Promise<Response> => {
    try {
      return await transport(req);
    } catch (e) {
      throw e instanceof ApiError ? e : new ApiError('network', NETWORK);
    }
  };
  const call = async <T>(method: string, url: string, body?: BodyInit, json = true): Promise<T> =>
    readJson<T>(
      await send(
        new Request(abs(url), {
          method,
          credentials: 'same-origin',
          headers: json && body !== undefined ? { 'content-type': 'application/json' } : undefined,
          body,
        }),
      ),
    );

  const api = {} as Record<string, unknown>;
  for (const [name, op] of Object.entries(operations) as [
    OperationName,
    (typeof operations)[OperationName],
  ][]) {
    api[name] = (input: Record<string, unknown> = {}) => {
      const { url, rest } = bindPath(op.path, input);
      if (op.method === 'GET' || op.method === 'DELETE') {
        const qs = new URLSearchParams();
        for (const [k, v] of Object.entries(rest))
          if (v !== undefined && v !== null && v !== '') qs.set(k, String(v));
        return call(op.method, qs.size ? `${url}?${qs}` : url);
      }
      return call(op.method, url, JSON.stringify(rest));
    };
  }
  return {
    ...(api as unknown as WebApi),
    uploadMedia(file: Blob, name: string, kind?: MediaKind): Promise<MediaView> {
      const form = new FormData();
      form.append('file', file, name);
      return call('POST', kind ? `/api/media?kind=${kind}` : '/api/media', form, false);
    },
    async mediaSrc(m) {
      if (opts.media === 'url') return m.url;
      const res = await send(new Request(abs(m.url)));
      if (!res.ok) await readJson(res);
      return opts.objectUrl(m.id, await res.blob());
    },
  };
}

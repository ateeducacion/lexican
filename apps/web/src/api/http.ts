import { bindPath, operations, type MediaView, type OperationName } from '@lexican/core';
import { ApiError, type WebApi } from './types.ts';

async function request<T>(method: string, url: string, body?: BodyInit, json = true): Promise<T> {
  let res: Response;
  try {
    res = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: json && body !== undefined ? { 'content-type': 'application/json' } : undefined,
      body,
    });
  } catch {
    throw new ApiError(
      'network',
      'No hay conexión con el servidor. Comprueba tu red e inténtalo de nuevo.',
    );
  }
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

/** Production adapter: one REST call per operation, derived from the shared operation table. */
export function createHttpClient(): WebApi {
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
        return request(op.method, qs.size ? `${url}?${qs}` : url);
      }
      return request(op.method, url, JSON.stringify(rest));
    };
  }
  return {
    ...(api as unknown as WebApi),
    uploadMedia(file: Blob, name: string): Promise<MediaView> {
      const form = new FormData();
      form.append('file', file, name);
      return request('POST', '/api/media', form, false);
    },
    mediaSrc: async (m) => m.url,
  };
}

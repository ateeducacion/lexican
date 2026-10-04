import type { Logger } from '@lexican/http';
import type { buildApp } from '../app.ts';

export const silentLog: Logger = {
  info: () => undefined,
  warn: () => undefined,
  error: () => undefined,
};

export interface Cookie {
  name: string;
  value: string;
  path?: string;
  maxAge?: number;
  httpOnly: boolean;
  secure: boolean;
  sameSite?: string;
}

const parseCookie = (line: string): Cookie => {
  const [pair, ...attrs] = line.split(';').map((s) => s.trim());
  const eq = pair!.indexOf('=');
  const c: Cookie = {
    name: pair!.slice(0, eq),
    value: decodeURIComponent(pair!.slice(eq + 1)),
    httpOnly: false,
    secure: false,
  };
  for (const a of attrs) {
    const [k, v] = a.split('=') as [string, string | undefined];
    const key = k.toLowerCase();
    if (key === 'httponly') c.httpOnly = true;
    else if (key === 'secure') c.secure = true;
    else if (key === 'path') c.path = v;
    else if (key === 'max-age') c.maxAge = Number(v);
    else if (key === 'samesite') c.sameSite = v;
  }
  return c;
};

/** Fastify-`inject`-shaped helper over `app.fetch`, with the socket address the Bun adapter would pass. */
export function injector(app: ReturnType<typeof buildApp>['app']) {
  return async (o: {
    method: string;
    url: string;
    headers?: Record<string, string>;
    payload?: string | object | Uint8Array;
    remoteAddress?: string;
  }) => {
    const headers = new Headers(o.headers ?? {});
    let body: BodyInit | undefined;
    if (o.payload instanceof Uint8Array) body = o.payload as BodyInit;
    else if (typeof o.payload === 'string') body = o.payload;
    else if (o.payload !== undefined) {
      body = JSON.stringify(o.payload);
      if (!headers.has('content-type')) headers.set('content-type', 'application/json');
    }
    const res = await app.fetch(
      new Request(`http://localhost${o.url}`, { method: o.method, headers, body }),
      {
        remoteAddress: o.remoteAddress ?? '127.0.0.1',
      },
    );
    const raw = new Uint8Array(await res.arrayBuffer());
    const text = new TextDecoder().decode(raw);
    return {
      statusCode: res.status,
      headers: Object.fromEntries(res.headers) as Record<string, string>,
      body: text,
      rawPayload: Buffer.from(raw),
      json: () => JSON.parse(text),
      cookies: res.headers.getSetCookie().map(parseCookie),
    };
  };
}

/** The server app with `inject` (and a no-op `close`, kept for the Fastify-era tests). */
export function testApp(opts: Parameters<typeof buildApp>[0], build: typeof buildApp) {
  const built = build({ log: silentLog, ...opts });
  return { ...built, inject: injector(built.app), close: async () => undefined };
}

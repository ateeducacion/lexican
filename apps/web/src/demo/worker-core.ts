import { type SessionCarrier, safeError } from '@lexican/http';
import type { DemoContext, DemoContextUpdate, FromWorker, ToWorker } from './protocol.ts';

/** What the worker serves: the Hono app's `fetch`, called with the demo context as its env. */
export type Fetcher = {
  fetch(
    req: Request,
    env: { ctx: DemoContext; update: DemoContextUpdate },
  ): Response | Promise<Response>;
};

type Bindings = { ctx: DemoContext; update: DemoContextUpdate };

/** The session id lives in the page (localStorage) and rides on each message. */
const env = (c: { env: unknown }) => c.env as Bindings;
export const demoCarrier: SessionCarrier = {
  getSession: (c) => env(c).ctx.session ?? undefined,
  setSession: (c, id) => {
    env(c).update.session = id;
  },
  clearSession: (c) => {
    env(c).update.session = null;
  },
  // CAS is disabled in the static demo.
  getCasState: () => undefined,
  setCasState: () => undefined,
  clearCasState: () => undefined,
};

/**
 * Message loop of the demo worker, independent of the worker global so tests run it in-process: each request message
 * becomes a Request for `app.fetch`, each Response a message (status, headers, Blob body, context update).
 */
export function createMessageHandler(
  app: Promise<Fetcher>,
  post: (m: FromWorker) => void,
  origin: string,
) {
  const inflight = new Map<number, AbortController>();
  const running = new Set<Promise<void>>();
  let stopped = false;

  async function handle(m: Extract<ToWorker, { type: 'request' }>) {
    const ctrl = new AbortController();
    inflight.set(m.id, ctrl);
    try {
      const a = await app;
      if (stopped) throw new Error('resetting');
      const req = new Request(new URL(m.url, origin), {
        method: m.method,
        headers: m.headers,
        body: m.body,
        signal: ctrl.signal,
      });
      const env = { ctx: m.ctx, update: {} as DemoContextUpdate };
      const res = await a.fetch(req, env);
      const body = res.body === null || m.method === 'HEAD' ? null : await res.blob();
      if (ctrl.signal.aborted) return; // the page stopped waiting; the operation itself may have completed
      post({
        type: 'response',
        id: m.id,
        status: res.status,
        headers: [...res.headers],
        body,
        update: env.update,
      });
    } catch (e) {
      if (!ctrl.signal.aborted)
        post({ type: 'error', id: m.id, detail: safeError(e).message.slice(0, 300) });
    } finally {
      inflight.delete(m.id);
    }
  }

  return {
    /** Handles `request` and `abort`; returns false for other messages. */
    onMessage(m: ToWorker): boolean {
      if (m.type === 'request') {
        const p = handle(m);
        running.add(p);
        void p.finally(() => running.delete(p));
        return true;
      }
      if (m.type === 'abort') {
        inflight.get(m.id)?.abort();
        return true;
      }
      return false;
    },
    /** Refuse new requests and wait for the running ones (before closing the databases). */
    async stop() {
      stopped = true;
      await Promise.allSettled([...running]);
    },
  };
}

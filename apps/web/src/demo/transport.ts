import { createApiClient, type Transport } from '../api/client.ts';
import { ApiError, type WebApi } from '../api/types.ts';
import type { DemoContextUpdate, FromWorker, InitErrorCode, ToWorker } from './protocol.ts';
import { readDemoSession, writeDemoSession } from './session.ts';
import { setDemoStatus } from './status.ts';

/** Bodies that must be null in a Response. */
const NULL_BODY = new Set([101, 204, 205, 304]);

const INIT_ERRORS: Record<
  InitErrorCode | 'timeout' | 'crash',
  { title: string; message: string; canReset: boolean }
> = {
  locked: {
    title: 'La demo ya está abierta en otra pestaña',
    message:
      'Para no dañar los datos, solo una pestaña puede usar la demo a la vez. Cierra la otra pestaña y vuelve a intentarlo.',
    canReset: false,
  },
  unsupported: {
    title: 'Este navegador no puede ejecutar la demo',
    message: 'Falta una función necesaria (Web Locks). Actualiza el navegador o usa otro.',
    canReset: false,
  },
  storage: {
    title: 'No se puede usar el almacenamiento del navegador',
    message:
      'La demo guarda los datos en este navegador. Comprueba que no estás en navegación privada y que queda espacio libre.',
    canReset: true,
  },
  failed: {
    title: 'No se ha podido preparar la demo',
    message: 'Vuelve a intentarlo. Si el problema continúa, restablece los datos de demostración.',
    canReset: true,
  },
  timeout: {
    title: 'La demo tarda demasiado en arrancar',
    message: 'Vuelve a intentarlo. En móviles lentos la primera carga puede tardar más.',
    canReset: true,
  },
  crash: {
    title: 'La demo se ha detenido',
    message: 'Recarga la página para continuar. Lo que ya se había guardado se conserva.',
    canReset: true,
  },
};

export interface WorkerTransportOptions {
  /** Per-request limit; an upload gets `uploadTimeoutMs`. No request is ever retried after a timeout. */
  timeoutMs?: number;
  uploadTimeoutMs?: number;
  initTimeoutMs?: number;
  onUpdate?: (u: DemoContextUpdate) => void;
}

/**
 * Generic fetch-over-postMessage transport to the demo worker: any Request in, a Response out. It knows nothing
 * about operations; it only carries the demo session context and enforces timeouts, cancellation and crashes.
 */
export function createWorkerTransport(worker: Worker, opts: WorkerTransportOptions = {}) {
  const timeoutMs = opts.timeoutMs ?? 30_000;
  const uploadTimeoutMs = opts.uploadTimeoutMs ?? 120_000;
  type Pending = {
    resolve: (m: FromWorker) => void;
    reject: (e: unknown) => void;
    timer: ReturnType<typeof setTimeout>;
  };
  const pending = new Map<number, Pending>();
  let nextId = 1;
  let dead: ApiError | null = null;
  const post = (m: ToWorker) => worker.postMessage(m);

  const failAll = (e: ApiError) => {
    dead ??= e;
    for (const [id, p] of pending) {
      clearTimeout(p.timer);
      p.reject(e);
      pending.delete(id);
    }
  };

  let initDone: (err?: { code: InitErrorCode | 'timeout' | 'crash'; detail?: string }) => void =
    () => undefined;
  const init = new Promise<void>((resolve, reject) => {
    const timer = setTimeout(() => initDone({ code: 'timeout' }), opts.initTimeoutMs ?? 120_000);
    initDone = (err) => {
      clearTimeout(timer);
      initDone = () => undefined;
      if (!err) return resolve();
      const info = INIT_ERRORS[err.code];
      if (err.detail) console.error('LexiCán demo:', err.code, err.detail);
      setDemoStatus({ state: 'error', ...info });
      const e = new ApiError('unavailable', `${info.title}. ${info.message}`);
      failAll(e);
      reject(e);
    };
  });
  init.catch(() => undefined);
  setDemoStatus({ state: 'starting' });

  worker.onmessage = (ev: MessageEvent<FromWorker>) => {
    const m = ev.data;
    if (m.type === 'ready') {
      setDemoStatus({ state: 'ready' });
      return initDone();
    }
    if (m.type === 'init-error') return initDone({ code: m.code, detail: m.detail });
    const p = pending.get(m.id);
    if (!p) return; // aborted or timed out: the late answer is dropped
    pending.delete(m.id);
    clearTimeout(p.timer);
    p.resolve(m);
  };
  const crash = (ev: Event) => {
    ev.preventDefault();
    initDone({ code: 'crash' });
    const info = INIT_ERRORS.crash;
    setDemoStatus({ state: 'error', ...info });
    failAll(new ApiError('unavailable', `${info.title}. ${info.message}`));
  };
  worker.onerror = crash;
  worker.onmessageerror = crash;

  /** Send one message and wait for its answer (or a timeout / abort / crash). */
  function exchange(m: ToWorker, ms: number, signal?: AbortSignal | null): Promise<FromWorker> {
    // A reset may still reach a worker whose start-up failed (it holds the lock and can delete its databases).
    if (dead && m.type !== 'reset') return Promise.reject(dead);
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        pending.delete(m.id);
        if (m.type === 'request') post({ type: 'abort', id: m.id });
        // The operation may have completed in the worker: report it as unknown, never retry it.
        reject(
          new ApiError(
            'network',
            'La demo no ha respondido a tiempo. Comprueba si el cambio se guardó antes de repetirlo.',
          ),
        );
      }, ms);
      pending.set(m.id, { resolve, reject, timer });
      signal?.addEventListener(
        'abort',
        () => {
          if (!pending.delete(m.id)) return;
          clearTimeout(timer);
          post({ type: 'abort', id: m.id });
          reject(signal.reason ?? new DOMException('Aborted', 'AbortError'));
        },
        { once: true },
      );
      post(m);
    });
  }

  const fetch: Transport = async (req) => {
    await init;
    if (req.signal.aborted) throw req.signal.reason;
    const body = req.method === 'GET' || req.method === 'HEAD' ? null : await req.blob();
    const id = nextId++;
    const url = new URL(req.url);
    const upload = req.method === 'POST' && url.pathname === '/api/media';
    const m = await exchange(
      {
        type: 'request',
        id,
        method: req.method,
        url: url.pathname + url.search,
        headers: [...req.headers],
        body: body && body.size > 0 ? body : null,
        ctx: { session: readDemoSession() },
      },
      upload ? uploadTimeoutMs : timeoutMs,
      req.signal,
    );
    if (m.type === 'error')
      throw new ApiError('internal', 'Ha ocurrido un error inesperado en la demo.');
    if (m.type !== 'response') throw new ApiError('internal', 'Respuesta inesperada de la demo.');
    if (m.update.session !== undefined) writeDemoSession(m.update.session);
    opts.onUpdate?.(m.update);
    return new Response(NULL_BODY.has(m.status) ? null : m.body, {
      status: m.status,
      headers: m.headers,
    });
  };

  return {
    fetch,
    ready: init,
    /** Ask the worker to delete the demo databases; resolves to false when it could not. */
    async reset(): Promise<boolean> {
      const m = await exchange({ type: 'reset', id: nextId++ }, 20_000).catch(() => null);
      failAll(new ApiError('unavailable', 'La demo se está restableciendo.'));
      return m?.type === 'reset-done' && m.ok;
    },
  };
}

/** The demo API: the common client over the worker transport, plus reset. */
export function demoApi(): WebApi {
  const worker = new Worker(new URL('./worker.ts', import.meta.url), {
    type: 'module',
    name: 'lexican-demo',
  });
  // Object URLs of media authorized for the current session; revoked when the identity changes or on reset, never
  // while the same person is still using them.
  // ponytail: grows with the distinct media opened in one session (handles to IndexedDB Blobs, not copies).
  const urls = new Map<string, string>();
  const revokeAll = () => {
    for (const u of urls.values()) URL.revokeObjectURL(u);
    urls.clear();
  };
  const t = createWorkerTransport(worker, {
    onUpdate: (u) => u.session !== undefined && revokeAll(),
  });
  const api = createApiClient(t.fetch, {
    media: 'blob',
    objectUrl: (id, blob) => {
      const known = urls.get(id);
      if (known) return known;
      const u = URL.createObjectURL(blob);
      urls.set(id, u);
      return u;
    },
  });
  return {
    ...api,
    async resetDemo() {
      setDemoStatus({ state: 'resetting' });
      const ok = await t.reset();
      revokeAll();
      if (!ok) {
        setDemoStatus({
          state: 'error',
          title: 'No se ha podido restablecer la demo',
          message: 'Cierra las demás pestañas de la demo y vuelve a intentarlo.',
          canReset: false,
        });
        return;
      }
      writeDemoSession(null);
      location.reload();
    },
  };
}

/**
 * Pages CAS callback. The service URL is the app root itself with a marker (`/lexican/?cas=callback&state=…`): a
 * real file GitHub Pages serves, also on a direct reload, outside the `#` routes. The ticket is taken out of the
 * address bar as soon as the page starts, kept only in memory and handed to the worker once.
 */
let pending: { ticket: string | null; state: string | null } | null = null;

export function captureCasCallback(): void {
  const url = new URL(location.href);
  if (url.searchParams.get('cas') !== 'callback') return;
  pending = { ticket: url.searchParams.get('ticket'), state: url.searchParams.get('state') };
  history.replaceState(history.state, '', `${url.pathname}${url.hash}`);
}

/** Single use: a second call (StrictMode, re-render, reload) gets null. */
export function takeCasCallback() {
  const p = pending;
  pending = null;
  return p;
}

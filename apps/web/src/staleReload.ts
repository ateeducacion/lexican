const KEY = 'lexican-stale-reload';
let reloading = false;

/** Chunk-load failures look different per browser (Chromium, Firefox, WebKit). */
export const isChunkLoadError = (e: unknown): boolean =>
  /dynamically imported module|Importing a module script failed|error loading dynamically imported module|Unable to preload CSS/i.test(
    String((e as { message?: unknown } | null)?.message ?? e),
  );

/**
 * After a new deployment an already-open page may ask for code chunks that no longer exist: reload once to get
 * the current version. Returns false (and does nothing) if a reload already happened in the last minute, so a
 * real outage cannot cause a reload loop. Several failures from the same page (the preload and the import itself)
 * share one reload.
 */
export function reloadForNewVersion(): boolean {
  if (reloading) return true;
  let last = 0;
  try {
    last = Number(sessionStorage.getItem(KEY) ?? 0);
    sessionStorage.setItem(KEY, String(Date.now()));
  } catch {
    /* storage unavailable: still try once */
  }
  if (Date.now() - last < 60_000) return false;
  reloading = true;
  window.location.reload();
  return true;
}

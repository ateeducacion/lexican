import type { Logger } from '@lexican/http';

const LEVELS = ['trace', 'debug', 'info', 'warn', 'error', 'fatal', 'silent'] as const;

/**
 * One JSON line per event on stderr. Callers pass only safe fields (no cookies, tickets, tokens, directory payloads);
 * errors go through `safeError` first.
 */
export function createLogger(level: string): Logger {
  const min = Math.max(0, LEVELS.indexOf(level as (typeof LEVELS)[number]));
  const at = (l: (typeof LEVELS)[number]) => (obj: object, msg: string) => {
    if (LEVELS.indexOf(l) < min) return;
    console.error(JSON.stringify({ level: l, time: new Date().toISOString(), msg, ...obj }));
  };
  return { info: at('info'), warn: at('warn'), error: at('error') };
}

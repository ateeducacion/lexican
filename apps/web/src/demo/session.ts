/**
 * Demo session marker: the opaque id of a session row in the browser database (the demo's stand-in for the HttpOnly
 * cookie a Worker cannot set). Not a credential: the demo is no security boundary (docs/DEMO.md).
 */
export const DEMO_SESSION_KEY = 'lexican-demo-sid';
const CAS_STATE_KEY = 'lexican-demo-cas-state';

const read = (s: () => Storage, k: string) => {
  try {
    return s().getItem(k);
  } catch {
    return null;
  }
};
const write = (s: () => Storage, k: string, v: string | null) => {
  try {
    if (v === null) s().removeItem(k);
    else s().setItem(k, v);
  } catch {
    /* storage unavailable: lasts until reload */
  }
};

export const readDemoSession = () => read(() => localStorage, DEMO_SESSION_KEY);
export const writeDemoSession = (id: string | null) => {
  write(() => localStorage, 'lexican-demo-session', null); // marker of the previous demo version (a user id)
  write(() => localStorage, DEMO_SESSION_KEY, id);
};

/** Pending CAS login state: survives the same-tab navigation to the CAS server, dies with the tab. */
export function readCasState(): { value: string; exp: number } | null {
  try {
    const v = JSON.parse(read(() => sessionStorage, CAS_STATE_KEY) ?? 'null') as {
      value: string;
      exp: number;
    } | null;
    return v && typeof v.value === 'string' && typeof v.exp === 'number' ? v : null;
  } catch {
    return null;
  }
}
export const writeCasState = (v: { value: string; exp: number } | null) =>
  write(() => sessionStorage, CAS_STATE_KEY, v ? JSON.stringify(v) : null);

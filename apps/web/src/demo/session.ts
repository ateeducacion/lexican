/**
 * Demo session marker: the opaque id of a session row in the browser database (the demo's stand-in for the HttpOnly
 * cookie a Worker cannot set). Not a credential: the demo is no security boundary (docs/DEMO.md).
 */
export const DEMO_SESSION_KEY = 'lexican-demo-sid';

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

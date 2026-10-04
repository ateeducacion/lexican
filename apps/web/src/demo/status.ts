/** Demo engine status for the UI (initialization, errors). Tiny external store for useSyncExternalStore. */
export type DemoStatus =
  | { state: 'idle' | 'starting' | 'ready' | 'resetting' }
  | { state: 'error'; title: string; message: string; canReset: boolean };

let status: DemoStatus = { state: 'idle' };
const listeners = new Set<() => void>();

export const getDemoStatus = () => status;
export function setDemoStatus(s: DemoStatus) {
  status = s;
  for (const l of listeners) l();
}
export function subscribeDemoStatus(l: () => void) {
  listeners.add(l);
  return () => void listeners.delete(l);
}

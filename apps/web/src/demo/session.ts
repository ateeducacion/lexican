/** Demo session marker (which fictitious user acts). Not a credential: the demo has no security boundary. */
export const DEMO_SESSION_KEY = 'lexican-demo-session';

export function readDemoSession(): string | null {
  try {
    return localStorage.getItem(DEMO_SESSION_KEY);
  } catch {
    return null;
  }
}

export function writeDemoSession(userId: string | null): void {
  try {
    if (userId) localStorage.setItem(DEMO_SESSION_KEY, userId);
    else localStorage.removeItem(DEMO_SESSION_KEY);
  } catch {
    /* storage unavailable: the session lasts until reload */
  }
}

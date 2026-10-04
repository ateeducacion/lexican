import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router/dom';
import { router } from './router.tsx';
// Self-hosted fonts (OFL-1.1): no third-party font CDN (docs/PRIVACY.md).
import '@fontsource-variable/lexend';
import '@fontsource/literata/500.css';
import '@fontsource/literata/700.css';
import '@fontsource/literata/500-italic.css';
import './styles/global.css';

// After a new deployment an already-open page may ask for code chunks that no longer exist: reload once to get the
// current version instead of failing with a generic error (guarded so a real outage cannot cause a reload loop).
window.addEventListener('vite:preloadError', (event) => {
  const KEY = 'lexican-stale-reload';
  let last = 0;
  try {
    last = Number(sessionStorage.getItem(KEY) ?? 0);
    sessionStorage.setItem(KEY, String(Date.now()));
  } catch {
    /* storage unavailable: still try once */
  }
  if (Date.now() - last > 60_000) {
    event.preventDefault();
    window.location.reload();
  }
});

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <RouterProvider router={router} />
  </StrictMode>,
);

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

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <RouterProvider router={router} />
  </StrictMode>,
);

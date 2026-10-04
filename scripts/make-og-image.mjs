// Renders the link-preview card (Open Graph / Twitter, 1200x630) into apps/web/public/og-image.jpg:
// LexiCán's wordmark plus a real capture of the demo, in the same visual family as Tonga and Aritmates.
// Usage: bun run og-image
import { chromium } from '@playwright/test';
import { spawn } from 'node:child_process';
import { readFileSync, statSync } from 'node:fs';

const PORT = 4196;
const OUT = 'apps/web/public/og-image.jpg';
const dataUri = (file, mime) => `data:${mime};base64,${readFileSync(file).toString('base64')}`;
const server = spawn(
  'bunx',
  ['vite', 'preview', '--mode', 'demo', '--port', String(PORT), '--strictPort'],
  {
    cwd: 'apps/web',
    stdio: 'ignore',
  },
);
await new Promise((r) => setTimeout(r, 2500));

const browser = await chromium.launch();
try {
  // 1. A real capture of the personal dictionary with a seeded entry open.
  const page = await browser.newPage({
    viewport: { width: 1280, height: 780 },
    deviceScaleFactor: 2,
    colorScheme: 'light',
    locale: 'es-ES',
  });
  await page.goto(`http://localhost:${PORT}/lexican/`);
  await page.getByRole('button', { name: 'Entrar como alumno 1' }).click();
  await page.getByRole('button', { name: 'Desconectar' }).waitFor({ timeout: 60_000 });
  await page.getByRole('link', { name: 'guagua' }).first().click();
  await page.getByRole('heading', { name: 'guagua', level: 1 }).waitFor();
  await page.getByRole('button', { name: 'Ocultar el aviso de demostración' }).click();
  await page.mouse.move(0, 0);
  await page.waitForTimeout(800);
  const shot = await page.screenshot({ animations: 'disabled' });

  // 2. The card.
  const logo = readFileSync('apps/web/public/favicon.svg', 'utf8').replace(
    '<svg ',
    '<svg width="56" height="56" ',
  );
  const ate = dataUri('apps/web/public/ate-logo.png', 'image/png');
  const card = await browser.newPage({
    viewport: { width: 1200, height: 630 },
    deviceScaleFactor: 1,
  });
  await card.setContent(`<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
    * { box-sizing: border-box; margin: 0; }
    body { width: 1200px; height: 630px; overflow: hidden; position: relative; color: #fff;
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: radial-gradient(circle at 85% 15%, #2f8fb0 0, transparent 55%), linear-gradient(135deg, #0f4c81 0%, #0b3a63 100%); }
    body::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 10px; background: #3eb1ba; }
    .text { position: absolute; left: 72px; top: 68px; width: 540px; }
    .mark { display: inline-grid; place-items: center; width: 80px; height: 80px; border-radius: 20px; background: #fff;
      box-shadow: 0 10px 24px rgb(0 20 40 / .3); }
    h1 { font-family: Arial, Helvetica, sans-serif; font-size: 104px; font-weight: 700; line-height: 1; letter-spacing: 0.01em; margin-top: 30px; }
    h1 span { color: #b2ffe3; font-weight: 900; }
    h2 { font-size: 38px; font-weight: 500; margin-top: 10px; opacity: .95; }
    p { font-size: 26px; line-height: 1.38; margin-top: 24px; opacity: .9; }
    .ate { position: absolute; left: 72px; bottom: 54px; display: flex; align-items: center; gap: 16px; font-size: 21px; opacity: .92; }
    .ate img { height: 46px; filter: brightness(0) invert(1); }
    .shot { position: absolute; left: 650px; top: 78px; width: 690px; border-radius: 16px; overflow: hidden;
      box-shadow: 0 30px 60px rgb(0 15 35 / .5); transform: rotate(-2.5deg); background: #fff; }
    .shot img { display: block; width: 100%; }
  </style></head><body>
    <div class="text">
      <span class="mark">${logo}</span>
      <h1>LEXI<span>CÁN</span></h1>
      <h2>Diccionarios para el aula</h2>
      <p>Software libre para que el alumnado cree sus palabras y el profesorado las revise y publique en el diccionario de la clase.</p>
    </div>
    <div class="ate"><img src="${ate}" alt=""><span>Área de Tecnología Educativa · Gobierno de Canarias</span></div>
    <div class="shot"><img src="data:image/png;base64,${shot.toString('base64')}" alt=""></div>
  </body></html>`);
  await card.evaluate(() => document.fonts.ready);
  await card.screenshot({ path: OUT, type: 'jpeg', quality: 85 });
  console.log(`${OUT}: ${statSync(OUT).size} bytes`);
} finally {
  await browser.close();
  server.kill();
}

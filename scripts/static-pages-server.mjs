// Serves apps/web/dist-demo like GitHub Pages does for a project site: files under /lexican/, `index.html` for a
// directory, a plain 404 otherwise. No SPA fallback and no rewrites (unlike `vite preview`), so the E2E prove that
// the demo, its worker, WASM assets and the CAS callback URL work from static hosting.
// Usage: bun scripts/static-pages-server.mjs [port]
import { createReadStream, statSync } from 'node:fs';
import { createServer } from 'node:http';
import { extname, join, normalize } from 'node:path';

const root = join(
  process.env.PAGES_DIR ?? new URL('../apps/web/dist-demo/', import.meta.url).pathname,
  '/',
);
const base = '/lexican/';
const port = Number(process.argv[2] ?? 4317);
const types = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.json': 'application/json',
  '.wasm': 'application/wasm',
  '.data': 'application/octet-stream',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.woff2': 'font/woff2',
  '.woff': 'font/woff',
  '.map': 'application/json',
  '.txt': 'text/plain; charset=utf-8',
  '.webmanifest': 'application/manifest+json',
};

createServer((req, res) => {
  const path = decodeURIComponent(new URL(req.url, 'http://x').pathname);
  if (!path.startsWith(base)) return void res.writeHead(404).end('Not found');
  let file = normalize(join(root, path.slice(base.length)));
  if (!file.startsWith(root)) return void res.writeHead(404).end('Not found');
  try {
    if (statSync(file).isDirectory()) file = join(file, 'index.html');
    const size = statSync(file).size;
    res.writeHead(200, {
      'content-type': types[extname(file)] ?? 'application/octet-stream',
      'content-length': size,
      'cache-control': 'max-age=600',
    });
    if (req.method === 'HEAD') return void res.end();
    createReadStream(file).pipe(res);
  } catch {
    res.writeHead(404, { 'content-type': 'text/plain' }).end('Not found');
  }
}).listen(port, '127.0.0.1', () =>
  console.log(`Pages-like static server on http://localhost:${port}${base}`),
);

// Fake CAS 3.0 server for deterministic E2E of the Pages demo (never used by the app itself). It runs on its own
// origin, so the browser applies real CORS rules to the ticket validation made from the demo worker.
// - GET /login?service=…  → 302 to `service&ticket=ST-<mode>-<n>`; mode `cors` or `nocors` comes from the
//   `fakecas_mode` cookie the test sets, the user from `fakecas_user` (default alice).
// - GET /p3/serviceValidate?service=…&ticket=… → CAS XML; success only for a known, unused ticket issued for that
//   exact service. Access-Control-Allow-Origin is sent only for `cors` tickets.
// - GET /logout?service=… → 302 to service.
// Usage: bun scripts/fake-cas-server.mjs [port]
import { createServer } from 'node:http';

const port = Number(process.argv[2] ?? 4318);
const tickets = new Map(); // ticket → { service, user, cors, used }
let n = 0;
const xml = (body) =>
  `<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">${body}</cas:serviceResponse>`;
const cookie = (req, name) =>
  (req.headers.cookie ?? '')
    .split(/;\s*/)
    .find((c) => c.startsWith(`${name}=`))
    ?.slice(name.length + 1);

createServer((req, res) => {
  const url = new URL(req.url, `http://localhost:${port}`);
  const service = url.searchParams.get('service') ?? '';
  if (url.pathname === '/login') {
    const cors = cookie(req, 'fakecas_mode') !== 'nocors';
    const ticket = `ST-${cors ? 'cors' : 'nocors'}-${++n}`;
    tickets.set(ticket, {
      service,
      user: cookie(req, 'fakecas_user') ?? 'alice',
      cors,
      used: false,
    });
    return void res.writeHead(302, { location: `${service}&ticket=${ticket}` }).end();
  }
  if (url.pathname === '/logout')
    return void res.writeHead(302, { location: service || '/' }).end();
  if (url.pathname === '/p3/serviceValidate') {
    const ticket = url.searchParams.get('ticket') ?? '';
    const t = tickets.get(ticket);
    const headers = { 'content-type': 'text/html;charset=UTF-8' };
    if (t?.cors && req.headers.origin) headers['access-control-allow-origin'] = req.headers.origin;
    let body;
    if (!t || t.used)
      body = xml(
        `<cas:authenticationFailure code="INVALID_TICKET">Ticket not recognized</cas:authenticationFailure>`,
      );
    else if (t.service !== service)
      body = xml(
        `<cas:authenticationFailure code="INVALID_SERVICE">Wrong service</cas:authenticationFailure>`,
      );
    else
      body = xml(
        `<cas:authenticationSuccess><cas:user>${t.user}</cas:user></cas:authenticationSuccess>`,
      );
    if (t) t.used = true;
    console.log(
      `validate ${ticket} service=${service} → ${body.includes('Success') ? 'ok' : 'fail'}`,
    );
    return void res.writeHead(200, headers).end(body);
  }
  if (url.pathname === '/health') return void res.writeHead(200).end('ok');
  res.writeHead(404).end();
}).listen(port, '127.0.0.1', () => console.log(`fake CAS on http://localhost:${port}`));

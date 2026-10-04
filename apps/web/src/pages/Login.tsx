import { DEMO_ACCOUNTS, LoginInput } from '@lexican/core';
import { type FormEvent, useEffect, useState } from 'react';
import { redirect, useNavigate, useSearchParams } from 'react-router';
import { getApi } from '../api/index.ts';
import { ErrorMessage, Field, PageTitle, useAction } from '../components/ui.tsx';
import styles from './Login.module.css';

export async function loader() {
  // Production: no PGlite involved, the call is cheap. Demo: do not block the first paint on the database.
  if (!__DEMO__) {
    const { user } = await (await getApi()).me({});
    if (user) throw redirect('/');
  }
  return null;
}

/** Messages for `?error=` after a CAS login attempt (set by the API, server or demo worker). */
const CAS_ERRORS: Record<string, string> = {
  cas: 'No se ha podido completar el acceso con CAS. Vuelve a intentarlo desde el principio.',
  forbidden: 'Tu usuario no está autorizado en LexiCán o la cuenta está desactivada.',
  unavailable: __DEMO__
    ? 'No se ha podido validar el ticket con el CAS de pruebas desde el navegador. Puede ser un fallo de red o que ese servidor no permita leer su respuesta desde otro sitio web (CORS): así ocurría cuando se comprobó. Usa las personas ficticias de abajo o el despliegue con Docker.'
    : 'El servicio de acceso no responde. Inténtalo de nuevo más tarde.',
};

type Providers = { cas: 'institutional' | 'test' | null; password: boolean };

/** Only allow in-app return paths after login (no open redirect). */
const safeReturn = (v: string | null) => (v?.startsWith('/') && !v.startsWith('//') ? v : '/');

export function Component() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [providers, setProviders] = useState<Providers>({
    cas: __DEMO__ ? 'test' : 'institutional',
    password: __DEMO__,
  });
  const casError = CAS_ERRORS[params.get('error') ?? ''];

  useEffect(() => {
    if (__DEMO__) {
      // Start downloading PGlite while the user reads the page; a failure (or a reload aborting it)
      // surfaces later on login, not as an unhandled rejection here.
      getApi().catch(() => undefined);
      return;
    }
    fetch('/api/auth/providers')
      .then((r) => (r.ok ? r.json() : null))
      .then((p: Providers | null) => p && setProviders(p))
      .catch(() => undefined);
  }, []);

  const login = useAction(async (credentials: { email: string; password: string }) => {
    const parsed = LoginInput.safeParse(credentials);
    if (!parsed.success) {
      setFieldErrors(
        Object.fromEntries(parsed.error.issues.map((i) => [String(i.path[0]), [i.message]])),
      );
      return;
    }
    setFieldErrors({});
    await (await getApi()).login(parsed.data);
    await navigate(safeReturn(params.get('volver')), { replace: true });
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    void login.run({ email, password });
  };

  return (
    <div className={styles.wrap}>
      <section className={styles.intro}>
        <PageTitle title="Entrar">
          <span className="wordmark">
            Lexi<span>Cán</span>
          </span>
        </PageTitle>
        <p className={styles.lead}>
          Diccionarios personales y de aula para aprender vocabulario: cada alumno crea sus palabras
          y el profesorado las revisa y publica en el diccionario de la clase.
        </p>
      </section>

      <section className="card" aria-labelledby="login-title">
        <h2 id="login-title">Acceso</h2>
        {casError && (
          <div className="alert alert-error" role="alert">
            {casError}
          </div>
        )}
        {providers.cas === 'institutional' && (
          <p>
            <a className="btn btn-primary" href="/api/auth/cas/login">
              Entrar con tu usuario educativo
            </a>
          </p>
        )}
        {providers.cas === 'test' && <TestCas />}
        {providers.password && (
          <form onSubmit={submit} noValidate>
            <Field label="Correo electrónico" error={fieldErrors.email}>
              {(p) => (
                <input
                  {...p}
                  type="email"
                  name="email"
                  autoComplete="username"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                />
              )}
            </Field>
            <Field label="Contraseña" error={fieldErrors.password}>
              {(p) => (
                <input
                  {...p}
                  type="password"
                  name="password"
                  autoComplete="current-password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />
              )}
            </Field>
            <ErrorMessage error={login.error} />
            <button type="submit" className="btn btn-primary" disabled={login.busy}>
              {login.busy ? (__DEMO__ ? 'Preparando la base de datos…' : 'Entrando…') : 'Entrar'}
            </button>
          </form>
        )}
      </section>

      {__DEMO__ && (
        <section className="card" aria-labelledby="demo-title">
          <h2 id="demo-title">Cuentas de demostración</h2>
          <p className="small">
            Este es un entorno de demostración. Los usuarios, contraseñas y datos son ficticios y se
            guardan únicamente en este navegador.
          </p>
          <ul className={styles.accounts}>
            {DEMO_ACCOUNTS.map((a) => (
              <li key={a.email}>
                <div>
                  <strong>{a.label}</strong>
                  <br />
                  <span className="small">{a.email}</span>
                  <br />
                  <span className="small muted">contraseña: {a.password}</span>
                </div>
                <button
                  type="button"
                  className="btn btn-sm"
                  disabled={login.busy}
                  // Sign in directly, without typing into the password field: a filled password input
                  // that disappears makes browsers keep offering to save the (fictitious) password.
                  onClick={() => void login.run({ email: a.email, password: a.password })}
                >
                  Entrar como {a.label.toLowerCase()}
                </button>
              </li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
}

/**
 * Public test CAS (casserverpac4j.dev): real CAS protocol with fictitious accounts. Server: a link to the API.
 * Demo: the worker prepares the login state and the page navigates to the CAS server in the same tab.
 */
function TestCas() {
  const start = useAction(async () => {
    const api = await getApi();
    await api.startCasLogin?.();
  });
  const logout = useAction(async () => {
    const api = await getApi();
    await api.casLogout?.();
  });
  return (
    <div className={styles.testCas}>
      <p>
        {__DEMO__ ? (
          <button
            type="button"
            className="btn btn-primary"
            disabled={start.busy}
            onClick={() => void start.run()}
          >
            Entrar con CAS de pruebas
          </button>
        ) : (
          <a className="btn btn-primary" href="/api/auth/cas/login">
            Entrar con CAS de pruebas
          </a>
        )}
      </p>
      <ErrorMessage error={start.error} />
      <p className="small">
        Servidor CAS público de pruebas <strong>www.casserverpac4j.dev</strong>, ajeno a la
        Consejería. Usa sus cuentas de ejemplo <code>alice</code> / <code>pwd</code> (profesora) o{' '}
        <code>bob</code> / <code>pwd</code> (alumno).{' '}
        <strong>No escribas nunca tu usuario ni tu contraseña educativos en ese servidor.</strong>
      </p>
      <p className="small">
        {__DEMO__ ? (
          <button
            type="button"
            className="btn btn-sm"
            disabled={logout.busy}
            onClick={() => void logout.run()}
          >
            Cerrar también la sesión del CAS de pruebas
          </button>
        ) : (
          <a href="/api/auth/cas/logout">Cerrar también la sesión del CAS de pruebas</a>
        )}
      </p>
    </div>
  );
}

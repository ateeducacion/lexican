import { DEMO_ACCOUNTS, LoginInput } from '@lexican/core';
import { useEffect, useState, type FormEvent } from 'react';
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

/** Only allow in-app return paths after login (no open redirect). */
const safeReturn = (v: string | null) => (v && v.startsWith('/') && !v.startsWith('//') ? v : '/');

export function Component() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [providers, setProviders] = useState<{ cas: boolean; password: boolean }>({
    cas: !__DEMO__,
    password: __DEMO__,
  });

  useEffect(() => {
    if (__DEMO__) {
      void getApi(); // start downloading PGlite while the user reads the page
      return;
    }
    fetch('/api/auth/providers')
      .then((r) => (r.ok ? r.json() : null))
      .then((p) => p && setProviders(p))
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
        <PageTitle title="Entrar">LexiCán</PageTitle>
        <p className={styles.lead}>
          Diccionarios personales y de aula para aprender vocabulario: cada alumno crea sus palabras
          y el profesorado las revisa y publica en el diccionario de la clase.
        </p>
      </section>

      <section className="card" aria-labelledby="login-title">
        <h2 id="login-title">Acceso</h2>
        {providers.cas && (
          <p>
            <a className="btn btn-primary" href="/api/auth/cas/login">
              Entrar con tu usuario educativo
            </a>
          </p>
        )}
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
                  onClick={() => {
                    setEmail(a.email);
                    setPassword(a.password);
                    void login.run({ email: a.email, password: a.password });
                  }}
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

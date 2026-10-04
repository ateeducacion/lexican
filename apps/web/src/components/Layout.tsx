import { useEffect, useRef, useState } from 'react';
import {
  NavLink,
  Outlet,
  useLocation,
  useNavigate,
  useNavigation,
  useRouteLoaderData,
} from 'react-router';
import { getApi } from '../api/index.ts';
import { APP_COMMIT, APP_VERSION, SOURCE_URL } from '../env.ts';
import { ROLE_LABEL, type RootData } from '../session.ts';
import styles from './Layout.module.css';
import { Dialog, NotifyProvider } from './ui.tsx';

export function Layout() {
  const { user } = useRouteLoaderData('root') as RootData;
  const navigate = useNavigate();
  const navigation = useNavigation();
  const location = useLocation();
  const main = useRef<HTMLElement>(null);

  // Move focus to the main region after client-side navigation so screen readers announce the new page.
  useEffect(() => {
    main.current?.focus({ preventScroll: true });
  }, [location.pathname]);

  async function logout() {
    await (await getApi()).logout({});
    await navigate('/entrar', { replace: true });
  }

  const staff = user && (user.globalRole === 'admin' || user.globalRole === 'support');
  return (
    <NotifyProvider>
      <a className="skip-link" href="#contenido">
        Saltar al contenido
      </a>
      {__DEMO__ && <DemoBanner />}
      <header role="banner" className={styles.header}>
        <div className={styles.top}>
          <div className={`container ${styles.bar}`}>
            <NavLink
              to={user ? '/' : '/entrar'}
              className={styles.brand}
              aria-label="LexiCán, inicio"
            >
              <span className="wordmark" aria-hidden="true">
                Lexi<span>Cán</span>
              </span>
            </NavLink>
            {user && (
              <div className={styles.user}>
                <span className={styles.userName}>
                  {user.displayName}
                  <span className={styles.role}> · {ROLE_LABEL[user.globalRole]}</span>
                </span>
                <button type="button" className={styles.logout} onClick={logout}>
                  Desconectar
                </button>
              </div>
            )}
          </div>
        </div>
        <div className={styles.band}>
          <div className={`container ${styles.bandInner}`}>
            {user && (
              <>
                <NavLink to="/mi-diccionario?ayuda" className={styles.help}>
                  ¿Qué puedo hacer?
                </NavLink>
                <span className={styles.avatar} aria-hidden="true">
                  {initials(user.displayName)}
                </span>
              </>
            )}
          </div>
        </div>
        {user && (
          <nav aria-label="Principal" className="container">
            <ul className={styles.tabs}>
              <li>
                <NavLink to="/mi-diccionario">Diccionario personal</NavLink>
              </li>
              <li>
                <NavLink to="/aulas">Diccionario de aula</NavLink>
              </li>
              <li>
                <NavLink to="/comentarios">Comentarios</NavLink>
              </li>
              {staff && (
                <li>
                  <NavLink to="/admin">Administración</NavLink>
                </li>
              )}
            </ul>
          </nav>
        )}
        {navigation.state === 'loading' && <div className={styles.progress} aria-hidden="true" />}
      </header>
      <main id="contenido" ref={main} tabIndex={-1} className={`container ${styles.main}`}>
        <Outlet />
      </main>
      <footer role="contentinfo" className={`container ${styles.footer}`}>
        <span>
          LexiCán {APP_VERSION} ({APP_COMMIT})
        </span>
        <a href={SOURCE_URL}>Código fuente</a>
      </footer>
    </NotifyProvider>
  );
}

const initials = (name: string) =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]!.toLocaleUpperCase('es'))
    .join('');

function DemoBanner() {
  const [confirm, setConfirm] = useState(false);
  return (
    <div className={`no-print ${styles.demo}`}>
      <div className="container spread">
        <p className="small" style={{ margin: 0 }}>
          <strong>Entorno de demostración.</strong> Los usuarios, contraseñas y datos son ficticios
          y se guardan únicamente en este navegador.
        </p>
        <button type="button" className="btn btn-sm" onClick={() => setConfirm(true)}>
          Restablecer datos de demostración
        </button>
      </div>
      <Dialog
        open={confirm}
        onClose={() => setConfirm(false)}
        title="Restablecer datos de demostración"
      >
        <p>
          Se borrará todo lo que hayas creado en este navegador y se cargarán de nuevo los datos de
          ejemplo.
        </p>
        <div className="row">
          <button
            type="button"
            className="btn btn-primary"
            onClick={async () => (await getApi()).resetDemo?.()}
          >
            Restablecer
          </button>
          <button type="button" className="btn" onClick={() => setConfirm(false)}>
            Cancelar
          </button>
        </div>
      </Dialog>
    </div>
  );
}

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
import { ROLE_LABEL, type RootData } from '../session.ts';
import { AppInfo } from './AppInfo.tsx';
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
  const sections: { to: string; label: string; short: string; icon: keyof typeof ICONS }[] = [
    { to: '/mi-diccionario', label: 'Diccionario personal', short: 'Personal', icon: 'book' },
    { to: '/aulas', label: 'Diccionario de aula', short: 'Aula', icon: 'class' },
    { to: '/comentarios', label: 'Comentarios', short: 'Comentarios', icon: 'chat' },
    ...(staff
      ? [{ to: '/admin', label: 'Administración', short: 'Admin', icon: 'gear' as const }]
      : []),
  ];
  return (
    <NotifyProvider>
      <a className="skip-link" href="#contenido">
        Saltar al contenido
      </a>
      <header role="banner" className={styles.header}>
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
            <nav aria-label="Principal" className={styles.nav}>
              {sections.map((s) => (
                <NavLink key={s.to} to={s.to}>
                  {s.label}
                </NavLink>
              ))}
            </nav>
          )}
          <AppInfo role={user?.globalRole ?? null} />
          {user && (
            <div className={styles.user}>
              <span className={styles.avatar} aria-hidden="true">
                {initials(user.displayName)}
              </span>
              <span className={styles.userName}>
                {user.displayName}
                <span className="visually-hidden"> · {ROLE_LABEL[user.globalRole]}</span>
              </span>
              <button type="button" className={styles.logout} onClick={logout}>
                Desconectar
              </button>
            </div>
          )}
        </div>
        <div className={styles.band} />
        {navigation.state === 'loading' && <div className={styles.progress} aria-hidden="true" />}
      </header>
      <main id="contenido" ref={main} tabIndex={-1} className={`container ${styles.main}`}>
        <Outlet />
      </main>
      {user && (
        <nav aria-label="Principal (móvil)" className={styles.bottomNav}>
          {sections.map((s) => (
            <NavLink key={s.to} to={s.to}>
              <svg viewBox="0 0 24 24" aria-hidden="true">
                {ICONS[s.icon]}
              </svg>
              {s.short}
            </NavLink>
          ))}
        </nav>
      )}
      {__DEMO__ && <DemoNotice />}
    </NotifyProvider>
  );
}

const ICONS = {
  book: (
    <>
      <path d="M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3z" />
      <path d="M5 17a3 3 0 0 1 3-3h11" />
    </>
  ),
  class: (
    <>
      <rect x="3" y="4" width="18" height="12" rx="2" />
      <path d="M8 20h8M12 16v4" />
    </>
  ),
  chat: <path d="M4 5h16v11H9l-5 4z" />,
  gear: (
    <>
      <circle cx="12" cy="12" r="3" />
      <path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1" />
    </>
  ),
} as const;

const initials = (name: string) =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]!.toLocaleUpperCase('es'))
    .join('');

const NOTICE_KEY = 'lexican-demo-notice-hidden';
const noticeHidden = () => {
  try {
    return sessionStorage.getItem(NOTICE_KEY) === '1';
  } catch {
    return false;
  }
};

/** Floating demo notice; hidden with ✕ for this visit only, so the reset stays reachable next time. */
function DemoNotice() {
  const [confirm, setConfirm] = useState(false);
  const [hidden, setHidden] = useState(noticeHidden);
  const hide = () => {
    setHidden(true);
    try {
      sessionStorage.setItem(NOTICE_KEY, '1');
    } catch {
      /* storage unavailable: hidden until reload */
    }
  };
  return (
    <>
      {!hidden && (
        <aside className={`no-print ${styles.demo}`} aria-label="Entorno de demostración">
          <p>
            <strong>Entorno de demostración.</strong> Los usuarios, contraseñas y datos son
            ficticios y se guardan únicamente en este navegador.
          </p>
          <button type="button" className="btn btn-sm" onClick={() => setConfirm(true)}>
            Restablecer datos de demostración
          </button>
          <button
            type="button"
            className={styles.demoClose}
            onClick={hide}
            aria-label="Ocultar el aviso de demostración"
          >
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 6l12 12M18 6 6 18" />
            </svg>
          </button>
        </aside>
      )}
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
    </>
  );
}

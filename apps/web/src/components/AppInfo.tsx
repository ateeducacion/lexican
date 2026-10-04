import { useEffect, useId, useRef, useState } from 'react';
import { Link } from 'react-router';
import type { GlobalRole } from '@lexican/core';
import { APP_COMMIT, APP_VERSION, SOURCE_URL } from '../env.ts';
import styles from './Layout.module.css';
import { Dialog } from './ui.tsx';

/** "?" (help adapted to the role) and "i" (version, licence, source code) buttons for the top bar. */
export function AppInfo({ role }: { role: GlobalRole | null }) {
  const [open, setOpen] = useState<'help' | 'about' | 'licenses' | null>(null);
  const close = () => setOpen(null);
  // A native dialog also fires `close` when we switch to another one: only clear our own state.
  const closeIf = (which: 'help' | 'about' | 'licenses') => () =>
    setOpen((current) => (current === which ? null : current));
  return (
    <div className={styles.infoButtons}>
      <ThemeToggle />
      <button
        type="button"
        className={styles.iconButton}
        aria-label="Ayuda"
        aria-haspopup="dialog"
        onClick={() => setOpen('help')}
      >
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="12" r="9" />
          <path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6M12 17h.01" />
        </svg>
      </button>
      <button
        type="button"
        className={styles.iconButton}
        aria-label="Acerca de LexiCán"
        aria-haspopup="dialog"
        onClick={() => setOpen('about')}
      >
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="12" r="9" />
          <path d="M12 11v6M12 7.5h.01" />
        </svg>
      </button>

      <Dialog open={open === 'help'} onClose={closeIf('help')} title="¿Qué puedo hacer?">
        {role === 'teacher' || role === 'admin' ? <TeacherHelp onNavigate={close} /> : null}
        {role === 'student' || role === 'support' ? <StudentHelp onNavigate={close} /> : null}
        {role === null && (
          <p>
            Entra con tu usuario para crear tu diccionario personal. En la demostración puedes usar
            las cuentas de ejemplo que aparecen bajo el formulario.
          </p>
        )}
        <div className="row">
          <button type="button" className="btn btn-primary" onClick={close}>
            Entendido
          </button>
        </div>
      </Dialog>

      <Dialog open={open === 'about'} onClose={closeIf('about')} title="Acerca de LexiCán">
        <div className={styles.aboutHero}>
          <span className="wordmark" aria-hidden="true">
            Lexi<span>Cán</span>
          </span>
          <span>
            Versión {APP_VERSION} <span className={styles.commit}>({APP_COMMIT})</span>
          </span>
        </div>
        <p>
          Diccionarios personales y de aula para aprender vocabulario: cada alumno crea sus palabras
          y el profesorado las revisa y publica en el diccionario de la clase.
        </p>
        <ul className={styles.aboutLinks}>
          <li>
            <a href={SOURCE_URL}>
              <svg viewBox="0 0 16 16" aria-hidden="true" className={styles.github}>
                <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z" />
              </svg>
              <span>
                Código fuente
                <small>github.com/ateeducacion/lexican</small>
              </span>
            </a>
          </li>
          <li>
            <button type="button" onClick={() => setOpen('licenses')}>
              <svg viewBox="0 0 24 24" aria-hidden="true" className={styles.lineIcon}>
                <path d="M7 3h7l5 5v13H7z" />
                <path d="M14 3v5h5M10 13h6M10 17h6" />
              </svg>
              <span>
                Licencias
                <small>GNU AGPL-3.0 o posterior y componentes de terceros</small>
              </span>
            </button>
          </li>
        </ul>
        <p className="small muted">
          {__DEMO__
            ? 'Demostración: los datos son ficticios y solo se guardan en este navegador.'
            : 'Sin analítica ni servicios de terceros.'}
        </p>
        <div className={styles.aboutFooter}>
          <img src={`${import.meta.env.BASE_URL}ate-logo.png`} alt="" className={styles.ateLogo} />
          <span>Área de Tecnología Educativa · Gobierno de Canarias</span>
        </div>
        <div className="row">
          <button type="button" className="btn btn-primary" onClick={close}>
            Cerrar
          </button>
        </div>
      </Dialog>

      <LicensesPanel
        open={open === 'licenses'}
        onClose={closeIf('licenses')}
        onBack={() => setOpen('about')}
      />
    </div>
  );
}

function StudentHelp({ onNavigate }: { onNavigate: () => void }) {
  return (
    <ol className={styles.helpSteps}>
      <li>
        Pulsa <strong>Añadir</strong>, escribe la palabra y su definición. Puedes añadir un ejemplo,
        temáticas, una imagen, un audio o un vídeo.
      </li>
      <li>
        Pulsa <strong>Guardar</strong>. La palabra queda en tu diccionario; solo tú la ves.
      </li>
      <li>
        Únete al diccionario de tu clase con el código que te dé tu profesor o profesora en{' '}
        <Link to="/aulas" onClick={onNavigate}>
          Diccionario de aula
        </Link>
        .
      </li>
      <li>
        Abre una palabra y pulsa <strong>Enviar al aula</strong>. Verás si está{' '}
        <em>Pendiente de revisión</em>, <em>Publicada</em> o <em>Devuelta</em>; si te la devuelven,
        corrígela y vuelve a enviarla.
      </li>
      <li>
        Lee lo que te escribe el profesorado en{' '}
        <Link to="/comentarios" onClick={onNavigate}>
          Comentarios
        </Link>
        .
      </li>
    </ol>
  );
}

function TeacherHelp({ onNavigate }: { onNavigate: () => void }) {
  return (
    <ol className={styles.helpSteps}>
      <li>
        En{' '}
        <Link to="/aulas" onClick={onNavigate}>
          Diccionario de aula
        </Link>{' '}
        pulsa <strong>Nuevo diccionario de aula</strong>: elige nivel, materia, campos obligatorios
        y el plazo de envíos.
      </li>
      <li>
        Comparte con tu clase el <strong>código de unión</strong> de seis letras.
      </li>
      <li>
        En <strong>Revisar envíos</strong> lee cada palabra enviada: publícala en el diccionario del
        aula, devuélvela con una nota o escribe un comentario al alumno.
      </li>
      <li>
        Consulta el diccionario publicado, oculta o corrige entradas y expórtalo en PDF, CSV o
        DMLex.
      </li>
      <li>
        También tienes un{' '}
        <Link to="/mi-diccionario" onClick={onNavigate}>
          Diccionario personal
        </Link>{' '}
        propio.
      </li>
    </ol>
  );
}

interface BundledPackage {
  name: string;
  version: string;
  license: string;
}

/** Right-hand side panel with LexiCán's licence and the third-party packages bundled in this build. */
function LicensesPanel({
  open,
  onClose,
  onBack,
}: {
  open: boolean;
  onClose: () => void;
  onBack: () => void;
}) {
  const ref = useRef<HTMLDialogElement>(null);
  const titleId = useId();
  const [packages, setPackages] = useState<BundledPackage[] | null>(null);
  useEffect(() => {
    const d = ref.current;
    if (!d) return;
    if (open && !d.open) d.showModal();
    if (!open && d.open) d.close();
    if (open && !packages)
      fetch(`${import.meta.env.BASE_URL}licenses.json`)
        .then((r) => (r.ok ? (r.json() as Promise<BundledPackage[]>) : []))
        .then(setPackages)
        .catch(() => setPackages([]));
  }, [open, packages]);
  return (
    <dialog ref={ref} className={styles.drawer} aria-labelledby={titleId} onClose={onClose}>
      <div className={styles.drawerHead}>
        <button type="button" className="btn btn-sm" onClick={onBack}>
          ← Acerca de
        </button>
        <button type="button" className={styles.drawerClose} onClick={onClose} aria-label="Cerrar">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18" />
          </svg>
        </button>
      </div>
      <h2 id={titleId}>Licencias</h2>
      <section className={styles.licenseCard}>
        <span className="eyebrow">LexiCán</span>
        <p>
          <strong>GNU Affero General Public License v3.0 o posterior</strong> (AGPL-3.0-or-later).
          Puedes usar, estudiar, modificar y compartir LexiCán; si ofreces una versión modificada a
          través de la red, debes publicar su código fuente con la misma licencia.
        </p>
        <p className="row">
          <a href={`${SOURCE_URL}/blob/main/LICENSE`}>Texto completo de la licencia</a>
          <a href={`${SOURCE_URL}/blob/main/THIRD_PARTY_NOTICES.md`}>Avisos de terceros</a>
        </p>
      </section>
      <h3>Componentes incluidos en esta aplicación</h3>
      {!packages ? (
        <p className="muted">Cargando…</p>
      ) : (
        <ul className={styles.packageList}>
          {packages.map((p) => (
            <li key={p.name}>
              <span>
                <strong>{p.name}</strong> <span className="muted small">{p.version}</span>
              </span>
              <span className="chip">{p.license}</span>
            </li>
          ))}
          <li>
            <span>
              <strong>Ilustraciones de la demostración</strong>
            </span>
            <span className="chip">CC0-1.0</span>
          </li>
        </ul>
      )}
    </dialog>
  );
}

type Theme = 'light' | 'dark';
const currentTheme = (): Theme =>
  document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';

/** Light/dark switch. The first visit follows the system; an explicit choice is remembered in this browser. */
function ThemeToggle() {
  const [theme, setTheme] = useState<Theme>(currentTheme);
  useEffect(() => {
    // Follow system changes while the user has not chosen explicitly.
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    const onChange = () => {
      let saved: string | null = null;
      try {
        saved = localStorage.getItem('lexican-theme');
      } catch {
        /* storage unavailable */
      }
      if (saved) return;
      document.documentElement.dataset.theme = mq.matches ? 'dark' : 'light';
      setTheme(currentTheme());
    };
    mq.addEventListener('change', onChange);
    return () => mq.removeEventListener('change', onChange);
  }, []);
  const toggle = () => {
    const next: Theme = theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try {
      localStorage.setItem('lexican-theme', next);
    } catch {
      /* storage unavailable: applies until reload */
    }
    setTheme(next);
  };
  const dark = theme === 'dark';
  return (
    <button
      type="button"
      className={styles.iconButton}
      aria-label={dark ? 'Activar tema claro' : 'Activar tema oscuro'}
      title={dark ? 'Tema claro' : 'Tema oscuro'}
      onClick={toggle}
    >
      <svg viewBox="0 0 24 24" aria-hidden="true">
        {dark ? (
          <>
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
          </>
        ) : (
          <path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5z" />
        )}
      </svg>
    </button>
  );
}

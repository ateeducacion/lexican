import { useState } from 'react';
import { Link } from 'react-router';
import type { GlobalRole } from '@lexican/core';
import { APP_COMMIT, APP_VERSION, SOURCE_URL } from '../env.ts';
import styles from './Layout.module.css';
import { Dialog } from './ui.tsx';

/** "?" (help adapted to the role) and "i" (version, licence, source code) buttons for the top bar. */
export function AppInfo({ role }: { role: GlobalRole | null }) {
  const [open, setOpen] = useState<'help' | 'about' | null>(null);
  const close = () => setOpen(null);
  return (
    <div className={styles.infoButtons}>
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

      <Dialog open={open === 'help'} onClose={close} title="¿Qué puedo hacer?">
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

      <Dialog open={open === 'about'} onClose={close} title="Acerca de LexiCán">
        <p>
          Diccionarios personales y de aula para aprender vocabulario: cada alumno crea sus palabras
          y el profesorado las revisa y publica en el diccionario de la clase.
        </p>
        <dl className={styles.about}>
          <dt>Versión</dt>
          <dd>
            {APP_VERSION} <span className="muted">({APP_COMMIT})</span>
          </dd>
          <dt>Licencia</dt>
          <dd>
            Software libre bajo{' '}
            <a href={`${SOURCE_URL}/blob/main/LICENSE`} rel="license">
              GNU AGPL-3.0 o posterior
            </a>
          </dd>
          <dt>Código fuente</dt>
          <dd>
            <a href={SOURCE_URL}>github.com/ateeducacion/lexican</a>
          </dd>
          <dt>Componentes de terceros</dt>
          <dd>
            <a href={`${SOURCE_URL}/blob/main/THIRD_PARTY_NOTICES.md`}>Licencias y avisos</a>
          </dd>
          <dt>Privacidad</dt>
          <dd>
            {__DEMO__
              ? 'Demostración: los datos son ficticios y solo se guardan en este navegador.'
              : 'Sin analítica ni servicios de terceros. '}
            {!__DEMO__ && <a href={`${SOURCE_URL}/blob/main/docs/PRIVACY.md`}>Más información</a>}
          </dd>
          <dt>Desarrollo</dt>
          <dd>Área de Tecnología Educativa · Gobierno de Canarias</dd>
        </dl>
        <div className="row">
          <button type="button" className="btn btn-primary" onClick={close}>
            Cerrar
          </button>
        </div>
      </Dialog>
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

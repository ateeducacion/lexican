import type { DictionaryView } from '@lexican/core';
import { Link, useLoaderData } from 'react-router';
import { getApi } from '../../api/index.ts';
import { EntryList } from '../../components/EntryList.tsx';
import { PageTitle } from '../../components/ui.tsx';
import { Avatar } from './Avatar.tsx';
import styles from './personal.module.css';

export async function loader(): Promise<DictionaryView> {
  return (await getApi()).myDictionary({});
}

export function Component() {
  const dict = useLoaderData<DictionaryView>();
  return (
    <div className="stack">
      <header className={styles.header}>
        <Avatar id={dict.avatar} title={dict.title} />
        <div className={styles.headerText}>
          <PageTitle title="Mi diccionario">{dict.title}</PageTitle>
          <p className="muted small">
            {dict.entryCount} {dict.entryCount === 1 ? 'palabra' : 'palabras'} en tu diccionario
            personal
          </p>
        </div>
      </header>

      <div className={styles.actions}>
        <Link to="/mi-diccionario/nueva" className="btn btn-primary">
          + Nueva palabra
        </Link>
        <Link to="/mi-diccionario/enviar" className="btn">
          Enviar al aula
        </Link>
        <Link to={`/diccionarios/${dict.id}/imprimir`} className="btn">
          Imprimir o exportar
        </Link>
        <Link to="/mi-diccionario/ajustes" className="btn">
          Ajustes
        </Link>
      </div>

      <details className="card" open={dict.entryCount === 0 || undefined}>
        <summary className={styles.helpSummary}>¿Qué puedo hacer?</summary>
        <ol className={styles.steps}>
          <li>
            Pulsa <strong>Nueva palabra</strong>, escribe la palabra y su definición. Puedes añadir
            un ejemplo, temáticas, una imagen, un audio o un vídeo.
          </li>
          <li>
            Pulsa <strong>Guardar</strong>. La palabra queda en tu diccionario; solo tú la ves.
          </li>
          <li>
            Ábrela y pulsa <strong>Enviar al aula</strong> para mandarla al diccionario de tu clase
            (antes tienes que unirte con el código que te dé tu profesor o profesora en{' '}
            <Link to="/aulas">Mis aulas</Link>).
          </li>
          <li>
            Mira el estado del envío: <em>Pendiente de revisión</em>, <em>Publicada</em> o{' '}
            <em>Devuelta</em>. Si te la devuelven, corrígela y vuelve a enviarla.
          </li>
          <li>
            Lee lo que te escribe el profesorado en <Link to="/comentarios">Comentarios</Link>.
          </li>
        </ol>
      </details>

      <EntryList
        dictionaryId={dict.id}
        entryHref={(id) => `/mi-diccionario/entradas/${id}`}
        canEdit
        showSubmissions
      />
    </div>
  );
}

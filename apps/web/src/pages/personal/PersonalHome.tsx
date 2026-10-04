import type { DictionaryView } from '@lexican/core';
import { Link, useLoaderData, useSearchParams } from 'react-router';
import { getApi } from '../../api/index.ts';
import { EntryList } from '../../components/EntryList.tsx';
import { Tile, Tiles } from '../../components/Tiles.tsx';
import { PageTitle } from '../../components/ui.tsx';
import { Avatar } from './Avatar.tsx';
import styles from './personal.module.css';

export async function loader(): Promise<DictionaryView> {
  return (await getApi()).myDictionary({});
}

export function Component() {
  const dict = useLoaderData<DictionaryView>();
  const [params] = useSearchParams();
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

      <Tiles label="Acciones del diccionario personal">
        <Tile to="/mi-diccionario/nueva" icon="add" label="Añadir entrada" />
        <Tile to="/mi-diccionario/enviar" icon="send" label="Enviar al aula" />
        <Tile to={`/diccionarios/${dict.id}/imprimir`} icon="export" label="Imprimir o exportar" />
        <Tile to="/mi-diccionario/ajustes" icon="manage" label="Gestión del diccionario" />
      </Tiles>

      <details className="card" open={dict.entryCount === 0 || params.has('ayuda') || undefined}>
        <summary className={styles.helpSummary}>¿Qué puedo hacer?</summary>
        <ol className={styles.steps}>
          <li>
            Pulsa <strong>Añadir entrada</strong>, escribe la palabra y su definición. Puedes añadir
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

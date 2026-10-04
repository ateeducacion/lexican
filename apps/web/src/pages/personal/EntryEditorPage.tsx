import { headwordKey, type EntryInput, type EntryView } from '@lexican/core';
import { useState } from 'react';
import { Link, useLoaderData, useNavigate, type LoaderFunctionArgs } from 'react-router';
import { getApi, toApiError } from '../../api/index.ts';
import { EntryEditor } from '../../components/EntryEditor.tsx';
import { PageTitle, useNotify } from '../../components/ui.tsx';

interface Data {
  dictionaryId: string;
  entry: EntryView | null;
}

export async function loader({ params }: LoaderFunctionArgs): Promise<Data> {
  const api = await getApi();
  const [dict, entry] = await Promise.all([
    api.myDictionary({}),
    params.entryId ? api.getEntry({ entryId: params.entryId }) : Promise.resolve(null),
  ]);
  return { dictionaryId: dict.id, entry };
}

export function Component() {
  const { dictionaryId, entry } = useLoaderData<Data>();
  const navigate = useNavigate();
  const notify = useNotify();
  const [existing, setExisting] = useState<{ id: string; headword: string } | null>(null);
  const back = entry ? `/mi-diccionario/entradas/${entry.id}` : '/mi-diccionario';

  async function save(input: EntryInput): Promise<EntryView> {
    const api = await getApi();
    setExisting(null);
    try {
      const saved = entry
        ? await api.updateEntry({ entryId: entry.id, version: entry.version, entry: input })
        : await api.createEntry({ dictionaryId, entry: input });
      notify(entry ? 'Cambios guardados' : `«${saved.headword}» añadida a tu diccionario`);
      navigate(`/mi-diccionario/entradas/${saved.id}`, { replace: !entry });
      return saved;
    } catch (e) {
      // Duplicate headword: point to the entry that already uses it.
      if (toApiError(e).details.headword) {
        const page = await api.listEntries({ dictionaryId, q: input.headword, includeHidden: true, limit: 20 }).catch(() => null);
        const match = page?.items.find((i) => headwordKey(i.headword) === headwordKey(input.headword) && i.id !== entry?.id);
        if (match) setExisting({ id: match.id, headword: match.headword });
      }
      throw e;
    }
  }

  return (
    <div className="stack">
      <p className="small">
        <Link to={back}>← {entry ? `Volver a «${entry.headword}»` : 'Mi diccionario'}</Link>
      </p>
      <PageTitle>{entry ? `Editar «${entry.headword}»` : 'Nueva palabra'}</PageTitle>
      {existing && (
        <p className="alert alert-warning" role="status">
          Ya tienes la palabra «{existing.headword}». <Link to={`/mi-diccionario/entradas/${existing.id}`}>Abrir la entrada existente</Link>{' '}
          para añadirle acepciones.
        </p>
      )}
      <EntryEditor key={entry?.version ?? 'new'} entry={entry ?? undefined} onSave={save} onCancel={() => navigate(back)} />
    </div>
  );
}

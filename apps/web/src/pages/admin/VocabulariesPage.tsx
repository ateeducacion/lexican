import {
  VOCABULARIES,
  VOCABULARY_LABELS,
  type Vocabulary,
  type VocabularyValue,
} from '@lexican/core';
import { type FormEvent, useState } from 'react';
import { useLoaderData, useRevalidator, useSearchParams } from 'react-router';
import { getApi } from '../../api/index.ts';
import { ErrorMessage, PageTitle, useAction, useNotify } from '../../components/ui.tsx';
import { invalidateVocabulary } from '../../session.ts';
import { AdminNav } from './AdminNav.tsx';
import { requireStaff } from './guard.ts';

export async function loader(): Promise<VocabularyValue[]> {
  await requireStaff(true);
  return (await getApi()).vocabularies({});
}

/** Stable code from a label: "Flora canaria" → "flora_canaria". */
const slug = (s: string) =>
  s
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, '')
    .slice(0, 60);

/** Administrable controlled vocabularies (§31, §56): codes are stable, labels and order can change. */
export function Component() {
  const values = useLoaderData() as VocabularyValue[];
  const [params, setParams] = useSearchParams();
  const vocabulary = (VOCABULARIES as readonly string[]).includes(params.get('lista') ?? '')
    ? (params.get('lista') as Vocabulary)
    : 'topic';
  const list = values.filter((v) => v.vocabulary === vocabulary);
  const { revalidate } = useRevalidator();
  const notify = useNotify();
  const save = useAction(async (v: Omit<VocabularyValue, 'id'> & { id?: string }) => {
    await (await getApi()).adminSaveVocabularyValue(v);
    invalidateVocabulary();
    notify('Lista actualizada');
    await revalidate();
  });
  const [label, setLabel] = useState('');

  const add = async (e: FormEvent) => {
    e.preventDefault();
    if (!label.trim()) return;
    await save.run({
      vocabulary,
      code: slug(label),
      label: label.trim(),
      abbreviation: null,
      position: list.length,
      featured: false,
      active: true,
    });
    setLabel('');
  };

  return (
    <>
      <PageTitle>Listas de valores</PageTitle>
      <AdminNav />
      <div className="field" style={{ maxWidth: '22rem' }}>
        <label htmlFor="lista">Lista</label>
        <select
          id="lista"
          value={vocabulary}
          onChange={(e) => setParams({ lista: e.target.value })}
        >
          {VOCABULARIES.map((v) => (
            <option key={v} value={v}>
              {VOCABULARY_LABELS[v]}
            </option>
          ))}
        </select>
      </div>
      <ErrorMessage error={save.error} />
      <form onSubmit={add} className="row" style={{ marginBottom: 'var(--space-4)' }}>
        <label htmlFor="nuevo" className="visually-hidden">
          Nuevo valor
        </label>
        <input
          id="nuevo"
          type="text"
          value={label}
          onChange={(e) => setLabel(e.target.value)}
          placeholder="Nuevo valor"
          style={{ maxWidth: '20rem' }}
        />
        <button type="submit" className="btn btn-primary" disabled={save.busy || !label.trim()}>
          Añadir
        </button>
      </form>
      <div className="table-wrap">
        <table>
          <caption>
            {VOCABULARY_LABELS[vocabulary]} ({list.length})
          </caption>
          <thead>
            <tr>
              <th scope="col">Etiqueta</th>
              <th scope="col">Código</th>
              <th scope="col">Destacado</th>
              <th scope="col">Activo</th>
            </tr>
          </thead>
          <tbody>
            {list.map((v) => (
              <tr key={v.id}>
                <th scope="row">
                  <label className="visually-hidden" htmlFor={`l-${v.id}`}>
                    Etiqueta de {v.label}
                  </label>
                  <input
                    id={`l-${v.id}`}
                    type="text"
                    defaultValue={v.label}
                    onBlur={(e) =>
                      e.target.value.trim() &&
                      e.target.value.trim() !== v.label &&
                      save.run({ ...v, label: e.target.value.trim() })
                    }
                  />
                </th>
                <td>
                  <code>{v.code}</code>
                </td>
                <td>
                  <input
                    type="checkbox"
                    aria-label={`Destacar ${v.label}`}
                    checked={v.featured}
                    onChange={(e) => save.run({ ...v, featured: e.target.checked })}
                  />
                </td>
                <td>
                  <input
                    type="checkbox"
                    aria-label={`${v.label} activo`}
                    checked={v.active}
                    onChange={(e) => save.run({ ...v, active: e.target.checked })}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  );
}

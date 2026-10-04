import { operations, type DictionaryView } from '@lexican/core';
import { useState, type FormEvent } from 'react';
import { Link, useLoaderData, useNavigate } from 'react-router';
import { getApi } from '../../api/index.ts';
import { ErrorMessage, Field, PageTitle, useAction, useNotify } from '../../components/ui.tsx';
import { AVATARS, Avatar } from './Avatar.tsx';
import styles from './personal.module.css';

export async function loader(): Promise<DictionaryView> {
  return (await getApi()).myDictionary({});
}

export function Component() {
  const dict = useLoaderData<DictionaryView>();
  const navigate = useNavigate();
  const notify = useNotify();
  const [title, setTitle] = useState(dict.title);
  const [avatar, setAvatar] = useState(
    dict.avatar && AVATARS.some((a) => a.id === dict.avatar) ? dict.avatar : AVATARS[0].id,
  );
  const [errors, setErrors] = useState<Record<string, string[]>>({});

  const save = useAction(async () => {
    const parsed = operations.updateMyDictionary.input.safeParse({ title, avatar });
    if (!parsed.success) {
      setErrors({ title: ['Escribe un título de 1 a 150 caracteres'] });
      return;
    }
    setErrors({});
    await (await getApi()).updateMyDictionary(parsed.data);
    notify('Ajustes guardados');
    navigate('/mi-diccionario');
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    void save.run();
  };

  return (
    <div className="stack" style={{ maxWidth: '40rem' }}>
      <p className="small">
        <Link to="/mi-diccionario">← Mi diccionario</Link>
      </p>
      <PageTitle>Ajustes de mi diccionario</PageTitle>
      <form onSubmit={submit} noValidate className="card">
        <Field label="Título del diccionario" error={errors.title}>
          {(p) => (
            <input
              {...p}
              type="text"
              maxLength={150}
              value={title}
              onChange={(e) => setTitle(e.target.value)}
            />
          )}
        </Field>
        <fieldset>
          <legend>Imagen del diccionario</legend>
          <div className={styles.avatars}>
            {AVATARS.map((a) => (
              <label key={a.id}>
                <input
                  type="radio"
                  name="avatar"
                  className="visually-hidden"
                  value={a.id}
                  checked={avatar === a.id}
                  onChange={() => setAvatar(a.id)}
                />
                <Avatar id={a.id} title={title} size={40} />
                <span>{a.label}</span>
              </label>
            ))}
          </div>
        </fieldset>
        <ErrorMessage error={save.error} />
        <div className="row">
          <button type="submit" className="btn btn-primary" disabled={save.busy}>
            {save.busy ? 'Guardando…' : 'Guardar'}
          </button>
          <Link to="/mi-diccionario" className="btn">
            Cancelar
          </Link>
        </div>
      </form>
    </div>
  );
}

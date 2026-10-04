import { type DictionaryView, JoinInput } from '@lexican/core';
import { type FormEvent, useState } from 'react';
import { Link, useLoaderData, useNavigate } from 'react-router';
import { getApi } from '../../api/index.ts';
import { EmptyState, ErrorMessage, Field, useAction, useNotify } from '../../components/ui.tsx';
import { useSession } from '../../session.ts';
import styles from './Classrooms.module.css';
import { ClassroomState, JoinCode, PageHeader } from './shared.tsx';

export async function loader() {
  return (await getApi()).myClassrooms({});
}

export function Component() {
  const classrooms = useLoaderData<DictionaryView[]>();
  const { user } = useSession();
  const canCreate = user.globalRole === 'teacher' || user.globalRole === 'admin';
  const teaching = classrooms.filter((c) => c.myRole === 'teacher');
  const learning = classrooms.filter((c) => c.myRole !== 'teacher');
  const pending = teaching.reduce((n, c) => n + c.pendingCount, 0);

  return (
    <>
      <PageHeader eyebrow="Diccionario de aula" title="Mis aulas">
        {canCreate && (
          <Link className="btn btn-primary" to="/aulas/nueva">
            Nuevo diccionario de aula
          </Link>
        )}
      </PageHeader>

      {teaching.length > 0 && (
        <section aria-labelledby="teaching-title">
          <h2 id="teaching-title">Diccionarios que coordino</h2>
          <p className="muted small">
            {pending === 0
              ? 'No tienes envíos pendientes de revisar.'
              : `Tienes ${pending} ${pending === 1 ? 'envío pendiente' : 'envíos pendientes'} de revisar.`}
          </p>
          <ul className={styles.cards}>
            {teaching.map((c) => (
              <li key={c.id} className={styles.dcard}>
                <h3>
                  <Link to={`/aulas/${c.id}`}>{c.title}</Link>
                </h3>
                <ClassroomState dictionary={c} />
                <p className={styles.stats}>
                  <span>
                    <strong>{c.entryCount}</strong>{' '}
                    {c.entryCount === 1 ? 'entrada publicada' : 'entradas publicadas'}
                  </span>
                  <span>
                    Código <JoinCode code={c.classroom?.joinCode ?? ''} />
                  </span>
                </p>
                <div className={styles.cardActions}>
                  {c.pendingCount > 0 ? (
                    <Link className={`btn btn-primary ${styles.cta}`} to={`/aulas/${c.id}/envios`}>
                      Revisar {c.pendingCount} {c.pendingCount === 1 ? 'envío' : 'envíos'}
                    </Link>
                  ) : (
                    <Link className="btn" to={`/aulas/${c.id}/envios`}>
                      Sin envíos pendientes
                    </Link>
                  )}
                  <Link className="btn btn-sm btn-link" to={`/aulas/${c.id}/participantes`}>
                    Participantes
                  </Link>
                  <Link className="btn btn-sm btn-link" to={`/aulas/${c.id}/editar`}>
                    Editar
                  </Link>
                </div>
              </li>
            ))}
          </ul>
        </section>
      )}

      {(learning.length > 0 || teaching.length === 0) && (
        <section
          aria-labelledby="learning-title"
          className={teaching.length ? styles.section : undefined}
        >
          <h2 id="learning-title">
            {teaching.length
              ? 'Otros diccionarios de aula'
              : 'Diccionarios de aula en los que participo'}
          </h2>
          {learning.length === 0 ? (
            <EmptyState title="Todavía no participas en ningún diccionario de aula">
              <p>Pide el código a tu profesor o profesora y escríbelo abajo para unirte.</p>
            </EmptyState>
          ) : (
            <ul className={styles.cards}>
              {learning.map((c) => (
                <li key={c.id} className={styles.dcard}>
                  <h3>
                    <Link to={`/aulas/${c.id}`}>{c.title}</Link>
                  </h3>
                  <ClassroomState dictionary={c} />
                  {c.description && <p className="small">{c.description}</p>}
                  <div className={styles.cardActions}>
                    <Link className="btn btn-primary" to={`/aulas/${c.id}`}>
                      Ver diccionario
                    </Link>
                    {c.classroom?.guidelines && (
                      <Link className="btn btn-sm btn-link" to={`/aulas/${c.id}?pautas`}>
                        Pautas
                      </Link>
                    )}
                    {c.classroom?.windowState === 'open' && (
                      <Link className="btn btn-sm btn-link" to="/mi-diccionario/enviar">
                        Enviar palabras
                      </Link>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </section>
      )}

      <JoinForm />
    </>
  );
}

function JoinForm() {
  const navigate = useNavigate();
  const notify = useNotify();
  const [code, setCode] = useState('');
  const [fieldError, setFieldError] = useState<string>();
  const join = useAction(async (raw: string) => {
    const parsed = JoinInput.safeParse({ code: raw });
    if (!parsed.success) {
      setFieldError(parsed.error.issues[0]?.message);
      return;
    }
    setFieldError(undefined);
    const dict = await (await getApi()).joinClassroom(parsed.data);
    notify(`Te has unido a «${dict.title}».`);
    navigate(`/aulas/${dict.id}`);
  });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    void join.run(code);
  };
  return (
    <section aria-labelledby="join-title" className={`card ${styles.section}`}>
      <h2 id="join-title">Unirse con un código</h2>
      <p className="small muted">
        Tu profesor o profesora te dará un código de 6 letras o números.
      </p>
      <form onSubmit={submit} noValidate className={styles.joinForm}>
        <Field label="Código del diccionario de aula" error={fieldError}>
          {(p) => (
            <input
              {...p}
              type="text"
              name="code"
              autoComplete="off"
              autoCapitalize="characters"
              spellCheck={false}
              maxLength={6}
              value={code}
              onChange={(e) => setCode(e.target.value)}
            />
          )}
        </Field>
        <button type="submit" className="btn btn-primary" disabled={join.busy}>
          {join.busy ? 'Uniéndote…' : 'Unirme'}
        </button>
      </form>
      <ErrorMessage error={join.error} />
    </section>
  );
}

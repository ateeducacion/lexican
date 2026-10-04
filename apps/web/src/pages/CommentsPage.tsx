import type { CommentView } from '@lexican/core';
import { Link, useLoaderData } from 'react-router';
import { getApi } from '../api/index.ts';
import { EmptyState, formatDate, PageTitle } from '../components/ui.tsx';
import { useSession } from '../session.ts';
import styles from './CommentsPage.module.css';

export async function loader(): Promise<CommentView[]> {
  return (await getApi()).listComments({});
}

export function Component() {
  const comments = useLoaderData<CommentView[]>();
  const { user } = useSession();
  const teacher = user.globalRole !== 'student';
  const groups = new Map<string, CommentView[]>();
  for (const c of comments)
    groups.set(c.classroomTitle, [...(groups.get(c.classroomTitle) ?? []), c]);

  return (
    <div className="stack">
      <PageTitle>Comentarios</PageTitle>
      {teacher && (
        <p className="alert">
          Los comentarios al alumnado se escriben desde la revisión de envíos de cada aula. Entra en{' '}
          <Link to="/aulas">Mis aulas</Link> y abre los envíos de un diccionario de aula.
        </p>
      )}
      {comments.length === 0
        ? !teacher && (
            <EmptyState title="No tienes comentarios">
              <p className="muted">Cuando el profesorado comente tus palabras, lo verás aquí.</p>
            </EmptyState>
          )
        : [...groups].map(([classroom, list]) => (
            <section
              key={classroom}
              aria-labelledby={`c-${list[0]!.classroomId}`}
              className={styles.group}
            >
              <p className="eyebrow">Diccionario de aula</p>
              <h2 id={`c-${list[0]!.classroomId}`}>{classroom}</h2>
              <ol className={styles.list}>
                {list.map((c) => (
                  <li key={c.id}>
                    <span className={styles.avatar} aria-hidden="true">
                      {initials(c.author.displayName)}
                    </span>
                    <div className={styles.body}>
                      <p className={styles.meta}>
                        <strong>{c.author.displayName}</strong>
                        {teacher && ` → ${c.student.displayName}`} ·{' '}
                        <time dateTime={c.createdAt}>{formatDate(c.createdAt, true)}</time>
                      </p>
                      <p className={styles.bubble}>{c.body}</p>
                      {c.headword && (
                        <p className={styles.about}>
                          Sobre la palabra{' '}
                          {teacher ? (
                            <strong lang="es">{c.headword}</strong>
                          ) : (
                            <Link
                              to={`/mi-diccionario?q=${encodeURIComponent(c.headword)}`}
                              lang="es"
                            >
                              {c.headword}
                            </Link>
                          )}
                        </p>
                      )}
                    </div>
                  </li>
                ))}
              </ol>
            </section>
          ))}
    </div>
  );
}

const initials = (name: string) =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]!.toLocaleUpperCase('es'))
    .join('');

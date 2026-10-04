import type { CommentView } from '@lexican/core';
import { Link, useLoaderData } from 'react-router';
import { getApi } from '../api/index.ts';
import { EmptyState, PageTitle, formatDate } from '../components/ui.tsx';
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
            <section key={classroom} aria-labelledby={`c-${list[0]!.classroomId}`}>
              <h2 id={`c-${list[0]!.classroomId}`}>{classroom}</h2>
              <ul className={styles.list}>
                {list.map((c) => (
                  <li key={c.id} className="card">
                    {c.headword && (
                      <p className="small">
                        Sobre la palabra{' '}
                        <Link to={`/mi-diccionario?q=${encodeURIComponent(c.headword)}`} lang="es">
                          <strong>{c.headword}</strong>
                        </Link>
                      </p>
                    )}
                    <p className="prose">{c.body}</p>
                    <p className="small muted">
                      {c.author.displayName} ·{' '}
                      <time dateTime={c.createdAt}>{formatDate(c.createdAt, true)}</time>
                    </p>
                  </li>
                ))}
              </ul>
            </section>
          ))}
    </div>
  );
}

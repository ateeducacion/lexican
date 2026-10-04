import { CommentInput, type DictionaryView, type MemberView } from '@lexican/core';
import { useState, type FormEvent } from 'react';
import { Link, useLoaderData, useRevalidator, type LoaderFunctionArgs } from 'react-router';
import { getApi } from '../../api/index.ts';
import {
  Dialog,
  EmptyState,
  ErrorMessage,
  Field,
  useAction,
  useNotify,
} from '../../components/ui.tsx';
import { useSession } from '../../session.ts';
import { JoinCode, PageHeader } from './shared.tsx';

interface Data {
  dictionary: DictionaryView;
  members: MemberView[];
}

export async function loader({ params }: LoaderFunctionArgs): Promise<Data> {
  const api = await getApi();
  const [dictionary, members] = await Promise.all([
    api.getDictionary({ dictionaryId: params.classroomId! }),
    api.listMembers({ classroomId: params.classroomId! }),
  ]);
  return { dictionary, members };
}

export function Component() {
  const { dictionary: d, members } = useLoaderData<Data>();
  const { user } = useSession();
  const { revalidate } = useRevalidator();
  const notify = useNotify();
  const [commentFor, setCommentFor] = useState<MemberView | null>(null);
  const teacher = d.myRole === 'teacher';

  const update = useAction(
    async (
      m: MemberView,
      change: { role?: MemberView['role']; active?: boolean },
      message: string,
    ) => {
      await (await getApi()).updateMember({ classroomId: d.id, userId: m.user.id, ...change });
      await revalidate();
      notify(message);
    },
  );

  const table = (role: MemberView['role'], caption: string) => {
    const rows = members.filter((m) => m.role === role);
    if (rows.length === 0) return <EmptyState title={`Sin ${caption.toLowerCase()}`} />;
    return (
      <div className="table-wrap">
        <table>
          <caption className="visually-hidden">{caption}</caption>
          <thead>
            <tr>
              <th scope="col">Nombre</th>
              <th scope="col">Envíos</th>
              <th scope="col">Estado</th>
              {teacher && <th scope="col">Acciones</th>}
            </tr>
          </thead>
          <tbody>
            {rows.map((m) => {
              const name = m.user.displayName;
              const self = m.user.id === user.id;
              return (
                <tr key={m.user.id}>
                  <th scope="row">
                    {name}
                    {m.isOwner && <span className="small muted"> · creó el diccionario</span>}
                    {self && <span className="small muted"> · tú</span>}
                  </th>
                  <td>{m.submissionCount}</td>
                  <td>
                    {m.active ? (
                      'Activo'
                    ) : (
                      <span className="badge badge-hidden">Deshabilitado</span>
                    )}
                  </td>
                  {teacher && (
                    <td>
                      <div className="row">
                        {m.role === 'student' ? (
                          <button
                            type="button"
                            className="btn btn-sm"
                            disabled={update.busy}
                            onClick={() =>
                              void update.run(
                                m,
                                { role: 'teacher' },
                                `${name} ya tiene permisos de docente.`,
                              )
                            }
                          >
                            Dar permisos de docente
                            <span className="visually-hidden"> a {name}</span>
                          </button>
                        ) : (
                          !m.isOwner && (
                            <button
                              type="button"
                              className="btn btn-sm"
                              disabled={update.busy}
                              onClick={() =>
                                void update.run(
                                  m,
                                  { role: 'student' },
                                  `${name} ya no tiene permisos de docente.`,
                                )
                              }
                            >
                              Quitar permisos de docente
                              <span className="visually-hidden"> a {name}</span>
                            </button>
                          )
                        )}
                        {!self && !m.isOwner && (
                          <button
                            type="button"
                            className={`btn btn-sm ${m.active ? 'btn-danger' : ''}`}
                            disabled={update.busy}
                            onClick={() =>
                              void update.run(
                                m,
                                { active: !m.active },
                                m.active ? `${name} deshabilitado.` : `${name} habilitado.`,
                              )
                            }
                          >
                            {m.active ? 'Deshabilitar' : 'Habilitar'}
                            <span className="visually-hidden"> a {name}</span>
                          </button>
                        )}
                        {m.role === 'student' && (
                          <button
                            type="button"
                            className="btn btn-sm btn-link"
                            onClick={() => setCommentFor(m)}
                          >
                            Comentario general<span className="visually-hidden"> para {name}</span>
                          </button>
                        )}
                      </div>
                    </td>
                  )}
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    );
  };

  return (
    <>
      <PageHeader
        back={<Link to={`/aulas/${d.id}`}>← {d.title}</Link>}
        eyebrow={d.title}
        title="Participantes"
        docTitle={`Participantes · ${d.title}`}
      >
        {d.classroom && (
          <p className="small" style={{ margin: 0 }}>
            Para invitar a alguien, comparte el código <JoinCode code={d.classroom.joinCode} />.
            <br />
            Se escribe en «Mis aulas › Unirse con un código».
          </p>
        )}
      </PageHeader>
      <ErrorMessage error={update.error} />

      <section aria-labelledby="teachers-title" className="stack">
        <h2 id="teachers-title">Profesorado</h2>
        {table('teacher', 'Profesorado')}
      </section>
      <section
        aria-labelledby="students-title"
        className="stack"
        style={{ marginTop: 'var(--space-6)' }}
      >
        <h2 id="students-title">Alumnado</h2>
        {table('student', 'Alumnado')}
      </section>

      <Dialog
        open={commentFor !== null}
        onClose={() => setCommentFor(null)}
        title={`Comentario general para ${commentFor?.user.displayName ?? ''}`}
      >
        {commentFor && (
          <CommentForm classroomId={d.id} member={commentFor} onDone={() => setCommentFor(null)} />
        )}
      </Dialog>
    </>
  );
}

function CommentForm({
  classroomId,
  member,
  onDone,
}: {
  classroomId: string;
  member: MemberView;
  onDone: () => void;
}) {
  const notify = useNotify();
  const [body, setBody] = useState('');
  const [fieldError, setFieldError] = useState<string>();
  const send = useAction(async () => {
    const parsed = CommentInput.safeParse({ classroomId, studentId: member.user.id, body });
    if (!parsed.success) {
      setFieldError(parsed.error.issues[0]?.message);
      return;
    }
    await (await getApi()).createComment(parsed.data);
    notify(`Comentario enviado a ${member.user.displayName}.`);
    onDone();
  });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    void send.run();
  };
  return (
    <form onSubmit={submit} noValidate>
      <Field
        label="Comentario"
        hint="Lo verá en su página de comentarios si la visibilidad del diccionario lo permite."
        error={fieldError}
      >
        {(p) => (
          <textarea
            {...p}
            maxLength={2000}
            rows={5}
            value={body}
            onChange={(e) => setBody(e.target.value)}
          />
        )}
      </Field>
      <ErrorMessage error={send.error} />
      <div className="row">
        <button type="submit" className="btn btn-primary" disabled={send.busy}>
          Enviar comentario
        </button>
        <button type="button" className="btn" onClick={onDone}>
          Cancelar
        </button>
      </div>
    </form>
  );
}

import { type DictionaryView, formatSchoolYear } from '@lexican/core';
import { Link, type LoaderFunctionArgs, useLoaderData, useSearchParams } from 'react-router';
import { getApi } from '../../api/index.ts';
import { EntryList } from '../../components/EntryList.tsx';
import { EmptyState } from '../../components/ui.tsx';
import { useVocab } from '../../components/vocab.ts';
import styles from './Classrooms.module.css';
import { ClassroomState, PageHeader } from './shared.tsx';

export async function loader({ params }: LoaderFunctionArgs) {
  return (await getApi()).getDictionary({ dictionaryId: params.classroomId! });
}

/** Clean public viewer of a classroom dictionary (§50); teachers get a small toolbar on top. */
export function Component() {
  const d = useLoaderData<DictionaryView>();
  const v = useVocab();
  const [params] = useSearchParams();
  const c = d.classroom;
  const teacher = d.myRole === 'teacher';
  const studentBlocked = d.myRole === 'student' && c && !c.visibleToStudents;
  const meta = [c ? v.label(c.studyLevelId) : '', c ? v.label(c.subjectId) : '']
    .filter(Boolean)
    .join(' · ');

  return (
    <>
      <PageHeader
        back={<Link to="/aulas">← Mis aulas</Link>}
        eyebrow={[meta, c ? `Curso ${formatSchoolYear(c.schoolYear)}` : '']
          .filter(Boolean)
          .join(' · ')}
        title={<span className={styles.serifTitle}>{d.title}</span>}
        docTitle={d.title}
      >
        {teacher && (
          <nav aria-label="Herramientas del profesorado" className={`no-print ${styles.toolbar}`}>
            <Link
              className={`btn ${d.pendingCount > 0 ? 'btn-primary' : ''}`}
              to={`/aulas/${d.id}/envios`}
            >
              Revisar envíos{d.pendingCount > 0 && ` (${d.pendingCount})`}
            </Link>
            <Link className="btn" to={`/aulas/${d.id}/participantes`}>
              Participantes
            </Link>
            <Link className="btn" to={`/aulas/${d.id}/editar`}>
              Editar
            </Link>
            <Link className="btn" to={`/diccionarios/${d.id}/imprimir`}>
              Imprimir / exportar
            </Link>
          </nav>
        )}
      </PageHeader>
      {d.description && <p className="prose">{d.description}</p>}
      <ClassroomState dictionary={d} />
      {!teacher && !studentBlocked && (
        <p className="no-print">
          <Link to={`/diccionarios/${d.id}/imprimir`}>Imprimir / exportar</Link>
        </p>
      )}

      {c?.guidelines && (
        <details
          open={params.has('pautas')}
          className={`card ${styles.guidelines}`}
          style={{ marginBottom: 'var(--space-5)' }}
        >
          <summary>Pautas del diccionario</summary>
          <p className="prose">{c.guidelines}</p>
        </details>
      )}

      {studentBlocked ? (
        <EmptyState title="Este diccionario aún no está visible">
          <p>
            Tu profesor o profesora todavía no lo ha abierto al alumnado. Mientras tanto puedes
            seguir trabajando en tu diccionario personal.
          </p>
          <Link className="btn btn-primary" to="/mi-diccionario">
            Ir a mi diccionario
          </Link>
        </EmptyState>
      ) : (
        <section aria-label="Entradas">
          <EntryList
            dictionaryId={d.id}
            entryHref={(id) => `/aulas/${d.id}/entradas/${id}`}
            canEdit={teacher}
          />
        </section>
      )}
    </>
  );
}

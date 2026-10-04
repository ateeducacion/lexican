import { formatSchoolYear, lastCurrentSchoolYear, type DictionaryView, type WindowState } from '@lexican/core';
import { Link } from 'react-router';
import { formatDate } from '../../components/ui.tsx';
import styles from './Classrooms.module.css';

export const WINDOW_LABEL: Record<WindowState, string> = {
  open: 'Plazo de envíos abierto',
  disabled: 'Envíos deshabilitados',
  not_started: 'El plazo de envíos aún no ha empezado',
  ended: 'Plazo de envíos cerrado',
  expired: 'No vigente: solo consulta',
};

/** Classroom state as text + icon (never colour alone, §61). */
export function ClassroomState({ dictionary }: { dictionary: DictionaryView }) {
  const c = dictionary.classroom;
  if (!c) return null;
  const last = lastCurrentSchoolYear(c);
  const window = c.windowState;
  return (
    <ul className={styles.state} aria-label="Estado">
      <li>
        Curso {formatSchoolYear(c.schoolYear)}
        {c.groupLabel && ` · Grupo ${c.groupLabel}`}
      </li>
      <li className={c.current ? styles.ok : styles.off}>
        <span aria-hidden="true">{c.current ? '●' : '○'}</span>{' '}
        {c.current ? (last === null ? 'Vigente sin caducidad' : `Vigente hasta ${formatSchoolYear(last)}`) : 'No vigente'}
      </li>
      {c.current && (
        <li className={window === 'open' ? styles.ok : styles.off}>
          <span aria-hidden="true">{window === 'open' ? '✉' : '✕'}</span> {WINDOW_LABEL[window]}
          {window === 'open' && c.submissionsEndAt && ` hasta el ${formatDate(c.submissionsEndAt, true)}`}
          {window === 'not_started' && c.submissionsStartAt && `: empieza el ${formatDate(c.submissionsStartAt, true)}`}
        </li>
      )}
      {!c.visibleToStudents && (
        <li className={styles.off}>
          <span aria-hidden="true">◌</span> Oculto para el alumnado
        </li>
      )}
    </ul>
  );
}

/** Back link to the classroom viewer, used by all classroom sub-pages. */
export function BackToClassroom({ dictionary }: { dictionary: Pick<DictionaryView, 'id' | 'title'> }) {
  return (
    <p className="no-print small">
      <Link to={`/aulas/${dictionary.id}`}>← {dictionary.title}</Link>
    </p>
  );
}

/** Join code shown big enough to dictate in class. */
export function JoinCode({ code }: { code: string }) {
  return (
    <span className={styles.code}>
      <span className="visually-hidden">Código para unirse: </span>
      {code}
    </span>
  );
}

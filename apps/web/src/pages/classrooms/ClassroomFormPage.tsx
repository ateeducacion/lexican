import {
  ClassroomInput,
  type CommentVisibility,
  type DictionaryView,
  formatSchoolYear,
  MAX_VALIDITY_YEARS,
  SENSE_FIELDS,
  type SenseField,
  schoolYearOf,
} from '@lexican/core';
import { type FormEvent, useState } from 'react';
import {
  Link,
  type LoaderFunctionArgs,
  useLoaderData,
  useNavigate,
  useRevalidator,
} from 'react-router';
import { getApi } from '../../api/index.ts';
import { Dialog, ErrorMessage, Field, useAction, useNotify } from '../../components/ui.tsx';
import { useVocab } from '../../components/vocab.ts';
import { useSession } from '../../session.ts';
import styles from './Classrooms.module.css';
import { JoinCode, PageHeader } from './shared.tsx';

export async function loader({ params }: LoaderFunctionArgs) {
  return params.classroomId
    ? (await getApi()).getDictionary({ dictionaryId: params.classroomId })
    : null;
}

/** ISO → value of <input type="datetime-local"> in local time. */
const toLocal = (iso: string | null, dateOnly = false) => {
  if (!iso) return '';
  const d = new Date(iso);
  const local = new Date(d.getTime() - d.getTimezoneOffset() * 60_000).toISOString();
  return dateOnly ? local.slice(0, 10) : local.slice(0, 16);
};
const fromLocal = (s: string) => (s ? new Date(s) : null);

type Form = {
  title: string;
  description: string;
  studyLevelId: string;
  subjectId: string;
  groupLabel: string;
  validityYears: number;
  maxSenses: string;
  visibleFields: SenseField[];
  requiredFields: SenseField[];
  guidelines: string;
  visibleToStudents: boolean;
  submissionsEnabled: boolean;
  submissionsStartAt: string;
  submissionsEndAt: string;
  commentsVisibility: CommentVisibility;
  commentsVisibleBefore: string;
};

const initial = (d: DictionaryView | null): Form => {
  const c = d?.classroom;
  return {
    title: d?.title ?? '',
    description: d?.description ?? '',
    studyLevelId: c?.studyLevelId ?? '',
    subjectId: c?.subjectId ?? '',
    groupLabel: c?.groupLabel ?? '',
    validityYears: c?.validityYears ?? 1,
    maxSenses: c?.maxSenses ? String(c.maxSenses) : '',
    visibleFields: c ? [...c.visibleFields] : SENSE_FIELDS.map((f) => f.code),
    requiredFields: c ? [...c.requiredFields] : [],
    guidelines: c?.guidelines ?? '',
    visibleToStudents: c?.visibleToStudents ?? true,
    submissionsEnabled: c?.submissionsEnabled ?? true,
    submissionsStartAt: toLocal(c?.submissionsStartAt ?? null),
    submissionsEndAt: toLocal(c?.submissionsEndAt ?? null),
    commentsVisibility: c?.commentsVisibility ?? 'visible',
    commentsVisibleBefore: toLocal(c?.commentsVisibleBefore ?? null, true),
  };
};

export function Component() {
  const dictionary = useLoaderData<DictionaryView | null>();
  const { user } = useSession();
  const v = useVocab();
  const navigate = useNavigate();
  const notify = useNotify();
  const [form, setForm] = useState<Form>(() => initial(dictionary));
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const set = <K extends keyof Form>(k: K, value: Form[K]) =>
    setForm((f) => ({ ...f, [k]: value }));

  const editing = dictionary !== null;
  const schoolYear = dictionary?.classroom?.schoolYear ?? schoolYearOf(new Date());
  const timeless = dictionary?.classroom?.timeless ?? false;

  const save = useAction(async (f: Form) => {
    const parsed = ClassroomInput.safeParse({
      ...f,
      studyLevelId: f.studyLevelId || null,
      subjectId: f.subjectId || null,
      maxSenses: f.maxSenses === '' ? null : f.maxSenses,
      submissionsStartAt: f.submissionsEnabled ? fromLocal(f.submissionsStartAt) : null,
      submissionsEndAt: f.submissionsEnabled ? fromLocal(f.submissionsEndAt) : null,
      commentsVisibleBefore:
        f.commentsVisibility === 'before_date' ? fromLocal(f.commentsVisibleBefore) : null,
    });
    if (!parsed.success) {
      const next: Record<string, string[]> = {};
      for (const i of parsed.error.issues) (next[String(i.path[0])] ??= []).push(i.message);
      setErrors(next);
      return;
    }
    setErrors({});
    const api = await getApi();
    const saved = dictionary
      ? await api.updateClassroom({ classroomId: dictionary.id, classroom: parsed.data })
      : await api.createClassroom(parsed.data);
    notify(dictionary ? 'Cambios guardados.' : 'Diccionario de aula creado.');
    navigate(`/aulas/${saved.id}`);
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    void save.run(form);
  };

  const toggleVisible = (code: SenseField, on: boolean) =>
    setForm((f) => ({
      ...f,
      visibleFields: on ? [...f.visibleFields, code] : f.visibleFields.filter((x) => x !== code),
      requiredFields: on ? f.requiredFields : f.requiredFields.filter((x) => x !== code),
    }));
  const toggleRequired = (code: SenseField, on: boolean) =>
    setForm((f) => ({
      ...f,
      requiredFields: on ? [...f.requiredFields, code] : f.requiredFields.filter((x) => x !== code),
      visibleFields:
        on && !f.visibleFields.includes(code) ? [...f.visibleFields, code] : f.visibleFields,
    }));

  const title = editing ? 'Editar diccionario de aula' : 'Nuevo diccionario de aula';
  return (
    <>
      <PageHeader
        back={
          dictionary ? (
            <Link to={`/aulas/${dictionary.id}`}>← {dictionary.title}</Link>
          ) : (
            <Link to="/aulas">← Mis aulas</Link>
          )
        }
        eyebrow={dictionary?.title ?? 'Diccionario de aula'}
        title={title}
      />
      {dictionary?.classroom && !dictionary.classroom.current && (
        <div className="alert alert-warning">
          Este diccionario ya no está vigente: solo se puede consultar y no se pueden guardar
          cambios.
        </div>
      )}

      {dictionary?.classroom && <JoinCodeCard dictionary={dictionary} />}

      <form onSubmit={submit} noValidate className="card" aria-label={title}>
        <h2>Datos generales</h2>
        <Field label="Nombre del diccionario" error={errors.title}>
          {(p) => (
            <input
              {...p}
              type="text"
              required
              maxLength={150}
              value={form.title}
              onChange={(e) => set('title', e.target.value)}
            />
          )}
        </Field>
        <Field
          label="Descripción"
          hint="Opcional. Aparece bajo el título."
          error={errors.description}
        >
          {(p) => (
            <textarea
              {...p}
              maxLength={255}
              value={form.description}
              onChange={(e) => set('description', e.target.value)}
            />
          )}
        </Field>
        <div className="grid">
          <Field label="Nivel" error={errors.studyLevelId}>
            {(p) => (
              <select
                {...p}
                value={form.studyLevelId}
                onChange={(e) => set('studyLevelId', e.target.value)}
              >
                <option value="">Sin indicar</option>
                {v.list('study_level').map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.label}
                  </option>
                ))}
              </select>
            )}
          </Field>
          <Field label="Área o materia" error={errors.subjectId}>
            {(p) => (
              <select
                {...p}
                value={form.subjectId}
                onChange={(e) => set('subjectId', e.target.value)}
              >
                <option value="">Sin indicar</option>
                {v.list('subject').map((o) => (
                  <option key={o.id} value={o.id}>
                    {o.label}
                  </option>
                ))}
              </select>
            )}
          </Field>
          <Field label="Grupo" hint="Por ejemplo, B." error={errors.groupLabel}>
            {(p) => (
              <input
                {...p}
                type="text"
                maxLength={20}
                value={form.groupLabel}
                onChange={(e) => set('groupLabel', e.target.value)}
              />
            )}
          </Field>
        </div>

        <Field
          label="Vigencia"
          hint={
            timeless
              ? 'Administración ha marcado este diccionario como permanente: no caduca.'
              : `Creado en el curso ${formatSchoolYear(schoolYear)}. Con esta vigencia seguirá vigente hasta el curso ${formatSchoolYear(schoolYear + form.validityYears - 1)}; después solo se podrá consultar.`
          }
          error={errors.validityYears}
        >
          {(p) => (
            <select
              {...p}
              value={form.validityYears}
              onChange={(e) => set('validityYears', Number(e.target.value))}
            >
              {Array.from({ length: MAX_VALIDITY_YEARS }, (_, i) => i + 1).map((n) => (
                <option key={n} value={n}>
                  {n} {n === 1 ? 'curso' : 'cursos'}
                </option>
              ))}
            </select>
          )}
        </Field>

        <h2>Entradas</h2>
        <Field
          label="Máximo de acepciones por entrada"
          hint="Opcional. Déjalo vacío para no limitar."
          error={errors.maxSenses}
        >
          {(p) => (
            <input
              {...p}
              type="number"
              min={1}
              max={50}
              inputMode="numeric"
              value={form.maxSenses}
              onChange={(e) => set('maxSenses', e.target.value)}
              style={{ maxWidth: '8rem' }}
            />
          )}
        </Field>
        <div className="table-wrap" style={{ marginBottom: 'var(--space-4)' }}>
          <table className={styles.fieldsTable}>
            <caption className="visually-hidden">Campos de cada acepción</caption>
            <thead>
              <tr>
                <th scope="col">Campo de la acepción</th>
                <th scope="col">Visible</th>
                <th scope="col">Obligatorio</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row">Definición</th>
                <td>
                  <input
                    type="checkbox"
                    checked
                    disabled
                    aria-label="Definición visible (siempre)"
                  />
                </td>
                <td>
                  <input
                    type="checkbox"
                    checked
                    disabled
                    aria-label="Definición obligatoria (siempre)"
                  />
                </td>
              </tr>
              {SENSE_FIELDS.map((f) => (
                <tr key={f.code}>
                  <th scope="row">{f.label}</th>
                  <td>
                    <input
                      type="checkbox"
                      aria-label={`${f.label} visible`}
                      checked={form.visibleFields.includes(f.code)}
                      onChange={(e) => toggleVisible(f.code, e.target.checked)}
                    />
                  </td>
                  <td>
                    <input
                      type="checkbox"
                      aria-label={`${f.label} obligatorio`}
                      checked={form.requiredFields.includes(f.code)}
                      onChange={(e) => toggleRequired(f.code, e.target.checked)}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <p className="small muted">
          Un campo obligatorio también es visible. Los campos no visibles no se piden al alumnado ni
          se muestran en el diccionario.
        </p>

        <Field
          label="Pautas"
          hint="Indicaciones para el alumnado: qué palabras buscar, cómo escribir las definiciones…"
          error={errors.guidelines}
        >
          {(p) => (
            <textarea
              {...p}
              rows={6}
              maxLength={5000}
              value={form.guidelines}
              onChange={(e) => set('guidelines', e.target.value)}
            />
          )}
        </Field>

        <h2>Alumnado</h2>
        <label className="check">
          <input
            type="checkbox"
            checked={form.visibleToStudents}
            onChange={(e) => set('visibleToStudents', e.target.checked)}
          />
          <span>Diccionario visible para el alumnado</span>
        </label>
        <label className="check" style={{ marginBottom: 'var(--space-3)' }}>
          <input
            type="checkbox"
            checked={form.submissionsEnabled}
            onChange={(e) => set('submissionsEnabled', e.target.checked)}
          />
          <span>Envíos habilitados</span>
        </label>
        {form.submissionsEnabled && (
          <div className="grid">
            <Field
              label="Inicio del plazo de envíos"
              hint="Opcional."
              error={errors.submissionsStartAt}
            >
              {(p) => (
                <input
                  {...p}
                  type="datetime-local"
                  value={form.submissionsStartAt}
                  onChange={(e) => set('submissionsStartAt', e.target.value)}
                />
              )}
            </Field>
            <Field label="Fin del plazo de envíos" hint="Opcional." error={errors.submissionsEndAt}>
              {(p) => (
                <input
                  {...p}
                  type="datetime-local"
                  value={form.submissionsEndAt}
                  onChange={(e) => set('submissionsEndAt', e.target.value)}
                />
              )}
            </Field>
          </div>
        )}

        <fieldset>
          <legend>Visibilidad de los comentarios al alumnado</legend>
          {(
            [
              ['hidden', 'No visibles'],
              ['visible', 'Visibles'],
              ['before_date', 'Visibles los escritos antes de una fecha'],
            ] as const
          ).map(([value, label]) => (
            <label key={value} className="check">
              <input
                type="radio"
                name="commentsVisibility"
                value={value}
                checked={form.commentsVisibility === value}
                onChange={() => set('commentsVisibility', value)}
              />
              <span>{label}</span>
            </label>
          ))}
          {form.commentsVisibility === 'before_date' && (
            <Field label="Fecha límite" error={errors.commentsVisibleBefore}>
              {(p) => (
                <input
                  {...p}
                  type="date"
                  value={form.commentsVisibleBefore}
                  onChange={(e) => set('commentsVisibleBefore', e.target.value)}
                  style={{ maxWidth: '12rem' }}
                />
              )}
            </Field>
          )}
        </fieldset>

        {Object.keys(errors).length > 0 && (
          <div className="alert alert-error" role="alert">
            Revisa los campos marcados.
          </div>
        )}
        <ErrorMessage error={save.error} />
        <div className="row">
          <button type="submit" className="btn btn-primary" disabled={save.busy}>
            {save.busy ? 'Guardando…' : editing ? 'Guardar cambios' : 'Crear diccionario'}
          </button>
          <button
            type="button"
            className="btn"
            onClick={() => navigate(dictionary ? `/aulas/${dictionary.id}` : '/aulas')}
          >
            Cancelar
          </button>
        </div>
      </form>

      {dictionary && dictionary.owner.id === user.id && <DeleteClassroom dictionary={dictionary} />}
    </>
  );
}

function JoinCodeCard({ dictionary }: { dictionary: DictionaryView }) {
  const { revalidate } = useRevalidator();
  const notify = useNotify();
  const regen = useAction(async () => {
    await (await getApi()).regenerateJoinCode({ classroomId: dictionary.id });
    await revalidate();
    notify('Se ha generado un código nuevo. El anterior ya no sirve para unirse.');
  });
  return (
    <section
      className="card"
      aria-labelledby="code-title"
      style={{ marginBottom: 'var(--space-4)' }}
    >
      <h2 id="code-title">Código para unirse</h2>
      <div className="spread">
        <JoinCode code={dictionary.classroom?.joinCode ?? ''} />
        <button
          type="button"
          className="btn btn-sm"
          onClick={() => void regen.run()}
          disabled={regen.busy}
        >
          Generar otro código
        </button>
      </div>
      <p className="small muted" style={{ marginTop: 'var(--space-2)' }}>
        El alumnado ya unido no se ve afectado al cambiar el código.
      </p>
      <ErrorMessage error={regen.error} />
    </section>
  );
}

function DeleteClassroom({ dictionary }: { dictionary: DictionaryView }) {
  const [open, setOpen] = useState(false);
  const navigate = useNavigate();
  const notify = useNotify();
  const del = useAction(async () => {
    await (await getApi()).deleteClassroom({ classroomId: dictionary.id });
    notify('Diccionario de aula borrado.');
    navigate('/aulas', { replace: true });
  });
  return (
    <section
      className="card"
      aria-labelledby="danger-title"
      style={{ marginTop: 'var(--space-5)' }}
    >
      <h2 id="danger-title">Borrar el diccionario</h2>
      <p className="small">Solo quien creó el diccionario puede borrarlo.</p>
      <button type="button" className="btn btn-danger" onClick={() => setOpen(true)}>
        Borrar diccionario
      </button>
      <Dialog open={open} onClose={() => setOpen(false)} title={`¿Borrar «${dictionary.title}»?`}>
        <p>
          Desaparecerán el diccionario, sus {dictionary.entryCount} entradas publicadas, los envíos
          pendientes y los comentarios. El alumnado dejará de verlo. Los diccionarios personales del
          alumnado no se tocan.
        </p>
        <ErrorMessage error={del.error} />
        <div className="row">
          <button
            type="button"
            className="btn btn-danger"
            onClick={() => void del.run()}
            disabled={del.busy}
          >
            Borrar definitivamente
          </button>
          <button type="button" className="btn" onClick={() => setOpen(false)}>
            Cancelar
          </button>
        </div>
      </Dialog>
    </section>
  );
}

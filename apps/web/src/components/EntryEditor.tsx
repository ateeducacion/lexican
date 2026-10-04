import {
  EntryInput,
  MEDIA_ACCEPT,
  SENSE_FIELDS,
  type DictionaryView,
  type EntryView,
  type MediaKind,
  type MediaView,
  type SenseField,
} from '@lexican/core';
import { useEffect, useId, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { Link } from 'react-router';
import { getApi, toApiError, type ApiError } from '../api/index.ts';
import { EntryContent } from './EntryContent.tsx';
import styles from './EntryEditor.module.css';
import { Dialog, ErrorMessage, Field, useNotify } from './ui.tsx';
import { useVocab } from './vocab.ts';

// Full entry editor (personal dictionary; reused by teachers to edit published classroom entries).
// Three columns on desktop: outline · headword + reorderable sense cards · live preview + classroom checklist.
// Validates with the shared Zod schema (EntryInput) inline and accessibly; shows server errors (409 conflict,
// duplicate headword) without losing input.
export interface EntryEditorProps {
  /** Existing entry to edit, or undefined to create. */
  entry?: EntryView;
  /** Classroom being edited (teacher), for policy hints: maxSenses, visibleFields, requiredFields. */
  classroom?: DictionaryView;
  submitLabel?: string;
  /** Page heading shown in the sticky action bar (desktop). */
  heading?: ReactNode;
  /** Personal editor: classrooms the entry could be sent to, for the "ready to send" checklist. */
  sendTargets?: DictionaryView[];
  /** Persist; resolve with the saved entry. Errors are ApiError and must be shown in the form. */
  onSave: (input: EntryInput) => Promise<EntryView>;
  onCancel: () => void;
}

interface SenseDraft {
  key: string;
  id?: string;
  definition: string;
  extraInfo: string;
  example: string;
  partOfSpeechId: string;
  genderId: string;
  numberId: string;
  languageId: string;
  foreignForm: string;
  hidden: boolean;
  topicIds: string[];
  media: MediaView[];
}
type PillKey = 'partOfSpeechId' | 'genderId' | 'numberId';
type Errors = Record<string, string[]>;

const newKey = () => crypto.randomUUID();
const blankSense = (): SenseDraft => ({
  key: newKey(),
  definition: '',
  extraInfo: '',
  example: '',
  partOfSpeechId: '',
  genderId: '',
  numberId: '',
  languageId: '',
  foreignForm: '',
  hidden: false,
  topicIds: [],
  media: [],
});
const fromView = (s: EntryView['senses'][number]): SenseDraft => ({
  key: s.id,
  id: s.id,
  definition: s.definition,
  extraInfo: s.extraInfo,
  example: s.example,
  partOfSpeechId: s.partOfSpeechId ?? '',
  genderId: s.genderId ?? '',
  numberId: s.numberId ?? '',
  languageId: s.languageId ?? '',
  foreignForm: s.foreignForm,
  hidden: s.hidden,
  topicIds: s.topicIds,
  media: s.media,
});
/** Comparable form of the draft, for the "unsaved changes" status. */
const snapshot = (headword: string, senses: SenseDraft[]) =>
  JSON.stringify([
    headword,
    senses.map(({ key: _k, ...s }) => ({ ...s, media: s.media.map((m) => m.id) })),
  ]);

const MEDIA_LABEL: Record<MediaKind, string> = { image: 'Imagen', audio: 'Audio', video: 'Vídeo' };
const FIELD_LABEL = Object.fromEntries(SENSE_FIELDS.map((f) => [f.code, f.label])) as Record<
  SenseField,
  string
>;

/** Classroom required fields, checked client-side so the student sees them before sending (§5.7). */
const REQUIRED_CHECK: Record<SenseField, [string, (s: SenseDraft) => boolean]> = {
  part_of_speech: ['partOfSpeechId', (s) => !!s.partOfSpeechId],
  gender: ['genderId', (s) => !!s.genderId],
  number: ['numberId', (s) => !!s.numberId],
  topics: ['topicIds', (s) => s.topicIds.length > 0],
  extra_info: ['extraInfo', (s) => !!s.extraInfo.trim()],
  example: ['example', (s) => !!s.example.trim()],
  language: ['foreignForm', (s) => !!s.languageId && !!s.foreignForm.trim()],
  image: ['media-image', (s) => s.media.some((m) => m.kind === 'image')],
  audio: ['media-audio', (s) => s.media.some((m) => m.kind === 'audio')],
  video: ['media-video', (s) => s.media.some((m) => m.kind === 'video')],
};

/** Filled fields of a sense, in form order, for the outline checklist. */
const filledFields = (s: SenseDraft): string[] =>
  [
    s.definition.trim() && 'Definición',
    s.partOfSpeechId && 'Categoría gramatical',
    s.genderId && 'Género',
    s.numberId && 'Número',
    s.languageId && s.foreignForm.trim() && 'Otra lengua',
    s.extraInfo.trim() && 'Más datos',
    s.example.trim() && 'Ejemplo de uso',
    s.topicIds.length > 0 && 'Temáticas',
    ...s.media.map((m) => MEDIA_LABEL[m.kind]),
  ].filter((x): x is string => !!x);

const truncate = (text: string, n: number) => (text.length > n ? `${text.slice(0, n - 1)}…` : text);

export function EntryEditor({
  entry,
  classroom,
  submitLabel = 'Guardar',
  heading,
  sendTargets = [],
  onSave,
  onCancel,
}: EntryEditorProps) {
  const v = useVocab();
  const notify = useNotify();
  const form = useRef<HTMLFormElement>(null);
  const [headword, setHeadword] = useState(entry?.headword ?? '');
  const [senses, setSenses] = useState<SenseDraft[]>(() =>
    entry?.senses.length ? entry.senses.map(fromView) : [blankSense()],
  );
  const [initial] = useState(() => snapshot(headword, senses));
  const [active, setActive] = useState(senses[0]!.key);
  const [errors, setErrors] = useState<Errors>({});
  const [serverError, setServerError] = useState<ApiError | null>(null);
  const [saving, setSaving] = useState(false);
  const [focusErrors, setFocusErrors] = useState(0);
  const pendingFocus = useRef<string | null>(null);
  const [toDelete, setToDelete] = useState<string | null>(null);
  const [uploading, setUploading] = useState<Record<string, boolean>>({});

  const settings = classroom?.classroom ?? null;
  const show = (f: SenseField) => !settings || settings.visibleFields.includes(f);
  const required = (f: SenseField) => !!settings?.requiredFields.includes(f);
  const max = settings?.maxSenses ?? null;
  const label = (text: string, f?: SenseField): ReactNode =>
    f && required(f) ? (
      <>
        {text} <span className={styles.req}>(obligatorio)</span>
      </>
    ) : (
      text
    );
  const dirty = snapshot(headword, senses) !== initial;

  useEffect(() => {
    if (focusErrors)
      form.current?.querySelector<HTMLElement>('[aria-invalid="true"], [data-invalid]')?.focus();
  }, [focusErrors]);
  // Focus requested by an action (moved/added sense, outline), applied once the DOM reflects it.
  useEffect(() => {
    if (!pendingFocus.current) return;
    form.current?.querySelector<HTMLElement>(`[data-focus="${pendingFocus.current}"]`)?.focus();
    pendingFocus.current = null;
  });

  const err = (key: string) => errors[key];
  const patch = (key: string, change: Partial<SenseDraft>) =>
    setSenses((list) => list.map((s) => (s.key === key ? { ...s, ...change } : s)));

  const focusSense = (key: string) => {
    setActive(key);
    form.current?.querySelector<HTMLElement>(`[data-focus="${key}-definition"]`)?.focus();
  };

  const move = (index: number, delta: -1 | 1) => {
    const to = index + delta;
    const moved = senses[index];
    if (!moved || to < 0 || to >= senses.length) return;
    const next = [...senses];
    next.splice(index, 1);
    next.splice(to, 0, moved);
    setSenses(next);
    // Keep focus on the same control; if it reached an edge (disabled), use the other one.
    const edge = (delta === -1 && to === 0) || (delta === 1 && to === senses.length - 1);
    pendingFocus.current = `${moved.key}-${(delta === -1) !== edge ? 'up' : 'down'}`;
    notify(`Acepción movida a la posición ${to + 1}`, 'info');
  };

  const duplicate = (index: number) => {
    const src = senses[index];
    if (!src || (max && senses.length >= max)) return;
    const copy: SenseDraft = { ...src, key: newKey(), id: undefined, hidden: false };
    setSenses([...senses.slice(0, index + 1), copy, ...senses.slice(index + 1)]);
    setActive(copy.key);
    pendingFocus.current = `${copy.key}-definition`;
  };

  const add = () => {
    const s = blankSense();
    setSenses([...senses, s]);
    setActive(s.key);
    pendingFocus.current = `${s.key}-definition`;
  };

  const remove = (key: string) => {
    const rest = senses.filter((s) => s.key !== key);
    setSenses(rest);
    if (active === key) setActive(rest[0]!.key);
    setToDelete(null);
    notify('Acepción quitada. Se borrará al guardar.', 'info');
  };

  async function upload(sense: SenseDraft, kind: MediaKind, file: File | undefined) {
    if (!file) return;
    const k = `senses.${senses.indexOf(sense)}.media-${kind}`;
    setUploading((u) => ({ ...u, [`${sense.key}-${kind}`]: true }));
    try {
      const m = await (await getApi()).uploadMedia(file, file.name, kind);
      if (m.kind !== kind) throw new Error(`El archivo no es ${MEDIA_LABEL[kind].toLowerCase()}`);
      setSenses((list) =>
        list.map((s) =>
          s.key === sense.key ? { ...s, media: [...s.media.filter((x) => x.kind !== kind), m] } : s,
        ),
      );
      setErrors(({ [k]: _drop, ...rest }) => rest);
    } catch (e) {
      const message = e instanceof Error && !('code' in e) ? e.message : toApiError(e).message;
      setErrors((x) => ({ ...x, [k]: [message] }));
    } finally {
      setUploading((u) => ({ ...u, [`${sense.key}-${kind}`]: false }));
    }
  }

  function validate(): EntryInput | null {
    const raw = {
      headword,
      hidden: entry?.hidden ?? false,
      senses: senses.map((s) => ({
        ...(s.id ? { id: s.id } : {}),
        definition: s.definition,
        extraInfo: s.extraInfo,
        example: s.example,
        partOfSpeechId: s.partOfSpeechId || null,
        genderId: s.genderId || null,
        numberId: s.numberId || null,
        languageId: s.languageId || null,
        foreignForm: s.foreignForm,
        hidden: s.hidden,
        topicIds: s.topicIds,
        mediaIds: s.media.map((m) => m.id),
      })),
    };
    const parsed = EntryInput.safeParse(raw);
    const found: Errors = {};
    const addErr = (key: string, msg: string) => (found[key] ??= []).push(msg);
    if (!parsed.success)
      for (const i of parsed.error.issues) addErr(i.path.join('.') || 'form', i.message);
    if (settings) {
      senses.forEach((s, i) => {
        for (const f of settings.requiredFields) {
          const [key, ok] = REQUIRED_CHECK[f];
          if (show(f) && !ok(s))
            addErr(`senses.${i}.${key}`, 'Este campo es obligatorio en este diccionario de aula');
        }
      });
      if (max && senses.length > max)
        addErr('form', `Este diccionario admite como máximo ${max} acepciones por entrada`);
    }
    setErrors(found);
    if (Object.keys(found).length || !parsed.success) {
      setFocusErrors((n) => n + 1);
      return null;
    }
    return parsed.data;
  }

  async function submit(e: FormEvent) {
    e.preventDefault();
    setServerError(null);
    const input = validate();
    if (!input) return;
    setSaving(true);
    try {
      await onSave(input);
    } catch (ex) {
      const apiErr = toApiError(ex);
      setServerError(apiErr);
      if (Object.keys(apiErr.details).length) {
        setErrors(apiErr.details);
        setFocusErrors((n) => n + 1);
      }
    } finally {
      setSaving(false);
    }
  }

  const errorCount = Object.keys(errors).length;
  const pillGroups: [PillKey, string, 'part_of_speech' | 'gender' | 'number'][] = [
    ['partOfSpeechId', 'Categoría gramatical', 'part_of_speech'],
    ['genderId', 'Género', 'gender'],
    ['numberId', 'Número', 'number'],
  ];
  const languages = v.list('language');
  const topics = v.list('topic');
  const deleting = senses.find((s) => s.key === toDelete);
  const status = saving ? 'Guardando…' : dirty ? 'Cambios sin guardar' : 'Sin cambios';
  const target = classroom ?? null;

  return (
    <form ref={form} onSubmit={submit} noValidate className={styles.form}>
      <div className={styles.top}>
        {heading && <div className={styles.heading}>{heading}</div>}
        <div className={styles.actions}>
          <span className={styles.status} aria-live="polite" data-dirty={dirty || undefined}>
            <span className={styles.dot} aria-hidden="true" />
            {status}
          </span>
          <button type="button" className="btn" onClick={onCancel} disabled={saving}>
            Cancelar
          </button>
          <button type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? 'Guardando…' : submitLabel}
          </button>
        </div>
      </div>

      <nav aria-label="Estructura de la entrada" className={styles.outline}>
        <h2 className="eyebrow">Estructura</h2>
        <ul>
          <li>
            <button
              type="button"
              className={styles.outlineItem}
              onClick={() =>
                form.current?.querySelector<HTMLElement>('[data-focus="headword"]')?.focus()
              }
            >
              <span className={styles.outlineHeadword} lang="es">
                {headword.trim() || 'Sin palabra'}
              </span>
              <span className={styles.outlineMeta}>entrada</span>
            </button>
          </li>
          {senses.map((s, i) => {
            const current = s.key === active;
            const filled = filledFields(s);
            return (
              <li key={s.key}>
                <button
                  type="button"
                  className={styles.outlineItem}
                  aria-current={current || undefined}
                  onClick={() => focusSense(s.key)}
                >
                  <span className={styles.num} aria-hidden="true">
                    {i + 1}
                  </span>
                  <span className={styles.outlineText}>
                    <span className="visually-hidden">Acepción {i + 1}: </span>
                    {s.definition.trim()
                      ? truncate(s.definition.trim(), 40)
                      : `Acepción ${i + 1} (vacía)`}
                  </span>
                </button>
                {current && filled.length > 0 && (
                  <ul
                    className={styles.filled}
                    aria-label={`Campos rellenos de la acepción ${i + 1}`}
                  >
                    {filled.map((f, n) => (
                      <li key={`${f}-${n}`}>
                        <span aria-hidden="true">✓</span> {f}
                      </li>
                    ))}
                  </ul>
                )}
              </li>
            );
          })}
        </ul>
        <button
          type="button"
          className={`${styles.addSense} ${styles.addSenseOutline}`}
          onClick={add}
          disabled={!!max && senses.length >= max}
        >
          + Añadir acepción
        </button>
      </nav>

      <div className={styles.main}>
        {errorCount > 0 && (
          <div className="alert alert-error" role="alert">
            Revisa {errorCount === 1 ? 'el campo marcado' : `los ${errorCount} campos marcados`}.
            {errors.form && <> {errors.form.join(' ')}</>}
          </div>
        )}

        <Field
          label={
            <>
              Palabra <span className={styles.req}>(obligatorio)</span>
            </>
          }
          error={err('headword')}
        >
          {(p) => (
            <input
              {...p}
              type="text"
              data-focus="headword"
              className={styles.headword}
              maxLength={150}
              autoComplete="off"
              lang="es"
              value={headword}
              onChange={(e) => setHeadword(e.target.value)}
            />
          )}
        </Field>

        <div className="spread">
          <h2 className={styles.sectionTitle}>Acepciones</h2>
          <span className="small muted" aria-live="polite">
            {max
              ? `${senses.length} de ${max} como máximo`
              : `${senses.length} ${senses.length === 1 ? 'acepción' : 'acepciones'}`}
          </span>
        </div>
        {err('senses') && <p className="error">{err('senses')?.join(' ')}</p>}

        <ol className={styles.senses}>
          {senses.map((s, i) => {
            const base = `senses.${i}`;
            const n = i + 1;
            return (
              <li
                key={s.key}
                className={styles.sense}
                data-active={s.key === active || undefined}
                onFocusCapture={() => setActive(s.key)}
              >
                <div className={styles.senseHead}>
                  <span className={styles.numLg} aria-hidden="true">
                    {n}
                  </span>
                  <h3>
                    Acepción {n}
                    {s.hidden && <span className="badge badge-hidden">Oculta</span>}
                  </h3>
                  <div className={styles.senseTools}>
                    <button
                      type="button"
                      data-focus={`${s.key}-up`}
                      className={styles.iconBtn}
                      aria-label={`Subir acepción ${n}`}
                      disabled={i === 0}
                      onClick={() => move(i, -1)}
                    >
                      ↑
                    </button>
                    <button
                      type="button"
                      data-focus={`${s.key}-down`}
                      className={styles.iconBtn}
                      aria-label={`Bajar acepción ${n}`}
                      disabled={i === senses.length - 1}
                      onClick={() => move(i, 1)}
                    >
                      ↓
                    </button>
                    <button
                      type="button"
                      className={`btn btn-sm ${styles.tool}`}
                      disabled={!!max && senses.length >= max}
                      onClick={() => duplicate(i)}
                    >
                      Duplicar<span className="visually-hidden"> acepción {n}</span>
                    </button>
                    <button
                      type="button"
                      className={`btn btn-sm btn-danger ${styles.tool}`}
                      disabled={senses.length === 1}
                      onClick={() => setToDelete(s.key)}
                    >
                      Borrar<span className="visually-hidden"> acepción {n}</span>
                    </button>
                  </div>
                </div>

                <Field
                  label={
                    <>
                      Definición <span className={styles.req}>(obligatorio)</span>
                    </>
                  }
                  error={err(`${base}.definition`)}
                >
                  {(p) => (
                    <textarea
                      {...p}
                      data-focus={`${s.key}-definition`}
                      className={styles.definition}
                      maxLength={1000}
                      value={s.definition}
                      onChange={(e) => patch(s.key, { definition: e.target.value })}
                    />
                  )}
                </Field>

                <div className={styles.pillRow}>
                  {pillGroups
                    .filter(([, , f]) => show(f))
                    .map(([key, text, f]) => (
                      <PillGroup
                        key={key}
                        name={`${s.key}-${key}`}
                        legend={label(text, f)}
                        wide={f === 'part_of_speech'}
                        options={v.list(f)}
                        value={s[key]}
                        error={err(`${base}.${key}`)}
                        onChange={(value) => patch(s.key, { [key]: value } as Partial<SenseDraft>)}
                      />
                    ))}
                </div>

                {show('language') && (
                  <div className={styles.cols}>
                    <Field
                      label={label('Otra lengua', 'language')}
                      error={err(`${base}.languageId`)}
                    >
                      {(p) => (
                        <select
                          {...p}
                          value={s.languageId}
                          onChange={(e) => patch(s.key, { languageId: e.target.value })}
                        >
                          <option value="">Ninguna</option>
                          <optgroup label="Más usadas">
                            {languages
                              .filter((o) => o.featured)
                              .map((o) => (
                                <option key={o.id} value={o.id}>
                                  {o.label}
                                </option>
                              ))}
                          </optgroup>
                          <optgroup label="Todas">
                            {languages
                              .filter((o) => !o.featured)
                              .map((o) => (
                                <option key={o.id} value={o.id}>
                                  {o.label}
                                </option>
                              ))}
                          </optgroup>
                        </select>
                      )}
                    </Field>
                    <Field label="Palabra en esa lengua" error={err(`${base}.foreignForm`)}>
                      {(p) => (
                        <input
                          {...p}
                          type="text"
                          maxLength={150}
                          lang="und"
                          value={s.foreignForm}
                          onChange={(e) => patch(s.key, { foreignForm: e.target.value })}
                        />
                      )}
                    </Field>
                  </div>
                )}

                {show('extra_info') && (
                  <Field label={label('Más datos', 'extra_info')} error={err(`${base}.extraInfo`)}>
                    {(p) => (
                      <input
                        {...p}
                        type="text"
                        maxLength={255}
                        value={s.extraInfo}
                        onChange={(e) => patch(s.key, { extraInfo: e.target.value })}
                      />
                    )}
                  </Field>
                )}
                {show('example') && (
                  <Field label={label('Ejemplo de uso', 'example')} error={err(`${base}.example`)}>
                    {(p) => (
                      <input
                        {...p}
                        type="text"
                        className={styles.example}
                        maxLength={255}
                        value={s.example}
                        onChange={(e) => patch(s.key, { example: e.target.value })}
                      />
                    )}
                  </Field>
                )}

                {show('topics') && (
                  <TopicChips
                    legend={label('Temáticas', 'topics')}
                    options={topics}
                    selected={s.topicIds}
                    error={err(`${base}.topicIds`)}
                    onChange={(topicIds) => patch(s.key, { topicIds })}
                  />
                )}
                {(['image', 'audio', 'video'] as const).some(show) && (
                  <fieldset className={styles.group}>
                    <legend>Medios</legend>
                    <div className={styles.mediaRow}>
                      {(['image', 'audio', 'video'] as const)
                        .filter((kind) => show(kind))
                        .map((kind) => (
                          <MediaSlot
                            key={kind}
                            kind={kind}
                            label={label(MEDIA_LABEL[kind], kind)}
                            current={s.media.find((m) => m.kind === kind)}
                            busy={!!uploading[`${s.key}-${kind}`]}
                            error={err(`${base}.media-${kind}`)}
                            onFile={(file) => void upload(s, kind, file)}
                            onRemove={() =>
                              patch(s.key, { media: s.media.filter((m) => m.kind !== kind) })
                            }
                          />
                        ))}
                    </div>
                  </fieldset>
                )}
              </li>
            );
          })}
        </ol>

        <button
          type="button"
          className={`${styles.addSense} ${styles.addSenseMain}`}
          onClick={add}
          disabled={!!max && senses.length >= max}
        >
          + Añadir acepción
        </button>

        {serverError && (
          <div className={styles.serverError}>
            <ErrorMessage error={serverError} />
            {serverError.code === 'conflict' && entry && !serverError.details.headword && (
              <p className="small">
                Tus cambios siguen en el formulario. Puedes copiarlos y{' '}
                <button
                  type="button"
                  className="btn btn-sm"
                  onClick={() => window.location.reload()}
                >
                  Recargar
                </button>{' '}
                para ver la versión actual.
              </p>
            )}
          </div>
        )}
      </div>

      {/* Focusable so keyboard users can scroll it when it overflows (sticky column). */}
      <aside aria-label="Vista previa y comprobaciones" className={styles.side} tabIndex={0}>
        <h2 className="eyebrow">Así se verá</h2>
        <div className={styles.preview}>
          <EntryContent
            headword={headword.trim() || 'Palabra'}
            headingLevel={3}
            visibleFields={settings?.visibleFields}
            senses={senses.map((s, i) => ({
              ...s,
              id: s.key,
              position: i,
              partOfSpeechId: s.partOfSpeechId || null,
              genderId: s.genderId || null,
              numberId: s.numberId || null,
              languageId: s.languageId || null,
            }))}
          />
        </div>
        {target ? (
          <PolicyCheck classroom={target} senses={senses} teacher />
        ) : (
          <SendCheck targets={sendTargets} senses={senses} />
        )}
      </aside>

      <Dialog open={!!deleting} onClose={() => setToDelete(null)} title="¿Borrar esta acepción?">
        <p>
          Se quitará la acepción {deleting ? senses.indexOf(deleting) + 1 : ''}
          {deleting?.definition ? <> («{deleting.definition.slice(0, 80)}»)</> : null}. El cambio se
          aplica al guardar la entrada.
        </p>
        <div className="row">
          <button
            type="button"
            className="btn btn-danger"
            onClick={() => toDelete && remove(toDelete)}
          >
            Borrar acepción
          </button>
          <button type="button" className="btn" onClick={() => setToDelete(null)}>
            Cancelar
          </button>
        </div>
      </Dialog>
    </form>
  );
}

/** Single choice as native radios styled as pills (global .pills), with a "Sin indicar" option. */
function PillGroup({
  name,
  legend,
  wide,
  options,
  value,
  error,
  onChange,
}: {
  name: string;
  legend: ReactNode;
  wide: boolean;
  options: { id: string; label: string }[];
  value: string;
  error: string[] | undefined;
  onChange: (value: string) => void;
}) {
  const errorId = useId();
  return (
    <fieldset
      className={`${styles.group} ${wide ? styles.wide : ''}`}
      aria-describedby={error ? errorId : undefined}
    >
      <legend>{legend}</legend>
      <div className="pills">
        {[{ id: '', label: 'Sin indicar' }, ...options].map((o, i) => (
          <label key={o.id || 'none'}>
            <input
              type="radio"
              name={name}
              value={o.id}
              checked={value === o.id}
              onChange={() => onChange(o.id)}
              {...(error && i === 0 ? { 'data-invalid': true } : {})}
            />
            <span>{o.label}</span>
          </label>
        ))}
      </div>
      {error && (
        <span id={errorId} className={styles.error}>
          {error.join(' ')}
        </span>
      )}
    </fieldset>
  );
}

/** Topics as removable chips plus a native select to add one. */
function TopicChips({
  legend,
  options,
  selected,
  error,
  onChange,
}: {
  legend: ReactNode;
  options: { id: string; label: string }[];
  selected: string[];
  error: string[] | undefined;
  onChange: (ids: string[]) => void;
}) {
  const id = useId();
  const chosen = selected
    .map((t) => options.find((o) => o.id === t))
    .filter((o): o is { id: string; label: string } => !!o);
  const rest = options.filter((o) => !selected.includes(o.id));
  return (
    <fieldset className={styles.group} aria-describedby={error ? `${id}-error` : undefined}>
      <legend>{legend}</legend>
      <div className={styles.chipBox}>
        {chosen.length > 0 && (
          <ul className={styles.chips}>
            {chosen.map((o) => (
              <li key={o.id} className="chip">
                {o.label}
                <button
                  type="button"
                  className={styles.chipRemove}
                  aria-label={`Quitar ${o.label}`}
                  onClick={() => onChange(selected.filter((x) => x !== o.id))}
                >
                  ×
                </button>
              </li>
            ))}
          </ul>
        )}
        <label htmlFor={id} className="visually-hidden">
          Añadir temática
        </label>
        <select
          id={id}
          className={styles.chipAdd}
          value=""
          aria-invalid={error ? true : undefined}
          aria-describedby={error ? `${id}-error` : undefined}
          onChange={(e) => e.target.value && onChange([...selected, e.target.value])}
        >
          <option value="">Añadir…</option>
          {rest.map((o) => (
            <option key={o.id} value={o.id}>
              {o.label}
            </option>
          ))}
        </select>
      </div>
      {error && (
        <span id={`${id}-error`} className={styles.error}>
          {error.join(' ')}
        </span>
      )}
    </fieldset>
  );
}

/** One media slot: a dashed "+ Imagen" picker, or the file chip with Cambiar/Quitar. */
function MediaSlot({
  kind,
  label,
  current,
  busy,
  error,
  onFile,
  onRemove,
}: {
  kind: MediaKind;
  label: ReactNode;
  current: MediaView | undefined;
  busy: boolean;
  error: string[] | undefined;
  onFile: (file: File | undefined) => void;
  onRemove: () => void;
}) {
  const id = useId();
  const name = MEDIA_LABEL[kind].toLowerCase();
  return (
    <div className={styles.slot}>
      <input
        id={id}
        type="file"
        className={`visually-hidden ${styles.fileInput}`}
        accept={MEDIA_ACCEPT[kind]}
        disabled={busy}
        aria-invalid={error ? true : undefined}
        aria-describedby={error ? `${id}-error` : undefined}
        onChange={(e) => {
          onFile(e.target.files?.[0]);
          e.target.value = '';
        }}
      />
      {current ? (
        <div className={styles.fileChip}>
          <MediaIcon kind={kind} />
          <span className={styles.fileName} title={current.originalName}>
            {current.originalName || MEDIA_LABEL[kind]}
          </span>
          <label htmlFor={id} className={styles.fileAction}>
            Cambiar<span className="visually-hidden"> {name}</span>
          </label>
          <button type="button" className={styles.fileAction} onClick={onRemove}>
            Quitar<span className="visually-hidden"> {name}</span>
          </button>
        </div>
      ) : (
        <label htmlFor={id} className={styles.slotAdd} data-busy={busy || undefined}>
          {busy ? (
            'Subiendo…'
          ) : (
            <>
              <span aria-hidden="true">+</span> {label}
            </>
          )}
        </label>
      )}
      {error && (
        <span id={`${id}-error`} className={styles.error}>
          {error.join(' ')}
        </span>
      )}
    </div>
  );
}

function MediaIcon({ kind }: { kind: MediaKind }) {
  return (
    <svg className={styles.mediaIcon} viewBox="0 0 24 24" aria-hidden="true">
      {kind === 'image' ? (
        <>
          <rect x="3" y="5" width="18" height="14" rx="2" />
          <circle cx="9" cy="10" r="2" />
          <path d="m21 16-5-5-8 8" />
        </>
      ) : kind === 'audio' ? (
        <path d="M9 18V6l10-2v12M9 18a3 3 0 1 1-3-3 3 3 0 0 1 3 3Zm10-2a3 3 0 1 1-3-3 3 3 0 0 1 3 3Z" />
      ) : (
        <>
          <rect x="3" y="6" width="13" height="12" rx="2" />
          <path d="m16 10 5-3v10l-5-3" />
        </>
      )}
    </svg>
  );
}

/** Personal editor: checklist against a classroom with an open submission window. */
function SendCheck({ targets, senses }: { targets: DictionaryView[]; senses: SenseDraft[] }) {
  const [id, setId] = useState(targets[0]?.id ?? '');
  const selectId = useId();
  const target = targets.find((t) => t.id === id) ?? targets[0];
  if (!target)
    return (
      <div className={styles.check}>
        <h2 className={styles.checkTitle}>Enviar a un aula</h2>
        <p className="small">
          Ahora no estás en ningún aula con envíos abiertos. Pide a tu profesora o profesor el
          código del aula y únete desde <Link to="/aulas">Diccionario de aula</Link>.
        </p>
      </div>
    );
  return (
    <>
      {targets.length > 1 && (
        <div className="field">
          <label htmlFor={selectId}>Comprobar para el aula</label>
          <select id={selectId} value={target.id} onChange={(e) => setId(e.target.value)}>
            {targets.map((t) => (
              <option key={t.id} value={t.id}>
                {t.title}
              </option>
            ))}
          </select>
        </div>
      )}
      <PolicyCheck classroom={target} senses={senses} />
      <p className="small muted">
        Al enviar se guarda una copia: lo que cambies después no altera lo que revisa tu profesora.
      </p>
    </>
  );
}

/** Classroom policy computed client-side: definitions, required fields, maximum senses. */
function PolicyCheck({
  classroom,
  senses,
  teacher = false,
}: {
  classroom: DictionaryView;
  senses: SenseDraft[];
  teacher?: boolean;
}) {
  const c = classroom.classroom;
  const reqFields = (c?.requiredFields ?? []).filter((f) => c?.visibleFields.includes(f));
  const max = c?.maxSenses ?? null;
  const n = senses.length;
  const all = n === 1 ? 'la acepción' : `las ${n} acepciones`;
  const items: [boolean, string][] = [
    [senses.every((s) => !!s.definition.trim()), 'Todas las acepciones tienen definición'],
    ...reqFields.map((f): [boolean, string] => [
      senses.every(REQUIRED_CHECK[f][1]),
      `${FIELD_LABEL[f]} en ${all}`,
    ]),
    ...(max ? [[n <= max, `${n} de ${max} acepciones`] as [boolean, string]] : []),
  ];
  const ready = items.every(([ok]) => ok);
  const asks = [
    reqFields.length > 0 && `pide ${reqFields.map((f) => FIELD_LABEL[f].toLowerCase()).join(', ')}`,
    max && `admite hasta ${max} ${max === 1 ? 'acepción' : 'acepciones'}`,
  ].filter(Boolean);
  return (
    <div className={styles.check}>
      <h2 className={styles.checkTitle}>
        {teacher
          ? `Normas de «${classroom.title}»`
          : ready
            ? `Lista para enviar a «${classroom.title}»`
            : `Antes de enviar a «${classroom.title}»`}
      </h2>
      {asks.length > 0 && <p className="small">El aula {asks.join(' y ')}.</p>}
      <ul className={styles.checkList}>
        {items.map(([ok, text]) => (
          <li key={text} data-ok={ok}>
            <span aria-hidden="true">{ok ? '✓' : '✗'}</span>
            <span>
              <span className="visually-hidden">{ok ? 'Cumple: ' : 'Falta: '}</span>
              {text}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}

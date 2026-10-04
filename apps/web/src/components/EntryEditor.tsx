import {
  EntryInput,
  MEDIA_MIME,
  type DictionaryView,
  type EntryView,
  type MediaKind,
  type MediaView,
  type SenseField,
} from '@lexican/core';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { getApi, toApiError, type ApiError } from '../api/index.ts';
import styles from './EntryEditor.module.css';
import { Media } from './Media.tsx';
import { Dialog, ErrorMessage, Field, useNotify } from './ui.tsx';
import { useVocab } from './vocab.ts';

// CONTRACT (implemented by the personal-dictionary agent; reused by teachers to edit published entries).
// Full entry editor: headword + reorderable sense cards (definition, part of speech, gender, number, language +
// foreign form, extra info, usage example, topics, image/audio/video upload). Validates with the shared Zod schema
// (EntryInput) inline and accessibly; shows server errors (409 conflict, duplicate headword) without losing input.
export interface EntryEditorProps {
  /** Existing entry to edit, or undefined to create. */
  entry?: EntryView;
  /** Classroom being edited (teacher), for policy hints: maxSenses, visibleFields, requiredFields. */
  classroom?: DictionaryView;
  submitLabel?: string;
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
type SelectKey = 'partOfSpeechId' | 'genderId' | 'numberId';
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

const MEDIA_LABEL: Record<MediaKind, string> = { image: 'Imagen', audio: 'Audio', video: 'Vídeo' };

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

export function EntryEditor({
  entry,
  classroom,
  submitLabel = 'Guardar',
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

  useEffect(() => {
    if (focusErrors)
      form.current?.querySelector<HTMLElement>('[aria-invalid="true"], [data-invalid]')?.focus();
  }, [focusErrors]);
  // Focus requested by an action (moved/added sense), applied once the DOM reflects it.
  useEffect(() => {
    if (!pendingFocus.current) return;
    form.current?.querySelector<HTMLElement>(`[data-focus="${pendingFocus.current}"]`)?.focus();
    pendingFocus.current = null;
  });

  const err = (key: string) => errors[key];
  const patch = (key: string, change: Partial<SenseDraft>) =>
    setSenses((list) => list.map((s) => (s.key === key ? { ...s, ...change } : s)));

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
    pendingFocus.current = `${copy.key}-definition`;
  };

  const add = () => {
    const s = blankSense();
    setSenses([...senses, s]);
    pendingFocus.current = `${s.key}-definition`;
  };

  const remove = (key: string) => {
    setSenses((list) => list.filter((s) => s.key !== key));
    setToDelete(null);
    notify('Acepción quitada. Se borrará al guardar.', 'info');
  };

  async function upload(sense: SenseDraft, kind: MediaKind, file: File | undefined) {
    if (!file) return;
    const k = `senses.${senses.indexOf(sense)}.media-${kind}`;
    setUploading((u) => ({ ...u, [`${sense.key}-${kind}`]: true }));
    try {
      const m = await (await getApi()).uploadMedia(file, file.name);
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
  const selects: [SelectKey, string, 'part_of_speech' | 'gender' | 'number'][] = [
    ['partOfSpeechId', 'Categoría gramatical', 'part_of_speech'],
    ['genderId', 'Género', 'gender'],
    ['numberId', 'Número', 'number'],
  ];
  const deleting = senses.find((s) => s.key === toDelete);

  return (
    <form ref={form} onSubmit={submit} noValidate className={styles.form}>
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
          const titleId = `${s.key}-title`;
          return (
            <li key={s.key} className={`card ${styles.sense}`}>
              <div className={styles.senseHead}>
                <h3 id={titleId}>
                  Acepción {i + 1}
                  {s.hidden && <span className="badge badge-hidden">Oculta</span>}
                </h3>
                <div className="row">
                  <button
                    type="button"
                    data-focus={`${s.key}-up`}
                    className="btn btn-sm"
                    disabled={i === 0}
                    onClick={() => move(i, -1)}
                  >
                    <span aria-hidden="true">↑</span> Subir
                    <span className="visually-hidden"> acepción {i + 1}</span>
                  </button>
                  <button
                    type="button"
                    data-focus={`${s.key}-down`}
                    className="btn btn-sm"
                    disabled={i === senses.length - 1}
                    onClick={() => move(i, 1)}
                  >
                    <span aria-hidden="true">↓</span> Bajar
                    <span className="visually-hidden"> acepción {i + 1}</span>
                  </button>
                  <button
                    type="button"
                    className="btn btn-sm"
                    disabled={!!max && senses.length >= max}
                    onClick={() => duplicate(i)}
                  >
                    Duplicar<span className="visually-hidden"> acepción {i + 1}</span>
                  </button>
                  <button
                    type="button"
                    className="btn btn-sm btn-danger"
                    disabled={senses.length === 1}
                    onClick={() => setToDelete(s.key)}
                  >
                    Borrar<span className="visually-hidden"> acepción {i + 1}</span>
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
                    maxLength={1000}
                    value={s.definition}
                    onChange={(e) => patch(s.key, { definition: e.target.value })}
                  />
                )}
              </Field>

              <div className={styles.cols}>
                {selects
                  .filter(([, , f]) => show(f))
                  .map(([key, text, f]) => (
                    <Field key={key} label={label(text, f)} error={err(`${base}.${key}`)}>
                      {(p) => (
                        <select
                          {...p}
                          value={s[key]}
                          onChange={(e) =>
                            patch(s.key, { [key]: e.target.value } as Partial<SenseDraft>)
                          }
                        >
                          <option value="">—</option>
                          {v.list(f).map((o) => (
                            <option key={o.id} value={o.id}>
                              {o.label}
                            </option>
                          ))}
                        </select>
                      )}
                    </Field>
                  ))}
              </div>

              {show('language') && (
                <div className={styles.cols}>
                  <Field label={label('Otra lengua', 'language')} error={err(`${base}.languageId`)}>
                    {(p) => (
                      <select
                        {...p}
                        value={s.languageId}
                        onChange={(e) => patch(s.key, { languageId: e.target.value })}
                      >
                        <option value="">—</option>
                        {v.list('language').map((o) => (
                          <option key={o.id} value={o.id}>
                            {o.label}
                          </option>
                        ))}
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
                      maxLength={255}
                      value={s.example}
                      onChange={(e) => patch(s.key, { example: e.target.value })}
                    />
                  )}
                </Field>
              )}

              {show('topics') && (
                <TopicPicker
                  label={label('Temáticas', 'topics')}
                  options={v.list('topic')}
                  selected={s.topicIds}
                  error={err(`${base}.topicIds`)}
                  onChange={(topicIds) => patch(s.key, { topicIds })}
                />
              )}

              <div className={styles.cols}>
                {(['image', 'audio', 'video'] as const)
                  .filter((kind) => show(kind))
                  .map((kind) => {
                    const current = s.media.find((m) => m.kind === kind);
                    const busy = uploading[`${s.key}-${kind}`];
                    return (
                      <Field
                        key={kind}
                        label={label(
                          current
                            ? `Cambiar ${MEDIA_LABEL[kind].toLowerCase()}`
                            : MEDIA_LABEL[kind],
                          kind,
                        )}
                        hint={busy ? 'Subiendo…' : undefined}
                        error={err(`${base}.media-${kind}`)}
                      >
                        {(p) => (
                          <>
                            {current && (
                              <div className={styles.preview}>
                                <Media
                                  media={current}
                                  alt={`${headword || 'Entrada'}: ${MEDIA_LABEL[kind].toLowerCase()}`}
                                />
                                <button
                                  type="button"
                                  className="btn btn-sm btn-link"
                                  onClick={() =>
                                    patch(s.key, { media: s.media.filter((m) => m.kind !== kind) })
                                  }
                                >
                                  Quitar {MEDIA_LABEL[kind].toLowerCase()}
                                </button>
                              </div>
                            )}
                            <input
                              {...p}
                              type="file"
                              accept={MEDIA_MIME[kind].join(',')}
                              disabled={busy}
                              onChange={(e) => {
                                void upload(s, kind, e.target.files?.[0]);
                                e.target.value = '';
                              }}
                            />
                          </>
                        )}
                      </Field>
                    );
                  })}
              </div>
            </li>
          );
        })}
      </ol>

      <div>
        <button
          type="button"
          className="btn"
          onClick={add}
          disabled={!!max && senses.length >= max}
        >
          + Añadir acepción
        </button>
      </div>

      {serverError && (
        <div className={styles.serverError}>
          <ErrorMessage error={serverError} />
          {serverError.code === 'conflict' && entry && !serverError.details.headword && (
            <p className="small">
              Tus cambios siguen en el formulario. Puedes copiarlos y{' '}
              <button type="button" className="btn btn-sm" onClick={() => window.location.reload()}>
                Recargar
              </button>{' '}
              para ver la versión actual.
            </p>
          )}
        </div>
      )}

      <div className={`row ${styles.actions}`}>
        <button type="submit" className="btn btn-primary" disabled={saving}>
          {saving ? 'Guardando…' : submitLabel}
        </button>
        <button type="button" className="btn" onClick={onCancel} disabled={saving}>
          Cancelar
        </button>
      </div>

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

/** Accessible multi-select: a filterable checkbox list inside a disclosure. */
function TopicPicker({
  label,
  options,
  selected,
  error,
  onChange,
}: {
  label: ReactNode;
  options: { id: string; label: string }[];
  selected: string[];
  error: string[] | undefined;
  onChange: (ids: string[]) => void;
}) {
  const [filter, setFilter] = useState('');
  const norm = (t: string) => t.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
  const shown = options.filter((o) => norm(o.label).includes(norm(filter)));
  const names = options.filter((o) => selected.includes(o.id)).map((o) => o.label);
  const toggle = (id: string, on: boolean) =>
    onChange(on ? [...selected, id] : selected.filter((x) => x !== id));
  return (
    <div className="field">
      <details className={styles.topics} open={error ? true : undefined}>
        <summary {...(error ? { 'data-invalid': true } : {})}>
          {label}: {names.length ? names.join(', ') : <span className="muted">ninguna</span>}
        </summary>
        <fieldset>
          <legend className="visually-hidden">{label}</legend>
          <input
            type="search"
            aria-label="Filtrar temáticas"
            placeholder="Filtrar temáticas"
            value={filter}
            onChange={(e) => setFilter(e.target.value)}
          />
          <div className={styles.topicList}>
            {shown.map((o) => (
              <label key={o.id} className="check">
                <input
                  type="checkbox"
                  checked={selected.includes(o.id)}
                  onChange={(e) => toggle(o.id, e.target.checked)}
                />
                {o.label}
              </label>
            ))}
            {shown.length === 0 && <p className="small muted">Ninguna temática coincide.</p>}
          </div>
        </fieldset>
      </details>
      {error && <span className="error">{error.join(' ')}</span>}
    </div>
  );
}

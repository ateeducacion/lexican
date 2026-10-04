import { writeFile } from 'node:fs/promises';
import { csvLine } from './transform.ts';

/** Migration report (§70/§71): per-table metrics, media, every anomaly with its class, and post-load checks. */

export type AnomalyClass =
  'repairable automatically' | 'needs rule' | 'needs human review' | 'cannot migrate';

export interface Anomaly {
  table: string;
  legacyId: number | null;
  class: AnomalyClass;
  message: string;
}

export interface TableStats {
  legacy: number;
  /** Rows in the target that carry this legacy_source (null for pivots without legacy columns). */
  new: number | null;
  mapped: number;
  skipped: number;
  invalid: number;
  orphaned: number;
}

export interface MediaStats {
  /** media_assets created (one per distinct content). */
  migrated: number;
  /** Legacy rows whose content was already migrated (same sha256) and now share one asset. */
  sameContent: number;
  missing: number;
  corrupt: number;
  /** Extra rows of the same kind on one sense (legacy allows only one; the newest wins). */
  duplicates: number;
  /** Files on disk not referenced by any legacy row (thumbnails excluded). */
  unreferenced: number;
  /** Rows without a usable stored file name (empty, external URL or unsafe path). */
  brokenRefs: number;
  thumbnailsIgnored: number;
  bytesCopied: number;
}

export interface Check {
  check: string;
  ok: boolean;
  detail: string;
}

export interface Report {
  tool: 'lexican-legacy-migrator';
  startedAt: string;
  finishedAt: string;
  durationMs: number;
  dryRun: boolean;
  committed: boolean;
  failure: string | null;
  tables: Record<string, TableStats>;
  notMigrated: Record<string, { count: number | null; reason: string }>;
  media: MediaStats;
  verification: Check[];
  anomalySummary: Record<AnomalyClass, number>;
  orphans: number;
  anomalies: Anomaly[];
}

export function newReport(dryRun: boolean): Report {
  return {
    tool: 'lexican-legacy-migrator',
    startedAt: new Date().toISOString(),
    finishedAt: '',
    durationMs: 0,
    dryRun,
    committed: false,
    failure: null,
    tables: {},
    notMigrated: {},
    media: {
      migrated: 0,
      sameContent: 0,
      missing: 0,
      corrupt: 0,
      duplicates: 0,
      unreferenced: 0,
      brokenRefs: 0,
      thumbnailsIgnored: 0,
      bytesCopied: 0,
    },
    verification: [],
    anomalySummary: {
      'repairable automatically': 0,
      'needs rule': 0,
      'needs human review': 0,
      'cannot migrate': 0,
    },
    orphans: 0,
    anomalies: [],
  };
}

export function stat(r: Report, table: string): TableStats {
  return (r.tables[table] ??= {
    legacy: 0,
    new: null,
    mapped: 0,
    skipped: 0,
    invalid: 0,
    orphaned: 0,
  });
}

export function anomaly(
  r: Report,
  table: string,
  legacyId: number | null,
  cls: AnomalyClass,
  message: string,
): void {
  r.anomalies.push({ table, legacyId, class: cls, message });
  r.anomalySummary[cls]++;
}

/** Orphans: rows whose parent is missing. Counted in the table stats and in the report total (--fail-on-orphans). */
export function orphan(
  r: Report,
  table: string,
  legacyId: number,
  cls: AnomalyClass,
  message: string,
): void {
  stat(r, table).orphaned++;
  r.orphans++;
  anomaly(r, table, legacyId, cls, message);
}

/** Writes `<file>` (JSON) and the anomalies as CSV next to it (`<file without .json>.csv`). */
export async function writeReport(r: Report, file: string): Promise<string> {
  await writeFile(file, JSON.stringify(r, null, 2) + '\n');
  const csv = file.replace(/\.json$/i, '') + '.csv';
  const lines = [
    csvLine(['table', 'legacy_id', 'class', 'message']),
    ...r.anomalies.map((a) => csvLine([a.table, a.legacyId, a.class, a.message])),
  ];
  await writeFile(csv, lines.join('\n') + '\n');
  return csv;
}

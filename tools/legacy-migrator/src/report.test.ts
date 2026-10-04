import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';
import { anomaly, newReport, orphan, stat, writeReport } from './report.ts';

describe('migration report', () => {
  it('counts anomalies per class and orphans per table', () => {
    const r = newReport(true);
    anomaly(r, 'users', 1, 'needs rule', 'a');
    orphan(r, 'dp_entradas', 7, 'cannot migrate', 'b');
    orphan(r, 'dp_entradas', 8, 'cannot migrate', 'c');
    expect(r.anomalySummary).toEqual({
      'repairable automatically': 0,
      'needs rule': 1,
      'needs human review': 0,
      'cannot migrate': 2,
    });
    expect(r.orphans).toBe(2);
    expect(stat(r, 'dp_entradas')).toEqual({
      legacy: 0,
      new: null,
      mapped: 0,
      skipped: 0,
      invalid: 0,
      orphaned: 2,
    });
    expect(r).toMatchObject({ dryRun: true, committed: false, failure: null });
  });

  it('writes the JSON report and the anomalies as RFC 4180 CSV next to it', async () => {
    const dir = await mkdtemp(join(tmpdir(), 'lexican-report-'));
    try {
      const r = newReport(false);
      anomaly(r, 'comentarios', null, 'needs human review', 'texto con "comillas", coma\ny salto');
      const csv = await writeReport(r, join(dir, 'Informe.JSON'));
      expect(csv).toBe(join(dir, 'Informe.csv'));
      expect(JSON.parse(await readFile(join(dir, 'Informe.JSON'), 'utf8')).anomalies).toHaveLength(
        1,
      );
      expect(await readFile(csv, 'utf8')).toBe(
        'table,legacy_id,class,message\ncomentarios,,needs human review,"texto con ""comillas"", coma\ny salto"\n',
      );
      // Any other extension keeps the full name before «.csv».
      expect(await writeReport(r, join(dir, 'informe.txt'))).toBe(join(dir, 'informe.txt.csv'));
    } finally {
      await rm(dir, { recursive: true, force: true });
    }
  });
});

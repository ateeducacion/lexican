import { fileURLToPath } from 'node:url';
import type { Page } from '@playwright/test';
import { expect, test } from './fixtures.ts';
import { findAndOpen } from './student-steps.ts';

const TONE = fileURLToPath(new URL('../fixtures/media/tone.mp3', import.meta.url));

/** Play, pause and seek the first <audio> of the page; fails on any media error. */
async function expectPlays(page: Page) {
  const audio = page.locator('audio').first();
  await expect(audio).toHaveAttribute('src', /^(blob:|\/media\/)/, { timeout: 60_000 });
  const r = await audio.evaluate(async (a: HTMLAudioElement) => {
    a.muted = true; // autoplay policies allow muted playback without a gesture
    await a.play();
    await new Promise((ok) => a.addEventListener('timeupdate', ok, { once: true }));
    a.pause();
    a.currentTime = 0.5;
    await new Promise((ok) => a.addEventListener('seeked', ok, { once: true }));
    return {
      duration: a.duration,
      time: a.currentTime,
      paused: a.paused,
      error: a.error?.code ?? null,
    };
  });
  expect(r).toMatchObject({ error: null, paused: true });
  expect(r.duration).toBeGreaterThan(0.9);
  expect(r.time).toBeCloseTo(0.5, 1);
}

test('audio: attach, play, pause, seek, reload and play again, also after signing in again @mobile', async ({
  page,
  app,
}) => {
  await app.login('student1');
  await app.goto('/mi-diccionario/nueva');
  await expect(page.getByRole('heading', { name: 'Nueva palabra', level: 1 })).toBeVisible();
  await page.getByLabel('Palabra (obligatorio)').fill('timple');
  await page
    .getByLabel(/^Definición/)
    .first()
    .fill('Pequeño instrumento de cuerda típico de Canarias.');
  await page
    .getByRole('group', { name: 'Categoría gramatical' })
    .first()
    .getByText('Sustantivo', { exact: true })
    .click();
  await page.locator('input[type=file][accept*="audio/mpeg"]').first().setInputFiles(TONE);
  await expect(page.getByTitle('tone.mp3')).toBeVisible();
  await page.getByRole('button', { name: 'Guardar' }).click();
  await expect(page.getByRole('heading', { name: 'timple', level: 1 })).toBeVisible();
  await expectPlays(page);

  await page.reload();
  await expect(page.getByRole('heading', { name: 'timple', level: 1 })).toBeVisible({
    timeout: 90_000,
  });
  await expectPlays(page);

  await app.logout();
  await app.login('student1');
  await app.goto('/mi-diccionario');
  await findAndOpen(page, 'timple');
  await expectPlays(page);
});

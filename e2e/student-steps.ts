import type { Page } from '@playwright/test';
import { expect } from './fixtures.ts';

export const CLASSROOM = 'Canarismos de 2º ESO B';

/** From the personal home: create an entry with two senses (both with part of speech) and land on its page. */
export async function createEntry(page: Page, headword: string) {
  await page.getByRole('link', { name: 'Añadir entrada' }).click();
  await expect(page.getByRole('heading', { name: 'Nueva palabra', level: 1 })).toBeVisible();
  await page.getByLabel('Palabra (obligatorio)').fill(headword);
  await page
    .getByLabel(/^Definición/)
    .first()
    .fill('Lío, alboroto o desorden.');
  await page.getByLabel('Categoría gramatical').first().selectOption({ label: 'Sustantivo' });
  await page.getByRole('button', { name: '+ Añadir acepción' }).click();
  await page
    .getByLabel(/^Definición/)
    .nth(1)
    .fill('Puesto de venta improvisado en la calle.');
  await page.getByLabel('Categoría gramatical').nth(1).selectOption({ label: 'Sustantivo' });
  await page.getByRole('button', { name: 'Guardar' }).click();
  await expect(page.getByRole('heading', { name: headword, level: 2 })).toBeVisible();
}

/** From the personal home: search an entry (case-insensitive) and open it. */
export async function findAndOpen(page: Page, headword: string) {
  await page.getByRole('searchbox', { name: 'Buscar una palabra' }).fill(headword.toUpperCase());
  await page.getByRole('button', { name: 'Buscar' }).click();
  await expect(page.getByText('1 entrada encontradas')).toBeVisible();
  await page.getByRole('link', { name: headword, exact: true }).click();
  await expect(page.getByRole('heading', { name: headword, level: 2 })).toBeVisible();
}

/** From an entry page: send it to the demo classroom and wait for the pending status. */
export async function sendToClassroom(page: Page) {
  await page.getByRole('button', { name: 'Enviar al aula' }).click();
  const dialog = page.getByRole('dialog');
  await dialog.getByLabel(CLASSROOM).check();
  await dialog.getByRole('button', { name: 'Enviar', exact: true }).click();
  await expect(dialog).toBeHidden();
  await expect(
    page.getByRole('region', { name: 'Estado de los envíos' }).getByText('Pendiente de revisión'),
  ).toBeVisible();
}

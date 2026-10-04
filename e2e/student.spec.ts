import { expect, expectAccessible, test } from './fixtures.ts';
import { createEntry, findAndOpen, sendToClassroom } from './student-steps.ts';

test.describe('student', () => {
  test('login page explains the demo, is accessible and logs in with one click', async ({
    page,
    app,
  }) => {
    test.skip(!app.demo, 'demo accounts only exist in the demo build');
    await app.goto('/entrar');
    await expect(page.getByRole('heading', { name: 'Cuentas de demostración' })).toBeVisible();
    await expect(page.getByText(/se guardan únicamente en este navegador/).first()).toBeVisible();
    await expectAccessible(page);
    await page.getByRole('button', { name: 'Entrar como alumno 1' }).click();
    await expect(page.getByRole('button', { name: 'Salir' })).toBeVisible({ timeout: 30_000 });
    await expect(page.getByRole('link', { name: '+ Nueva palabra' })).toBeVisible();
  });

  test('§77 journey: create, search, send and see the pending status', async ({ page, app }) => {
    await app.login('student1');
    await page
      .getByRole('navigation', { name: 'Principal' })
      .getByRole('link', { name: 'Mi diccionario' })
      .click();
    await createEntry(page, 'tenderete');
    // Sense order is what was typed (first sense first).
    await expect(page.locator('.sense-list > li').first()).toContainText('Lío, alboroto');
    await page.getByRole('link', { name: '← Mi diccionario' }).click();
    await findAndOpen(page, 'tenderete');
    await sendToClassroom(page);
    await app.logout();
  });

  test('data and session survive a reload', async ({ page, app }) => {
    await app.login('student1');
    await createEntry(page, 'mentidero');
    await page.reload();
    await expect(page.getByRole('heading', { name: 'mentidero', level: 2 })).toBeVisible({
      timeout: 30_000,
    });
    await expect(page.getByRole('button', { name: 'Salir' })).toBeVisible();
    await app.goto('/mi-diccionario');
    await findAndOpen(page, 'mentidero');
  });

  test('personal dictionary, entry page and editor are accessible', async ({ page, app }) => {
    await app.login('student1');
    await app.goto('/mi-diccionario');
    await expect(page.getByRole('link', { name: 'guagua', exact: true })).toBeVisible();
    await expectAccessible(page);
    await page.getByRole('link', { name: 'guagua', exact: true }).click();
    await expect(page.getByRole('region', { name: 'Estado de los envíos' })).toBeVisible();
    await expectAccessible(page);
    await page.getByRole('link', { name: 'Editar' }).click();
    await expect(page.getByLabel('Palabra (obligatorio)')).toHaveValue('guagua');
    await expectAccessible(page);
    // Inline validation state must stay accessible too.
    await page
      .getByLabel(/^Definición/)
      .first()
      .fill('');
    await page.getByRole('button', { name: 'Guardar' }).click();
    await expect(page.getByText('La definición es obligatoria')).toBeVisible();
    await expect(page.getByLabel(/^Definición/).first()).toBeFocused();
    await expectAccessible(page);
  });

  test('create and search on a phone without horizontal scroll @mobile', async ({ page, app }) => {
    await app.login('student1');
    await app.goto('/mi-diccionario');
    const noHorizontalScroll = () =>
      expect
        .poll(() => page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth))
        .toBe(true);
    await noHorizontalScroll();
    await createEntry(page, 'magua');
    await noHorizontalScroll();
    await page.getByRole('link', { name: '← Mi diccionario' }).click();
    await findAndOpen(page, 'magua');
    await noHorizontalScroll();
  });

  test('demo reset restores the seed data', async ({ page, app }) => {
    test.skip(!app.demo, 'demo only');
    await app.login('student1');
    await app.goto('/mi-diccionario');
    await createEntry(page, 'efímera');
    await page.getByRole('button', { name: 'Restablecer datos de demostración' }).click();
    await page
      .getByRole('dialog')
      .getByRole('button', { name: 'Restablecer', exact: true })
      .click();
    // Reset wipes the browser database and the session, then reloads on the login page.
    await expect(page.getByRole('heading', { name: 'Acceso' })).toBeVisible({ timeout: 30_000 });
    await app.login('student1');
    await app.goto('/mi-diccionario');
    await expect(page.getByRole('link', { name: 'guagua', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'efímera', exact: true })).toHaveCount(0);
  });
});

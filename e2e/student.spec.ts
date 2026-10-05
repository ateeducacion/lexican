import { expect, expectAccessible, test } from './fixtures.ts';
import { createEntry, findAndOpen, sendToClassroom } from './student-steps.ts';

test.describe('student', () => {
  test('login page offers only demo accounts, is accessible and logs in with one click @mobile', async ({
    page,
    app,
  }) => {
    test.skip(!app.demo, 'demo accounts only exist in the demo build');
    await app.goto('/entrar');
    await expect(page.getByRole('heading', { name: 'Cuentas de demostración' })).toBeVisible();
    await expect(page.getByText(/se guardan únicamente en este navegador/).first()).toBeVisible();
    await expect(page.getByRole('button', { name: /CAS/ })).toHaveCount(0);
    await expect(page.getByRole('link', { name: /CAS|usuario educativo/ })).toHaveCount(0);
    await expect(page.getByText('www.casserverpac4j.dev')).toHaveCount(0);
    await expectAccessible(page);
    await page.getByRole('button', { name: 'Entrar como alumno 1' }).click();
    await expect(page.getByLabel(/^Menú de usuario:/)).toBeVisible({
      timeout: 90_000,
    });
    await expect(page.getByRole('link', { name: 'Añadir entrada' })).toBeVisible();
  });

  test('account dropdown supports keyboard, dismissal and logout; dictionary settings stay beside the title @mobile', async ({
    page,
    app,
  }) => {
    await app.login('student1');
    const account = page.getByLabel(/^Menú de usuario:/);
    const logout = page.getByRole('button', { name: 'Desconectar' });
    await expect(logout).toBeHidden();
    await account.focus();
    await account.press('Enter');
    await expect(logout).toBeVisible();
    await account.press('Tab');
    await expect(logout).toBeFocused();
    await expectAccessible(page);
    await logout.press('Escape');
    await expect(logout).toBeHidden();
    await expect(account).toBeFocused();
    await account.press('Space');
    await expect(logout).toBeVisible();
    await page.getByRole('main').click({ position: { x: 8, y: 8 } });
    await expect(logout).toBeHidden();

    await account.click();
    const settings = page.getByRole('link', { name: 'Ajustes de mi diccionario' });
    await settings.focus();
    await settings.press('Enter');
    await expect(page.getByRole('heading', { name: 'Ajustes de mi diccionario' })).toBeVisible();
    await expect(logout).toBeHidden();
    await page.getByRole('link', { name: '← Mi diccionario' }).click();
    await expectAccessible(page);
    for (const width of [768, 1024, 320]) {
      await page.setViewportSize({ width, height: 720 });
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
        true,
      );
    }
    await account.click();
    await expect(logout).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(
      true,
    );
    const panel = await logout.boundingBox();
    expect(panel!.x).toBeGreaterThanOrEqual(0);
    expect(panel!.x + panel!.width).toBeLessThanOrEqual(320);
    await account.click();
    await app.logout();
  });

  test('§77 journey: create, search, send and see the pending status', async ({ page, app }) => {
    await app.login('student1');
    await page
      .getByRole('navigation', { name: 'Principal' })
      .getByRole('link', { name: 'Diccionario personal' })
      .click();
    await createEntry(page, 'tenderete');
    // Sense order is what was typed (first sense first).
    await expect(page.locator('.sense-list > li').first()).toContainText('Lío, alboroto');
    // Desktop workspace: the list stays beside the entry, so search right away.
    await findAndOpen(page, 'tenderete');
    await sendToClassroom(page);
    await app.logout();
  });

  test('data and session survive a reload', async ({ page, app }) => {
    await app.login('student1');
    await createEntry(page, 'mentidero');
    await page.reload();
    await expect(page.getByRole('heading', { name: 'mentidero', level: 1 })).toBeVisible({
      timeout: 90_000,
    });
    await expect(page.getByLabel(/^Menú de usuario:/)).toBeVisible();
    await app.goto('/mi-diccionario');
    await findAndOpen(page, 'mentidero');
  });

  test('personal dictionary, entry page and editor are accessible', async ({ page, app }) => {
    await app.login('student1');
    await app.goto('/mi-diccionario');
    await expect(page.getByRole('link', { name: 'guagua', exact: true })).toBeVisible();
    await expectAccessible(page);
    await page.getByRole('link', { name: 'guagua', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'guagua', level: 1 })).toBeVisible();
    await expect(page.getByRole('region', { name: 'En el aula' })).toBeVisible();
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
    // Phones show the entry alone with a back link; wider screens keep the list beside it.
    const back = page.getByRole('link', { name: '← Mi diccionario' });
    if (await back.isVisible()) await back.click();
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
    await expect(page.getByRole('heading', { name: 'Acceso' })).toBeVisible({ timeout: 90_000 });
    await app.login('student1');
    await app.goto('/mi-diccionario');
    await expect(page.getByRole('link', { name: 'guagua', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'efímera', exact: true })).toHaveCount(0);
  });
});

test('demo notice floats at the bottom and can be dismissed for the visit', async ({
  page,
  app,
}) => {
  test.skip(!app.demo, 'demo build only');
  await app.goto('/entrar');
  const notice = page.getByRole('complementary', { name: 'Entorno de demostración' });
  await expect(notice).toBeVisible();
  const box = await notice.boundingBox();
  if (!test.info().project.name.includes('mobile'))
    expect(box!.y + box!.height).toBeGreaterThan(page.viewportSize()!.height - 120);
  await notice.getByRole('button', { name: 'Ocultar el aviso de demostración' }).click();
  await expect(notice).toBeHidden();
  // Let the background PGlite download finish: Firefox reports an aborted fetch inside PGlite otherwise.
  await page.waitForLoadState('networkidle');
  await page.reload();
  await expect(page.getByRole('heading', { name: 'Cuentas de demostración' })).toBeVisible();
  await expect(notice).toBeHidden();
});

test('the top bar offers help and an about dialog with licence, version and source code', async ({
  page,
  app,
}) => {
  await app.login('student1');
  await page.getByRole('button', { name: 'Ayuda' }).click();
  const help = page.getByRole('dialog', { name: '¿Qué puedo hacer?' });
  await expect(help.getByText('Enviar al aula')).toBeVisible();
  await help.getByRole('button', { name: 'Entendido' }).click();
  await page.getByRole('button', { name: 'Acerca de LexiCán' }).click();
  const about = page.getByRole('dialog', { name: 'Acerca de LexiCán' });
  await expect(about.getByText(/^Versión 2\.\d+\.\d+/)).toBeVisible();
  await expect(about.getByRole('link', { name: /Código fuente/ })).toHaveAttribute(
    'href',
    /github\.com\/ateeducacion\/lexican/,
  );
  await expectAccessible(page);
  await about.getByRole('button', { name: /Licencias/ }).click();
  const licenses = page.getByRole('dialog', { name: 'Licencias' });
  await expect(licenses.getByText('AGPL-3.0-or-later', { exact: false }).first()).toBeVisible();
  await expect(licenses.getByText('react', { exact: true })).toBeVisible();
  await expectAccessible(page);
});

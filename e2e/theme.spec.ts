import { expect, test } from './fixtures.ts';

test('theme toggle switches between light and dark and is remembered', async ({ page, app }) => {
  await app.goto('/entrar');
  const html = page.locator('html');
  await expect(html).toHaveAttribute('data-theme', 'light');
  await page.getByRole('button', { name: 'Activar tema oscuro' }).click();
  await expect(html).toHaveAttribute('data-theme', 'dark');
  await page.waitForLoadState('networkidle'); // the demo preloads its database in the background
  await page.reload();
  await expect(html).toHaveAttribute('data-theme', 'dark');
  await page.getByRole('button', { name: 'Activar tema claro' }).click();
  await expect(html).toHaveAttribute('data-theme', 'light');
});

test('a missing code chunk after a deployment reloads once instead of showing an error', async ({
  page,
  app,
}) => {
  test.skip(!app.demo, 'demo build only');
  let fail = true;
  await page.route(/assets\/client-.*\.js$/, (r) =>
    fail ? ((fail = false), r.fulfill({ status: 404, body: '' })) : r.continue(),
  );
  // The forced 404 itself is logged by the browser; that is the scenario under test.
  page.removeAllListeners('console');
  // Depending on the browser the reload happens while the login page preloads the database chunk or on the
  // first login attempt; either way the user ends up signed in without the generic error.
  await app.goto('/entrar');
  await page.waitForLoadState('networkidle');
  const signedIn = page.getByRole('button', { name: 'Desconectar' });
  for (let attempt = 0; attempt < 3 && !(await signedIn.isVisible()); attempt++) {
    const button = page.getByRole('button', { name: 'Entrar como alumno 1' });
    if (await button.isVisible()) await button.click().catch(() => undefined);
    await signedIn.waitFor({ timeout: 20_000 }).catch(() => undefined);
    await page.waitForLoadState('networkidle');
  }
  await expect(signedIn).toBeVisible();
  await expect(page.getByText('Ha ocurrido un error inesperado')).toHaveCount(0);
});

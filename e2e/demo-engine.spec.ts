import { expect, test } from './fixtures.ts';

test('a second tab does not open the demo database and explains why; the first keeps working', async ({
  page,
  app,
}) => {
  test.skip(!app.demo, 'the Pages demo engine');
  await app.login('student1');
  const second = await page.context().newPage();
  // Same browser profile: the session marker is shared, so the second tab tries to start the engine at once.
  await second.goto('./#/mi-diccionario');
  await expect(
    second.getByRole('alert').filter({ hasText: 'La demo ya está abierta en otra pestaña' }),
  ).toBeVisible({
    timeout: 30_000,
  });
  await second.close();
  await app.goto('/mi-diccionario');
  await expect(page.getByRole('link', { name: 'Añadir entrada' })).toBeVisible();
});

import { expect, test } from './fixtures.ts';
import { CLASSROOM, createEntry, sendToClassroom } from './student-steps.ts';

// One test and one browser context: the demo database (PGlite in IndexedDB) is shared by every role.
test('§77 cross-role journey: student sends, teacher comments and publishes, student sees the result', async ({
  page,
  app,
}) => {
  // Distinct from student.spec.ts: production E2E shares one database across tests.
  const word = 'ventorrillo';
  const comment = 'Muy bien: añade un ejemplo de uso la próxima vez.';

  await test.step('student 1 creates and sends an entry', async () => {
    await app.login('student1');
    await app.goto('/mi-diccionario');
    await createEntry(page, word);
    await sendToClassroom(page);
    await app.logout();
  });

  await test.step('teacher reviews, comments and publishes', async () => {
    await app.login('teacher');
    await app.goto('/aulas');
    await page.getByRole('link', { name: CLASSROOM, exact: true }).click();
    await page.getByRole('link', { name: /^Revisar envíos/ }).click();
    await expect(page.getByRole('heading', { name: 'Revisar envíos', level: 1 })).toBeVisible();
    await page.getByRole('link', { name: word, exact: true }).click();
    await page.getByLabel('Nuevo comentario sobre esta entrada').fill(comment);
    await page.getByRole('button', { name: 'Enviar comentario' }).click();
    await expect(page.getByText(comment)).toBeVisible();
    await page.getByRole('button', { name: 'Publicar' }).click();
    // The review screen confirms and moves on to the next pending submission.
    await expect(page.getByText(`«${word}» publicada.`)).toBeVisible();
  });

  await test.step('teacher finds it in the classroom viewer', async () => {
    await page.getByRole('link', { name: `← ${CLASSROOM}` }).click();
    await page.getByRole('searchbox', { name: 'Buscar una palabra' }).fill(word.toUpperCase());
    await page.getByRole('button', { name: 'Buscar' }).click();
    await expect(page.getByRole('link', { name: word, exact: true })).toBeVisible();
    await app.logout();
  });

  await test.step('student 1 sees «Publicada» and the teacher comment', async () => {
    await app.login('student1');
    await app.goto('/mi-diccionario');
    await page.getByRole('link', { name: word, exact: true }).click();
    await expect(
      page.getByRole('region', { name: 'En el aula' }).getByText('Publicada'),
    ).toBeVisible();
    await expect(
      page.getByRole('region', { name: 'Comentarios del profesorado' }).getByText(comment),
    ).toBeVisible();
    await page
      .getByRole('navigation', { name: 'Principal' })
      .getByRole('link', { name: 'Comentarios' })
      .click();
    await expect(page.getByText(comment)).toBeVisible();
  });
});

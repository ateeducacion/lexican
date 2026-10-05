import { SQL } from 'bun';

/** Empty, unmigrated database inside the TEST_DATABASE_URL server, for tests of the migration and start-up paths. */
export async function emptyDatabase(serverUrl: string): Promise<{
  url: string;
  query: (q: string) => Promise<{ rows: Record<string, unknown>[] }>;
  drop: () => Promise<void>;
}> {
  const name = `lexican_p_${crypto.randomUUID().replaceAll('-', '').slice(0, 12)}`;
  const admin = new SQL({ url: serverUrl, max: 1 });
  await admin.unsafe(`create database ${name}`);
  const target = new URL(serverUrl);
  target.pathname = `/${name}`;
  const url = target.toString();
  return {
    url,
    async query(q) {
      const c = new SQL({ url, max: 1 });
      try {
        return { rows: await c.unsafe(q) };
      } finally {
        await c.close();
      }
    },
    async drop() {
      await admin.unsafe(`drop database ${name} with (force)`);
      await admin.close();
    },
  };
}

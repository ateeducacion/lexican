import pg from 'pg';

/** Empty, unmigrated database inside the TEST_DATABASE_URL server, for tests of the migration and start-up paths. */
export async function emptyDatabase(serverUrl: string): Promise<{
  url: string;
  query: (q: string) => Promise<pg.QueryResult>;
  drop: () => Promise<void>;
}> {
  const name = `lexican_p_${crypto.randomUUID().replaceAll('-', '').slice(0, 12)}`;
  const admin = new pg.Client({ connectionString: serverUrl });
  await admin.connect();
  await admin.query(`create database ${name}`);
  const target = new URL(serverUrl);
  target.pathname = `/${name}`;
  const url = target.toString();
  return {
    url,
    async query(q) {
      const c = new pg.Client({ connectionString: url });
      await c.connect();
      try {
        return await c.query(q);
      } finally {
        await c.end();
      }
    },
    async drop() {
      await admin.query(`drop database ${name} with (force)`);
      await admin.end();
    },
  };
}

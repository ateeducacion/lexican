import type { SchoolYearConfig } from '@lexican/core';
import { z } from 'zod';

const bool = z
  .enum(['true', 'false', '1', '0', ''])
  .default('false')
  .transform((v) => v === 'true' || v === '1');
const optionalUrl = z.preprocess((v) => (v === '' ? undefined : v), z.url().optional());

const Env = z.object({
  NODE_ENV: z.string().default('development'),
  DATABASE_URL: z.string().min(1),
  PORT: z.coerce.number().int().min(1).max(65535).default(3000),
  HOST: z.string().default('0.0.0.0'),
  PUBLIC_URL: z.url(),
  SESSION_TTL_HOURS: z.coerce
    .number()
    .positive()
    .max(24 * 30)
    .default(8),
  MEDIA_DIR: z.string().min(1),
  MAX_UPLOAD_MB: z.coerce.number().positive().max(100).default(10),
  CAS_BASE_URL: optionalUrl,
  CAUCE_URL: optionalUrl,
  CAUCE_TOKEN: z.string().optional(),
  CAS_ALLOWED_SLO_HOSTS: z.string().default(''),
  AUTH_DEV_LOGIN: bool,
  SCHOOL_YEAR_START: z
    .string()
    .regex(/^\d{2}-\d{2}$/)
    .default('08-30'),
  WEB_DIST: z.string().optional(),
  LOG_LEVEL: z.enum(['fatal', 'error', 'warn', 'info', 'debug', 'trace', 'silent']).default('info'),
  /** Fastify `trustProxy`: `true` or a comma list of proxy IPs/CIDRs. */
  TRUST_PROXY: z.string().default(''),
  MIGRATE_ON_START: bool,
});

export interface Config {
  production: boolean;
  databaseUrl: string;
  port: number;
  host: string;
  publicUrl: string;
  publicOrigin: string;
  secureCookies: boolean;
  sessionTtlMs: number;
  mediaDir: string;
  maxUploadBytes: number;
  cas: { baseUrl: string; serviceUrl: string; sloHosts: string[] } | null;
  cauce: { url: string; token: string } | null;
  devLogin: boolean;
  schoolYear: SchoolYearConfig;
  webDist: string | null;
  logLevel: string;
  trustProxy: boolean | string;
  migrateOnStart: boolean;
}

/** Parse and validate the environment; throws a readable error (never echoing values) when invalid. */
export function loadConfig(env: Record<string, string | undefined> = process.env): Config {
  const parsed = Env.safeParse(env);
  if (!parsed.success) {
    const fields = parsed.error.issues.map((i) => `${i.path.join('.')}: ${i.message}`).join('; ');
    throw new Error(`Invalid configuration: ${fields}`);
  }
  const e = parsed.data;
  const production = e.NODE_ENV === 'production';
  if (e.AUTH_DEV_LOGIN && production)
    throw new Error('AUTH_DEV_LOGIN=true is not allowed with NODE_ENV=production.');
  if (e.CAS_BASE_URL && (!e.CAUCE_URL || !e.CAUCE_TOKEN))
    throw new Error(
      'CAS is enabled (CAS_BASE_URL) but CAUCE_URL/CAUCE_TOKEN are missing: refusing to start.',
    );

  const publicUrl = e.PUBLIC_URL.replace(/\/+$/, '');
  const [startMonth, startDay] = e.SCHOOL_YEAR_START.split('-').map(Number) as [number, number];
  const tp = e.TRUST_PROXY.trim();
  return {
    production,
    databaseUrl: e.DATABASE_URL,
    port: e.PORT,
    host: e.HOST,
    publicUrl,
    publicOrigin: new URL(publicUrl).origin,
    secureCookies: publicUrl.startsWith('https:'),
    sessionTtlMs: e.SESSION_TTL_HOURS * 3600_000,
    mediaDir: e.MEDIA_DIR,
    maxUploadBytes: Math.round(e.MAX_UPLOAD_MB * 1024 * 1024),
    cas: e.CAS_BASE_URL
      ? {
          baseUrl: e.CAS_BASE_URL.replace(/\/+$/, ''),
          serviceUrl: `${publicUrl}/api/auth/cas/callback`,
          sloHosts: e.CAS_ALLOWED_SLO_HOSTS.split(',')
            .map((s) => s.trim())
            .filter(Boolean),
        }
      : null,
    cauce: e.CAUCE_URL && e.CAUCE_TOKEN ? { url: e.CAUCE_URL, token: e.CAUCE_TOKEN } : null,
    devLogin: e.AUTH_DEV_LOGIN,
    schoolYear: { startMonth, startDay },
    webDist: e.WEB_DIST || null,
    logLevel: e.LOG_LEVEL,
    trustProxy: tp === '' ? false : tp === 'true' ? true : tp,
    migrateOnStart: e.MIGRATE_ON_START,
  };
}

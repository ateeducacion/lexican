import { isIP } from 'node:net';
import type { SchoolYearConfig } from '@lexican/core';
import { isCasPath } from '@lexican/http';
import { z } from 'zod';

/** The public test CAS (https://www.casserverpac4j.dev, accounts documented on its login page). Never in production. */
export const TEST_CAS_URL = 'https://www.casserverpac4j.dev';
const TEST_CAS_HOSTS = new Set(['www.casserverpac4j.dev', 'casserverpac4j.dev']);

const bool = z
  .enum(['true', 'false', '1', '0', ''])
  .default('false')
  .transform((v) => v === 'true' || v === '1');
const blankToUndefined = (v: unknown) => (v === '' ? undefined : v);
const optionalUrl = z.preprocess(blankToUndefined, z.url().optional());
const casPath = (d: string) =>
  z.preprocess(
    blankToUndefined,
    z.string().refine(isCasPath, 'must be an absolute path').default(d),
  );

const Env = z.object({
  /**
   * Deployment profile, decided by configuration only (never by hostname or NODE_ENV):
   * - production (default, so an unconfigured image is safe): institutional CAS + CAUCE; no test CAS, no demo data.
   * - local: Docker/dev on a workstation; the public test CAS and fixture profiles by default, demo seed allowed.
   * - test: automated tests; nothing enabled unless set.
   */
  APP_ENV: z.enum(['production', 'local', 'test']).default('production'),
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
  MEDIA_QUOTA_MB_PER_DAY: z.coerce.number().positive().max(100_000).default(100),
  /** Password-login attempts per minute and IP (dev/test provider only). */
  LOGIN_RATE_LIMIT: z.coerce.number().int().min(1).max(10_000).default(10),
  CAS_URL: optionalUrl,
  CAS_LOGIN_PATH: casPath('/login'),
  CAS_VALIDATE_PATH: casPath('/p3/serviceValidate'),
  CAS_LOGOUT_PATH: casPath('/logout'),
  /** Who resolves a validated CAS subject: the institutional directory, or the fixture profiles of the test CAS. */
  CAS_PROFILES: z.preprocess(blankToUndefined, z.enum(['cauce', 'test']).optional()),
  CAS_BASE_URL: z.string().optional(),
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
  /** Reverse proxies to trust for client IPs: a hop count or a comma list of IPs/CIDRs (never `true`). */
  TRUST_PROXY: z.string().default(''),
  MIGRATE_ON_START: bool,
});

export type AppEnv = 'production' | 'local' | 'test';

export interface CasSettings {
  url: string;
  loginPath: string;
  validatePath: string;
  logoutPath: string;
  profiles: 'cauce' | 'test';
  sloHosts: string[];
}

export interface Config {
  appEnv: AppEnv;
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
  cas: CasSettings | null;
  cauce: { url: string; token: string } | null;
  devLogin: boolean;
  schoolYear: SchoolYearConfig;
  webDist: string | null;
  logLevel: string;
  /** `false`, number of trusted hops, or trusted proxy IPs/CIDRs. */
  trustProxy: false | number | string[];
  mediaQuotaBytesPerDay: number;
  migrateOnStart: boolean;
  loginRateLimit: number;
}

/** Parse and validate the environment; throws a readable error (never echoing values) when invalid or unsafe. */
export function loadConfig(env: Record<string, string | undefined> = process.env): Config {
  const parsed = Env.safeParse(env);
  if (!parsed.success) {
    const fields = parsed.error.issues.map((i) => `${i.path.join('.')}: ${i.message}`).join('; ');
    throw new Error(`Invalid configuration: ${fields}`);
  }
  const e = parsed.data;
  const production = e.APP_ENV === 'production';
  if (e.CAS_BASE_URL !== undefined)
    throw new Error(
      'CAS_BASE_URL was renamed to CAS_URL (with CAS_LOGIN_PATH, CAS_VALIDATE_PATH, CAS_LOGOUT_PATH).',
    );

  const casUrl = e.CAS_URL ?? (e.APP_ENV === 'local' ? TEST_CAS_URL : undefined);
  const profiles = e.CAS_PROFILES ?? (production ? 'cauce' : 'test');
  const casUrlParsed = casUrl ? new URL(casUrl) : null;
  if (casUrlParsed && (casUrlParsed.search || casUrlParsed.hash))
    throw new Error('CAS_URL must not contain a query or fragment.');
  const sloHosts = e.CAS_ALLOWED_SLO_HOSTS.split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  const publicUrl = e.PUBLIC_URL.replace(/\/+$/, '');

  if (production) {
    if (e.AUTH_DEV_LOGIN)
      throw new Error('AUTH_DEV_LOGIN=true is not allowed with APP_ENV=production.');
    if (!casUrlParsed) throw new Error('APP_ENV=production requires CAS_URL (institutional CAS).');
    if (profiles !== 'cauce') throw new Error('APP_ENV=production only allows CAS_PROFILES=cauce.');
    if (TEST_CAS_HOSTS.has(casUrlParsed.hostname))
      throw new Error('The public test CAS is not allowed with APP_ENV=production.');
    if (casUrlParsed.protocol !== 'https:')
      throw new Error('CAS_URL must use https in production.');
    if (!publicUrl.startsWith('https:'))
      throw new Error('PUBLIC_URL must use https in production.');
    if (sloHosts.length === 0)
      throw new Error(
        'CAS_ALLOWED_SLO_HOSTS is required in production when CAS is enabled (back-channel logout source IPs).',
      );
  }
  if (casUrlParsed && profiles === 'cauce' && (!e.CAUCE_URL || !e.CAUCE_TOKEN))
    throw new Error(
      'CAS with CAS_PROFILES=cauce requires CAUCE_URL and CAUCE_TOKEN: refusing to start.',
    );

  const [startMonth, startDay] = e.SCHOOL_YEAR_START.split('-').map(Number) as [number, number];
  return {
    appEnv: e.APP_ENV,
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
    cas: casUrlParsed
      ? {
          url: casUrlParsed.href.replace(/\/+$/, ''),
          loginPath: e.CAS_LOGIN_PATH,
          validatePath: e.CAS_VALIDATE_PATH,
          logoutPath: e.CAS_LOGOUT_PATH,
          profiles,
          sloHosts,
        }
      : null,
    cauce: e.CAUCE_URL && e.CAUCE_TOKEN ? { url: e.CAUCE_URL, token: e.CAUCE_TOKEN } : null,
    devLogin: e.AUTH_DEV_LOGIN,
    schoolYear: { startMonth, startDay },
    webDist: e.WEB_DIST || null,
    logLevel: e.LOG_LEVEL,
    trustProxy: parseTrustProxy(e.TRUST_PROXY.trim()),
    mediaQuotaBytesPerDay: Math.round(e.MEDIA_QUOTA_MB_PER_DAY * 1024 * 1024),
    migrateOnStart: e.MIGRATE_ON_START,
    loginRateLimit: e.LOGIN_RATE_LIMIT,
  };
}

const isCidrOrIp = (s: string): boolean => {
  const [ip, bits, ...rest] = s.split('/');
  const v = isIP(ip ?? '');
  if (!v || rest.length) return false;
  return bits === undefined || (/^\d{1,3}$/.test(bits) && Number(bits) <= (v === 4 ? 32 : 128));
};

/**
 * TRUST_PROXY: empty (no proxy), a hop count, or IPs/CIDRs. `true` would trust any X-Forwarded-For and let
 * clients spoof their IP (rate limits, SLO allowlist), so it is refused (SEC-007).
 */
function parseTrustProxy(tp: string): false | number | string[] {
  if (tp === '') return false;
  if (/^\d{1,2}$/.test(tp)) return Number(tp);
  const list = tp
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  if (list.length === 0 || !list.every(isCidrOrIp))
    throw new Error(
      'TRUST_PROXY must be a hop count or a comma list of proxy IPs/CIDRs (true is not allowed).',
    );
  return list;
}

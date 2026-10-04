import { isIP } from 'node:net';
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
  MEDIA_QUOTA_MB_PER_DAY: z.coerce.number().positive().max(100_000).default(100),
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
  /** Reverse proxies to trust for client IPs: a hop count or a comma list of IPs/CIDRs (never `true`). */
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
  /** `false`, number of trusted hops, or trusted proxy IPs/CIDRs. */
  trustProxy: false | number | string[];
  mediaQuotaBytesPerDay: number;
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

  const sloHosts = e.CAS_ALLOWED_SLO_HOSTS.split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  if (production && e.CAS_BASE_URL && sloHosts.length === 0)
    throw new Error(
      'CAS_ALLOWED_SLO_HOSTS is required in production when CAS is enabled (back-channel logout source IPs).',
    );

  const publicUrl = e.PUBLIC_URL.replace(/\/+$/, '');
  const [startMonth, startDay] = e.SCHOOL_YEAR_START.split('-').map(Number) as [number, number];
  const trustProxy = parseTrustProxy(e.TRUST_PROXY.trim());
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
          sloHosts,
        }
      : null,
    cauce: e.CAUCE_URL && e.CAUCE_TOKEN ? { url: e.CAUCE_URL, token: e.CAUCE_TOKEN } : null,
    devLogin: e.AUTH_DEV_LOGIN,
    schoolYear: { startMonth, startDay },
    webDist: e.WEB_DIST || null,
    logLevel: e.LOG_LEVEL,
    trustProxy,
    mediaQuotaBytesPerDay: Math.round(e.MEDIA_QUOTA_MB_PER_DAY * 1024 * 1024),
    migrateOnStart: e.MIGRATE_ON_START,
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

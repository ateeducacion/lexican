import { DomainError, mediaKindOf, type MediaKind, type MediaView } from '@lexican/core';
import {
  dictionaries,
  dictionaryMemberships,
  entries,
  entryRevisions,
  entrySenses,
  mediaAssets,
  senseMedia,
  submissions,
} from '@lexican/db';
import { and, eq, gte, isNull, sql } from 'drizzle-orm';
import { fileTypeFromBuffer } from 'file-type';
import { dictionaryAccess } from './access.ts';
import { notFound, requireUser, type Actor, type Deps } from './context.ts';

const DEFAULT_MAX_BYTES = 10 * 1024 * 1024;
/** Sniffed types stored under their canonical name. */
const ALIASES: Record<string, string> = { 'audio/x-m4a': 'audio/mp4' };
/** Containers that may hold only audio; the sniffer cannot tell, so the slot being filled decides. */
const AUDIO_CONTAINERS: Record<string, string> = {
  'video/webm': 'audio/webm',
  'video/mp4': 'audio/mp4',
};

/** Canonical stored type and kind of uploaded bytes, decided by content (never by name or client Content-Type). */
export function classifyMedia(
  sniffed: string,
  slot?: MediaKind,
): { mime: string; kind: MediaKind } | null {
  let mime = sniffed.split(';')[0]!.trim().toLowerCase();
  mime = ALIASES[mime] ?? mime;
  if (slot === 'audio' && AUDIO_CONTAINERS[mime]) mime = AUDIO_CONTAINERS[mime]!;
  const kind = mediaKindOf(mime);
  return kind ? { mime, kind } : null;
}
const DEFAULT_MAX_BYTES_PER_DAY = 100 * 1024 * 1024;
const DAY_MS = 24 * 3600_000;

async function sha256Hex(bytes: Uint8Array): Promise<string> {
  const buf = await crypto.subtle.digest('SHA-256', bytes as BufferSource);
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

/**
 * Uploads are identified by content, never by the client's name or Content-Type (§45, RULE-079/080 fixed).
 * Stored under a random key; the original name is metadata only.
 */
export function mediaServices(deps: Deps) {
  const { db } = deps;
  const maxBytes = deps.maxUploadBytes ?? DEFAULT_MAX_BYTES;
  const maxPerDay = deps.maxUploadBytesPerDay ?? DEFAULT_MAX_BYTES_PER_DAY;

  return {
    async uploadMedia(
      actor: Actor | null,
      file: { bytes: Uint8Array; name: string; kind?: MediaKind },
    ): Promise<MediaView> {
      const a = requireUser(actor);
      if (file.bytes.byteLength === 0)
        throw new DomainError('validation', 'El archivo está vacío.');
      if (file.bytes.byteLength > maxBytes)
        throw new DomainError(
          'validation',
          `El archivo supera el máximo de ${Math.round(maxBytes / 1024 / 1024)} MB.`,
        );
      const type = await fileTypeFromBuffer(file.bytes);
      const media = type ? classifyMedia(type.mime, file.kind) : null;
      if (!type || !media)
        throw new DomainError(
          'validation',
          'Formato no admitido. Usa una imagen (PNG, JPG, WebP, GIF), un audio (MP3, M4A, OGG, Opus, WAV, WebM) o un vídeo (MP4, WebM).',
        );
      const { mime, kind } = media;
      // Per-user rolling quota (SEC-004). ponytail: check-then-insert, concurrent uploads may overshoot by one file.
      const [usage] = await db
        .select({ used: sql<number>`coalesce(sum(${mediaAssets.byteSize}), 0)::float8` })
        .from(mediaAssets)
        .where(
          and(
            eq(mediaAssets.createdBy, a.userId),
            gte(mediaAssets.createdAt, new Date(deps.clock().getTime() - DAY_MS)),
          ),
        );
      if (Number(usage?.used ?? 0) + file.bytes.byteLength > maxPerDay)
        throw new DomainError(
          'validation',
          `Has alcanzado el límite de subida de ${Math.round(maxPerDay / 1024 / 1024)} MB en 24 horas. Inténtalo más tarde.`,
        );
      const storageKey = `${crypto.randomUUID()}.${type.ext}`;
      const originalName = file.name.replace(/[^\p{L}\p{N} ._-]/gu, '_').slice(0, 120);
      const sha256 = await sha256Hex(file.bytes);
      // Bytes first, row second: a row never points to a missing file. If the row fails, compensate by deleting
      // the bytes; a crash in between leaves an orphan file that sweepOrphans() removes at the next start.
      await deps.media.put(storageKey, file.bytes, mime);
      let row: typeof mediaAssets.$inferSelect | undefined;
      try {
        [row] = await db
          .insert(mediaAssets)
          .values({
            kind,
            mime,
            byteSize: file.bytes.byteLength,
            sha256,
            storageKey,
            originalName,
            createdBy: a.userId,
          })
          .returning();
      } catch (e) {
        await deps.media.delete(storageKey).catch(() => undefined);
        throw e;
      }
      return { id: row!.id, kind, mime: row!.mime, originalName, url: deps.mediaUrl(row!.id) };
    },

    /**
     * A media asset if the actor uploaded it or can read an entry using it (RULE-236 fixed). Authorization runs on
     * metadata first; only then is the storage opened, and the returned Blob is read lazily (ranges, streaming).
     */
    async readMedia(
      actor: Actor | null,
      mediaId: string,
    ): Promise<{ blob: Blob; mime: string; name: string; size: number }> {
      const a = requireUser(actor);
      const [asset] = await db.select().from(mediaAssets).where(eq(mediaAssets.id, mediaId));
      if (!asset) throw notFound('El archivo');
      if (asset.createdBy !== a.userId) {
        // Hidden or deleted entries and hidden senses only count for people who can edit that dictionary (SEC-009).
        const uses = await db
          .selectDistinct({
            dictionaryId: entries.dictionaryId,
            visible: sql<boolean>`not (${entries.hidden} or ${entrySenses.hidden} or ${entries.deletedAt} is not null)`,
          })
          .from(senseMedia)
          .innerJoin(entrySenses, eq(entrySenses.id, senseMedia.senseId))
          .innerJoin(entries, eq(entries.id, entrySenses.entryId))
          .innerJoin(dictionaries, eq(dictionaries.id, entries.dictionaryId))
          .where(and(eq(senseMedia.mediaId, mediaId), isNull(dictionaries.deletedAt)));
        let allowed = false;
        for (const u of uses) {
          const acc = await dictionaryAccess(deps, a, u.dictionaryId).catch(() => null);
          if (acc?.canEdit || (acc?.canRead && u.visible)) {
            allowed = true;
            break;
          }
        }
        // Teachers reviewing a submission see media referenced only by the snapshot.
        if (!allowed) allowed = await usedInReviewableSubmission(deps, a, mediaId);
        if (!allowed) throw notFound('El archivo');
      }
      const blob = await deps.media.open(asset.storageKey);
      if (!blob) throw notFound('El archivo');
      return { blob, mime: asset.mime, name: asset.originalName, size: blob.size };
    },

    /**
     * Delete stored files no row references (left by a crash between the file write and the insert). Run at start-up,
     * before serving: with uploads in flight, a just-written file would look orphaned.
     * ponytail: single-instance assumption; with several API replicas, add an age threshold to the storage keys.
     */
    async sweepOrphans(): Promise<number> {
      const known = new Set(
        (await db.select({ k: mediaAssets.storageKey }).from(mediaAssets)).map((r) => r.k),
      );
      const orphans = (await deps.media.keys()).filter((k) => !known.has(k));
      for (const k of orphans) await deps.media.delete(k);
      return orphans.length;
    },
  };
}

async function usedInReviewableSubmission(
  deps: Deps,
  actor: Actor,
  mediaId: string,
): Promise<boolean> {
  const rows = await deps.db
    .select({ id: submissions.id })
    .from(submissions)
    .innerJoin(entryRevisions, eq(entryRevisions.id, submissions.revisionId))
    .innerJoin(
      dictionaryMemberships,
      and(
        eq(dictionaryMemberships.dictionaryId, submissions.classroomId),
        eq(dictionaryMemberships.userId, actor.userId),
        eq(dictionaryMemberships.role, 'teacher'),
        eq(dictionaryMemberships.active, true),
      ),
    )
    // Structural match on the snapshot's media ids, never a text search (SEC-001).
    .where(
      sql`${entryRevisions.snapshot}->'senses' @> ${JSON.stringify([{ media: [{ id: mediaId }] }])}::jsonb`,
    )
    .limit(1);
  return rows.length > 0;
}

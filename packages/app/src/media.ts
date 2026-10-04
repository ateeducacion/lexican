import { DomainError, mediaKindOf, type MediaView } from '@lexican/core';
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
import { and, eq, isNull, sql } from 'drizzle-orm';
import { fileTypeFromBuffer } from 'file-type';
import { dictionaryAccess } from './access.ts';
import { notFound, requireUser, type Actor, type Deps } from './context.ts';

const DEFAULT_MAX_BYTES = 10 * 1024 * 1024;

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

  return {
    async uploadMedia(actor: Actor | null, file: { bytes: Uint8Array; name: string }): Promise<MediaView> {
      const a = requireUser(actor);
      if (file.bytes.byteLength === 0) throw new DomainError('validation', 'El archivo está vacío.');
      if (file.bytes.byteLength > maxBytes)
        throw new DomainError('validation', `El archivo supera el máximo de ${Math.round(maxBytes / 1024 / 1024)} MB.`);
      const type = await fileTypeFromBuffer(file.bytes);
      const kind = type ? mediaKindOf(type.mime) : null;
      if (!type || !kind)
        throw new DomainError('validation', 'Formato no admitido. Usa una imagen (PNG, JPG, WebP, GIF), un audio (MP3, OGG, WAV) o un vídeo (MP4, WebM).');
      const storageKey = `${crypto.randomUUID()}.${type.ext}`;
      await deps.media.put(storageKey, file.bytes, type.mime);
      const originalName = file.name.replace(/[^\p{L}\p{N} ._-]/gu, '_').slice(0, 120);
      const [row] = await db
        .insert(mediaAssets)
        .values({ kind, mime: type.mime, byteSize: file.bytes.byteLength, sha256: await sha256Hex(file.bytes), storageKey, originalName, createdBy: a.userId })
        .returning();
      return { id: row!.id, kind, mime: row!.mime, originalName, url: deps.mediaUrl(row!.id) };
    },

    /** Bytes of a media asset if the actor uploaded it or can read an entry using it (RULE-236 fixed). */
    async readMedia(actor: Actor | null, mediaId: string): Promise<{ bytes: Uint8Array; mime: string; name: string }> {
      const a = requireUser(actor);
      const [asset] = await db.select().from(mediaAssets).where(eq(mediaAssets.id, mediaId));
      if (!asset) throw notFound('El archivo');
      if (asset.createdBy !== a.userId) {
        const uses = await db
          .selectDistinct({ dictionaryId: entries.dictionaryId })
          .from(senseMedia)
          .innerJoin(entrySenses, eq(entrySenses.id, senseMedia.senseId))
          .innerJoin(entries, eq(entries.id, entrySenses.entryId))
          .innerJoin(dictionaries, eq(dictionaries.id, entries.dictionaryId))
          .where(and(eq(senseMedia.mediaId, mediaId), isNull(dictionaries.deletedAt)));
        let allowed = false;
        for (const u of uses) {
          const acc = await dictionaryAccess(deps, a, u.dictionaryId).catch(() => null);
          if (acc?.canRead) {
            allowed = true;
            break;
          }
        }
        // Teachers reviewing a submission see media referenced only by the snapshot.
        if (!allowed) allowed = await usedInReviewableSubmission(deps, a, mediaId);
        if (!allowed) throw notFound('El archivo');
      }
      const bytes = await deps.media.get(asset.storageKey);
      if (!bytes) throw notFound('El archivo');
      return { bytes, mime: asset.mime, name: asset.originalName };
    },
  };
}

async function usedInReviewableSubmission(deps: Deps, actor: Actor, mediaId: string): Promise<boolean> {
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
    .where(sql`${entryRevisions.snapshot}::text like ${'%' + mediaId + '%'}`)
    .limit(1);
  return rows.length > 0;
}

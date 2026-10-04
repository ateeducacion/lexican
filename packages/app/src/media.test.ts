import { readFileSync } from 'node:fs';
import { fileTypeFromBuffer } from 'file-type';
import { describe, expect, it } from 'vitest';
import { classifyMedia } from './media.ts';

const tone = (ext: string) =>
  new Uint8Array(readFileSync(new URL(`../../../fixtures/media/tone.${ext}`, import.meta.url)));
const sniff = async (ext: string, slot?: 'audio' | 'video') => {
  const t = await fileTypeFromBuffer(tone(ext));
  return t ? classifyMedia(t.mime, slot) : null;
};

describe('media type by content', () => {
  it('accepts the audio phones record and browsers play, under canonical types', async () => {
    expect(await sniff('mp3')).toEqual({ mime: 'audio/mpeg', kind: 'audio' });
    expect(await sniff('m4a')).toEqual({ mime: 'audio/mp4', kind: 'audio' });
    // OGG Opus is sniffed as "audio/ogg; codecs=opus": parameters are dropped.
    expect(await sniff('opus')).toEqual({ mime: 'audio/ogg', kind: 'audio' });
    expect(await sniff('ogg')).toEqual({ mime: 'audio/ogg', kind: 'audio' });
    expect(await sniff('wav')).toEqual({ mime: 'audio/wav', kind: 'audio' });
  });

  it('lets the slot decide audio vs video only for containers that hold either', async () => {
    expect(await sniff('webm')).toEqual({ mime: 'video/webm', kind: 'video' });
    expect(await sniff('webm', 'audio')).toEqual({ mime: 'audio/webm', kind: 'audio' });
    expect(classifyMedia('video/mp4', 'audio')).toEqual({ mime: 'audio/mp4', kind: 'audio' });
    // The slot never turns an image or an unsupported container into something else.
    expect(classifyMedia('image/png', 'audio')).toEqual({ mime: 'image/png', kind: 'image' });
    expect(await sniff('3gp', 'audio')).toBeNull();
    expect(classifyMedia('application/x-php', 'audio')).toBeNull();
  });
});

import { useEffect, useState } from 'react';
import type { MediaView } from '@lexican/core';
import { getApi } from '../api/index.ts';

/** Renders an image, audio or video; resolves the source through the active adapter. */
export function Media({ media, alt }: { media: MediaView; alt: string }) {
  const [src, setSrc] = useState<string | null>(null);
  useEffect(() => {
    let live = true;
    getApi()
      .then((api) => api.mediaSrc(media))
      .then((s) => live && setSrc(s))
      .catch(() => live && setSrc(null));
    return () => {
      live = false;
    };
  }, [media]);
  if (!src) return <span className="muted small">{media.originalName || 'Archivo'}</span>;
  if (media.kind === 'image') return <img src={src} alt={alt} loading="lazy" />;
  if (media.kind === 'audio') return <audio controls src={src} aria-label={alt} preload="none" />;
  return <video controls src={src} aria-label={alt} preload="metadata" style={{ maxHeight: 240 }} />;
}

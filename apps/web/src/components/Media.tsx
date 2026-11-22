import type { MediaView } from '@lexican/core';
import { useEffect, useState } from 'react';

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
  // Uploaded clips come without caption files; the sense's definition and example are their text alternative.
  if (media.kind === 'audio')
    // eslint-disable-next-line jsx-a11y/media-has-caption -- no caption track exists for user uploads (see above).
    return <audio controls src={src} aria-label={alt} preload="none" />;
  return (
    // eslint-disable-next-line jsx-a11y/media-has-caption -- no caption track exists for user uploads (see above).
    <video controls src={src} aria-label={alt} preload="metadata" style={{ maxHeight: 240 }} />
  );
}

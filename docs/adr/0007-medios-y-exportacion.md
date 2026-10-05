# ADR 0007 — Medios en sistema de ficheros y exportación sin servicio PDF

- Estado: aceptada
- Fecha: 2026-10-05

## Decisión

- `MediaStorage` con una implementación de sistema de ficheros (`MEDIA_DIR`, claves `uuid.ext` repartidas por los dos
  primeros caracteres, escritura atómica) y otra de Blobs en IndexedDB para la demo. `open` devuelve un Blob perezoso;
  el servidor usa `openAsBlob`, sin cargar el archivo entero. SQL guarda solo metadatos y referencias.
- Tipo detectado por contenido con `file-type` 22 (nunca por el nombre ni el `Content-Type` del cliente); lista
  permitida de imagen, audio y vídeo; límite de tamaño en el servidor; sin SVG. Se sirve por `/media/:id` con
  autorización, `nosniff` y `Content-Disposition` saneado.
- `/media/:id` admite rangos HTTP (`206`/`416`) y `HEAD`. Se autorizan los metadatos antes de abrir el archivo;
  un acceso no autorizado responde 404 sin revelar su tamaño.
- La demo guarda medios en `lexican-demo-media`, con `ArrayBuffer` cuando IndexedDB no acepta Blobs. El Worker copia
  los bytes de la antigua tabla `media_blobs` antes de aplicar la migración que la elimina.
- Límites configurables en el servidor (por defecto 10 MB por archivo y 100 MB por usuario al día); el cuerpo se
  limita mientras llega. Primero se guardan los bytes y después los metadatos; si falla SQL se eliminan los bytes.
  Al arrancar se limpian los huérfanos.
- PDF mediante impresión del navegador (CSS `@media print`) desde `/diccionarios/:id/imprimir`. CSV, JSON y DMLex JSON
  se generan en el cliente con `packages/core/src/export.ts`. Se elimina dompdf y no hay servicio PDF.
- Sin miniaturas de vídeo (FFmpeg) ni recodificación de imágenes: el navegador escala las imágenes.

## Consecuencias

La maquetación del PDF depende del navegador; se cubre con la vista de impresión. Si aparece un requisito de cabeceras
repetidas o paginación exacta, se evaluará `pdfmake` cargado bajo demanda.

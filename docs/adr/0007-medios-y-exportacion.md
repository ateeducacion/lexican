# ADR 0007 — Medios en sistema de ficheros y exportación sin servicio PDF

- Estado: aceptada
- Fecha: 2026-10-04

## Decisión

- `MediaStorage` con una implementación de sistema de ficheros (`MEDIA_DIR`, claves `uuid.ext` repartidas por los dos
  primeros caracteres, escritura atómica) y otra en `media_blobs` para la demo. Preparado para un *adapter* S3 futuro.
- Tipo detectado por contenido con `file-type` 22 (nunca por el nombre ni el `Content-Type` del cliente); lista
  permitida de imagen, audio y vídeo; límite de tamaño en el servidor; sin SVG. Se sirve por `/media/:id` con
  autorización, `nosniff` y `Content-Disposition` saneado.
- PDF mediante impresión del navegador (CSS `@media print`) desde `/diccionarios/:id/imprimir`. CSV, JSON y DMLex JSON
  se generan en el cliente con `packages/core/src/export.ts`. Se elimina dompdf y no hay servicio PDF.
- Sin miniaturas de vídeo (FFmpeg) ni recodificación de imágenes: el navegador escala las imágenes.

## Consecuencias

La maquetación del PDF depende del navegador; se cubre con la vista de impresión. Si aparece un requisito de cabeceras
repetidas o paginación exacta, se evaluará `pdfmake` cargado bajo demanda.

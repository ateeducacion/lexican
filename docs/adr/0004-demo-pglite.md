# ADR 0004 — Demo en GitHub Pages con PGlite persistido en IndexedDB

- Estado: aceptada
- Fecha: 2026-10-04

## Decisión

- `vite build --mode demo` genera `apps/web/dist-demo` con `base: '/lexican/'` y enrutado por *hash* (no hace falta
  `404.html`). El modo se decide en el build (`import.meta.env.MODE`), nunca por el nombre del host.
- PGlite 0.5.8 en el hilo principal con `idb://lexican-demo-v1`. Se carga con `import()` dinámico solo en la demo: la
  pantalla de acceso se pinta antes de descargar los ≈5 MB comprimidos de WASM, y el build de producción no contiene
  PGlite (`scripts/check-dist.mjs` lo comprueba).
- Primera visita: crear base → migrar → sembrar datos ficticios (`packages/app/src/demo-seed.ts`) → marcar
  `app_settings.demo_seed_version`. Recargas posteriores conservan los datos.
- «Restablecer datos de demostración» cierra PGlite, borra la base IndexedDB `/pglite/lexican-demo-v1` y recarga.
- La demo ejecuta **los mismos servicios** (`createServices`) que la API: mismas reglas y la misma autorización.
- El acceso de demo no es una frontera de seguridad: solo elige qué usuario ficticio actúa.
- No se usa `relaxedDurability` (perdió escrituras al recargar en las pruebas).
- Medios subidos en la demo: tabla `media_blobs` (bytea), máximo 2 MB por archivo.

## Alternativas descartadas

- *PGliteWorker* multi-pestaña: añade un worker y un *cast* de tipos; innecesario para una demo de un usuario.
- Simular la API con `fetch` interceptado o un *service worker*: dos implementaciones de las reglas.

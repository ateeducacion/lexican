# ADR 0004 — Demo en GitHub Pages con PGlite persistido en IndexedDB

- Estado: aceptada
- Fecha: 2026-10-05

## Decisión

- `vite build --mode demo` genera `apps/web/dist-demo` con `base: '/lexican/'` y enrutado por *hash* (no hace falta
  `404.html`). El modo se decide en el build (`import.meta.env.MODE`), nunca por el nombre del host.
- PGlite en un Web Worker dedicado con `idb://lexican-demo-v2`. Se carga solo en la demo: la
  pantalla de acceso se pinta antes de descargar los ≈5 MB comprimidos de WASM, y el build de producción no contiene
  PGlite (`scripts/check-dist.mjs` lo comprueba).
- Primera visita: crear base → migrar → sembrar datos ficticios (`packages/app/src/demo-seed.ts`) → marcar
  `app_settings.demo_seed_version`. Recargas posteriores conservan los datos.
- «Restablecer datos de demostración» detiene y espera las peticiones, cierra PGlite y el almacén de medios, borra
  solo sus bases IndexedDB y la sesión, y recarga.
- El Worker ejecuta **la misma API Hono** que el servidor, con los mismos servicios, reglas y autorización. React usa
  el mismo cliente API; solo cambia el transporte, HTTP o mensajes al Worker ([ADR 0009](0009-hono-bun-worker.md)).
- El Worker mantiene el Web Lock `lexican-demo-database`: una segunda pestaña del mismo perfil no abre la base.
- El acceso de demo usa solo cuentas ficticias, sin CAS; no es una frontera de seguridad.
- No se usa `relaxedDurability` (perdió escrituras al recargar en las pruebas).
- Medios en `lexican-demo-media` (Blobs en IndexedDB, con alternativa `ArrayBuffer`); SQL guarda solo metadatos.
  Límites: 10 MB por archivo y 100 MB por usuario al día. Persistencia y migración de los medios anteriores:
  [ADR 0007](0007-medios-y-exportacion.md). Esquema y migraciones SQL comunes: [ADR 0003](0003-drizzle-pglite.md).

## Alternativas descartadas

- *PGliteWorker* multi-pestaña: un Worker propio ejecuta también la API y permite limitar la demo a una pestaña.
- Llamar directamente a servicios desde React o simular otra API: dejaría sin comprobar el contrato HTTP compartido.

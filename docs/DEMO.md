# Demo en GitHub Pages

<https://ateeducacion.github.io/lexican/>

La demo es el **mismo** frontend React compilado en modo `demo`. No tiene servidor: el navegador ejecuta los servicios
de `packages/app` sobre PGlite (PostgreSQL compilado a WebAssembly) con el mismo esquema y las mismas migraciones que
producción. Las reglas de negocio y la autorización son las mismas. Decisión: [ADR 0004](adr/0004-demo-pglite.md).

## Cuentas

Aparecen debajo del formulario de acceso, cada una con un botón «Entrar como…». Fuente:
`packages/core/src/demo.ts`.

| Cuenta | Correo | Contraseña | Rol |
|---|---|---|---|
| Profesora (Yaiza Tutoriales) | `profesor@ejemplo.com` | `profesor` | profesorado |
| Alumno 1 (Alumno Padrón Armas) | `alumno1@ejemplo.com` | `alumno1` | alumnado |
| Alumna 2 (Alumna Armas Padrón) | `alumno2@ejemplo.com` | `alumno2` | alumnado |
| Administración | `admin@ejemplo.com` | `admin` | administración |

Todos los nombres, correos y el centro son ficticios.

## Datos sembrados

`packages/app/src/demo-seed.ts` crea los datos **a través de los servicios de aplicación**, así que cumplen todas las
reglas:

- Vocabularios controlados (categoría gramatical, género, número, lenguas, temáticas, niveles, materias…).
- Un centro ficticio («IES Ficticio Las Palmeras», código `99999999`).
- Diccionario personal de Alumno 1: *guagua* (dos acepciones, imagen), *gofio* (imagen), *baifo*, *millo* y *fisco*
  (oculta).
- Diccionario personal de Alumna 2: *cholas*, *machango*, *mojo*, *tenique*.
- Aula «Canarismos de 2º ESO B» (vigente, plazo de envíos abierto 60 días, máximo 3 acepciones, categoría gramatical
  obligatoria, pautas) con los dos alumnos unidos por código.
- Aula «Vocabulario de Biología 1º ESO» con envíos cerrados.
- Envíos: *guagua*, *gofio* y *cholas* publicados; *baifo* y *machango* pendientes; *mojo* rechazado con nota.
- Dos comentarios de la profesora y una acepción de *guagua* oculta por la profesora en la copia publicada.
- Dos ilustraciones CC0 (`apps/web/public/demo/`).

La semilla se ejecuta una sola vez y marca `app_settings.demo_seed_version`.

## Persistencia

- Base de datos: IndexedDB `idb://lexican-demo-v2` (en el navegador, `/pglite/lexican-demo-v2`). El sufijo se sube
  (`DATA_DIR` en `apps/web/src/demo/db.ts`) cuando el esquema o la semilla cambian de forma incompatible.
- Usuario activo: clave `lexican-demo-session` de `localStorage` (solo el identificador del usuario ficticio).
- Al recargar se conservan datos y sesión.
- **Restablecer datos de demostración** (en el aviso de demostración de cada pantalla, con confirmación) cierra PGlite, borra la base IndexedDB y
  recarga: vuelve a sembrarse desde cero.

## Límites

- **No es una frontera de seguridad.** El acceso solo elige qué usuario ficticio actúa; cualquiera puede leer o
  modificar la base de su navegador.
- Archivos subidos: máximo **2 MB** por archivo, guardados en la tabla `media_blobs`.
- Pensada para **una pestaña**: PGlite usa una única conexión (no se usa `PGliteWorker`). Con dos pestañas abiertas a la vez
  el comportamiento no está garantizado.
- La primera carga descarga el WASM de PGlite (≈5 MB comprimidos) después de pintar la pantalla de acceso.
- Sin CAS, CAUCE, correo ni servidor: nada sale del navegador. Los E2E fallan si la página pide algo fuera de su origen.
- Los datos no se comparten entre navegadores ni dispositivos.

## Build y despliegue

| Paso | Comando / fichero |
|---|---|
| Build estático | `npm run build:demo` → `vite build --mode demo` → `apps/web/dist-demo/` con `base: '/lexican/'` |
| Enrutado | *hash* (`createHashRouter`): `…/lexican/#/mi-diccionario`; no hace falta `404.html` |
| Modo | decidido en el build (`import.meta.env.MODE === 'demo'`, `apps/web/src/env.ts`), nunca por el nombre del host |
| Comprobación del bundle | `npm run check:dist` (`scripts/check-dist.mjs`): sin hosts internos, claves ni tokens; `index.html` con `/lexican/`; el build de producción sin PGlite, WASM ni contraseñas demo |
| Publicación | `.github/workflows/pages.yml`: solo tras un CI correcto en un *push* a `main` (o manual desde `main`), comprueba que el commit está en `main`, `npm ci`, `build:demo`, `check:dist`, sube `apps/web/dist-demo` y despliega con `actions/deploy-pages` |

## Ejecutar en local

```bash
npm ci
npm run dev:demo          # Vite en modo demo (http://localhost:5173/lexican/)
# o, igual que en Pages:
npm run build:demo && npm run preview:demo
```

Los E2E de la demo usan `preview:demo` en el puerto 4173 ([TESTING.md](TESTING.md)).

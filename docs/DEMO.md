# Demo en GitHub Pages

<https://ateeducacion.github.io/lexican/>

La demo es el **mismo** frontend React compilado en modo `demo`. No tiene servidor: un **Web Worker** ejecuta la misma
API Hono (`packages/http`) que Docker, con los mismos servicios (`packages/app`), sobre PGlite (PostgreSQL compilado a
WebAssembly) con el mismo esquema y las mismas migraciones. React habla con el Worker con el mismo cliente que usa en
producción; solo cambia el transporte (mensajes en vez de HTTP). Decisiones: [ADR 0004](adr/0004-demo-pglite.md) y
[ADR 0009](adr/0009-hono-bun-worker.md).

## Cómo probar un cambio

1. *Push* a `main` → `pages.yml` construye la demo, la comprueba (tipos, `check:dist`, E2E esenciales sobre un
   servidor estático como Pages) y publica ese mismo artefacto. No espera a la validación completa (`ci.yml`).
2. Abrir <https://ateeducacion.github.io/lexican/>. En «Acerca de LexiCán» se ven la versión y el *commit* publicados.
3. Para empezar de cero: «Restablecer datos de demostración».

## Cuentas

Aparecen debajo del formulario de acceso, cada una con un botón «Entrar como…» (acceso rápido). Fuente:
`packages/core/src/demo.ts`. La demo estática solo permite estas cuentas ficticias; no ofrece acceso CAS.

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

- Base de datos: IndexedDB `idb://lexican-demo-v2` (en el navegador, `/pglite/lexican-demo-v2`), durabilidad por
  defecto (cada escritura llega a IndexedDB antes de responder; no se usa `relaxedDurability`). El sufijo se sube
  (`DATA_DIR` en `apps/web/src/demo/protocol.ts`) cuando el esquema o la semilla cambian de forma incompatible.
- Medios: Blobs en la base IndexedDB `lexican-demo-media`, **fuera de PGlite**; la base SQL guarda metadatos, permisos,
  tamaño, hash y clave. Al actualizar desde la versión anterior, el Worker copia los bytes de `media_blobs` a ese
  almacén antes de que la migración 0001 elimine la tabla: no se pierden datos.
- Sesión: clave `lexican-demo-sid` de `localStorage` (identificador opaco de una fila `sessions` de la base del
  navegador).
- Al recargar se conservan datos y sesión. Nada depende de `beforeunload`.
- Semilla versionada (`app_settings.demo_seed_version`): se siembra y se calculan las contraseñas una sola vez.
- **Restablecer datos de demostración** (en el aviso de demostración, con confirmación) detiene las peticiones nuevas,
  espera a las que están en curso, cierra PGlite y borra **solo** `/pglite/lexican-demo-v2`, `lexican-demo-media`, la
  sesión y las URL `blob:`; después recarga y siembra de nuevo.

## Arranque, estado y errores

- El Worker arranca una sola vez por pestaña (también con React StrictMode): toma el bloqueo, abre PGlite, migra,
  siembra si hace falta, limpia medios huérfanos y avisa de que está listo. Las peticiones esperan como mucho 120 s.
- Mientras arranca se muestra «Preparando la demo en este navegador…» (`role="status"`). Los errores se muestran con
  `role="alert"`, un botón «Reintentar» y, cuando es seguro, «Restablecer datos de demostración»; nunca una pantalla
  en blanco. Peticiones: 30 s (subidas 120 s); tras un *timeout* no se reintenta (la operación pudo completarse).
- Si el Worker se detiene, todas las peticiones pendientes fallan con un mensaje y se pide recargar.

## Límites

- **No es una frontera de seguridad.** El acceso solo elige qué usuario ficticio actúa; cualquiera puede leer o
  modificar la base de su navegador.
- Archivos subidos: mismos límites que el servidor (10 MB por archivo, 100 MB al día).
- **Una pestaña a la vez**: el Worker toma el Web Lock `lexican-demo-database`; una segunda pestaña no abre la base y
  explica que la demo ya está abierta en otra. Sin Web Locks la demo no arranca (más seguro que arriesgar los datos).
- En WebKit con navegación privada, IndexedDB no admite Blobs: los medios se guardan como `ArrayBuffer`.
- La primera carga descarga el WASM de PGlite (≈5 MB comprimidos) después de pintar la pantalla de acceso; PGlite y la
  API trabajan en el Worker, no en el hilo de la interfaz.
- Sin CAUCE, correo ni servidor. La demo no hace peticiones fuera de su origen; los E2E lo comprueban.
- No sustituye a las pruebas de Docker: ver la tabla «Qué valida Pages y qué solo valida Docker» en
  [AUTHENTICATION.md](AUTHENTICATION.md).
- Los datos no se comparten entre navegadores ni dispositivos.

## Build y despliegue

| Paso | Comando / fichero |
|---|---|
| Build estático | `bun run build:demo` → `vite build --mode demo` → `apps/web/dist-demo/` con `base: '/lexican/'` |
| Enrutado | *hash* (`createHashRouter`): `…/lexican/#/mi-diccionario`; no hace falta `404.html` |
| Modo | decidido en el build (`import.meta.env.MODE === 'demo'`, `apps/web/src/env.ts`), nunca por el nombre del host |
| Comprobación del bundle | `bun run check:dist` (`scripts/check-dist.mjs`): JS, chunk del Worker, CSS, HTML y sourcemaps sin hosts internos, claves ni tokens; `index.html` con `/lexican/`; el Worker y el WASM presentes; el build de producción sin PGlite, WASM, Worker, API demo ni contraseñas |
| Publicación | `.github/workflows/pages.yml`: en cada *push* a `main` (o manual desde `main`), comprueba que el commit está en `main`, `bun ci`, `typecheck`, `build:demo`, `check:dist`, E2E esenciales (Chromium) sobre ese mismo artefacto, y lo despliega. Ejecuciones en serie: un commit antiguo nunca publica encima de uno nuevo |

## Ejecutar en local

```bash
bun ci
bun run dev:demo          # Vite en modo demo (http://localhost:5173/lexican/)
# o, igual que en Pages (servidor estático estricto, sin *fallback*):
bun run build:demo && node scripts/static-pages-server.mjs 4317   # http://localhost:4317/lexican/
```

Los E2E de la demo usan ese servidor estático en el puerto 4317 ([TESTING.md](TESTING.md)).

## Comprobación manual en Android (pendiente en dispositivo real)

La emulación de Pixel de Playwright no prueba un Android real. Recorrido en Chrome para Android:

1. Abrir la demo publicada; esperar a «Preparando la demo…» y entrar como alumno 1.
2. Crear una palabra; en «Audio» elegir una nota de voz grabada con el teléfono (M4A/AAC, OGG/Opus o WebM) y guardar.
3. Reproducir, pausar y desplazarse por el audio; girar la pantalla.
4. Cerrar la pestaña, volver a abrir la demo: la palabra y el audio siguen ahí y se reproducen.
5. Salir y entrar como alumna 2: el audio del alumno 1 no aparece en su diccionario.
6. Abrir la demo en una segunda pestaña: debe aparecer el aviso «La demo ya está abierta en otra pestaña».
7. «Restablecer datos de demostración»: vuelve a los datos de ejemplo.

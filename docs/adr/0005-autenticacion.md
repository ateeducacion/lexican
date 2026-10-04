# ADR 0005 — CAS 3.0 propio, directorio CAUCE como adapter y sesiones en PostgreSQL

- Estado: aceptada
- Fecha: 2026-10-04

## Contexto

El legacy usa phpCAS 1.5 (CAS 3.0, *single logout*) y después llama a CAUCE (servicio REST con token *bearer*) para
decidir si el usuario puede entrar y con qué rol. Consume solo el identificador CAS. Además desactiva la validación TLS
de CAUCE, registra en los logs la respuesta completa (NIF, CIAL, centros) y fija una contraseña común a todos los
usuarios CAS (`analysis/lexican/FUNCTIONAL_INVENTORY.md` §11).

## Decisión

- **CAS**: implementación directa del protocolo CAS 3.0 (`/login`, `/p3/serviceValidate`, `/logout` y SLO por
  *back-channel*) con `fetch` y `fast-xml-parser` 5.11, rechazando cualquier `<!DOCTYPE`/`<!ENTITY>`. Todas las
  librerías CAS de npm están sin mantenimiento desde 2022 o antes (`analysis/lexican/TECH_RESEARCH.md` §4).
- **CAUCE**: interfaz `InstitutionalDirectory` (`apps/api/src/cauce.ts`) con TLS verificado y *timeout*; la demo y los
  tests usan datos ficticios. No se guardan NIF/NIE, pasaporte ni CIAL; el sujeto CAS es el identificador.
- **Rol global** recalculado en cada acceso (CAUCE 1, 4 y 5 → profesorado; resto → alumnado). Administración y oficina
  técnica son concesiones locales que se conservan.
- **Sesiones**: tabla `sessions` con el *hash* SHA-256 de un identificador aleatorio de 256 bits; cookie
  `__Host-sid` `HttpOnly; Secure; SameSite=Lax`; caducidad explícita; el ticket CAS se guarda para el SLO.
- **CSRF**: lista de orígenes permitidos (`Origin`) en métodos no seguros + `SameSite=Lax` + la API solo acepta JSON y
  *multipart*. Sin Redis.
- **Proveedor de contraseña** solo para la demo y para desarrollo/test (`AUTH_DEV_LOGIN`), prohibido con
  `NODE_ENV=production`.

## Consecuencias

El SLO y el comportamiento exacto del CAS del Gobierno de Canarias deben verificarse en preproducción (no es accesible
desde CI). El *adapter* CAUCE se probó con XML grabado ficticio.

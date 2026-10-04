# ADR 0008 — Sustituir Voyager por pantallas propias mínimas

- Estado: aceptada
- Fecha: 2026-10-04

## Decisión

Solo se reimplementan las funciones administrativas con uso real según el inventario:

| Legacy | Nuevo |
|---|---|
| BREAD de usuarios y roles | `/admin/usuarios`: buscar y conceder administración u oficina técnica |
| Vigencia (compass) | `/admin/aulas`: vigentes / no vigentes / atemporales, hacer atemporal, reactivar con tope de 10 cursos |
| Estadísticas y CSV (`getCSV?methodName=`) | `/admin`: totales y tabla por curso + CSV fijo, sin invocación dinámica de métodos |
| Tablas maestras (solo por SQL) | `/admin/listas`: editar etiquetas, destacar, desactivar o añadir valores |
| `laravel-auditing` + BREAD Audits | `/admin/auditoria`: `audit_events` sin contenidos |
| Visor de logs | Eliminado: los logs son de la plataforma y no contienen PII |
| Avisos, mantenimiento, configuración, grabación | Eliminados: rutas sin implementación o pruebas de concepto |

La vigencia se calcula al leer; no hace falta una tarea programada para desactivar aulas.

# ADR 0001 — Reconstruir LexiCán en lugar de actualizar Laravel

- Estado: aceptada
- Fecha: 2026-10-04

## Contexto

El código heredado (Laravel 8.83, Voyager 1.5, phpCAS 1.5, Blade, jQuery, Bootstrap 4, TinyMCE 5, MariaDB) ya no se
instala: el repositorio de Composer `larapack.io` devuelve 404 y Composer 2.10 rechaza `phpcas 1.5.0` y `dompdf ^2` por
avisos de seguridad (`analysis/lexican/ASSESSMENT.md`). La auditoría encontró 16 vulnerabilidades en PHP y 25 en npm,
dos rutas confirmadas de ejecución remota (`/upload` y el visor de logs de Voyager) y decenas de acciones sin
autorización. La lógica vive en 6,5 k líneas de *helpers* globales, no en controladores. No hay una costura de API que
permita sustituir el sistema por piezas.

## Decisión

Reconstruir desde la intención extraída (304 reglas citadas, 123 funcionalidades inventariadas) sobre la arquitectura
fijada en `1er-prompt.md` §8 y §105, sin portar código. El legacy sigue en producción hasta el corte; `upstream` conserva
la foto histórica.

## Alternativas

- *Actualizar Laravel a 11/12*: obliga a sustituir Voyager (abandonado, vistas ausentes del repo), phpCAS y dompdf, y a
  reescribir los *helpers* igualmente; no resuelve la demo estática.
- *Reescritura por piezas (strangler)*: sin API ni módulos separables, el coste de convivencia supera al de reconstruir.

## Consecuencias

La equivalencia no puede probarse ejecutando ambos sistemas (el legacy no se instala); se demuestra con las tarjetas
de reglas convertidas en tests y con el migrador de datos. Los defectos marcados en las reglas se corrigen en vez de
reproducirse (`analysis/lexican/MODERNIZATION_BRIEF.md` §5).

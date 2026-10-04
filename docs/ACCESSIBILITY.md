# Accesibilidad

Objetivo: **WCAG 2.2 nivel AA** en los flujos principales (acceso, diccionario personal, editor de entradas, envío,
aulas, revisión, comentarios, impresión/exportación y administración).

**Cero errores automáticos no equivale a cumplimiento.** axe detecta una parte de los problemas; el resto exige la
revisión manual de abajo.

## Qué está automatizado

| Comprobación | Dónde |
|---|---|
| axe (`@axe-core/playwright`) con etiquetas `wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa`, `wcag22aa`: cero violaciones en acceso, diccionario personal, ficha de entrada y editor | `expectAccessible()` en `e2e/fixtures.ts`, usado en `e2e/student.spec.ts` |
| Cada E2E falla ante errores de página, `console.error` o peticiones fuera del origen | *fixture* automático `guard` en `e2e/fixtures.ts` |
| Los E2E localizan elementos por rol y nombre accesible (`getByRole`, `getByLabel`): si se pierde una etiqueta, fallan | `e2e/*.spec.ts` |
| Móvil (Pixel 7): crear y buscar sin *scroll* horizontal | `e2e/student.spec.ts` (`@mobile`, proyecto `demo-mobile`) |
| Chromium, Firefox y WebKit | `playwright.config.ts` |

Ejecutar: `npm run e2e:demo` ([TESTING.md](TESTING.md)).

## Prácticas en el código

- HTML nativo antes que ARIA: `<button>`, `<a>`, `<label>`, `<dialog>`, `<table>` con cabeceras.
- Los campos de formulario usan el componente `Field` (`apps/web/src/components/ui.tsx`), que asocia etiqueta y error.
- Errores de validación junto al campo y resumidos en texto, no solo con color.
- Contenido de usuario en texto plano.
- Idioma de la página: español.

## Revisión manual (antes de cada versión)

Marcar en la PR de *release* qué se ha revisado y con qué navegador/lector.

### Teclado y foco

- [ ] Todo el recorrido alumno → envío → profesora → publicación se completa solo con teclado.
- [ ] El foco es siempre visible y sigue un orden lógico.
- [ ] Al abrir un diálogo el foco entra en él; `Esc` lo cierra y el foco vuelve al botón que lo abrió.
- [ ] Reordenar acepciones funciona con botones (subir/bajar), sin arrastrar.
- [ ] No hay trampas de foco.

### Lector de pantalla (prueba rápida)

Con NVDA + Firefox o VoiceOver + Safari:

- [ ] **Acceso**: se anuncian el título, los campos con su etiqueta, los errores y las cuentas de demostración.
- [ ] **Editor de entrada**: cada acepción se identifica (número), los campos tienen nombre, añadir/quitar/mover
      acepciones se anuncia, los errores se leen al enviar.
- [ ] **Revisión de envíos**: la tabla/lista se recorre con sentido, el estado de cada envío se lee como texto y el
      resultado de publicar/rechazar se anuncia.
- [ ] Encabezados en orden (`h1` → `h2` …) y *landmarks* (`header`, `nav`, `main`).

### Visual

- [ ] Zoom del navegador al 200 %: sin pérdida de contenido ni funciones.
- [ ] 400 % (equivale a 320 px de ancho): una sola columna, sin *scroll* horizontal salvo tablas.
- [ ] Contraste de texto ≥ 4,5:1 (≥ 3:1 en texto grande e iconos/bordes de controles), también en estados de foco,
      error y deshabilitado.
- [ ] Ningún estado depende solo del color (publicado, pendiente, rechazado, oculto).
- [ ] Con `prefers-reduced-motion: reduce` no hay animaciones no esenciales.
- [ ] Anchos de prueba: 360×800, 768×1024, 1366×768, 1920×1080.

### Multimedia

- [ ] Las imágenes de acepción tienen texto alternativo útil (la palabra) o son decorativas.
- [ ] Audio y vídeo usan controles nativos.

## Limitaciones conocidas

- axe solo se ejecuta en algunas pantallas del alumnado; aulas, revisión, comentarios y administración se revisan a
  mano por ahora.
- El alumnado puede subir audio y vídeo sin subtítulos ni transcripción: LexiCán no los genera.
- La vista de impresión/PDF depende del navegador.
- No se ha hecho una auditoría formal con personas usuarias de tecnologías de apoyo.

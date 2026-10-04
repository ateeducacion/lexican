# LexiCán legacy — Inventario de pantallas (UI_SCREENS)

Fuente: lectura estática de `resources/views/**`, `resources/lang/es/*.php`, `resources/js/*.js` y de los
controladores que fijan validaciones, migas y mensajes flash. No se ha ejecutado la aplicación.
Todas las rutas de cita son relativas a la raíz del repositorio. Los textos entre comillas son literales
(incluidas erratas y mayúsculas) para poder reproducir el vocabulario en la UI React.

Abreviaturas: `L:` = `resources/lang/es/diccionario.php`, `V:` = `resources/views/`, `C:` = `app/Http/Controllers/`.

---

## 0. Convenciones globales que afectan a todas las pantallas

### 0.1 Traducciones: trampas a conocer

- No existe `resources/lang/es.json`. Toda llamada `__('Texto')` sin prefijo de fichero muestra **la clave literal**
  (p. ej. `__('Login')` → "Login", `__('vigencia')` → "vigencia", `__('Diccionario De Aula')` → "Diccionario De Aula").
- `L:` contiene **claves duplicadas**; en PHP gana la última. Texto efectivo:
  | Clave | Primer valor (no se ve) | Valor efectivo |
  |---|---|---|
  | `exportar_pdf` | "EXPORTAR A PDF" (L:145) | **"Exportar pdf"** (L:286) |
  | `unirse_dic` | "UNIRSE A DICCIONARIO" (L:140) | **"Unirse a un diccionario"** (L:288) |
  | `subir_portfolio` | "SUBIR A PORTAFOLIO" (L:146) | "Subir portfolio" (L:287) — sin uso (comentado) |
  | `diccionario_borrado_success` | "Diccionario de aula borrado" (L:356) | **"Se ha borrado correctamente el diccionario"** (L:423) |
  | `tooltip_vigencia` | "…opción de hístoricos." (L:506) | "…opción de historicos." (L:508) |
- Claves usadas en vistas que **no existen** (se ve la clave): `diccionario.editar_acepcion`
  (V:layouts/partials/consulta/dpEntradaAcepcion.blade.php:33, sobrescrita por `acepcion_editar` en el botón real),
  `diccionario.entrada_oculta` (app/Helpers/dicAula_helper.php ~870).
- Mensajes de validación genéricos: `resources/lang/es/validation.php`. Laravel convierte el nombre del campo a
  "snake con espacios", así que los mensajes muestran p. ej. "El campo **entrada entrada** es obligatorio." o
  "El campo nombre diccionario es obligatorio.". El mensaje `max.string` tiene errata: "no puede ser más grande de
  :max **characteres**." (validation.php:82). Mensajes personalizados (validation.php:131-142):
  - `entrada_entrada.required` → "Debes especificar un texto para la entrada"
  - `pautasEspecificas.required_if` → "No se ha escrito ninguna pauta específica."
  - `comentariosVisibleFecha.required_if` → "El campo Fecha para los comentarios es obligatorio si visibilidad comentarios es Visibles los anteriores a:"
  - `comentariosVisibleFecha.before_or_equal` → "El campo Fecha para los comentarios debe ser una fecha igual o anterior a la de hoy."

### 0.2 Layout base (`V:layouts/app.blade.php`)

- `<title>`: "`@yield('title')` - `APP_NAME`" (L:11).
- Barra superior azul (`navbar bg-primary`): escudo "Escudo Gobierno De Canarias" (alt) + logotipo texto
  "LEXI**CÁN**" que enlaza a `/` (L:41-49).
- Lado derecho de la barra:
  - Invitado: enlace "Login" → `GET /cas/login`, y "Desconectar" (POST oculto a `/cas/logout`) (L:63-80).
  - Autenticado: contenido `@yield('nav')` + "Desconectar" (`L:372`) (L:86-101).
- Franja `#header-line` (avatar) y `#header-tabs` (pestañas).
- Iconos Bootstrap Icons cargados desde CDN jsDelivr (L:32) — a eliminar (privacidad).

### 0.3 Cabecera de las pantallas de usuario (`V:diccionario/home.blade.php`)

Todas las pantallas de alumnado/profesorado extienden esta vista.

- **Avatar** (arriba a la derecha): imagen `storage/avatares/oficiales/<avatar_URL>` o `imagenes/users/default.png`;
  enlaza a "Modificar Diccionario Personal". Tooltip: "`nombre` `apellidos` (`rol`)" donde rol se traduce
  "Estudiante" (`L:476`, rol BD "alumno") o "Docente" (`L:477`) (home.blade.php:15-35).
- **Pestañas** (`getDatosTabs()`, app/Helpers/global_helper.php:651-665): "Diccionario Personal" → `/personal`,
  "Diccionario De Aula" → `/aula`. La activa usa `tab-active.svg`.
- **Menú de navegación (`nav`, sólo visible en móvil vía toggler)** (home.blade.php:40-150):
  - En contexto aula: botón "Diccionario de Aula" que despliega la lista de diccionarios de aula unidos
    (icono libro verde + título; los no visibles para alumnado llevan tooltip "Diccionario de aula temporalmente no visible")
    y el botón "Unirse a un diccionario"; más enlace "Diccionario Personal".
  - En contexto personal: enlace "Diccionario de Aula".
  - Botón "¿Qué puedo hacer?" (`L:478`) que despliega los enlaces de ayuda (ver 0.4).
- **Migas de pan** (`V:layouts/partials/components/breadcrumb.blade.php`): lista `Inicio › …`, último elemento sin enlace.
  Si el controlador no las define, se generan a partir de la URL.
- **Bloque de alertas** (`V:layouts/partials/alerts/alertas.blade.php`): rojo para `$errors` y `session('errores')`,
  verde para `session('success')` (texto o array) y `session('successArray')`; cada mensaje en `<h4><li>`.
- Pie: componente "Créditos" (0.5).
- Contenedor `#padre_modal_ajax` donde se inyectan los modales cargados por AJAX.

### 0.4 "¿Qué puedo hacer?" (ayuda) (`V:layouts/partials/components/quehacer.blade.php`)

Imagen `quehacer.png` clicable que despliega un menú flotante con enlaces (se abren en pestaña nueva):

| Contexto | Enlaces (texto) | Destino |
|---|---|---|
| Personal | "Aspectos generales", "Manual de uso" | `imagenes/que_puedo_hacer_diccionario_Personal.pdf`, `imagenes/Manual Usuario Lexican.pdf` (quepuedohacer-personal-menuLinks.blade.php:1-2) |
| Aula | "Aspectos generales", "Manual de uso", "Manual de Coordinación" (sólo docentes), "Pautas" | `que_puedo_hacer_diccionario_Docente.pdf`, `Manual Usuario Lexican.pdf`, `Manual de coordinación.pdf`; "Pautas" abre el panel lateral 3.5 (quepuedohacer-aula-menuLinks.blade.php:1-7) |

### 0.5 Pie de página y créditos (`V:layouts/partials/components/creditos.blade.php`)

- Barra inferior: "Créditos - © Gobierno de Canarias 2020-`<año actual>`" (`L:404`) y enlaces "Aviso Legal" y
  "Política de privacidad" (URLs públicas de gobiernodecanarias.org, `L:405-406`).
- Panel lateral "Créditos" con logotipos (Gobierno de Canarias, Unión Europea, UCTICEE, ATE, Canarias Avanza,
  FEDER) y textos: "Los contenidos y programas que constituyen esta obra son propiedad del Gobierno de Canarias que ha
  promovido su creación y desarrollo con el propósito de que la comunidad educativa haga libre uso de los mismos." y
  "**Empresas que han intervenido en el desarrollo de esta obra:**" (logos de las empresas).

### 0.6 Aviso de cookies (`V:diccionario/cookies.blade.php`)

Modal mostrado si no existe la cookie `lxcn_acceptcookies`. Texto `L:516`: "LexiCán utiliza cookies propias para su
correcto funcionamiento, pero no recogen información de carácter personal. Usted puede permitir su uso o rechazarlo,
o también puede cambiar su configuración siempre que lo desee. Dispone de más información en la página del Gobierno de
Canarias" (enlace). Botones: "×" y "Aceptar" (→ `GET /acceptCookies`). No hay opción de rechazo real.

### 0.7 Componentes reutilizados

- **Botones de acción grandes** (`actionbutton.blade.php`): icono + texto en mayúsculas. Si `disable`, tooltip
  "Crear o unirse a un diccionario de aula para activar" (`L:464`).
- **Bocadillo** (`dc-bocadillo`): al pulsar un botón de acción se despliega un globo con sub-botones (icono + texto).
- **Modal Aceptar/Cancelar** (`modal-AceptarCancelar.blade.php`, `ajaxmodal-AceptarCancelar.blade.php`): título,
  cuerpo HTML, botones "Aceptar" (navega a la acción con **GET**) y "Cancelar".
- **Modal simple** (`modal-simple.blade.php`): título, cuerpo, un botón (normalmente "Aceptar").
- **Modal de reproducción de medios** (`#mediaModal`): título dinámico, `<audio>`/`<video>`, botón "Cerrar".
- **Abecedario** (`abecedario.blade.php`): 27 letras "A…N, Ñ, O…Z" (global_helper.php:682-685) como sprites; la
  seleccionada resaltada. Enlaza a `…/entradas/letra/{letra}` o, en modo AJAX, rellena un campo oculto.
- **Buscador** (`buscador.blade.php`): ver 1.2.

---

## 1. Acceso

### 1.1 Login CAS (sin pantalla propia)

- `GET /login` redirige a `GET /cas/login` (routes/web.php:409-411), que redirige al CAS institucional.
- Vuelta: `GET /cas/callback` (C:Auth/CasController.php:28-41). Si CAUCE no autoriza → **error 403** (1.3).
  Si autoriza → redirige a "Diccionario Personal".
- `GET /` (autenticado): rol global `admin`/`user` → `/admin` (Voyager); resto → `/personal` (routes/web.php:31-38).
- Logout: enlace "Desconectar" en la barra (POST `/cas/logout`, routes/web.php:402-407).
- La vista `V:auth/login.blade.php` (email/contraseña, "Remember Me", "Forgot Your Password?") es la plantilla por
  defecto de Laravel y **no se usa** (sus rutas existen por `Auth::routes()` en web.php:280).

### 1.2 Error 403 (`V:errors/403.blade.php`)

Título "Error 403"; icono; texto: "Acceso no autorizado con su perfil a esta acción." Sin botón de vuelta.
Se usa también como pantalla de "usuario no autorizado por CAUCE". La barra muestra "Login" y "Desconectar".

### 1.3 Error 404 (`V:errors/404.blade.php`)

`<title>` "Error 403" (errata), encabezado "Error 404", textos "Página no encontrada" y "La página solicitada puede
que no se encuentre disponible, haber cambiado de dirección o no existir. Perdone las molestias."

---

## 2. Diccionario personal (alumnado y profesorado)

Todos los usuarios no administradores tienen un diccionario personal creado automáticamente con título
"Mi diccionario personal" (app/Helpers/dpDiccionarios_helper.php:87).

### 2.0 Panel lateral de acciones del diccionario personal (`V:layouts/partials/components/actionbuttonPersonal.blade.php`)

Aparece a la derecha (o debajo en móvil) en casi todas las pantallas personales. Tres botones grandes:

1. **"AÑADIR ENTRADA"** (`L:239`) → `GET /personal/entrada/create` (2.3).
2. **"ENVÍOS Y COMENTARIOS"** (`L:240`) → despliega bocadillo con:
   - "Unirse a un diccionario" → modal 2.10.
   - "ENVIAR ENTRADAS" (`L:141`):
     - si el diccionario no tiene entradas → modal simple título "ENVIAR DICCIONARIO PERSONAL", cuerpo
       "No se puede enviar el diccionario personal porque no tiene ninguna entrada", botón "Aceptar", tooltip
       "Enviar el diccionario personal al diccionario de aula" (`L:172-175`);
     - si no está unido a ningún aula → modal 2.10 con cuerpo de envío (C:EnviosController.php:434-482);
     - en otro caso → pantalla 2.8.
   - "VER COMENTARIOS" (`L:142`) → si no hay comentarios visibles, modal "Comentarios a la entrada" con
     "No hay comentarios o no son visibles." (C:ComentariosController.php:181-191); si hay → pantalla 2.11.
3. **"GESTIÓN DE DICCIONARIO"** (`L:241`) → bocadillo con:
   - "MODIFICAR DICCIONARIO" (`L:144`) → 2.7.
   - "Exportar pdf" (`L:286`, ver 0.1) → 2.12.
   - ("Subir portfolio" está comentado: no existe.)

### 2.1 Inicio del diccionario personal

- Ruta: `GET /personal` (`DiccionarioPersonalController@dpHome`, C:DiccionarioPersonalController.php:1150-1195);
  vista `V:diccionario/personal.blade.php`. Título "Diccionario Personal". Admin → redirige a `/admin`.
- Migas: "Inicio › Diccionario Personal".
- Contenido (de arriba abajo):
  1. Abecedario (enlaza a 2.2 por letra).
  2. Buscador (2.2).
  3. Enlace contador: "Ver todas las entradas de `<título diccionario>` (`N`)"; si 0 →
     "No hay entradas en este diccionario" (`L:118`) (`V:layouts/partials/nentradas.blade.php`).
  4. Fila horizontal con los 3 botones de 2.0 (personal.blade.php:120-266).

### 2.2 Listado / búsqueda de entradas personales

- Rutas (todas GET salvo la búsqueda): `/personal/entradas` (todas), `/personal/entradas/letra/{letra}`,
  `/personal/entradas/{consulta}`, `/personal/entradas/{consulta}/etiquetas/{ids}`, `/personal/entradas/etiquetas/{ids}`,
  `POST /personal/entradas/consulta` (envío del formulario). Scroll infinito AJAX `…/n/{offset}`.
  Vista `V:diccionario/consulta.blade.php`. Título "Todas las entradas " o "Resultados `<consulta>`".
- Migas: "Inicio › Diccionario Personal › Búsqueda [› Por Letra X | › `<consulta>`]".
- **Buscador** (`V:layouts/partials/components/buscador.blade.php`):
  | Campo | Tipo | Obligatorio | Notas |
  |---|---|---|---|
  | (sin label) placeholder/aria "Buscar" | text `entrada_entrada`, autocompletado typeahead (mín. 2 caracteres, lista vacía: 'No se encuentra entrada "<query>"') | no | max 150 (validación servidor) |
  | Botón "etiquetas" + flecha | despliega chips de temáticas (badges) para filtrar | no | máx. `TEMATICAS_MAX`=10; si se supera: "Para las búsquedas solo está permitido seleccionar un máximo de 10 etiquetas" (resources/js/searchAutocompletion.js:147) |
  | "Filtro:" + chips seleccionados + "x" (borrar filtros) | — | — | |
  | Botón lupa (submit) | | | Si texto y etiquetas vacíos (aula): "Debe especificar texto o categoria a buscar" (`L:535`) |
- **Estados vacíos** (consulta.blade.php:7-28):
  - Búsqueda exacta sin resultados: caja info "No existe la entrada "`<texto>`"" (+ "con la etiqueta selecionada" /
    "con las etiquetas seleccionadas" si hay filtro) y botón **"Crear entrada"** → crea la entrada con ese texto.
  - Otros casos: "No se encontró ningún resultado "`<consulta>`"".
- **Tarjeta de entrada** (`V:layouts/partials/consulta/entrada.blade.php`), por cada entrada:
  - Título de la entrada (enlace a 2.4).
  - Iconos: lápiz (tooltip "Ir a edición de entrada") → 2.4; ojo (ocultar/mostrar, 2.13); avión
    "Enviar entrada al diccionario de aula" → modal 2.9 (si está oculta, modal "ENVIAR ENTRADA" con
    "¡Vaya! La entrada está oculta y no se puede enviar. Utiliza el icono de ocultar/mostrar [ojo] para mostrar la
    entrada y después envíala" `L:149-154`); bocadillo "Ver comentarios docente" → modal 2.11b o, si no hay,
    modal "Comentarios a la entrada" / "La entrada no tiene comentarios o no son visibles." / "Aceptar".
  - Acepciones: la 1ª visible, el resto plegadas; flecha "desplegar"/"plegar" si hay >1.
  - Cada acepción (`consulta/acepcion*.blade.php`): "`orden`. `atributos`" (abreviaturas de categoría, género y
    número, p. ej. "sust. m. sing."; config/ctes.php:197-218), salto, definición; luego "Más datos. `frase_ejemplo`"
    (`L:384`), el ejemplo de uso (`ejemplo2`, sin etiqueta: `L:386` vacío), "`Lengua`: `palabra`" si hay
    traducción, "Etiquetas: t1, t2" (`L:387`); a la derecha iconos audio ("Oír audio") y vídeo ("Ver vídeo"),
    grises si no hay, y miniatura de imagen (enlace de descarga). Texto "Leer mas..." (clave literal) para expandir.
  - Columna lateral: panel 2.0.
- Spinner de carga "Cargando..." (sr-only).

### 2.3 Añadir entrada (buscador de alta)

- Ruta: `GET /personal/entrada/create` (`dpCreateEntradaGET`), vista `V:diccionario/dpEntrada/masterEntrada.blade.php`
  modo `BUSCADOR_ENTRADA` → `V:layouts/partials/dpEntrada/dpEntradaBuscador.blade.php`. Título "Buscador entrada".
- Migas: "Inicio › Diccionario Personal › Añadir Entrada" (`L:57`).
- Ilustración de libro con texto imagen; un único campo:
  | Campo | Tipo | Oblig. | Placeholder | Validación |
  |---|---|---|---|---|
  | (aria-label "Añadir una nueva entrada:") `entrada_entrada` | text | sí | "Escribe aquí una entrada y pulse ..." | required → "Debes especificar un texto para la entrada"; max 150 → "El campo entrada entrada no puede ser más grande de 150 characteres." |
- Botón: imagen "nueva entrada" (submit, **GET** `/personal/entrada/insert?entrada_entrada=…`).
- Resultado (C:DiccionarioPersonalController.php:178-245):
  - Si ya existe → redirige a 2.4 con alerta amarilla: "La entrada "`X`" ya existe" (`L:67-68`).
  - Si no existe → formulario de primera acepción (2.6) con título "**Creación de nueva acepción** para "`X`"".
  - El texto se normaliza (trim/espacios dobles).
- Panel lateral 2.0 con "AÑADIR ENTRADA" marcado.

### 2.4 Ver entrada personal

- Ruta: `GET /personal/{dic}/entrada/{entrada}` (`dpGetEntradaGET`), modo `MOSTRAR_ENTRADA`
  (`V:layouts/partials/dpEntrada/dpEntradaEntrada.blade.php`). Título "Entrada".
- Migas: "Inicio › Diccionario Personal › Búsqueda › Por Letra`X` › `<entrada>`".
- Alertas: verde "Se ha añadido la acepción para `X`" (`L:64`); amarilla "La entrada "X" ya existe".
- Cabecera: título de la entrada + iconos:
  - Lápiz (tooltip "Editar nombre") → 2.5.
  - Ojo → modal "OCULTAR ENTRADA" / "MOSTRAR ENTRADA" (2.13).
  - Papelera (tooltip "Borrar entrada") → modal "CONFIRMACIÓN" con cuerpo `confirm`:
    - normal: "¿Estás seguro o segura de borrar la entrada?" (`L:45`);
    - ya enviada: "La entrada "X" ha sido enviada al diccionario de aula. ¿Estás seguro o segura de querer eliminarla?
      Si confirmas el borrado, la entrada se borrará sin posibilidad de recuperarla." (`L:44`).
    - Botones "Aceptar" (GET `/personal/{dic}/entrada/{id}/delete`) y "Cancelar".
    - Resultados: verde "Se ha borrado la entrada: "X"" → listado; rojo "No se puede borrar la entrada porque ya se
      ha publicado. Contacta con la persona administradora del diccionario de aula para que anule la publicación de
      la entrada" (`L:30,32`).
- Acepciones (`V:layouts/partials/consulta/dpEntradaAcepcion.blade.php`), por cada una, columna de iconos:
  - "Subir" (si no es la 1ª) → GET `…/acepcion/{id}/up`.
  - "Bajar" (si no es la última) → GET `…/down`.
  - Lápiz (tooltip "Editar acepción") → 2.6 en modo edición.
  - Ojo: modal "OCULTAR ACEPCIÓN" — "¿Quieres ocultar la acepción nº`n` de "X"? Las acepciones ocultas no se podrán
    enviar al diccionario de aula." / "MOSTRAR ACEPCIÓN" — "¿Quieres mostrar la acepción nº`n` de "X"?" (`L:222-227`).
    Flash: "Se ha ocultado la acepción" / "Se ha recuperado la visibilidad de la acepción"; si se ocultan todas,
    se añade ". Se ha ocultado la entrada: X" y ". Para recuperar la visibilidad de la entrada pulsa en el icono "ojo"
    de la entrada" (C:DiccionarioPersonalController.php:1399-1420).
  - Papelera (tooltip "Borrar acepción"): modal "CONFIRMACIÓN" — si es la única: "Al borrar esta acepción se va a
    borrar la entrada. ¿Estás seguro o segura?"; si no: "¿Estás seguro o segura de borrar la acepción nº`n` de "X"?".
    Tras borrar: "Se ha borrado la acepción" (se muestra en el bloque **rojo** de errores, C:…:977-983).
  - Contenido como en 2.2 (en móvil aparece un literal "ejemplo2:" de depuración, dpEntradaAcepcion.blade.php:117).
- Bajo las acepciones: botón **"Añadir acepción"** (+) → 2.6 (`V:…/dpAcepcion/dpAcepcionAnadir.blade.php`).
- No se impone aquí el máximo de acepciones del aula (sólo se controla al enviar).

### 2.5 Editar nombre de entrada personal

- Ruta: `GET/POST /personal/{dic}/entrada/{id}/edit`; `V:layouts/partials/dpEntrada/dpEntradaEdit.blade.php`.
  Título "Editar entrada". Migas "Inicio › Diccionario Personal › `<entrada>`".
- Encabezado: "Editar nombre de entrada en tu diccionario".
- Campo: `entrada_entrada` text (sin label), maxlength 150, valor actual. required/max como 2.3.
- Botones: "GUARDAR" (submit), "CANCELAR" (volver a la página anterior).
- Error si el nuevo nombre ya existe: "No se puede modificar la entrada porque ya existe otra con ese mismo texto"
  (`L:28`). Éxito: vuelve a 2.4 sin mensaje.

### 2.6 Formulario de acepción (crear / editar) — editor central

- Rutas: crear primera acepción desde 2.3 (POST `/personal/entrada/acepcion/create`); añadir
  `GET /personal/{dic}/entrada/{e}/acepcion/create`; editar `GET …/acepcion/{a}/edit` + `POST …/acepcion/{a}/update`.
  Vista `V:layouts/partials/dpAcepcion/dpAcepcionFormulario.blade.php` + campos comunes
  `V:layouts/partials/dpAcepcion/acepcionFormFieldComun.blade.php` + `tematicas.blade.php`.
  Títulos de pestaña: "Crear Entrada" / "Añadir Acepción".
- Encabezado: "**Creación de nueva acepción** para "X"" o "**Edición de acepción** para "X"" (`L:89-90`).
- Migas: "Inicio › Diccionario Personal › `<entrada>` › Orden Acepción `n`" (crear) o "… › `n`" (editar).
- Botón redondo a la derecha **"Lengua"** con icono (`L:91`) → muestra/oculta la fila de idioma.
- Campos (orden visual):
  | Label (exacto) | name | Tipo | Oblig. | Opciones / límites |
  |---|---|---|---|---|
  | "Lengua" (oculta por defecto) | `idioma_id` | select | no | primera opción "--Seleccionar--" con **value=1** (no vacío); valores de `mst_campos_valores` campo 9 |
  | "Traducción" (oculta por defecto) | `idioma_palabra` | text | no | sin límite en servidor |
  | "Categoría Gramatical" | `cat_gramatical_id` | select | no | "--Seleccionar--" + valores campo 1 |
  | "Género" | `genero_id` | select | no | "--Seleccionar--" + valores campo 2 |
  | "Número" | `numero_id` | select | no | "--Seleccionar--" + valores campo 3 |
  | "Definición *" | `definicion` | textarea 3 filas, `required` HTML, maxlength 1000 | **sí** | placeholder "(Máximo 1000 Caracteres)"; vacío → "El campo definición es obligatorio."; >1000 → "El campo definicion no puede ser más grande de 1000 characteres." |
  | "Ejemplo de uso" | `ejemplo2` | textarea 3 filas, maxlength 255 | no | placeholder "(Máximo 255 Caracteres)"; **sin validación en servidor** |
  | "Más datos" | `frase_ejemplo` | textarea 3 filas, maxlength 255 | no | placeholder "(Máximo 255 Caracteres)"; servidor max 255 ("El campo frase ejemplo no puede ser más grande de 255 characteres.") |
  | "Etiquetas disponibles" + buscador (input text sin label) | `tematicas_disponibles` | select múltiple; doble clic añade | no | valores de temáticas (campo 4) |
  | botones "Añadir" (→) / "Quitar" (←) | | | | |
  | "Etiquetas seleccionadas" | `tematicas_seleccionadas` (+ oculto `listaTematicas` "`,id,id`") | select múltiple | no | |
  | Tarjeta imagen | `medio_imagen` | file (Dropify) | no | extensiones "jpg jpeg jpe gif png bmp tif tiff ico"; tamaño máx. `UPLOAD_MAXSIZE` (por defecto 200M) |
  | Tarjeta audio | `medio_audio` | file (Dropify) | no | "mp3 ogg wav" |
  | Tarjeta vídeo | `medio_video` | file (Dropify) | no | "mp4 ogg webm" |
  (config/ctes.php:113-117, 317-322)
- Textos Dropify (`L:94-100`): "Arrastra y suelta un archivo aquí o haz clic" + "Peso máximo permitido: 200MB";
  "Arrastra y suelta un archivo o haz clic para reemplazar"; botón "Eliminar"; errores "Lo sentimos, ha ocurrido un
  error", "El fichero es demasiado grande. Máximo `{{value}}`", "El tipo de fichero no está permitido. Los permitidos
  son `{{value}}`".
- Cabecera de cada tarjeta de medio: papelera (tooltip "Borrar imagen"/"Borrar audio"/"Borrar vídeo") y nombre del
  fichero actual. Si existe medio guardado, la papelera abre modal "CONFIRMACIÓN" — "¿Estás seguro o segura de borrar
  la imagen?" / "…el audio?" / "…el vídeo?" → GET `…/dpmedio/{id}/delete` (`L:49-51`). Para un medio aún no
  guardado: confirm del navegador "¿Quieres borrar la imagen?" / "¿Quieres borrar el audio?" / "¿Quieres borrar el video?".
- Botones de captura bajo cada tarjeta: **"Capturar"** (cámara, foto), **"Grabar"** (micrófono, audio), **"Grabar"**
  (cámara de vídeo). Al usarlos aparece la nota: 'Al usar "Capturar" se desactiva "Arrastrar y soltar"' /
  'Al usar "Grabar" se desactiva "Arrastrar y soltar"'.
- **Modal de grabación** (`#modal-grabacion`, estático, no se cierra con Esc): títulos "Sacar foto",
  "Grabación de audio", "Grabación de vídeo"; aviso "Recuerde que el tamaño máximo permitido de la grabación es 200MB,
  que corresponde a una duración aproximada de 5 minutos."; botones con tooltip "Iniciar", "Pausar", "Continuar",
  "Reproducir", "Borrar" (audio/vídeo) o "Sacar foto", "Borrar foto" (foto); pie "Guardar y Salir" y "Cancelar".
  Mensajes de estado: "Grabando...", "Pausa...", "Reproduciendo...", "Guardada...", "Borrado...", "Foto guardada...".
  Alertas (`alert()`): "La cámara está bloqueada. Asegúrate de concederle permisos de uso y de que no está siendo usada
  por otra aplicación.", "No hay micro", "No hay video", "Ya hay una grabación en curso", "No se puede pausar. No hay
  nada grabado", "La grabacion ya esta en pausa", "No se puede reproducir. No hay nada grabado", "La grabacion ya esta
  en reproduciendo.", "Pausa la grabacion, para poder reproducirla.", "No se puede guardar. No hay nada grabado",
  "No es posible guardar la grabación, excede el máximo permitido", "No se puede borrar. No hay nada grabado";
  confirm "¿Quieres borrar la grabación? Recuerda que una vez borrado no podrás recuperarla."
  (dpAcepcionFormulario.blade.php:450-1349). Con grabaciones el formulario se envía por `fetch` y los errores se
  pintan en el bloque de alertas.
- Botones del formulario: **"GUARDAR"** (submit) y **"CANCELAR"** (a listado si es la primera acepción, si no a 2.4).
- Éxito: vuelve a 2.4 con "Se ha añadido la acepción para `X`".

### 2.7 Modificar diccionario personal

- Ruta: `GET /personal/general/modificar`, `POST /personal/{dic}/general/update`;
  vista `V:diccionario/dpDiccionario/dpModificacion.blade.php`. Título y encabezado "Modificar Diccionario Personal".
- Migas: "Diccionario Personal › Modificar Diccionario Personal".
- Campos:
  | Label | name | Tipo | Oblig. | Notas |
  |---|---|---|---|---|
  | "Nombre del diccionario" | `nombre_diccionario` | text **deshabilitado**; icono lápiz (tooltip "Editar") lo habilita | no | sin validación (vacío = no cambia) |
  | "Selecciona un nuevo avatar" | `avatar_nuevo` | radio en carrusel Swiper (flechas prev/next); cada opción muestra imagen SVG y nombre de fichero | no | lista = ficheros `storage/app/public/avatares/oficiales/*.svg` |
  | "Avatar actual:" + imagen | — | — | — | |
- Botones: "GUARDAR", "CANCELAR" (vuelve a la URL de origen).
- Éxito: "Se han guardado correctamente los cambios" (`L:370`).
- Panel lateral 2.0.

### 2.8 Enviar diccionario personal a un diccionario de aula

- Ruta: `GET /personal/{dic}/enviar`, `POST /personal/{dic}/enviar`; vista
  `V:diccionario/dpDiccionario/dpDiccionarioEnviarFormulario.blade.php`. Título "Envío De Diccionario Personal A
  Diccionario De Aula". Migas "Inicio › Diccionario Personal › Enviar el diccionario personal al diccionario de aula".
- Encabezado: "**ENVÍO DE DICCIONARIO PERSONAL A DICCIONARIO DE AULA**".
- Texto: "Selecciona el diccionario al que quieres enviar las entradas de tu diccionario personal".
- Lista de diccionarios de aula unidos (`listaDicAula-customRadio.blade.php`): icono libro verde + título, selección
  única (checkbox que se comporta como radio); tooltip "Coordinador / Coordinadora: `nombre`"; los que tienen envíos
  deshabilitados en gris con tooltip "Diccionario con envíos deshabilitados" (`L:366`). Si sólo hay uno, se
  preselecciona.
- Nube de palabras (una "píldora" por entrada) y contador "Total palabras: `n`". Al seleccionar aula se consultan
  `GET /aula/{id}/entradas.json` y `POST /aula/{id}/comprobarEntradas` y se colorea cada palabra:
  - Leyenda "Leyenda:" con "Marcadas para enviar: n", "Enviadas con anterioridad : n" (tooltip "La palabra ya fue
    enviada. Se puede volver a enviar pulsando sobre ella."), "Ocultas : n" (tooltip "Las palabras ocultas no se
    pueden enviar. Pulsa sobre ella para enviarla"), "Con errores : n" (tooltip "Las palabras con errores no se pueden
    enviar. Pulsa sobre ella para ver los errores y corregirlos") (`L:412-421, 465-467`).
  - Pulsar una palabra alterna marcada/no marcada y muestra un panel de información: "Enviado una vez." /
    "Enviado `n` veces." + "Último envío `fecha`" o "Nunca enviadas"; para errores, alerta roja "Acepción `n`, le
    faltan los siguientes datos: `campos`" o "No se puede enviar la entrada porque no tiene acepciones, revisar
    ocultas"; iconos lápiz "Ir a edición de entrada", avión "Marcarda para enviar"/"No se va enviar".
  - Bajo la lista, por aula: "Último envío diccionario "`título`" : `dd/mm/aaaa`." o "Nunca se ha enviado.".
- Botones: **"ENVIAR"** (deshabilitado si 0 marcadas) y "CANCELAR" (a 2.1).
- Mensajes tras enviar (C:EnviosController.php:95-191): "Se han enviado correctamente las entradas del diccionario
  personal al diccionario de aula "`D`"" (verde); "No ha sido posible enviar el diccionario personal al diccionario de
  aula "`D`"" + "a alguna de las entradas enviadas le faltan los siguientes datos:" (rojo); sin aula seleccionada:
  "Debes seleccionar un diccionario de aula".
- Panel lateral 2.0.

### 2.9 Enviar una entrada (modales AJAX desde el icono avión)

`GET /personal/ajax/getDiccionariosAulaEntradaAjax?entrada_id=` decide (C:EnviosController.php:298-420):

1. **Sin aulas unidas** → modal 2.10 con cuerpo "Para enviar entradas de tu diccionario primero debes unirte a un
   diccionario de aula introduciendo el código que te ha proporcionado el profesorado." (`L:158`).
2. **Una sola aula** (`modal-enviarEntradaUnDiccionario.blade.php`): título "ENVIAR ENTRADA"; cuerpo
   "Vas a enviar la entrada "`E`" al diccionario "`D`" de "`profesor`"" (`L:194`) o, si envíos deshabilitados,
   ""`D`": Diccionario con envíos deshabilitados"; línea "último envío de esta entrada realizado el `dd/mm/aaaa`.";
   botones "Aceptar"/"Cancelar" (o sólo "VOLVER").
3. **Varias aulas** (`modal-enviarEntradaVariosDiccionarios.blade.php`): título "ENVIAR ENTRADA"; texto
   "Selecciona los diccionarios a los que quieres enviar la entrada "`E`""; lista de libros con checkbox (multi-selección;
   grises con tooltip "Diccionario con envíos deshabilitados"); por aula marcada: "Diccionario de Aula "`D`", último
   envío de esta entrada realizado el `fecha`."; botones "Aceptar"/"Cancelar".
- POST `/personal/{dic}/entrada/{e}/enviar`. Mensajes: "Se ha enviado correctamente "`E`" al diccionario de aula "`D`""
  / "No ha sido posible enviar "`E`" al diccionario de aula "`D`" revise los campos obligatorios: <lista>" /
  "Debes seleccionar un diccionario de aula" (`L:180-185`).

### 2.10 Modal "Unirse a diccionario de aula" (`V:layouts/partials/components/modal-unirsediccionario.blade.php`)

- Título "UNIRSE A DICCIONARIO DE AULA" (`L:157`). Cuerpo: "Únete a un diccionario de aula introduciendo su código"
  (o el texto de 2.9.1).
- Campo: label "CÓDIGO", input text `codigo` (sin validación de formato).
- Botones "Aceptar" (POST `/aula/unirse`) y "Cancelar".
- Resultados (C:DicAulaController.php:433-472): verde "Te has unido correctamente al diccionario de aula con el código
  "`C`""; verde "Ya estabas unido o unida al diccionario de aula con código "`C`""; rojo "no existe ningún diccionario
  de aula con el código indicado para este curso escolar, revisa los datos introducidos o habla con tu profesor/a";
  rojo (participante deshabilitado) "No tiene autorización para unirse, hable con el coordinador o coordinadora de
  este diccionario".

### 2.11 Ver comentarios del docente (alumnado)

**a) Página** — `GET /personal/{dic}/comentarios` (`ComentariosController@comentariosDiccionarioGET`),
`V:comentarios/comentariosDiccionarioVer.blade.php`. Título "Ver comentarios del o de la docente".
Migas "Inicio › Diccionario Personal › Comentarios al diccionario".
- Texto "Selecciona el diccionario del que quieras ver los comentarios" y lista de libros (radio; gris con tooltip
  "Diccionario sin comentarios" si no hay comentarios visibles; tooltip del título = nombre del coordinador).
- Sección **"Comentarios generales"** con botones de orden "Por fecha" y "Alfabéticamente"; tarjetas con fecha
  `dd/mm/aaaa` y el HTML del comentario. Vacío: "No hay comentarios generales".
- Sección **"Comentarios a entradas"** con "Por fecha"/"Alfabéticamente"; tarjetas "`dd/mm/aaaa` - **`entrada`**" +
  HTML. Vacío: "No hay comentarios comentarios a entrada" (sic).
- Botón "VOLVER". Panel lateral 2.0.

**b) Modal por entrada** (`modal-comentarosentrada.blade.php`): título "Ver comentarios docente"; mismo selector de
diccionario; bloque "Comentarios a la entrada."; tarjetas como arriba; botón "VOLVER".

### 2.12 Exportar PDF (diccionario personal)

- Ruta: `GET /personal/{dic}/pdf` → `V:diccionario/dpOptionsPDFView.blade.php` + `dpOptionsPDFform.blade.php`.
  Título/encabezado "Exportar a PDF". Migas "Inicio › Diccionario Personal › Exportar a PDF".
- Sección "entradas":
  | Label | name | Tipo |
  |---|---|---|
  | "Exportar las entradas y acepciones ocultas" | `showHidden` | checkbox |
  | "Exportar las acepciones marcadas con las etiquetas seleccionadas" | `exportOnlyTagged` | checkbox; al marcarlo aparece el selector doble de etiquetas de 2.6 |
- Botones: "Exportar a PDF" (POST a `/pdf/{dic}/getdp`, abre en pestaña nueva) y "Cancelar".
- Errores: "No se ha seleccionado ninguna eticqueta, seleccione al menos una o desmarque la opción" (sic) y
  "No se ha encontrado ninguna entrada" (`L:521-522`).

### 2.13 Modales ocultar / mostrar entrada

- "OCULTAR ENTRADA": "¿Quieres ocultar la entrada "`E`"? Las entradas ocultas no se podrán enviar al diccionario de
  aula." — tooltip "Ocultar entrada". "MOSTRAR ENTRADA": "¿Quieres mostrar la entrada "`E`"?" — tooltip "Mostrar
  entrada" (ojo rojo). Botones "Aceptar"/"Cancelar" (GET `…/ocultar`). Flash "Se ha ocultado la entrada: E" /
  "Se ha recuperado la visibilidad de la entrada: E" (`L:34-35, 209-219`).

---

## 3. Diccionario de aula — consulta (alumnado y profesorado)

### 3.1 Inicio del diccionario de aula

- Ruta: `GET /aula` (`DicAulaController@daHome`), vista `V:diccionario/aula.blade.php`. Título "Diccionario de Aula".
  Migas "Inicio › Diccionario de Aula".
- **Selector de diccionario activo** (`V:layouts/partials/components/select-DicAula.blade.php`), arriba a la derecha:
  título del diccionario activo + icono libro (atenuado si no es visible para alumnado). Al pulsar, bocadillo vertical
  con todos los diccionarios unidos (docentes ven también los no visibles, con tooltip "Diccionario de aula
  temporalmente no visible"; alumnado ve los no visibles atenuados con el mismo tooltip) y botón "Unirse a un
  diccionario". Seleccionar → POST `/aula/dicAulaActivo` (se guarda en sesión).
- **Estados vacíos** (aula.blade.php:7-21, 51-63):
  - Docente sin diccionarios del curso: "Todavía no has creado ningún diccionario de aula para este curso escolar,
    utiliza la opción o únete a un diccionario al que estés invitado (solo en el caso de que tenga una invitación a
    unirse a un diccionario)." (`L:482`).
  - Alumno sin diccionarios: "Únete a un diccionario de aula para poder consultarlo, pulsa sobre el icono del
    diccionario." (`L:483`).
  - Alumno sin diccionario activo: "No estás unida o unido a ningún diccionario o este no está visible para el
    alumnado." (`L:392`).
- Alumnado con diccionario activo: abecedario, buscador (igual que 2.2 pero sin "Crear entrada"), contador
  "Ver todas las entradas de `<título>` (`N`)".
- Profesorado: lo anterior + fila de botones (aula.blade.php:92-237):
  1. **"Crear diccionario de aula"** → 4.1.
  2. **"Gestionar Entradas Enviadas"** (deshabilitado sin diccionario activo) → bocadillo: "Ver y Publicar entradas"
     (4.2), "Comentarios generales" (4.3).
  3. **"Gestionar diccionario de aula"** (`L:283`) → bocadillo: "Invitar docente" (modal 4.1.f), "Modificar
     diccionario de aula" (4.1), "Exportar pdf" (4.6).

### 3.2 Listado / búsqueda del diccionario de aula

- Rutas: `/aula/entradas`, `/aula/entradas/letra/{l}`, `/aula/entradas/{consulta}`, `…/etiquetas/{ids}`,
  `POST /aula/entradas/consulta`; AJAX `…/n/{offset}`. Vista `V:diccionario/aula/consulta.blade.php`.
- Migas "Inicio › Diccionario de Aula › Búsqueda [› …]".
- Mensajes vacíos como 2.2 ("No existe la entrada "X"" — en aula **además aparecen literales de depuración**
  "datos: …" y "list tematica …" que romperían la vista, consulta.blade.php:11-12; y "No se encontró ningún
  resultado").
- Tarjeta de entrada (`V:layouts/partials/consulta/entradaAula.blade.php`): título; para el coordinador iconos
  lápiz (tooltip "Ir a edición de entrada" → 3.3) y pergamino publicar/anular (4.2). Acepciones
  (`acepcionAula*.blade.php`): sólo se muestran los campos configurados como visibles en el aula (más datos, ejemplo,
  lengua, etiquetas, audio, vídeo, imagen). Docentes ven a la izquierda el icono pergamino "Anular publicación" de la
  acepción y, si son coordinadores, el lápiz "Editar acepción" (4.5).
- Docentes: columna lateral de botones 3.1 (`actionbuttonAula.blade.php`, mismos textos; "Crear diccionario de aula" y
  "Gestionar Entradas Enviadas" escritos en código, no traducidos).

### 3.3 Ver entrada de aula

- Ruta: `GET /aula/{dic}/entrada/{id}` → `V:diccionario/aula/entrada.blade.php`. Título "`entrada` - Diccicionario de
  aula" (sic). Migas "Inicio › Diccionario de Aula › …".
- Título de la entrada; docentes: lápiz "Editar nombre" → 4.4. Acepciones como 3.2. Panel lateral docente.

### 3.4 Modales de ocultación en aula (docente)

- Entrada: icono pergamino, título "ANULAR PUBLICACIÓN", texto "¿Quieres anular la publicación de la entrada "E"?",
  tooltip "Anular publicación" (GET `/aula/{id}/entrada/{e}/ocultar`).
- Acepción: "OCULTAR ACEPCIÓN"/"MOSTRAR ACEPCIÓN" como 2.4 (GET `/aula/{id}/entrada/{e}/acepcion/{a}/ocultar`).
  No se puede ocultar la última: "No se puede ocultar la última acepción de una entrada, si lo desea puede anular la
  publicación de la entrada" (`L:213`).

### 3.5 Panel lateral "Pautas" (`V:diccionario/aula/pautas.blade.php`)

Desde "¿Qué puedo hacer?" → "Pautas". Panel deslizante con título "Pautas de "`<título aula>`"" y el HTML de las
pautas del aula en un TinyMCE de sólo lectura. Botón "+" girado para cerrar.

---

## 4. Diccionario de aula — gestión (profesorado)

### 4.1 Crear / Modificar diccionario de aula

- Rutas: `GET/POST /aula/create`; `GET/POST /aula/{id}/edit`. Vista `V:diccionario/crearDicAula.blade.php`.
  Título "Diccionario de Aula". Encabezado "Crear diccionario de aula" / "Modificar diccionario de aula".
  Migas "Inicio › Diccionario de Aula › Crear" / "… › Editar". Si el diccionario está inactivo no se puede editar:
  error "diccionario inactivo" (`L:509`).
- **Cabecera informativa**: "Coordinador o coordinadora: `nombre`" (`L:293`), "Curso escolar: `2025/2026`",
  "Código: `XXXXXX`" (sólo edición).
- **Datos generales**:
  | Label | name | Tipo | Oblig. | Opciones / validación |
  |---|---|---|---|---|
  | "Nombre del diccionario*" | `nombreDiccionario` | text | sí | "El campo nombre diccionario es obligatorio."; max 255 |
  | "Descripción" | `descripcionDiccionario` | textarea | no | |
  | "Nivel de Estudio" | `estudio` | select | no | "-- Seleccionar --" (0) + `mst_nivel_estudios` |
  | "Grupo" | `grupoLetra` | select | no | "-- Seleccionar --" (0) + letras A…Z |
  | "Área/Materia/Ámbito/Otros" | `areaMateria` | select | no (sin opción vacía: se preselecciona la primera) | `mst_areas_materias` |
  | "vigencia" (literal en minúscula) + icono info (tooltip `L:508`: "Cuando un diccionario deja de estar vigente, el alumnado no podrá enviar entradas ni unirse al diccionario pero se podrá consultar desde la opción de historicos.") | `selectVigencia` → oculto `vigencia` | select; en edición deshabilitado hasta pulsar lápiz "Editar" | no | opciones 1…`VIGENCIA_MAX`(10): "Vigente hasta el final de `2025/2026` (1 curso)" / "(`n` cursos)"; en edición se deshabilitan valores menores que los cursos ya transcurridos |
  ("Tipo de diccionario" existe en lang pero el campo está comentado; se fuerza a 1.)
- **Sección plegable "Importar entradas"** (sólo crear):
  - Radio "Crear diccionario vacío" (por defecto).
  - Radio "Importar entradas de otro diccionario" (abre modal 4.1.b) o, deshabilitado, "Importar entradas de otro
    diccionario (no existen diccionarios que se puedan importar)". Tras elegir: "Importar entradas "`D`" (`n`)".
- **Sección plegable "Configuración entradas"**:
  - "Número máximo de acepciones por entrada": select `maxAcepciones`, "Seleccione un valor..." + 1…10.
  - "Campos visibles en entradas": checkbox por campo (`<id>_visible`), **marcados por defecto al crear**.
  - "Campos obligatorios en entradas": checkbox por campo (`<id>_obligatorio`).
  - Etiquetas de los campos = `mst_campos_entrada.nombre_campo`: "Categoría gramatical", "Género", "Número",
    "Temáticas generales", "Más datos", "Ejemplo de uso", "Vídeo", "Audio", "Imagen", "Lengua"
    (database/seeders/MasterTablesDataSeeder.php:49-60 + MasterTablesDataSeederUpdate.php:23-27).
- **Sección plegable "Pautas diccionario"**: botón "Restablecer" (popover "Confirme el borrado de las pautas actuales
  y el restablecimiento de las originales" con "Restablecer"/"Cancelar"); label "Pautas diccionario"; editor TinyMCE
  `pautasEspecificas` precargado con la pauta maestra (`mst_pautas`). Obligatorio: "El campo pautas especificas es
  obligatorio.".
- **Sección plegable "Visibilidad, envíos y avisos"**:
  | Label | name | Tipo | Opciones |
  |---|---|---|---|
  | "Avisar de envíos de entradas al correo electrónico:" | `correoAvisos` | text | sin validación de email |
  | "Visibilidad del diccionario de aula" + info ("Permite (o no) que el alumnado vea el diccionario") | `visibilidad` | select | "Visible para el alumnado" (1) / "No visible para el alumnado" (0) |
  | "Permisos de envío del diccionario del aula" + info ("Permite (o no) enviar entradas desde el diccionario personal al diccionario de aula") | `habilitarEnvio` | select | "Permitir envíos de entradas desde diccionario personal" (1) / "No permitir envíos de entradas desde diccionario personal" (0) |
  | "Fecha de inicio para los envíos" + info ("Permite enviar entradas a partir de esta fecha") | `envioFecha` | date (deshabilitado si no se permiten envíos) | |
  | "Configurar visibilidad de comentarios para el alumnado" + info ("Permite mostrar (o no) al alumnado los comentarios de las entradas") | `visibilidadComentarios` | select, oblig. | "No visibles" (0) / "Visibles" (1) / "No visibles los posteriores al" (2) |
  | "Fecha para los comentarios" + info ("Los comentarios posteriores a esta fecha no estarán visibles") | `comentariosVisibleFecha` | date; sólo habilitado con opción 2 | required_if/before_or_equal (mensajes 0.1) |
- **Sección plegable "Participantes"** (sólo edición):
  - "Estudiantes" — "Quienes dispongan del código de diccionario" + icono ojo → modal 4.1.d.
  - "Profesorado" — tabla columnas "Nombre", "Apellidos", "Estado", "Administración" (`participantesAjax.blade.php`):
    - Estado: ojo (tooltip "deshabilitar acceso al diccionario") / ojo tachado ("habilitar acceso al diccionario").
    - Administración: ojo ("quitar permisos de administración") / ojo tachado ("dar permisos de administración").
    - Errores en fila roja: "No puedes autoexcluirte del diccionario", "No puedes quitarte permisos de administración a
      ti mismo", "Solo el propietario puede editar los permisos de administración", "No puedes eliminar el último
      coordinador", "No se pudo realizar el cambio" (`L:498-503`).
  - Enlace "Añadir profesorado" + icono "+" → modal 4.1.f.
- **Botones**: "Borrar diccionario" (sólo edición, rojo) y "Crear diccionario" / "Modificar diccionario" (abre 4.1.a).
- Éxito crear: "Diccionario de aula creado, código: `XXXXXX`" → `/aula`. Éxito editar: "Diccionario de aula
  modificado". Fallo de código: "No se puedo generar el código de aula" (sic). Borrado: "Se ha borrado correctamente el
  diccionario".

Modales de 4.1:

- **a) "Confirmación"**: "Confirme la creación del diccionario de aula." / "Confirme la modificación del diccionario de
  aula."; campos de sólo lectura "CURSO ESCOLAR" y "CÓDIGO" (en creación muestra "0": el código se genera al guardar);
  botones "Crear"/"Guardar" (submit) y "Cancelar".
- **b) "Importar entradas"**: secciones plegables "Diccionarios Actuales" y "Diccionarios años anteriores", cada una
  con lista de libros (selección única, tooltip "Coordinador / Coordinadora: …"); botones "Importar entradas" y
  "Cancelar".
- **c) Borrar** (tras `GET /aula/{id}/testdelete`), título "Borrar diccionario":
  - con publicadas: "No es posible eliminar un diccionario de aula que tiene entradas publicadas" + "Cancelar";
  - con envíos: "Este diccionario ya tiene envíos de entradas. Si se elimina se borrarán también los envíos ¿Desea
    eliminar de forma permanente este diccionario?" + "Borrar diccionario"/"Cancelar";
  - normal: ["Este diccionario ya tiene participantes que se han unido. "] "¿Desea eliminar de forma permanente este
    diccionario?" + "Borrar diccionario"/"Cancelar". Confirmar navega a **GET** `/aula/{id}/delete`.
- **d) "Alumnado unido al diccionario"**: tabla "Nombre", "Apellidos", "Estado" (ojo habilitar/deshabilitar acceso);
  botón "VOLVER".
- **f) "Invitar docentes"** (`modal-formulario-invitar-docente.blade.php`): campos "Email" (text, `emailDocente`),
  "Nombre" (`nombreDocente`), "Apellidos" (`apellidoDocente`), todos requeridos en servidor (max 150; mensajes
  genéricos "El campo email docente es obligatorio." etc.; **sin validación de formato email**); nota "Recuerde que el o
  la docente debe tener un nombramiento activo para poder acceder al diccionario"; botones "Invitar y enviar código" y
  "Cancelar". Resultado: "Se ha enviado el email" / "No se pudo enviar el email".

### 4.2 Ver y publicar entradas enviadas

- Ruta: `GET /aula/{id}/entradas` + `POST /aula/{id}/entradas` (AJAX de resultados). Vista
  `V:diccionario/entradasAula.blade.php`; resultados `V:layouts/partials/consulta/entradasAulaListado.blade.php`.
  Título "Diccionario de Aula Ver y Publicar entradas". Migas "Inicio › Diccionario de Aula › … › Ver y Publicar
  entradas".
- Vacío: "No se ha enviado ninguna entrada" (`L:514`).
- Encabezado: "Ver y Publicar entradas "`<título>`"".
- **Panel de búsqueda**:
  | Elemento | Tipo | Notas |
  |---|---|---|
  | placeholder "Buscar" (`buscarNombre`) | text | icono "x" tooltip "Limpiar filtros"; flecha tooltip "Mostrar opciones avanzadas del buscador" |
  | "Estado" (`estado`) | select | "-- Seleccionar --" (0), "Publicada", "No publicada", "Todas" (0) |
  | "Participante" (`estudiante`) | select | "-- Seleccionar --" + "Nombre Apellidos" de participantes |
  | "Envío" — "Desde" / "Hasta" | date ×2 | |
  | Carrusel de participantes | radio con avatar y nombre (tooltip nombre completo) | |
  | Abecedario (modo AJAX) | | letra seleccionada en `selectedLetra` |
  | Botón "Buscar" | | |
- **Resultados**: "Entradas enviadas por `Nombre`|el alumnado (`n` entradas)"; "Filtros activos: Buscando "x",
  empezando por la letra "A", estado "…", participante "…", envíos desde "dd/mm/aaaa" hasta …"; botones de orden
  "Por fecha" / "Alfabéticamente"; icono "Publicar todo".
- Cada entrada enviada: título; iconos:
  - Papelera "Eliminar envío" (gris si publicada) → popover "Va a eliminar, sin posibilidad de recuperación, el envío
    de la entrada "E" realizado por "P"." con "Eliminar"/"Cancelar" (POST). Mensajes "Entrada eliminada con éxito" /
    "Error al eliminar la entrada"; servidor: "No se puede eliminar un envío de entrada publicado.".
  - Bocadillo "Comentar" (verde si ya tiene comentarios, gris si no) → modal 4.2.a.
  - Pergamino gris "Publicar" → popover "Va a publicar en el diccionario de aula "D" la entrada "E" enviada por P" con
    "Publicar"/"Cancelar"; o pergamino de color "Anular publicación" → "La entrada "E" enviada por P ya está publicada
    en el diccionario "D". ¿Desea anular la publicación?" con "Anular publicación"/"Cancelar". Paneles flotantes:
    "Entrada publicada con éxito" / "Error al publicar la entrada" / "Publicación anulada con éxito" / "Error al anular
    la publicación"; duplicado: "Error: entrada duplicada" (`L:518`).
  - Línea "por `nombre` `apellidos` el `dd/mm/aaaa`" e icono "Entrada modificada por docente" si se editó.
  - Acepciones como 3.2.
- **"Publicar todo"** (popover `modal-publicar-listado.blade.php`): "**Publicación múltiple de entradas en el
  diccionario "D":**", "Número de entradas resultado de la búsqueda: n", "Número de entradas que se publicarán: n";
  "No hay entradas para publicar."; "**Detalle de entradas que no se publicarán:**" con "Por estar ya publicadas(n): "
  y "Por estar repetidas en el resultado de la búsqueda(n): " (enlaces a cada entrada); botones "Publicar"/"Cancelar"
  o "Cerrar". Mensajes "Entradas publicada con éxito" (sic) / "Error al publicar las entradas".

**4.2.a Modal "Comentar entrada"** (`modal-formulario-comentarEntrada.blade.php`, cargado por
`GET /personal/ajax/getComentarEntradaAjax/{id}`):
- Título "Comentar entrada"; línea "`entrada` ( `nombre apellidos` )"; editor TinyMCE `tinyComentario`; nota
  'La configuración actual de visibilidad de comentarios para el alumnado es "visibles"|"no visibles"'; "Historial de
  comentarios" con cada comentario (fecha `dd/mm/aaaa`, lápiz "Editar", papelera "Eliminar", HTML).
- Botones "Guardar" y "Cerrar". Vacío: panel "El comentario no puede estar vacío".
- Edición inline del historial: TinyMCE + "Guardar"/"Descartar"; borrado: popover "¿Quiere borrar el comentario?"
  "Si"/"No"; mensajes JSON "Comentario editado con éxito", "Comentario borrado con éxito", "Error al borrar el
  comentario".

### 4.3 Comentarios generales (docente)

- Ruta: `GET/POST /aula/{id}/comentarios` → `V:comentarios/comentariosDiccionarioEnviar.blade.php`. Título y
  encabezado "Comentarios Generales". Migas "Inicio › Diccionario de Aula › … › Comentarios Generales".
- Selector de diccionario activo (3.1).
- Campo "Participante" (`estudiante`, select "--Seleccionar--" + "Nombre Apellidos"); sin participantes: "No hay datos
  de estudiantes relacionados con este diccionario".
- Al elegir participante: botón **"Nuevo comentario"** (+) y lista "Historial de comentarios" (botón "Por fecha");
  vacío: "No hay comentarios".
- Bloque nuevo comentario: TinyMCE `tinyComentario` + nota "La visibilidad actual para comentarios es `Visible|No
  Visible|Anteriores al dd/mm/aaaa` para el alumnado"; botones "GUARDAR" y "DESCARTAR".
- Cada comentario: fecha + papelera (tooltip "Borrar") → modal "CONFIRMACIÓN" "¿Está seguro o segura de borrar el
  comentario?" "Cancelar"/"Aceptar" (GET `/aula/{id}/comentario/{c}/delete`).
- Botón "VOLVER" (a `/aula`).
- Validación: `estudiante` requerido ("El campo estudiante es obligatorio."), comentario vacío "No puede enviar un
  comentario vacío". Éxito: "Se ha guardado el comentario para el diccionario de `Nombre`"; borrado "Se ha borrado el
  comentario".

### 4.4 Editar nombre de entrada de aula

- Ruta `GET/POST /aula/{dic}/entrada/{id}/edit` → `V:diccionario/aula/daEntradaEdit.blade.php`. Título
  "`entrada` - Editar nombre de entrada". Encabezado "Editar nombre de entrada".
- Campo `entrada_entrada` text maxlength 150 (required/max como 2.5). Botones "GUARDAR", "CANCELAR".
- Error duplicado: "No se puede modificar la entrada porque ya existe otra con ese mismo texto".

### 4.5 Editar acepción enviada (docente coordinador)

- Ruta `GET /aula/{dic}/entrada/{e}/acepcion/{a}/edit`, `POST /aula/acepcion/{a}/edit` →
  `V:diccionario/aula/acepcionEdit.blade.php`. Encabezado "**Edición de acepción** para "E"". Migas
  "Inicio › Diccionario de Aula › `aula` › `entrada` › `n`".
- Mismos campos que 2.6 (Lengua/Traducción, Categoría Gramatical, Género, Número, Definición *, Ejemplo de uso, Más
  datos, Etiquetas, tarjetas de imagen/audio/vídeo con borrado vía GET `/aula/acepcion/{a}/dpmedio/{m}/delete`).
  **Sin** botones "Capturar"/"Grabar".
- Botones "GUARDAR", "CANCELAR" (a 3.2).

### 4.6 Exportar PDF (aula)

- Ruta `GET /aula/{dic}/pdf` → `V:diccionario/daOptionsPDF.blade.php` + `aulaOptionsPDFform.blade.php`.
  Encabezado "Exportar a PDF".
- Sólo el checkbox "Exportar las acepciones marcadas con las etiquetas seleccionadas" (+ selector de etiquetas);
  el de ocultas está comentado. Botones "Exportar a PDF" (POST `/pdf/{dic}/getda`, pestaña nueva) y "Cancelar".

---

## 5. Administración (Voyager, `/admin`)

### 5.1 Menú lateral de Voyager (database/seeders/MenuItemsTableSeeder.php:415-800, VoyagerCustomization.php:1980-2130)

Items sembrados: "Tablero", "Logs", "Multimedia", "Roles", "Herramientas" (› "Diseñador de Menús", "Base de Datos",
"BREAD", "Compás"), "Parámetros", "Vigencia", "Audits", "Usuarios", "Categorías", "Posts", "Páginas",
"Diccionarios curso escolar" (×2: BREAD `dic-aulaCursoEscolar` y estadística `dicsCursoEscolar`),
"Diccionarios personales" (×2: BREAD y estadística), "Centros curso escolar" (×2, ambos al BREAD `centrosBread`).
"Grabacion" está comentado.

### 5.2 BREAD genéricos (pantallas estándar de Voyager: listado/ver/editar/añadir/borrar)

| Slug | Título plural | Uso |
|---|---|---|
| users | Usuarios | usuarios |
| roles | Roles | roles |
| menus | Menús | infraestructura Voyager |
| audits | Audits | registro owen-it |
| categories / posts / pages | Categorías / Posts / Páginas | **demo de Voyager, sin uso en la app** |
| dic-aulaCursoEscolar | Dic Aulas Cursos Escolares | tabla `dic_aula` |
| dic-personal-bread | Diccionarios personales | tabla `dic_personal` |
| centrosBread | Centros curso escolar | tabla `centros` |
(database/seeders/DataTypesTableSeeder.php:25-194). **No hay BREAD para tablas maestras** (`mst_*`, pautas):
no se pueden editar desde la UI salvo con la herramienta "Base de Datos".

### 5.3 Pantallas propias (controlador `C:Voyager/VoyagerCompassController.php`)

**Las vistas `voyager::compass.vigencia`, `dicsCursoEsc`, `dicsPersonales`, `centrosAñoEscolar`, `logs` y `record`
no están en el repositorio** (no existe `resources/views/vendor/voyager`); vivían en el paquete Voyager modificado.
Sólo se pueden reconstruir sus datos y textos (`resources/lang/es/admin.php`):

- **Vigencia** (`GET /admin/vigencia`, `/admin/vigencia/{tipo}`): selector "Mostrando la lista de diccionarios: "
  (tipos 0 no vigentes, 1 vigentes, 2 atemporales, 3 errores de estado); bloques "Todos los diccionarios:",
  "Diccionarios con mas de un año de vigencia:", "Diccionarios atemporales:"; columnas "título", "año inicio",
  "año fin", "estado", "acciones"; acciones "Verificar vigencia diccionarios activos", "Verificar vigencia",
  "Hacer atemporal", "Quitar atemporal", "Activar"; confirmaciones "Atención se van a desactivar(estado=0) :n
  diccionarios de aula no vigentes para el curso escolar :curso. ¿Desea continuar?", "¿ Confirme la activación del
  diccionario en el curso escolar actual ?", "¿ Confirme que desea hacer atemporal este diccionario ?"; mensajes
  "Desactivados :num diccionarios", "Desactivados :nombre", "No se han realizado cambios", '":nombre" ahora es
  atemporal', '":nombre" ya no es atemporal', '":nombre" ahora esta vigente'. Todas las acciones son **GET**.
- **Estadísticas** (`/admin/diccionarios_curso`, `/admin/diccionarios_personales`, `/admin/centrosAñoEscolar`):
  títulos "ESTADÍSTICA 1: DIC DE AULA VIGENTES en el curso actual  a dia de hoy", "ESTADÍSTICA 1.b : DICIONARIOS DE
  AULA TODOS", "ESTADÍSTICA 2: DICCIONARIOS PERSONALES", " ESTADÍSTICA 3: CENTROS ACTIVOS", "ESTADÍSTICA 5: docentes
  que participan en diccionarios aula vigentes", " ESTADÍSTICA 6: entradas totales", "ESTADÍSTICA 7: entradas
  publicadas en diccionarios de aula"; columnas "curso", "número de diccionarios", "número de diccionarios vigentes",
  "rol diccionarios id", "número de personas", "centros", "total centros", "número de diccionarios creados",
  "número de entradas"; acción "Generar CSV" / "Exportar todos los datos de la tabla a CSV:" (rutas `/getCSV`,
  `/getAllDatesCSV`).
- **Logs** (`GET /admin/logs`): visor de logs de Laravel con borrado (mensajes de Voyager).
- **Grabación** (`GET /admin/grabacion`, POST `/admin/sendFileJs`, `/admin/sendFotosJs`): prueba técnica de grabación;
  sin menú.
- **Avisos** (`/admin/warnings…`), **Configuración** (`/admin/configuracion`), **Mantenimiento** (`/admin/maintenance`):
  rutas declaradas (routes/web.php:356-366) y textos en `resources/lang/es/voyager.php` ("Avisos", "Configuración de
  avisos mostrados en la plataforma", "Titulo", "Texto", "Estado", "Bloqueante", "Fecha de inicio", "Fecha de fin",
  "Activo"/"Inactivo", "Ya hay un aviso activado", "Se ha cambiado el estado de un aviso", "No se puede borrar porque el
  aviso está activado", "Borrado con éxito", "Añadir filtro", "Restaurar filtro", "Export"), pero **los métodos de
  controlador no existen** → pantallas muertas (error 500). No hay modelo ni tabla de avisos.

---

## 6. Correo visible para el usuario

Invitación a docente (`V:layouts/partials/mail/inviteDocenteMailView.blade.php`), asunto "LexiCán: Invitación a unirse
a un diccionario.": "Contenido automático no responda a este correo"; "`Nombre Apellido`, le informamos que se le ha
invitado a participar en el diccionario de aula con el código "`C`""; pasos: acceder (URL de la app), identificarse
en el sistema centralizado de autenticación de la Consejería de Educación, pulsar la pestaña "diccionario de aula",
pulsar el icono del diccionario y "+ Unirse a un diccionario", escribir el código, pulsar "unirse"; "Para más
información sobre el diccionario LexiCán pulse en "¿Qué puedo hacer?""; "Reciba un cordial saludo."; aviso legal de
confidencialidad. (El texto de los pasos no coincide con la UI real: el botón es "Aceptar", no "unirse".)

---

## 7. Vistas presentes pero sin uso real (no reconstruir)

| Vista | Motivo |
|---|---|
| `V:auth/*` (login, register, passwords, verify) | Autenticación sólo por CAS |
| `V:home.blade.php` ("Dashboard / You are logged in!"), `V:welcome.blade.php` | Scaffold Laravel |
| `V:diccionario/dpEntrada/listado.blade.php` | Incluye `layouts.partials.buscar.entrada` que no existe; imprime `__('diccionario.buscar_titulo')` literal |
| `V:development/unirsediccionario.blade.php` (`GET /aula/DEV_unirseGET`) | Pantalla de desarrollo con "CURSO ESCOLAR", "nivel Estudio", "grupo" |
| `V:media/grabacion.blade.php` ("Página de grabacion") | Prueba |
| `/pdf/paginapdf`, `/mail/paginamail`, `/mail/get`, `/test` | Rutas de prueba (botón PDF/mail, simulación CAUCE) |
| `V:layouts/partials/consulta/acepciones.blade.php` | Comentado como "no lo estoy usando" |
| `V:layouts/partials/alerts/alertas-test.blade.php`, `modal-base.blade.php` (imprime el id del modal) | Pruebas |
| Vue `ExampleComponent`, `entrada.vue`, `EntradaBuscador.vue` | No montados en ninguna vista |

---

## 8. Observaciones para el rediseño React

- Vocabulario clave a conservar: "Entrada", "Acepción", "Definición", "Ejemplo de uso", "Más datos", "Lengua",
  "Traducción", "Categoría Gramatical", "Género", "Número", "Etiquetas", "Diccionario Personal", "Diccionario de Aula",
  "Unirse a un diccionario", "CÓDIGO", "ENVIAR ENTRADAS", "Ver y Publicar entradas", "Publicar", "Anular publicación",
  "Eliminar envío", "Comentarios generales", "Comentarios a entradas", "Pautas", "Coordinador o coordinadora",
  "Participante", "Estudiante"/"Docente", "vigencia"/"Vigente hasta el final de …", "Exportar a PDF".
- Mezcla de MAYÚSCULAS ("GUARDAR", "CANCELAR", "ENVIAR", "VOLVER") y capitalización normal ("Aceptar", "Cancelar");
  conviene unificar.
- "Más datos" se guarda en `frase_ejemplo` y "Ejemplo de uso" en `ejemplo2` (inversión histórica de nombres,
  acepcionFormFieldComun.blade.php:95-100).
- El valor por defecto del select "Lengua" es `1` (no vacío) y la acepción guarda idioma aunque no se use.
- Todas las acciones destructivas de usuario (borrar entrada/acepción/medio, ocultar, borrar diccionario de aula,
  borrar comentario general) son enlaces **GET** detrás de un modal.
- Comentarios y pautas se guardan como HTML de TinyMCE (con subida de imágenes a `POST /upload`) y se pintan sin
  escapar (`{!! !!}`).
- Las pantallas de administración propias no pueden reproducirse fielmente porque sus vistas no están versionadas.

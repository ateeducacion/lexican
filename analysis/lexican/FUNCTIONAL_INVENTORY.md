# Inventario funcional de LexiCán (legacy Laravel 8)

Documento exigido por §6 y §104 («Inventario funcional») de `1er-prompt.md`. Se ha elaborado leyendo el código. No se ha ejecutado la aplicación. Todas las referencias `fichero:línea` son relativas a la raíz del código legacy (rama `upstream`, commit `c80ff65`).

Complementos:

- `analysis/lexican/UI_SCREENS.md`: pantallas, etiquetas y textos exactos.
- `analysis/lexican/PREFLIGHT.md`: entorno y verificación previa.

Convenciones:

- **Clasificación** (§6): `obligatoria`, `útil`, `prescindible`, `legacy sin uso`, `administrativa`, `integración externa`.
- **Decisión** (§104): `conservar`, `rediseñar`, `eliminar`, `dudosa`. «Conservar» se refiere al comportamiento, no al código. En el nuevo stack todo se reescribe.
- Roles: **A** = alumno, **D** = docente, **Coord** = docente con `rol_diccionario_id = docente` en ese aula (lo que la política `esCoordinador` llama coordinador), **Prop** = propietario del aula (`dic_aula.persona_id`), **Adm** = `admin` de Voyager, **OT** = rol `user` de Voyager («Oficina técnica»), **Anón** = sin sesión.
- ⚠ GET = ruta GET que modifica o borra datos. Todas se listan otra vez en la sección «Rutas GET destructivas».
- 🔓 = sin comprobación de autorización (o con una comprobación insuficiente) en el controlador.

---

## 0. Resumen

| Clasificación | Nº funcionalidades |
|---|---|
| obligatoria | 54 |
| útil | 29 |
| prescindible | 2 |
| legacy sin uso | 19 |
| administrativa | 13 |
| integración externa | 6 |
| **Total** (filas de §1–§6) | **123**, más 4 filas «no existe» |

| Decisión | Nº |
|---|---|
| conservar | 16 |
| rediseñar | 81 |
| eliminar | 20 |
| dudosa | 6 |

Funcionalidades que §6 da por supuestas y que **no existen**: pantalla de perfil, consulta de aulas «históricas», administración de tablas maestras, tareas programadas, CSV/JSON de diccionarios, notificaciones por correo de envíos y publicaciones, estado «rechazada», medios por URL externa y avisos de administración.

Hallazgos con más impacto en el rediseño:

1. **El seed limpio no coincide con producción en `mst_campos_entrada`.** El código asume `5=frase_ejemplo/«Más datos», 6=vídeo, 7=audio, 8=imagen, 9=lengua, 10=ejemplo2` (`config/ctes.php:451-462`, `app/Helpers/dpEnvios_helper.php:545-584`). `MasterTablesDataSeeder` inserta `5=Más datos, 6=Ejemplo de uso, 7=Vídeo, 8=Audio, 9=Imagen, 10=Otros lenguajes` (`database/seeders/MasterTablesDataSeeder.php:49-60`). Una instalación desde cero valida mal los campos obligatorios y cuelga los idiomas del campo «Imagen». El orden real de producción se reconstruye en «Datos maestros».
2. **«Máximo de acepciones por entrada» no funciona.** La UI ofrece 1..10, pero se guarda `isset(...) ?? …`, es decir, un booleano (`app/Helpers/dicAula_helper.php:78`), y el valor no se comprueba en ningún sitio.
3. **El «tipo de diccionario» (Diccionario general/Canarismos) y la «enseñanza» están muertos.** El tipo se fuerza a 1 (`app/Http/Controllers/DicAulaController.php:124`, selector comentado en `resources/views/diccionario/crearDicAula.blade.php:48-62`). `mst_ensenanza_id` siempre vale el valor por defecto 1 y no aparece en ningún formulario.
4. **No existe «rechazo».** El docente puede publicar, despublicar (pasa a oculta) o «eliminar envío», que hace `forceDelete` físico del envío y sus acepciones, temáticas y medios (`app/Helpers/dicAula_helper.php:919-962`). El alumno no recibe ningún estado «rechazada».
5. **Avisos, mantenimiento y configuración de Voyager son rutas sin implementación.** `warnings*`, `maintenance` y `configuracion` apuntan a métodos que no existen (`routes/web.php:356-366`, `app/Http/Controllers/Voyager/VoyagerController.php:16-22`). Las tablas `avisos*` y `entradas_compartidas` no se usan.
6. **Las vistas de administración propias (`voyager::compass.*`: vigencia, estadísticas, grabación) no están en el repositorio.** Las devuelven `VoyagerCompassController.php:155,205,248,272,520` y `vRecordingController.php:23`, pero no existe `resources/views/vendor/voyager`. Vivían en `vendor/`, que está en `.gitignore`.
7. **No hay CSV ni JSON de diccionarios.** El único CSV es de estadísticas de administración, y `GET /getCSV?methodName=` invoca cualquier método público de `VoyagerCompassController` para cualquier usuario autenticado (`app/Http/Controllers/CSVController.php:101-106`).
8. **Muchas acciones mutadoras carecen de autorización.** Ejemplos: borrar aula (`DicAulaController.php:1598-1608`), guardar edición de aula (`:297-336`), editar o borrar comentarios (`:1173-1243`), editar acepciones publicadas (`EnvioAcepcionController.php:36-224`), cambiar el rol de participantes (`DicAulaController.php:702-707`) y exportar a PDF cualquier diccionario personal (`PDFController.php:36-74`).
9. La promesa de «consultar desde la opción de históricos» (`resources/lang/es/diccionario.php:506-508`) **no existe**. Los aulas no vigentes solo aparecen como origen para «Importar entradas» al crear un aula.
10. Comentarios: el estado leído/no leído nunca pasa a «leído», y `comentarios_entradas.persona_id` guarda el **id de usuario**, no el de persona (`app/Helpers/dicAula_helper.php:1341`).

---

## 1. Acceso y usuarios

| Funcionalidad | Rol(es) | Rutas | Controlador@método | Vistas | Clasificación | Decisión | Evidencia |
|---|---|---|---|---|---|---|---|
| Login CAS (redirige al servidor CAS) | Anón | `GET /login`, `GET /cas/login` | closure → `cas()->authenticate()` | — | integración externa | rediseñar (ADR CAS 3.0, §38) | `routes/web.php:396-398,409-411`; `app/Cas/CasManager.php:202-215` |
| Callback CAS + validación CAUCE + alta/actualización de persona/usuario/centros | Anón→todos | `GET /cas/callback` | `Auth\CasController@callback` → `userValidatorCAUCE`, `asignarCentroaUsuario` | `errors/403` | integración externa | rediseñar (adapter `InstitutionalDirectory`) | `app/Http/Controllers/Auth/CasController.php:28-41`; `app/Helpers/global_helper.php:713-926,930-1113` |
| Sesión `userData` (user, persona, centros, roles; aula activa) | todos | — | `setUserDataSession()` | — | obligatoria | rediseñar (sesión en PostgreSQL, §40) | `app/Helpers/global_helper.php:466-497`; `app/Http/Controllers/DicAulaController.php:201-202` |
| Relación `users` ↔ `personas` 1:1 vía `users_personas`. La persona guarda NIF/NIE, pasaporte, CIAL, nombre, apellidos y avatar | sistema | — | `User::userPersona`, `Persona::personaUser` | — | obligatoria (identidad) | rediseñar (fusionar en `users` + `auth_identities`, sin NIF/CIAL; §21) | `database/migrations/2020_04_19_113507_create_initial_structure.php:106-115,381-386`; `app/User.php:52-61`; `app/Models/Persona.php:34-44` |
| Roles globales `admin`, `user` (Oficina técnica), `docente` y `alumno`. CAUCE 1, 4 y 5 dan docente; 3 o cualquier otro, alumno. **El rol solo se calcula al crear el usuario.** | sistema | — | `obtenerEquivalenciaRol` | — | obligatoria | rediseñar (rol global ≠ rol en aula, §26; recalcular en cada login) | `app/Helpers/global_helper.php:824-863,1116-1129`; `config/ctes.php:68-72,472-477`; `database/seeders/UsersRolesTablesSeeder.php:56-63`; `database/seeders/RolesTableSeeder.php:15-27` |
| Perfil de usuario | — | — | — | — | — | **no existe**. Solo hay nombre del diccionario y avatar (ver §2). La única ruta de perfil es `/admin/profile` de Voyager | `routes/web.php` (sin ruta de perfil); `config/voyager.php:131-145` |
| Centros asociados (`centros`, `users_centros`; centro comodín `00000000` «Sin centro educativo») | sistema | — | `asignarCentroaUsuario` | — | administrativa | dudosa (solo alimenta estadísticas de admin; no condiciona permisos) | `app/Helpers/global_helper.php:945-1111`; `app/Http/Controllers/Voyager/VoyagerCompassController.php:213-241` |
| Logout (cierra la sesión local y redirige al logout de CAS) | todos | `POST /cas/logout` | closure | `layouts/app.blade.php:67-99` | obligatoria | conservar | `routes/web.php:402-407`; `app/Cas/CasManager.php:309-330` |
| Login local de Voyager (`/admin/login`) para admin y OT. Es también el destino de `auth` en `APP_ENV=local` | Adm, OT | `GET/POST /admin/login` | `Voyager\VoyagerAuthController@login/postLogin` | Voyager | administrativa | rediseñar (login demo, §38; break-glass si se decide) | `app/Http/Middleware/Authenticate.php:26-34`; `app/Http/Controllers/Voyager/VoyagerAuthController.php:25-67` |
| Redirección inicial por rol (admin/user → `/admin`; resto → `/personal`) | todos | `GET /` | closure, `DiccionarioPersonalController@dpHome` | — | obligatoria | conservar | `routes/web.php:31-38`; `app/Http/Controllers/DiccionarioPersonalController.php:1164-1167` |
| Registro público y recuperación de contraseña de Laravel (`Auth::routes()`) | Anón | `GET/POST /register`, `/password/*` | `Auth\RegisterController`, `ForgotPassword…`, `ResetPassword…`, `Verification…` | `auth/register`, `auth/passwords/*`, `auth/verify` | legacy sin uso | eliminar (crea usuarios sin persona ni rol) | `routes/web.php:280`; `app/Http/Controllers/Auth/RegisterController.php:40-83` |
| Aceptación de cookies (cookie `lxcn_acceptcookies`, 3 meses) | todos | `GET /acceptCookies` | `HomeController@acceptCookies` | `diccionario/cookies` | prescindible | dudosa (solo hay cookies técnicas; revisar con el DPD) | `routes/web.php:28`; `app/Http/Controllers/HomeController.php:207-218` |
| Health check | Anón | `GET /health-check`, `GET /hc` | closures | — | útil | conservar (`/api/health`) | `routes/web.php:21-26` |
| Prueba de CAUCE con XML fijo: llama a `userValidatorCAUCE`, que **crea personas y usuarios** ⚠ GET | cualquiera autenticado | `GET /test` | `HomeController@test` | — | legacy sin uso | eliminar | `routes/web.php:266`; `app/Http/Controllers/HomeController.php:101-205` |
| Suplantación CAS (`cas_masquerade`) | dev | — | `CasManager` | — | legacy sin uso | eliminar (lo sustituye la autenticación demo) | `app/Cas/CasManager.php:84-88,203-205`; `config/cas.php:169` |
| Creación de admin con credenciales fijas en el código (método sin ruta) | — | — | `HomeController@createAdmin` | — | legacy sin uso | eliminar (rotar la credencial expuesta) | `app/Http/Controllers/HomeController.php:70-87` |

## 2. Diccionario personal

| Funcionalidad | Rol(es) | Rutas | Controlador@método | Vistas | Clasificación | Decisión | Evidencia |
|---|---|---|---|---|---|---|---|
| Creación automática del diccionario personal (uno por persona, título «Mi diccionario personal») | A, D | (implícita en `/personal`) | `dpDiccionario_GetDiccionarioByUserActual`, `dpDiccionario_AddDiccionario` | — | obligatoria | conservar | `app/Helpers/dpDiccionarios_helper.php:52-104` |
| Cambiar el nombre del diccionario y el avatar (SVG de `storage/app/public/avatares/oficiales`). 🔓 el avatar es un texto libre sin validar | A, D | `GET /personal/general/modificar`, `POST /personal/{d}/general/update` | `DiccionarioPersonalController@dpModificarDiccionarioGET`, `@dpSaveModificarDiccionarioPOST` | `diccionario/dpDiccionario/dpModificacion` | útil | rediseñar (avatar de una lista cerrada) | `app/Http/Controllers/DiccionarioPersonalController.php:1051-1150`; `config/ctes.php:100` |
| Inicio del diccionario personal (contador, buscador, alfabeto, menú «¿Qué puedo hacer?») | A, D | `GET /personal`, `GET /personal/{d}` | `@dpHome` | `diccionario/personal` | obligatoria | rediseñar | `routes/web.php:44,80`; `DiccionarioPersonalController.php:1158-1195` |
| Listado de todas las entradas con scroll infinito (10 por carga) | A, D | `GET /personal/entradas`, `GET /personal/entradas/n/{offset}` | `@dpGetEntradaAllGET`, `@dpGetEntradasAjax` | `diccionario/consulta`, `diccionario/consultaAjax` | obligatoria | rediseñar (paginación por cursor) | `routes/web.php:50,58`; `DiccionarioPersonalController.php:1207-1300`; `config/ctes.php:380` |
| Búsqueda por texto (`LIKE %q%`, máx. 150) con filtro opcional por temáticas | A, D | `POST /personal/entradas/consulta` → `GET /personal/entradas/{consulta}`, `…/{consulta}/etiquetas/{ids}`, `…/etiquetas/{ids}`, AJAX `/personal/x/entradas/…/n/{offset}` | `@dpBuscarEntradaPOST`, `@dpGetEntradaByConsultaGET`, `@dpGetEntradasAjax` | `diccionario/consulta`, `layouts/partials/components/buscador` | obligatoria | conservar (semántica) / rediseñar (API) | `routes/web.php:52-65`; `DiccionarioPersonalController.php:259-305,1437-1525`; `app/Helpers/dpEntradas_helper.php:530-618` |
| Búsqueda por inicial (A–N, Ñ, O–Z) | A, D | `GET /personal/entradas/letra/{letra}[/n/{offset}]` | `@dpGetEntradaByInitialGET`, `@dpGetEntradasbyInitialAjax` | `layouts/partials/components/abecedario` | obligatoria | conservar | `routes/web.php:51,59`; `DiccionarioPersonalController.php:1302-1375`; `app/Helpers/global_helper.php:682-685` |
| Autocompletado (JSON con todas las palabras) | A, D | `GET /personal/entradas_json` | `@dpGetEntradaAllJson` | `buscador`, `resources/js/searchAutocompletion.js` | útil | rediseñar (endpoint de sugerencias con límite) | `routes/web.php:47`; `DiccionarioPersonalController.php:1527-1548` |
| Crear entrada («buscador de añadir»): si la palabra existe redirige a ella; si no, abre el formulario de la primera acepción. La entrada se crea al guardar la acepción | A, D | `GET /personal/entrada/create`, `GET /personal/entrada/insert` | `@dpCreateEntradaGET`, `@dpInsertEntradaGET` | `diccionario/dpEntrada/masterEntrada`, `layouts/partials/dpEntrada/dpEntradaBuscador` | obligatoria | rediseñar | `routes/web.php:83-84`; `DiccionarioPersonalController.php:148-247,521-526` |
| Ver entrada con sus acepciones | A, D | `GET /personal/{d}/entrada/{e}` | `@dpGetEntradaGET` | `masterEntrada`, `layouts/partials/consulta/acepciones` | obligatoria | rediseñar (vista separada del editor, §50) | `routes/web.php:90`; `DiccionarioPersonalController.php:44-86` |
| Editar el texto de la entrada (normaliza espacios y es única en el diccionario) | A, D | `GET/POST /personal/{d}/entrada/{e}/edit` | `@dpEditEntradaGET`, `@dpEditEntradaPOST` | `layouts/partials/dpEntrada/dpEntradaEdit` | obligatoria | conservar | `routes/web.php:87-88`; `DiccionarioPersonalController.php:329-425`; `app/Helpers/global_helper.php:1229-1240` |
| Borrar entrada: soft delete. Si estaba enviada y sin publicar, también retira el envío; si está publicada, se impide ⚠ GET | A, D | `GET /personal/{d}/entrada/{e}/delete` | `@dpDeleteEntradaGET` → `dpEntrada_DeleteEntradaById` | `botones/entrada/delete` | obligatoria | rediseñar (DELETE, recuperable) | `routes/web.php:86`; `DiccionarioPersonalController.php:106-140`; `app/Helpers/dpEntradas_helper.php:44-104` |
| Ocultar/mostrar entrada (estado 1↔2). Las ocultas no se envían ni se exportan por defecto ⚠ GET | A, D | `GET /personal/{d}/entrada/{e}/ocultar` | `@dpOcultarEntradaGET` | `botones/entrada/ocultar` | útil | rediseñar | `routes/web.php:89`; `DiccionarioPersonalController.php:438-470`; `app/Helpers/dpEntradas_helper.php:663-690` |
| Añadir acepción (crea la entrada si no existe; el orden es el siguiente libre) | A, D | `GET /personal/{d}/entrada/{e}/acepcion/create`, `POST /personal/entrada/acepcion/create` | `@dpCreateAcepcionGET`, `@dpInsertAcepcionPOST` | `layouts/partials/dpAcepcion/dpAcepcionFormulario`, `acepcionFormFieldComun`, `tematicas` | obligatoria | rediseñar (editor por tarjetas, §34) | `routes/web.php:101-102`; `DiccionarioPersonalController.php:487-610,787-830` |
| Editar acepción | A, D | `GET …/acepcion/{a}/edit`, `POST …/acepcion/{a}/update` | `@dpEditAcepcionGET`, `@dpEditAcepcionPOST` | `dpAcepcionFormulario` | obligatoria | rediseñar | `routes/web.php:104-105`; `DiccionarioPersonalController.php:623-712,842-901` |
| Campos de la acepción: categoría gramatical, género, número, idioma + «palabra en otro idioma» (≤150), definición (**obligatoria**, ≤1000), «Más datos» (`frase_ejemplo`, ≤255), «Ejemplo de uso» (`ejemplo2`, ≤255), temáticas (N) y medios | A, D | — | `dpAcepcionSave` | `acepcionFormFieldComun` | obligatoria | rediseñar (entidad `entry_senses`) | `create_initial_structure.php:147-165`; `database/migrations/2022_07_11_120555_add_ejemplo_to_dp_acepciones.php`; `DiccionarioPersonalController.php:491-499`; `app/Helpers/dpAcepciones_helper.php:77-103`; `resources/lang/es/diccionario.php:86-87,107` |
| Temáticas/etiquetas de la acepción (multiselección de `mst_campos_valores` del campo 4) | A, D | — | `dpAcepcionTematicas_Actualizar` | `layouts/partials/dpAcepcion/tematicas` | obligatoria | conservar | `app/Helpers/dpAcepciones_helper.php:119-147` |
| Medios de la acepción: 1 imagen, 1 audio y 1 vídeo como máximo; al subir uno nuevo se reemplaza el anterior | A, D | (en el POST de la acepción) | `dpMedio_TratarMedio` | `dpAcepcionFormulario` (Dropify) | obligatoria | rediseñar (`MediaStorage`, §44-45) | `app/Helpers/dpMedios_helper.php:46-132` |
| Grabar audio/vídeo/foto desde el navegador (MediaRecorder/getUserMedia; se envía por `fetch` al mismo POST con `grabacion=true`) | A, D | `POST /personal/entrada/acepcion/create` | `@dpInsertAcepcionPOST` (rama JSON) | `dpAcepcionFormulario.blade.php:287-1349` | útil | dudosa (evaluar coste/beneficio; versión 1.2.0 #620169) | `DiccionarioPersonalController.php:501-509,601-606`; `version.md` (1.2.0) |
| Borrar medio de la acepción personal (no borra el fichero si la entrada ya se envió) ⚠ GET | A, D | `GET …/acepcion/{a}/dpmedio/{m}/delete` | `@dpDeleteMedioGET` | `dpAcepcionFormulario` | útil | rediseñar | `routes/web.php:112`; `DiccionarioPersonalController.php:1003-1041`; `app/Helpers/dpMedios_helper.php:134-218` |
| Borrar acepción: reordena las siguientes; si es la única, borra la entrada ⚠ GET | A, D | `GET …/acepcion/{a}/delete` | `@dpDeleteAcepcionGET` | `botones/acepcion/delete` | obligatoria | rediseñar | `routes/web.php:103`; `DiccionarioPersonalController.php:916-990`; `app/Helpers/dpAcepciones_helper.php:167-218` |
| Reordenar acepciones (subir/bajar = intercambio de `orden`) ⚠ GET 🔓 (`authorize` comentado) | A, D | `GET …/acepcion/{a}/up`, `…/down` | `@dpUpAcepcionGET`, `@dpDownAcepcionGET` | `botones/acepcion/subir`, `bajar` | obligatoria | rediseñar (PATCH con posiciones) | `routes/web.php:106-107`; `DiccionarioPersonalController.php:724-775` |
| Ocultar/mostrar acepción ⚠ GET | A, D | `GET …/acepcion/{a}/ocultar` | `@dpOcultarAcepcionGET` | `botones/acepcion/ocultar` | útil | rediseñar | `routes/web.php:108`; `DiccionarioPersonalController.php:1376-1430` |
| Borrado/soft-delete en general (`deleted_at` en todas las tablas + `estado` 0/1/2) | sistema | — | modelos con `SoftDeletes` | — | obligatoria | rediseñar (§52) | `config/ctes.php:267-271,282-287`; `app/Models/*.php` (trait `SoftDeletes`) |
| Exportar a PDF el diccionario personal (opciones: incluir ocultas, solo las temáticas elegidas). 🔓 `generarPdfDPGET` no comprueba que el diccionario sea del usuario | A, D | `GET /personal/{d}/pdf`, `GET/POST /pdf/{d}/getdp` | `PDFController@optionsPdfPersonal`, `@generarPdfDPGET` | `diccionario/dpOptionsPDFView`, `layouts/partials/dpOptionsPDFform`, `layouts/partials/pdf/dp*` | útil | rediseñar (impresión CSS/cliente, §47) | `routes/web.php:131,252-253`; `app/Http/Controllers/PDFController.php:36-74,135-180` |
| Enviar **una entrada** a uno o varios aulas (modal; si no hay aulas, se ofrece el modal «Unirse») | A, D | `GET /personal/ajax/getDiccionariosAulaEntradaAjax`, `POST /personal/{d}/entrada/{e}/enviar` | `EnviosController@getDiccionariosAulaEntradaAjax`, `@enviarEntradaPOST` | `modal-enviarEntradaUnDiccionario`, `modal-enviarEntradaVariosDiccionarios`, `modal-unirsediccionario` | obligatoria | rediseñar (submission + revisión inmutable, §27) | `routes/web.php:68,122`; `app/Http/Controllers/EnviosController.php:204-296,298-432` |
| Enviar **el diccionario completo**: el alumno elige entradas, las no elegidas se marcan ocultas y se comprueban requisitos por AJAX. **Solo se usa el primer aula seleccionada** | A, D | `GET /personal/ajax/getDiccionariosAulaDiccionarioAjax`, `GET/POST /personal/{d}/enviar`, `POST /aula/{id}/comprobarEntradas`, `GET /aula/{id}/entradas.json` | `EnviosController@enviarDiccionarioGET/POST`, `DicAulaController@comprobarEntradasAEnviar`, `@getEntradasJson` | `diccionario/dpDiccionario/dpDiccionarioEnviarFormulario`, `modal-enviarDiccionarioCompleto` | útil | rediseñar (envío por lotes; no ocultar como efecto lateral) | `routes/web.php:70,123-124,221,224`; `EnviosController.php:38-200` (`[0]` en `:149`); `DicAulaController.php:1143-1157,1296-1308` |
| Ver el estado de mis envíos (nº de envíos por entrada y estado del último) | A, D | `GET /aula/{id}/entradas.json` | `DicAulaController@getEntradasJson` → `getDAulaEnviosDiccionario` | `dpDiccionarioEnviarFormulario` | útil | rediseñar (pendiente/publicada/rechazada visibles en el editor, §35) | `app/Helpers/dicAula_helper.php:1489-1522` |
| Leer los comentarios del profesorado (de entrada y generales, según la visibilidad del aula) | A, D | `GET /personal/{d}/comentarios`, `GET /personal/ajax/getComentariosDiccionarioAjax`, `GET /personal/ajax/getComentariosEntradaAjax` | `ComentariosController@comentariosDiccionarioGET`, `@getComentariosDiccionarioAjax`, `@getComentariosEntradaAjax` | `comentarios/comentariosDiccionarioVer`, `modal-comentarosentrada`, `modal-Aceptar` | obligatoria | rediseñar | `routes/web.php:72,96-97`; `app/Http/Controllers/ComentariosController.php:43-213`; `app/Helpers/ComentariosHelper.php:202-372` |
| Comentarios de una entrada (ruta) | — | `GET /personal/{d}/entrada/{e}/comentarios` | `DiccionarioPersonalController@comentariosEntradaGET` | — | legacy sin uso | eliminar (**el método no existe**, la ruta da error) | `routes/web.php:92`; ausente en `DiccionarioPersonalController.php` |
| Unirse a un aula por código (si ya estaba unido o deshabilitado, lo informa). **Siempre entra como alumno**, también los docentes | A, D | `GET /personal/ajax/getUnirseDiccionarioAjax`, `POST /aula/unirse` | `EnviosController@getUnirseDiccionarioAjax`, `DicAulaController@unirse` | `modal-unirsediccionario`, `botones/diccionario/unirse` | obligatoria | rediseñar | `routes/web.php:69,168`; `DicAulaController.php:433-473`; `app/Helpers/dicAula_helper.php:426-474` |
| Pantalla de desarrollo «unirse» | dev | `GET /aula/DEV_unirseGET` | `DicAulaController@DEV_unirseGET` | `development/unirsediccionario` | legacy sin uso | eliminar | `routes/web.php:169`; `DicAulaController.php:749-762` |
| Modal genérico Aceptar/Cancelar por AJAX | A, D | `POST /personal/ajax/getAceptarCancelarAjax` | `ModalAjaxController@getAceptarCancelarAjax` | `ajaxmodal-AceptarCancelar` | prescindible | eliminar (componente cliente) | `routes/web.php:128`; `app/Http/Controllers/ModalAjaxController.php:35-55` |
| Endpoint `grabacionAcepcion` (método vacío) | — | `POST /personal/grabacionAcepcion` | `MediaTypeController@index` | `media/grabacion` | legacy sin uso | eliminar | `routes/web.php:78`; `app/Http/Controllers/MediaTypeController.php:16-22` |

## 3. Diccionario de aula

| Funcionalidad | Rol(es) | Rutas | Controlador@método | Vistas | Clasificación | Decisión | Evidencia |
|---|---|---|---|---|---|---|---|
| Inicio del aula y «aula activa» guardada en sesión (selector). 🔓 `setDicAulaActivo` acepta cualquier id | A, D | `GET /aula`, `POST /aula/dicAulaActivo`, `GET /aula/dicAulaActivo/{id}` | `DicAulaController@daHome`, `@setDicAulaActivo`, `@setDicAulaActivoGet` | `diccionario/aula`, `select-DicAula`, `listaDicAula-customRadio`, `diccionario/aula/pautas` | obligatoria | rediseñar (aula en la URL, no en sesión) | `routes/web.php:139,174-176`; `DicAulaController.php:189-213,775-805`; `app/Helpers/dicAula_helper.php:1175-1231,488-500` |
| Crear diccionario de aula | D | `GET/POST /aula/create` | `@index`, `@create` → `createOrUpdateDicAula` | `diccionario/crearDicAula` | obligatoria | rediseñar (`classroom_settings`, §25) | `routes/web.php:142-143`; `DicAulaController.php:61-175`; `app/Helpers/dicAula_helper.php:33-159` |
| Editar diccionario de aula (solo si está activo). 🔓 `save` no comprueba ni propietario ni coordinador | Coord (en teoría Prop, v1.2.3) | `GET/POST /aula/{id}/edit` | `@edit`, `@save` | `crearDicAula` | obligatoria | rediseñar | `routes/web.php:144-145`; `DicAulaController.php:226-336`; `version.md` (1.2.3) |
| Borrar diccionario de aula (soft delete en cascada por SQL) y comprobación previa por AJAX (publicadas, envíos, participantes). ⚠ GET 🔓 `delete` sin autorización | Prop | `GET /aula/{id}/delete`, `GET /aula/{id}/testdelete` | `@delete`, `@testDeleteAjax` | `crearDicAula` + `resources/js/dic_aula.js:436-470` | útil | rediseñar (DELETE + reglas explícitas) | `routes/web.php:148-150`; `DicAulaController.php:1598-1626`; `app/Models/DicAula.php:376-438` |
| Código de unión: 6 caracteres `[A-Z0-9]` generados en el cliente; el servidor valida `unique`, de 4 a 6 caracteres, y reintenta (el bucle no incrementa el contador). Se muestra partido (`separaCodigo`) | D | (en crear) | `daValidateCode`, `daGenerateCode` | `crearDicAula.blade.php:600-625` | obligatoria | rediseñar (generación en servidor, sin ambigüedad) | `DicAulaController.php:143-158`; `app/Helpers/dicAula_helper.php:1593-1622`; `app/Helpers/global_helper.php:1174-1178` |
| Tipo de diccionario (`mst_tipos_diccionario_aula`): forzado a 1 y con el selector comentado | — | — | — | `crearDicAula.blade.php:42-62` | legacy sin uso | eliminar (o dudosa si «Canarismos» tiene futuro) | `DicAulaController.php:124`; `app/Helpers/dicAula_helper.php:73` |
| Enseñanza (`mst_ensenanza_id`, por defecto 1 «Multienseñanza»): sin UI y nunca se asigna | — | — | — | — | legacy sin uso | eliminar | `create_initial_structure.php:211`; `app/Helpers/dicAula_helper.php:67-88` |
| Nivel de estudio (opcional; «Multiestudio» e «Infantil» van primero) | D | — | `getAllNivelEstudios` | `crearDicAula.blade.php:77-90` | útil | conservar (vocabulario) | `app/Helpers/global_helper.php:1309-1325` |
| Área/materia (si falta, 1 = «Interdisciplinar») | D | — | — | `crearDicAula.blade.php:109-118` | útil | conservar | `app/Helpers/dicAula_helper.php:76` |
| Grupo (letra A–Z; `0` = sin grupo) | D | — | — | `crearDicAula.blade.php:93-105` | útil | conservar | `app/Helpers/dicAula_helper.php:74` |
| Curso escolar (`ano_ini_curso_escolar`) e `INICIO_CURSO` (día/mes; valor por defecto 30/8) | D, sistema | — | `getAnoIniCursoEscolar`, `getFormattedCursoEscolar` | `crearDicAula.blade.php:604` | obligatoria | rediseñar (reloj inyectado, §55) | `app/Helpers/global_helper.php:364-455`; `config/ctes.php:483` |
| Vigencia (nº de cursos, de 1 a `VIGENCIA_MAX`=10; al editar no puede bajar de los cursos ya transcurridos). Se considera vigente si `vigencia > cursoActual − añoInicio` o si es atemporal | D, Adm | — | `daEsVigente`, `daDesactivarSiNoVigente`, `daConectadosByPersonaId` | `crearDicAula.blade.php:127-180` | obligatoria | rediseñar (§55) | `app/Helpers/dicAula_helper.php:225-257,1973-2058`; `DicAulaController.php:263-266`; `config/ctes.php:482` |
| Diccionario atemporal (`dic_aula_atemporales.estado`, `hasta`) | Adm | ver §5 | `DicAula::setAtemporal`, `esAtemporal` | — | administrativa | rediseñar (booleano en `classroom_settings`) | `database/migrations/2021_03_09_135756_add_table_dic_aula_dic_permanente.php`; `app/Models/DicAula.php:164-211` |
| Máximo de acepciones por entrada: selector 1..10, **se guarda un booleano y no se aplica** | D | — | — | `crearDicAula.blade.php:240-255` | legacy sin uso | dudosa (el prompt lo quiere en `entry_policy`; hoy no hace nada) | `app/Helpers/dicAula_helper.php:78`; ningún otro uso de `max_acepciones_entrada` |
| Campos visibles y obligatorios por aula (`dic_aula_campos`). «Obligatorio» se valida al enviar con ids 1..9 escritos en el código (el 10, «Ejemplo de uso», nunca se valida). «Visible» solo filtra imagen, audio, vídeo y «Más datos» en la consulta del aula | D | — | `createOrUpdateDicAula`, `dpEnvio_cumple_requisitos_diccionario` | `crearDicAula.blade.php:260-305`, `layouts/partials/consulta/acepcionAula-*.blade.php` | obligatoria | rediseñar (`entry_policy` tipada, §51) | `app/Helpers/dicAula_helper.php:89-98`; `app/Helpers/dpEnvios_helper.php:535-614` |
| Pautas: maestra (primer `mst_pautas` activo) y específicas del aula (HTML TinyMCE; botón «Restablecer» a la maestra), visibles para el alumnado. `dic_aula_pautas.url_fichero` y `mst_pautas.tipo` no se usan | D (edita), A (lee) | (en crear/editar) | `createOrUpdateDicAula` | `crearDicAula.blade.php:318-340`, `diccionario/aula/pautas` | útil | rediseñar (solo texto, §57) | `app/Helpers/dicAula_helper.php:100-125`; `DicAulaController.php:94,249-261`; `database/migrations/2021_02_03_124026_create_mst_pautas_table.php` |
| Importar entradas publicadas de otro aula (incluidas las no vigentes) al crear. **Comparte la misma fila `envio_entrada`** entre los dos aulas | D | (en crear) | `daCreateOrUpdateDicAulaByPersonaId` | `crearDicAula.blade.php:186-230,672-690` | útil | rediseñar (copia con procedencia) | `app/Helpers/dicAula_helper.php:129-143` |
| Participantes: listas separadas de alumnado y profesorado; habilitar/deshabilitar (`estado`); dar o quitar edición = cambiar el rol en el aula. Reglas: nadie se excluye a sí mismo y no se puede quitar al último docente. 🔓 no se comprueba que quien actúa coordine ese aula | Coord | `POST /aula/{id}/edit/habilitarParticipante/{idp}`, `…/deshabilitarParticipante/{idp}`, `…/habilitarParticipanteAdmin/{idp}`, `…/deshabilitarParticipanteAdmin/{idp}` | `@participantesAjax` | `diccionario/participantesAjax`, `crearDicAula.blade.php:480-600,815-860` | obligatoria | rediseñar (membresías con rol de aula, §26) | `routes/web.php:164-167`; `DicAulaController.php:702-738`; `app/Helpers/dicAula_helper.php:1777-1970`; `version.md` (1.2.4) |
| Otros docentes del aula: entran con el código como alumnos y el propietario los promueve; se les puede invitar por correo | Coord | ver filas anterior y siguiente | — | — | útil | rediseñar (rol `teacher` explícito, invitación por enlace) | `app/Helpers/dicAula_helper.php:426-433,1868-1896` |
| Invitar a un docente por correo (nombre, apellido, email y código). Se registra en `dic_aula_destinatario_avisos` con `estado=3` | Coord | `POST /aula/{d}/inviteMail` | `@invitarMailPOST` | `modal-formulario-invitar-docente`, `mail/inviteDocenteMailView` | útil | rediseñar (`MailAdapter`, §54) | `routes/web.php:171`; `DicAulaController.php:349-420` |
| «Enviar avisos de envíos de entradas al correo electrónico» (campo `correoAvisos`): **se muestra y nunca se guarda ni se usa** | D | — | — | `crearDicAula.blade.php:344-356` | legacy sin uso | eliminar | `grep correoAvisos app/` → sin resultados |
| Visibilidad para el alumnado (`visible_estudiante` 1/0): el alumno no ve aulas no visibles | D | — | `getDicAulaActivo` | `crearDicAula.blade.php:360-375` | obligatoria | conservar | `app/Helpers/dicAula_helper.php:1186-1193,1219-1222` |
| Ventana de envíos (`envios_habilitados` 0/1 + `envios_fecha_ini`). El estado «planificado» (2) no tiene UI y su lógica en el modelo está invertida; `envios_fecha_fin` se sobrescribe con «ahora». El envío masivo no comprueba `aceptaEnvios` | D | — | `aceptaEnvios`, `DicAula::enviosHabilitados` | `crearDicAula.blade.php:385-425` | obligatoria | rediseñar (`submissions_start_at/end_at`) | `app/Helpers/dicAula_helper.php:80-82,1470-1486`; `app/Models/DicAula.php:79-98`; `app/Helpers/dpEnvios_helper.php:66-131` |
| Visibilidad de comentarios para el alumnado: 0 no visible, 1 visible, 2 «visibles los anteriores a» una fecha (`≤ hoy`) | D | — | `comentarios*ByComentariosVisible` | `crearDicAula.blade.php:430-470` | útil | conservar | `DicAulaController.php:131-138`; `app/Models/DicAula.php:216-307` |
| Lista de entradas enviadas para revisar (filtros: estudiante, texto, desde/hasta, inicial, estado; límite 200) | Coord | `GET /aula/{id}/entradas`, `POST /aula/{id}/entradas` | `@entradas`, `@entradasAjax` | `diccionario/entradasAula`, `layouts/partials/consulta/entradasAulaListado`, `dpAcepcionPublicarEntradas` | obligatoria | rediseñar (bandeja «pendientes», §36) | `routes/web.php:153-154`; `DicAulaController.php:486-544`; `app/Helpers/dicAula_helper.php:1679-1760` |
| Publicar / despublicar una entrada. Se bloquea si ya hay una entrada publicada con el mismo título. Despublicar borra `dic_aula_entradas` y pone el estado 1/2 | Coord | `POST /aula/{id}/entrada/{e}/publicar` (`action=publicar` o despublicar) | `@publicar` → `publicarEntrada`, `despublicarEntrada` | `botones/entrada/publicar` | obligatoria | rediseñar | `routes/web.php:157`; `DicAulaController.php:555-562`; `app/Helpers/dicAula_helper.php:761-905` |
| Publicación masiva con previsualización (OK / ya publicadas / duplicadas en el listado) | Coord | `POST /aula/{id}/entradas/status`, `POST /aula/{id}/publicar` | `@getStatusEntradasParaPublicarListado`, `@publicarListado` | `modal-publicar-listado`, `botones/entrada/publicarListadoEntradas` | útil | conservar (comportamiento) | `routes/web.php:158,161`; `DicAulaController.php:564-665`; `app/Helpers/dicAula_helper.php:586-748` |
| «Eliminar envío» (solo si no está publicado): **borrado físico**. Hace las veces de rechazo | Coord | `POST /aula/{id}/entrada/{e}/eliminar` | `@eliminar` → `eliminarEnvioEntrada` | `botones/entrada/eliminarAula` | obligatoria | rediseñar (estado `rejected` + nota, §27) | `routes/web.php:159`; `app/Helpers/dicAula_helper.php:919-962`; `version.md` (1.2.0 #669974) |
| Comentar una entrada enviada (TinyMCE) y editar o borrar comentarios. 🔓 editar y borrar no comprueban nada | Coord | `GET /personal/ajax/getComentarEntradaAjax/{e}`, `POST /aula/{id}/entrada/{e}/comentar`, `POST …/comentario/{c}/edit`, `POST …/comentario/{c}/delete` | `EnviosController@getComentarEntradaAjax`, `DicAulaController@comentar`, `@comentarioEdit`, `@comentarioDelete` | `modal-formulario-comentarEntrada`, `botones/entrada/comentarios` | obligatoria | rediseñar (texto plano, §43) | `routes/web.php:75,162-163,182`; `DicAulaController.php:680-687,1173-1243`; `app/Helpers/dicAula_helper.php:1338-1374` |
| Comentarios generales a un alumno en un aula: crear, editar el último y borrar. ⚠ GET (borrar). 🔓 el POST no comprueba coordinación | Coord | `GET/POST /aula/{id}/comentarios`, `GET /aula/{id}/comentario/{c}/delete` | `ComentariosController@comentariosGet`, `@comentariosPost`, `@deleteComentarioGet` | `comentarios/comentariosDiccionarioEnviar` | útil | rediseñar (modelo de comentarios unificado) | `routes/web.php:179-181`; `ComentariosController.php:215-391` |
| Edición docente de una entrada publicada: cambiar el texto (único en el aula; `updated_by_persona_id`). 🔓 `daEditEntradaGET/POST` sin autorización | Coord | `GET /aula/{d}/entrada/{e}`, `GET/POST /aula/{d}/entrada/{e}/edit` | `@daEntradaGET`, `@daEditEntradaGET`, `@daEditEntradaPOST` | `diccionario/aula/entrada`, `diccionario/aula/daEntradaEdit` | obligatoria | rediseñar | `routes/web.php:234-236`; `DicAulaController.php:1322-1494`; `database/migrations/2021_01_13_125309_add_updated_by_persona_id_to_envios_entradas.php` |
| Edición docente de una acepción enviada (todos los campos y medios) y borrado de un medio. ⚠ GET (borrar medio). 🔓 sin ninguna autorización | Coord | `GET /aula/{d}/entrada/{e}/acepcion/{a}/edit`, `POST /aula/acepcion/{a}/edit`, `GET /aula/acepcion/{a}/dpmedio/{m}/delete` | `EnvioAcepcionController@editAcepcionGet`, `@editAcepcionPost`, `@editAcepcionDeleteMedio` | `diccionario/aula/acepcionEdit` | obligatoria | rediseñar | `routes/web.php:227-231`; `app/Http/Controllers/EnvioAcepcionController.php:36-224` |
| Ocultar una entrada en el aula (borra `dic_aula_entradas` y alterna el estado). Solo el **propietario** ⚠ GET | Prop | `GET /aula/{id}/entrada/{e}/ocultar` | `@ocultarEntrada` → `ocultaEntrada` | `botones/entrada/ocultarAula` | útil | rediseñar (bandera `hidden` sobre la entrada publicada) | `routes/web.php:218`; `DicAulaController.php:1278-1283`; `app/Helpers/dicAula_helper.php:1425-1456` |
| Ocultar una acepción en el aula (no se permite si es la única). Solo el propietario ⚠ GET | Prop | `GET /aula/{id}/entrada/{e}/acepcion/{a}/ocultar` | `@ocultarAcepcion` → `ocultaAcepcion` | `botones/acepcion/ocultarAula` | útil | rediseñar | `routes/web.php:217`; `DicAulaController.php:1258-1263`; `app/Helpers/dicAula_helper.php:1386-1414` |
| Consulta del aula: todas las entradas (scroll), por texto, por inicial, por temáticas (solo las presentes en el aula), autocompletado | A (si visible), D | `GET /aula/entradas`, `/aula/entradas/letra/{l}`, `/aula/entradas/{q}[/etiquetas/{ids}]`, `/aula/entradas/etiquetas/{ids}`, `POST /aula/entradas/consulta`, `GET /aula/entradas_json`, AJAX `…/n/{offset}`, `/aula/x/…` | `@getAllEntradasConsulta`, `@getEntradaByInitial`, `@getEntradaByConsulta`, `@buscarEntrada`, `@getAllEntradasJson`, `@getEntradasConsultaAjax`, `@getEntradasbyInitialAjax` | `diccionario/aula/consulta`, `consultaAulaAjax`, `layouts/partials/consulta/entradaAula`, `acepcionAula*` | obligatoria | rediseñar (visor limpio, §49-50) | `routes/web.php:192-213`; `DicAulaController.php:818-1131,1496-1596`; `app/Helpers/dicAula_helper.php:1033-1161,1247-1321` |
| Exportar a PDF el diccionario de aula (opción: solo temáticas elegidas). Incluye acepciones ocultas | participantes | `GET /aula/{d}/pdf`, `GET/POST /pdf/{d}/getda` | `PDFController@optionsPdfAula`, `@generarPdfDAGET` | `diccionario/daOptionsPDF`, `aulaOptionsPDFform`, `pdf/da*` | útil | rediseñar | `routes/web.php:239,255-256`; `PDFController.php:85-125,182-211`; `app/Helpers/pdf_helper.php:141-205` |
| Consultar aulas no vigentes («históricos») | — | — | — | — | — | **no existe**. El tooltip lo anuncia, pero solo aparecen como origen al importar | `resources/lang/es/diccionario.php:506-508`; `crearDicAula.blade.php:672-690` |

## 4. Flujo de envío y publicación

| Funcionalidad | Rol(es) | Rutas | Controlador@método | Vistas | Clasificación | Decisión | Evidencia |
|---|---|---|---|---|---|---|---|
| Relación entrada personal → envío: `dp_envios` (uno por envío y aula, con curso) → `envios_entradas.dp_entrada_id` | sistema | ver §2 | `dpEnvio_EnviarEntradaAListaDiccionariosAula` | — | obligatoria | rediseñar (`submissions.source_entry_id`) | `app/Helpers/dpEnvios_helper.php:261-356` |
| Copia de la entrada (texto y estado) y de las acepciones (todos los campos, solo las visibles) | sistema | — | `dpEnvio_EnviarEntradaADiccionarioAula` | — | obligatoria | rediseñar (`entry_revisions` inmutable, §29) | `app/Helpers/dpEnvios_helper.php:358-457` |
| Copia de las temáticas | sistema | — | `dpEnvioTematicas_Actualizar` | — | obligatoria | rediseñar | `app/Helpers/dpEnvios_helper.php:459-501` |
| Copia de los medios **por referencia** (mismo fichero, filas nuevas en `envios_acepciones_medios`). Al borrar en el personal no se elimina el fichero si la entrada ya se envió | sistema | — | `dpEnvio_EnviarEntradaADiccionarioAula` | — | obligatoria | rediseñar (medios con contenido direccionado, §46) | `app/Helpers/dpEnvios_helper.php:425-450`; `app/Helpers/dpMedios_helper.php:166-210` |
| Validación previa: requiere al menos una acepción y los campos obligatorios del aula (error `FALTAN_CAMPOS`) | A, D | `POST /aula/{id}/comprobarEntradas` | `dpEnvio_EntradaCumpleRequisitosDiccionario` | `dpDiccionarioEnviarFormulario` | obligatoria | rediseñar (validación compartida, §42) | `app/Helpers/dpEnvios_helper.php:535-658` |
| Estados del envío: `envios_entradas.estado` 0 borrado lógico, 1 enviado, 2 «borrado_estudiante» (sin uso), 3 publicado. Despublicar pone 1/2 (mezcla con `estados_entrada`) | sistema | — | — | — | obligatoria | rediseñar (enum `pending/published/rejected/withdrawn`) | `config/ctes.php:267-287`; `app/Helpers/dicAula_helper.php:861-864` |
| Publicación = fila `dic_aula_entradas` (`origen=1`, `estado=1`) que apunta a la misma `envio_entrada` | Coord | ver §3 | `publicarEntrada` | — | obligatoria | rediseñar (nueva entrada de aula con `source_submission_id`, §28) | `app/Helpers/dicAula_helper.php:799-810`; `create_initial_structure.php:362-371` |
| Edición posterior por el docente: modifica la copia (`envios_*`), nunca el original | Coord | ver §3 | `EnvioAcepcionController`, `daEditEntradaPOST` | — | obligatoria | conservar (comportamiento) | `EnvioAcepcionController.php:113-141` |
| Trazabilidad con el original (`dp_entrada_id`, `dic_personal_id`). `dic_aula_entradas.dic_origen` no se usa | sistema | — | — | — | útil | rediseñar | `create_initial_structure.php:300-303,369` |
| Reenvíos: cada envío crea un `dp_envio` y una `envio_entrada` nuevos, sin deduplicar. El docente ve todas las versiones; el duplicado de título solo bloquea al publicar | A, D | — | — | — | obligatoria | rediseñar (una submission por entrada y aula, con revisiones) | `app/Helpers/dpEnvios_helper.php:279-299`; `app/Helpers/dicAula_helper.php:672-680,1489-1522` |
| Retirada: borrar la entrada personal enviada y no publicada aplica soft delete al envío | A, D | ver §2 | `dpEntrada_DeleteEntradaById` | — | útil | rediseñar (`withdrawn`) | `app/Helpers/dpEntradas_helper.php:70-82` |
| Comentarios sobre la entrada (`comentarios_entradas` → `envio_entrada`) | Coord → A | ver §3 | `comentarEntrada` | — | obligatoria | rediseñar | `create_initial_structure.php:447-458`; `app/Helpers/dicAula_helper.php:1338-1374` |
| Comentarios generales (`comentarios_generales` → `dic_personal` + aula) | Coord → A | ver §3 | `ComentariosController@comentariosPost` | — | útil | rediseñar | `create_initial_structure.php:431-442`; `ComentariosController.php:287-364` |
| Estado leído/no leído del comentario (`visible_no_leido`/`visible_si_leido`): **nunca se marca como leído** | — | — | — | — | legacy sin uso | dudosa (útil si se implementa de verdad) | `config/ctes.php:349-353`; único uso: `ComentariosController.php:339` |
| Avisos/notificaciones de envíos y comentarios (`avisos`, `avisos_dp_envios`, `avisos_coment_*`, `avisos_entradas_compartidas`) y «entradas compartidas» | — | — | — | — | legacy sin uso | eliminar (las tablas no se usan; **no existe** la funcionalidad) | `create_initial_structure.php:468-534`; sin referencias en `app/` (salvo el borrado de `avisos` en `app/Models/DicAula.php:382`) |

## 5. Administración

| Funcionalidad | Rol(es) | Rutas | Controlador@método | Vistas | Clasificación | Decisión | Evidencia |
|---|---|---|---|---|---|---|---|
| Panel Voyager: Tablero, Multimedia, Diseñador de menús, Base de datos, BREAD, Parámetros, Compás, y Categorías/Posts/Páginas de ejemplo | Adm | `/admin/*` (`Voyager::routes()`, registrado **dos veces**) | `TCG\Voyager\*`, `App\Http\Controllers\Voyager\*` | Voyager (vendor) | administrativa | eliminar (§37) | `routes/web.php:351,355`; `database/seeders/MenuItemsTableSeeder.php:420-790` |
| BREAD de usuarios y roles | Adm | `/admin/users`, `/admin/roles` | `VoyagerUserController`, `VoyagerRoleController` | Voyager | administrativa | rediseñar (pantalla propia mínima: buscar usuario, ver o cambiar rol global) | `database/seeders/DataTypesTableSeeder.php:25-64` |
| BREAD «Dic Aulas Cursos Escolares» (`dic_aula`), «Diccionarios personales», «Centros curso escolar» | Adm | `/admin/dic-aulaCursoEscolar`, `/admin/dic-personal-bread`, `/admin/centrosBread` | `vDicAulaController`, `vDicPersonalController`, `vCentrosAñoEscolarController` | Voyager | administrativa | dudosa (verificar su uso real con la Oficina técnica) | `DataTypesTableSeeder.php:155-194`; `app/Http/Controllers/Voyager/v*.php` |
| Vigencia: listar aulas vigentes, no vigentes, atemporales y con «errores de estado»; comprobar todas (desactiva las no vigentes); comprobar una; hacer o quitar atemporal; activar en el curso actual (recalcula `vigencia`); JSONs. ⚠ GET en todas. 🔓 `/admin/vigencia/comprobar/{id}` **no tiene `auth` ni `admin.user`** | Adm, OT | `GET /admin/vigencia`, `/admin/vigencia/{tipo}`, `/admin/vigencia/comprobar`, `/admin/vigencia/comprobar/{id}`, `/admin/vigencia/atemporal/{id}[/desactivar]`, `/admin/vigencia/activacion/{id}`, `/admin/vigencia.json`, `/admin/vigencia/all.json`, `/admin/vigencia/atemporal.json` | `Voyager\VoyagerCompassController@vigencia…` | `voyager::compass.vigencia` (**no está en el repo**) | administrativa | rediseñar (caso de uso testeado + pantalla propia; posible tarea programada) | `routes/web.php:292-322`; `VoyagerCompassController.php:257-548`; `resources/lang/es/admin.php:4-45` |
| Estadísticas: aulas por curso, vigentes por curso y participantes por rol; diccionarios personales y entradas por curso; entradas publicadas; centros por curso | Adm, OT | `GET /admin/diccionarios_curso`, `/admin/diccionarios_personales`, `/admin/centrosAñoEscolar` | `@dicsCursoEscolar`, `@dicsPersonales`, `@centrosAñoEscolar` | `voyager::compass.dicsCursoEsc`, `.dicsPersonales`, `.centrosAñoEscolar` (**no están en el repo**) | administrativa | rediseñar (consultas agregadas sin PII) | `routes/web.php:324-334`; `VoyagerCompassController.php:95-250`; `version.md` (1.2.0 #610344) |
| CSV de estadísticas. 🔓 `getCSV?methodName=` ejecuta **cualquier método público** de `VoyagerCompassController` y lo puede pedir cualquier usuario autenticado. Los ficheros se escriben en `public/exports` | Adm, OT (de facto, cualquiera) | `GET /getCSV`, `GET /getAllDatesCSV?option=dicsCursos\|dicsPersonales\|centros` | `CSVController@generarCSV`, `@generarCsvAll` | (botones en las vistas compass ausentes) | administrativa | rediseñar (endpoint fijo, solo admin, en streaming) | `routes/web.php:244-245`; `app/Http/Controllers/CSVController.php:21-156` |
| Visor de logs (ver, descargar, borrar uno, borrar todos). ⚠ GET (`?del=`, `?delall`). Los logs contienen la respuesta completa de CAUCE (nombre, NIF, CIAL, centros) | Adm, OT | `GET /admin/logs` | `VoyagerCompassController@logs_viewer` | `voyager::compass.logs` | administrativa | eliminar (logs de plataforma; minimizar PII, §22) | `routes/web.php:289-290,357`; `VoyagerCompassController.php:27-69`; `app/Helpers/global_helper.php:762-767` |
| Auditoría `owen-it/laravel-auditing` (`audits`) + BREAD «Audits» | Adm | `/admin/audits` | Voyager BREAD | Voyager | administrativa | rediseñar (`audit_events` mínima, §53) | `database/migrations/2020_04_20_150059_create_audits_table.php`; `config/audit.php`; `app/Models/*` (`Auditable`) |
| Rol «Oficina técnica» (`user`) con menú Logs + Vigencia | OT | — | — | — | administrativa | rediseñar (rol `support` o `staff` explícito si se mantiene) | `database/seeders/VoyagerCustomization.php:2105-2120` |
| Avisos (warnings): listar, crear, editar, borrar y activar | Adm | `GET /admin/warnings[/{id}]`, `GET /admin/warnings/create`, `GET /admin/warnings/{id}/edit`, `POST /admin/warnings/create`, `PUT /admin/warnings/{id}/create`, `PUT /admin/warnings/status/{id}`, `DELETE /admin/warnings/{id}` | `VoyagerCompassController@warnings/createWarning/saveWarning/statusWarning/deleteWarnings` | — | legacy sin uso | eliminar (**los métodos no existen** y no hay tabla `warnings`) | `routes/web.php:360-366`; `grep -rn "createWarning" app/` → sin resultados |
| Mantenimiento y configuración (formulario que reescribe `config/*.php` y `.env` y limpia cachés) | Adm | `GET /admin/maintenance`, `GET/POST /admin/configuracion` | `Voyager\VoyagerController@maintenance/configuracion_view/configuracion_write` | — | legacy sin uso | eliminar (**los métodos no existen**; el helper `modify_config_files` es peligroso) | `routes/web.php:356-359`; `app/Http/Controllers/Voyager/VoyagerController.php:16-22`; `app/Helpers/global_helper.php:281-353` |
| «Grabación» (prueba de concepto de captura de foto y vídeo en el panel). 🔓 `sendFileJs` y `sendFotosJs` sin `auth` | Adm | `GET /admin/grabacion`, `POST /admin/sendFileJs`, `POST /admin/sendFotosJs` | `Voyager\vRecordingController@index/showResult/showFotosResult` | `voyager::compass.record` (no está en el repo) | legacy sin uso | eliminar | `routes/web.php:336-344`; `app/Http/Controllers/Voyager/vRecordingController.php:21-58` |
| Administración de tablas maestras (enseñanzas, niveles, áreas, campos, valores, pautas) | — | — | — | — | — | **no existe UI**. Solo hay seeders y SQL; no hay BREAD de `mst_*` | `database/seeders/DataTypesTableSeeder.php` (solo users, menus, roles, audits, categories, posts, pages, dic_aula, dic_personal, centros) |
| Tareas programadas (vigencia, limpieza) | — | — | — | — | — | **no existe** (`schedule()` vacío; la vigencia se revisa a mano) | `app/Console/Kernel.php:20-24` |

## 6. Integraciones y salida

| Funcionalidad | Rol(es) | Rutas | Controlador@método | Vistas | Clasificación | Decisión | Evidencia |
|---|---|---|---|---|---|---|---|
| PDF (dompdf 2.0) del diccionario personal y del aula | A, D | ver §2 y §3 | `PDFController` | `layouts/partials/pdf/*` | integración externa | rediseñar (§47) | `composer.json`; `config/dompdf.php`; `app/Http/Controllers/PDFController.php` |
| CSV | Adm, OT | ver §5 | `CSVController` | — | administrativa | rediseñar (solo estadísticas; **no existe CSV ni JSON de diccionarios**) | `app/Http/Controllers/CSVController.php` |
| Correo SMTP: invitación a docente | Coord | `POST /aula/{d}/inviteMail` | `sendHtmlMailwithView` | `mail/inviteDocenteMailView` | integración externa | rediseñar (`MailAdapter`; la demo nunca envía) | `app/Helpers/mail_helper.php:26-55`; `DicAulaController.php:370-397` |
| Correo de prueba (envía a direcciones externas fijas en el código) | cualquiera autenticado | `GET /mail/get`, `GET /mail/paginamail` | `MailController@sendHtmlMail`, vista directa | `mail/showMailButton`, `mail/mailview`, `mail/template` | legacy sin uso | eliminar | `routes/web.php:260-263`; `app/Http/Controllers/MailController.php:28-100` |
| Subida de medios de acepción (imagen, audio, vídeo) a `storage/app/public/dp/medios/*` y miniaturas | A, D, Coord | ver §2 y §3 | `dpMedio_*`, `envioAcepcionMedio_*` | `dpAcepcionFormulario`, `aula/acepcionEdit` | obligatoria | rediseñar (§44-46) | `app/Helpers/dpMedios_helper.php`; `app/Helpers/envioAcepcion_helper.php:70-160` |
| TinyMCE (pautas y comentarios) y subida de imágenes desde TinyMCE. 🔓 `/upload` acepta **cualquier fichero**, conserva el nombre original y lo publica en `storage/app/public/tinyUploads` | D | `POST /upload` | `HomeController@upload` | `crearDicAula`, `comentariosDiccionarioEnviar`, `modal-formulario-comentarEntrada` | útil | rediseñar (texto plano o Markdown limitado; eliminar el upload) | `routes/web.php:277`; `app/Http/Controllers/HomeController.php:55-59`; `resources/js/dic_aula.js:19-58` |
| Enlaces externos institucionales (logos, aviso legal, privacidad, accesibilidad, sugerencias) y PDFs de ayuda «¿Qué puedo hacer?» (aula/personal) en `public/imagenes` | todos | — | `config('gobcan.*')` | `layouts/app`, `layouts/partials/components/quehacer`, `creditos` | útil | conservar (sin hotlinking de imágenes externas, §22) | `config/gobcan.php:8-25`; `resources/views/layouts/partials/components/quehacer.blade.php:5,48`; `version.md` (1.2.2) |
| Almacenamiento NFS: `storage/app/public` (medios, avatares, `tinyUploads`) y `storage/logs`, montados como volúmenes NFS en OpenShift | sistema | — | — | — | integración externa | rediseñar (filesystem persistente + migración de ficheros, §46) | `developers.md:197-278`; `readme.md:26-133,362-380` |
| Límites de subida: `UPLOAD_MAXSIZE` (php.ini al construir la imagen + Dropify; valor por defecto `200M`); extensiones solo en el cliente | sistema | — | — | Dropify | obligatoria | rediseñar (validación en servidor, §45) | `config/ctes.php:113-117,317-322`; `developers.md:319-329` |
| Miniaturas: imagen GD de 250 px de ancho (`*_thumb.jpg`) y vídeo con FFmpeg (`lakshmaji/thumbnail`, segundo 1) | sistema | — | `getImageThumbnail`, `getVideoThumbnail` | — | integración externa | rediseñar (miniaturas de imagen; vídeo dudosa) | `app/Helpers/create-thumbnail.php:39-151`; `app/Helpers/video_thumbnail.php:8-39`; `config/thumbnail.php:25-47` |
| Vista provisional con botón PDF | — | `GET /pdf/paginapdf` | `Route::view` | `pdf/showPDFButton` | legacy sin uso | eliminar | `routes/web.php:249` |
| Componentes Vue de ejemplo (`entrada.vue`, `EntradaBuscador.vue`, `ExampleComponent.vue`) | — | — | — | — | legacy sin uso | eliminar (no se registran en `app.js`) | `resources/js/components/*.vue`; `resources/js/app.js:6-57` |

---

## 7. Rutas GET destructivas o mutadoras

Todas deben pasar a POST/PATCH/DELETE con CSRF (§41):

| Ruta | Efecto | Evidencia |
|---|---|---|
| `GET /personal/{d}/entrada/{e}/delete` | borra la entrada y retira el envío | `routes/web.php:86` |
| `GET /personal/{d}/entrada/{e}/ocultar` | alterna la visibilidad de la entrada | `routes/web.php:89` |
| `GET /personal/{d}/entrada/{e}/acepcion/{a}/delete` | borra la acepción (y la entrada si era la última) | `routes/web.php:103` |
| `GET /personal/{d}/entrada/{e}/acepcion/{a}/up` / `down` | reordena | `routes/web.php:106-107` |
| `GET /personal/{d}/entrada/{e}/acepcion/{a}/ocultar` | alterna la visibilidad de la acepción | `routes/web.php:108` |
| `GET /personal/{d}/entrada/{e}/acepcion/{a}/dpmedio/{m}/delete` | borra el medio y el fichero | `routes/web.php:112` |
| `GET /aula/{id}/delete` | borra el aula en cascada (🔓 sin autorización) | `routes/web.php:148` |
| `GET /aula/{id}/comentario/{c}/delete` | borra un comentario general | `routes/web.php:181` |
| `GET /aula/{id}/entrada/{e}/acepcion/{a}/ocultar` | alterna la acepción en el aula | `routes/web.php:217` |
| `GET /aula/{id}/entrada/{e}/ocultar` | despublica u oculta en el aula | `routes/web.php:218` |
| `GET /aula/acepcion/{a}/dpmedio/{m}/delete` | borra un medio de la copia (🔓) | `routes/web.php:231` |
| `GET /aula/dicAulaActivo/{id}` | cambia el aula activa en la sesión | `routes/web.php:176` |
| `GET /admin/vigencia/comprobar` | desactiva todos los aulas no vigentes | `routes/web.php:295` |
| `GET /admin/vigencia/comprobar/{id}` | desactiva un aula (🔓 sin `auth`) | `routes/web.php:298` |
| `GET /admin/vigencia/atemporal/{id}` y `…/desactivar` | activa o desactiva el atemporal | `routes/web.php:300-305` |
| `GET /admin/vigencia/activacion/{id}` | reactiva y recalcula la vigencia | `routes/web.php:309` |
| `GET /admin/logs?del=…` / `?delall` | borra ficheros de log | `VoyagerCompassController.php:46-63` |
| `GET /getCSV?methodName=…` | invoca cualquier método público, incluidos los que desactivan aulas | `CSVController.php:101-106` |
| `GET /test` | crea personas y usuarios a partir de XML de prueba | `routes/web.php:266` |
| `GET /mail/get` | envía un correo a direcciones fijas | `routes/web.php:262` |
| `GET /acceptCookies` | establece una cookie (inocuo) | `routes/web.php:28` |

## 8. Datos maestros (vocabularios controlados)

Estos valores son **configuración del producto, no datos personales**. Se han copiado tal como aparecen en `database/seeders/MasterTablesDataSeeder.php`, con las correcciones posteriores de `MasterTablesDataSeederUpdate.php`, `MasterTablesDataSeederUpdate27may.php`, `NuevoCampoEjemploSeeder.php` y `MstPautasSeeder.php`.

- «id seed» es el id que produce un **seed limpio** (autoincremento desde 1, en el orden del array, deduplicado como hace `firstOrCreate`). **No coincide necesariamente con producción.** La migración debe casar por `descripcion`, nunca por id (§30).
- El «código propuesto» es un slug estable para los nuevos seeds. Los códigos son propuesta de este inventario: el legacy no tiene códigos.
- Las etiquetas de idiomas con espacio inicial (`' alemán'`, …) son intencionadas. Las puso `MasterTablesDataSeederUpdate.php:67-76` para que «inglés, francés, alemán, notación científica y español» salgan primero al ordenar. En el nuevo modelo esto debe ser un campo `orden` o `destacado`, no un espacio.

### 8.1 `mst_campos_entrada` (campos de la acepción configurables por aula)

| Orden en **producción** (inferido) | Etiqueta actual en BD | Columna o dato que controla | Código propuesto | Orden en seed limpio | Evidencia |
|---|---|---|---|---|---|
| 1 | Categoría gramatical | `cat_gramatical_id` | `part_of_speech` | 1 | `config/ctes.php:452`; `dpEnvios_helper.php:546` |
| 2 | Género | `genero_id` | `gender` | 2 | `config/ctes.php:453` |
| 3 | Número | `numero_id` | `number` | 3 | `config/ctes.php:454` |
| 4 | Temáticas generales | `dp_acepciones_tematicas` | `topics` | 4 | `config/ctes.php:455` |
| 5 | Más datos (antes «Frase ejemplo») | `frase_ejemplo` | `extra_info` | 5 | `config/ctes.php:456`; `MasterTablesDataSeederUpdate.php:23-27`; `resources/lang/es/diccionario.php:86,384` |
| 6 | Vídeo | medio tipo 3 | `video` | **7** | `config/ctes.php:457` |
| 7 | Audio | medio tipo 2 | `audio` | **8** | `config/ctes.php:458` |
| 8 | Imagen | medio tipo 1 | `image` | **9** | `config/ctes.php:459` |
| 9 | Lengua (antes «Lengua-idioma» y luego «Otros lenguajes») | `idioma_id` + `idioma_palabra` | `language` | **10** («Otros lenguajes») | `config/ctes.php:460`; `MasterTablesDataSeederUpdate.php:24-26` |
| 10 | Ejemplo de uso | `ejemplo2` | `usage_example` | **6** | `config/ctes.php:461`; `NuevoCampoEjemploSeeder.php:18-26`; `version.md` (1.1.1/1.2.0) |

Conclusión: producción tiene el orden de la primera columna. `MasterTablesDataSeeder` desde cero produce otro, que rompe `dpEnvio_cumple_requisitos_diccionario` y deja los idiomas (`mst_campo_entrada_id = 9`) colgando de «Imagen». En el nuevo modelo estos campos son **columnas o relaciones explícitas** de `entry_senses` más una `entry_policy`. No deben ser un EAV.

### 8.2 `mst_campos_valores`, `mst_tipos_diccionario_aula`, `mst_ensenanzas`, `mst_nivel_estudios`, `mst_areas_materias`

Correcciones de `MasterTablesDataSeederUpdate.php` ya incorporadas o anotadas:

- `'italiano'` → `' italiano'` (`:75`). El seed limpio crea `'italiano'` sin espacio y la actualización lo renombra.
- Se borra `'dialecto canario'` de idiomas (`:126-128`). En un seed limpio ni siquiera llega a crearse, porque colisiona con la temática «Dialecto canario».
- Se añaden las temáticas «Flora canaria», «Literatura canaria» y «Personaje ilustre canario» (`:166`).
- Los renombrados «FP 1» → «Formación profesional» en niveles (`:244-246`, `MasterTablesDataSeederUpdate27may.php:21-24`) no aplican al seed limpio.
- `getAllNivelEstudios` ordena «Multiestudio», «Infantil» y luego el resto (`app/Helpers/global_helper.php:1309-1325`).

**mst_tipos_diccionario_aula**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 1 | `diccionario_general` | Diccionario general |
| 2 | `canarismos` | Canarismos |

**mst_ensenanzas**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 1 | `multiensenanza` | Multienseñanza |
| 2 | `primaria` | Primaria |
| 3 | `eso` | ESO |
| 4 | `formacion_profesional` | Formación profesional |

**mst_nivel_estudios**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 1 | `multiestudio` | Multiestudio |
| 2 | `infantil` | Infantil |
| 3 | `1_primaria` | 1º Primaria |
| 4 | `2_primaria` | 2º Primaria |
| 5 | `3_primaria` | 3º Primaria |
| 6 | `4_primaria` | 4º Primaria |
| 7 | `5_primaria` | 5º Primaria |
| 8 | `6_primaria` | 6º Primaria |
| 9 | `1_eso` | 1º ESO |
| 10 | `2_eso` | 2º ESO |
| 11 | `3_eso` | 3º ESO |
| 12 | `4_eso` | 4º ESO |
| 13 | `concrecion_curricular_adaptada` | Concreción Curricular Adaptada |
| 14 | `1_bachillerato` | 1º Bachillerato |
| 15 | `2_bachillerato` | 2º Bachillerato |
| 16 | `formacion_profesional_basica` | Formación Profesional Básica |
| 17 | `1_pmar` | 1º PMAR |
| 18 | `2_pmar` | 2º PMAR |
| 19 | `otros` | Otros |

**mst_areas_materias**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 1 | `interdisciplinar` | Interdisciplinar |
| 2 | `acondicionamiento_fisico` | Acondicionamiento Físico |
| 3 | `ambito_cientifico_y_matematico` | Ámbito Científico y Matemático |
| 4 | `ambito_de_autonomia_personal` | Ámbito de Autonomía Personal |
| 5 | `ambito_de_autonomia_social` | Ámbito de Autonomía Social |
| 6 | `ambito_de_comunicacion_y_representacion` | Ámbito de Comunicación y Representación |
| 7 | `ambito_de_lenguas_extranjeras` | Ámbito de Lenguas Extranjeras |
| 8 | `ambito_laboral` | Ámbito Laboral |
| 9 | `ambito_linguistico_y_social` | Ámbito Lingüístico y Social |
| 10 | `analisis_musical` | Análisis musical |
| 11 | `anatomia_aplicadas` | Anatomía Aplicadas |
| 12 | `antropologia_y_sociologiabioestadistica` | Antropología y SociologíaBioestadística |
| 13 | `artes_aplicadas_a_la_escultura` | Artes aplicadas a la escultura |
| 14 | `artes_escenicas_y_danza` | Artes Escénicas y Danza |
| 15 | `artes_escenicas` | Artes Escénicas |
| 16 | `biologia_humana` | Biología Humana |
| 17 | `biologia_y_geologia` | Biología y Geología |
| 18 | `ceramica` | Cerámica |
| 19 | `ciencias_aplicadas_a_la_actividad_profesional` | Ciencias Aplicadas a la Actividad Profesional |
| 20 | `ciencias_aplicadas` | Ciencias Aplicadas |
| 21 | `ciencias_de_la_naturaleza` | Ciencias de la Naturaleza |
| 22 | `ciencias_de_la_tierra_y_del_medio_ambiente` | Ciencias de la Tierra y del Medio Ambiente |
| 23 | `ciencias_sociales` | Ciencias Sociales |
| 24 | `comunicacion_y_sociedad` | Comunicación y Sociedad |
| 25 | `cultura_audiovisual` | Cultura Audiovisual |
| 26 | `cultura_cientifica` | Cultura Científica |
| 27 | `cultura_clasica` | Cultura Clásica |
| 28 | `dibujo_artistico_o_tecnico` | Dibujo Artístico o Técnico |
| 29 | `diseno` | Diseño |
| 30 | `economia_y_economia_de_la_empresa` | Economía y Economía de la empresa |
| 31 | `economia` | Economía |
| 32 | `educacion_artistica` | Educación Artística |
| 33 | `educacion_emocional_y_para_la_creatividad` | Educación Emocional y para la Creatividad |
| 34 | `educacion_fisica` | Educación Física |
| 35 | `educacion_para_la_ciudadania_y_los_derechos_humanos` | Educación para la ciudadanía y los derechos humanos |
| 36 | `educacion_plastica_visual_y_audiovisual` | Educación Plástica, Visual y Audiovisual |
| 37 | `electrotecnia` | Electrotecnia |
| 38 | `estrategia_para_la_autonomia_y_la_cooperacion` | Estrategia para la Autonomía y la Cooperación |
| 39 | `filosofia` | Filosofía |
| 40 | `fisica_y_quimica` | Física y Química |
| 41 | `fisica` | Física |
| 42 | `fotografia` | Fotografía |
| 43 | `fundamentos_de_administracion_y_gestion` | Fundamentos de Administración y Gestión |
| 44 | `fundamentos_del_arte` | Fundamentos del Arte |
| 45 | `geografia_e_historia` | Geografía e Historia |
| 46 | `geografia` | Geografía |
| 47 | `geologia` | Geología |
| 48 | `griego` | Griego |
| 49 | `historia_de_espana` | Historia de España |
| 50 | `historia_de_la_filosofia` | Historia de la Filosofía |
| 51 | `historia_de_la_musica_y_la_danza` | Historia de la Música y la Danza |
| 52 | `historia_del_arte` | Historia del Arte |
| 53 | `historia_del_mundo_contemporaneo` | Historia del Mundo Contemporáneo |
| 54 | `historia_y_geografia_de_canarias` | Historia y Geografía de Canarias |
| 55 | `imagen_y_sonido` | Imagen y sonido |
| 56 | `iniciacion_a_la_actividad_emprendedora_y_empresarial` | Iniciación a la actividad emprendedora y empresarial |
| 57 | `iniciacion_a_la_astronomia` | Iniciación a la Astronomía |
| 58 | `la_mitologia_y_las_artes` | La mitología y las artes |
| 59 | `latin` | Latín |
| 60 | `lengua_castellana_y_literatura` | Lengua Castellana y Literatura |
| 61 | `lenguaje_y_practica_musical` | Lenguaje y práctica musical |
| 62 | `literatura_canaria` | Literatura canaria |
| 63 | `literatura_universal` | Literatura universal |
| 64 | `matematicas_academicas` | Matemáticas Académicas |
| 65 | `matematicas_aplicadas_a_las_ciencias_sociales` | Matemáticas aplicadas a las Ciencias Sociales |
| 66 | `matematicas_aplicadas` | Matemáticas Aplicadas |
| 67 | `matematicas` | Matemáticas |
| 68 | `musica` | Música |
| 69 | `practicas_comunicativas_y_creativas` | Prácticas Comunicativas y Creativas |
| 70 | `primera_lengua_extranjera_ingles` | Primera Lengua Extranjera: Inglés |
| 71 | `psicologia` | Psicología |
| 72 | `quimica` | Química |
| 73 | `religion_catolica` | Religión Católica |
| 74 | `religion_evangelica` | Religión Evangélica |
| 75 | `religion_islamica` | Religión Islámica |
| 76 | `religion` | Religión |
| 77 | `segunda_lengua_extranjera_aleman` | Segunda Lengua Extranjera: Alemán |
| 78 | `segunda_lengua_extranjera_frances` | Segunda Lengua Extranjera: Francés |
| 79 | `segunda_lengua_extranjera_italiano` | Segunda Lengua Extranjera: Italiano |
| 80 | `segunda_lengua_extranjera_otras` | Segunda Lengua Extranjera: otras |
| 81 | `sesiones_profundizacion_curricular` | Sesiones profundización curricular |
| 82 | `tecnicas_de_expresion_grafico_plastica` | Técnicas de Expresión Gráfico-Plástica |
| 83 | `tecnicas_de_laboratorio` | Técnicas de Laboratorio |
| 84 | `tecnologia_e` | Tecnología (E) |
| 85 | `tecnologia_industrial` | Tecnología Industrial |
| 86 | `tecnologia` | Tecnología |
| 87 | `tecnologias_de_la_informacion_y_la_comunicacion` | Tecnologías de la información y la comunicación |
| 88 | `tutoria` | Tutoría |
| 89 | `valores_eticos` | Valores Éticos |
| 90 | `valores_sociales_y_civicos` | Valores Sociales y Cívicos |
| 91 | `volumen` | Volumen |
| 92 | `flora_canaria` | Flora canaria |

Valores repetidos en el array del seeder que `firstOrCreate` (búsqueda por `descripcion`, colación MySQL insensible a mayúsculas/acentos) no vuelve a crear: 'Cultura Audiovisual', 'Literatura canaria'.

**mst_campos_valores — Categoría gramatical (mst_campo_entrada_id = 1)**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 1 | `adjetivo` | Adjetivo |
| 2 | `sustantivo` | Sustantivo |
| 3 | `verbo` | Verbo |
| 4 | `preposicion` | Preposición |
| 5 | `conjuncion` | Conjunción |
| 6 | `adverbio` | Adverbio |
| 7 | `determinante` | Determinante |
| 8 | `interjeccion` | Interjección |
| 9 | `pronombre` | Pronombre |

**mst_campos_valores — Género (mst_campo_entrada_id = 2)**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 10 | `femenino` | Femenino |
| 11 | `masculino` | Masculino |
| 12 | `masculino_y_femenino` | Masculino y femenino |
| 13 | `neutro` | Neutro |
| 14 | `no_tiene` | No tiene |

**mst_campos_valores — Número (mst_campo_entrada_id = 3)**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 15 | `singular` | Singular |
| 16 | `plural` | Plural |

**mst_campos_valores — Temáticas generales / etiquetas (mst_campo_entrada_id = 4)**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 17 | `lengua` | Lengua |
| 18 | `arte` | Arte |
| 19 | `historia` | Historia |
| 20 | `educacion_fisica` | Educación física |
| 21 | `cultura` | Cultura |
| 22 | `general` | General |
| 23 | `gastronomia` | Gastronomía |
| 24 | `toponimia` | Toponimia |
| 25 | `fauna_canaria` | Fauna canaria |
| 26 | `astronomia` | Astronomía |
| 27 | `ingles` | Inglés |
| 28 | `frances` | Francés |
| 29 | `aleman` | Alemán |
| 30 | `otros_idiomas` | Otros idiomas |
| 31 | `matematicas` | Matemáticas |
| 32 | `tecnologia_y_electrotecnia` | Tecnología y Electrotecnia |
| 33 | `fisica_y_quimica` | Física y Química |
| 34 | `biologia_y_geologia` | Biología y Geología |
| 35 | `ciencias_naturales` | Ciencias Naturales |
| 36 | `ciencias_sociales` | Ciencias Sociales |
| 37 | `economia` | Economía |
| 38 | `geografia_e_historia` | Geografía e Historia |
| 39 | `valores` | Valores |
| 40 | `psicologia` | Psicología |
| 41 | `filosofia` | Filosofía |
| 42 | `religion` | Religión |
| 43 | `musica_y_danza` | Música y danza |
| 44 | `dialecto_canario` | Dialecto canario |
| 45 | `etnografia_y_artesania_canarias` | Etnografía y artesanía canarias |
| 46 | `paisaje_y_arquitectura_canaria` | Paisaje y arquitectura canaria |
| 47 | `vestuario_y_complementos_canarios` | Vestuario y complementos canarios |
| 48 | `deportes_autoctonos` | Deportes autóctonos |
| 49 | `musica_tradicional_canaria` | Música tradicional canaria |
| 50 | `espacios_naturales_canarios` | Espacios naturales canarios |
| 51 | `sociedad_y_cultura_canaria` | Sociedad y cultura canaria |
| 52 | `patrimonio_canario` | Patrimonio canario |
| 53 | `salud` | Salud |
| 54 | `medio_ambiente_y_sostenibilidad` | Medio Ambiente y Sostenibilidad |
| 55 | `tic` | TIC |
| 56 | `flora_canaria` | Flora canaria |
| 57 | `literatura_canaria` | Literatura canaria |
| 58 | `personaje_ilustre_canario` | Personaje ilustre canario |

**mst_campos_valores — Idioma / lengua (mst_campo_entrada_id = 9)**

| id seed | código propuesto | etiqueta (literal) |
|---|---|---|
| 59 | `aleman` | ` alemán` |
| 60 | `notacion_cientifica` | ` notación científica` |
| 61 | `ingles` | ` inglés` |
| 62 | `espanol` | ` español` |
| 63 | `frances` | ` francés` |
| 64 | `afar` | afar |
| 65 | `abjasio` | abjasio (o abjasiano) |
| 66 | `avestico` | avéstico |
| 67 | `afrikaans` | afrikáans |
| 68 | `akano` | akano |
| 69 | `amharico` | amhárico |
| 70 | `aragones` | aragonés |
| 71 | `arabe` | árabe |
| 72 | `asames` | asamés |
| 73 | `avar` | avar (o ávaro) |
| 74 | `aimara` | aimara |
| 75 | `azeri` | azerí |
| 76 | `baskir` | baskir |
| 77 | `bielorruso` | bielorruso |
| 78 | `bulgaro` | búlgaro |
| 79 | `bhoyapuri` | bhoyapurí |
| 80 | `bislama` | bislama |
| 81 | `bambara` | bambara |
| 82 | `bengali` | bengalí |
| 83 | `tibetano` | tibetano |
| 84 | `breton` | bretón |
| 85 | `bosnio` | bosnio |
| 86 | `catalan` | catalán |
| 87 | `checheno` | checheno |
| 88 | `chamorro` | chamorro |
| 89 | `corso` | corso |
| 90 | `cree` | cree |
| 91 | `checo` | checo |
| 92 | `eslavo_eclesiastico_antiguo` | eslavo eclesiástico antiguo |
| 93 | `chuvasio` | chuvasio |
| 94 | `gales` | galés |
| 95 | `danes` | danés |
| 96 | `maldivo` | maldivo (o dhivehi) |
| 97 | `dzongkha` | dzongkha |
| 98 | `ewe` | ewé |
| 99 | `griego` | griego (moderno) |
| 100 | `esperanto` | esperanto |
| 101 | `estonio` | estonio |
| 102 | `euskera` | euskera |
| 103 | `persa` | persa |
| 104 | `fula` | fula |
| 105 | `fines` | finés (o finlandés) |
| 106 | `fiyiano` | fiyiano (o fiyi) |
| 107 | `feroes` | feroés |
| 108 | `frison` | frisón (o frisio) |
| 109 | `irlandes` | irlandés (o gaélico) |
| 110 | `gaelico_escoces` | gaélico escocés |
| 111 | `gallego` | gallego |
| 112 | `guarani` | guaraní |
| 113 | `guyarati` | guyaratí (o gujaratí) |
| 114 | `manes` | manés (gaélico manés o de Isla de Man) |
| 115 | `hausa` | hausa |
| 116 | `hebreo` | hebreo |
| 117 | `hindi` | hindi (o hindú) |
| 118 | `hiri_motu` | hiri motu |
| 119 | `croata` | croata |
| 120 | `haitiano` | haitiano |
| 121 | `hungaro` | húngaro |
| 122 | `armenio` | armenio |
| 123 | `herero` | herero |
| 124 | `interlingua` | interlingua |
| 125 | `indonesio` | indonesio |
| 126 | `occidental` | occidental |
| 127 | `igbo` | igbo |
| 128 | `yi_de_sichuan` | yi de Sichuán |
| 129 | `inupiaq` | iñupiaq |
| 130 | `ido` | ido |
| 131 | `islandes` | islandés |
| 132 | `italiano` | italiano |
| 133 | `inuktitut` | inuktitut (o inuit) |
| 134 | `japones` | japonés |
| 135 | `javanes` | javanés |
| 136 | `georgiano` | georgiano |
| 137 | `kongo` | kongo (o kikongo) |
| 138 | `kikuyu` | kikuyu |
| 139 | `kuanyama` | kuanyama |
| 140 | `kazajo` | kazajo (o kazajio) |
| 141 | `groenlandes` | groenlandés (o kalaallisut) |
| 142 | `camboyano` | camboyano (o jemer) |
| 143 | `canares` | canarés |
| 144 | `coreano` | coreano |
| 145 | `kanuri` | kanuri |
| 146 | `cachemiro` | cachemiro (o cachemir) |
| 147 | `kurdo` | kurdo |
| 148 | `komi` | komi |
| 149 | `cornico` | córnico |
| 150 | `kirguis` | kirguís |
| 151 | `latin` | latín |
| 152 | `luxemburgues` | luxemburgués |
| 153 | `luganda` | luganda |
| 154 | `limburgues` | limburgués |
| 155 | `lingala` | lingala |
| 156 | `lao` | lao |
| 157 | `lituano` | lituano |
| 158 | `luba_katanga` | luba-katanga (o chiluba) |
| 159 | `leton` | letón |
| 160 | `malgache` | malgache (o malagasy) |
| 161 | `marshales` | marshalés |
| 162 | `maori` | maorí |
| 163 | `macedonio` | macedonio |
| 164 | `malayalam` | malayalam |
| 165 | `mongol` | mongol |
| 166 | `marati` | maratí |
| 167 | `malayo` | malayo |
| 168 | `maltes` | maltés |
| 169 | `birmano` | birmano |
| 170 | `nauruano` | nauruano |
| 171 | `noruego_bokmal` | noruego bokmål |
| 172 | `ndebele_del_norte` | ndebele del norte |
| 173 | `nepali` | nepalí |
| 174 | `ndonga` | ndonga |
| 175 | `neerlandes` | neerlandés (u holandés) |
| 176 | `nynorsk` | nynorsk |
| 177 | `noruego` | noruego |
| 178 | `ndebele_del_sur` | ndebele del sur |
| 179 | `navajo` | navajo |
| 180 | `chichewa` | chichewa |
| 181 | `occitano` | occitano |
| 182 | `ojibwa` | ojibwa |
| 183 | `oromo` | oromo |
| 184 | `oriya` | oriya |
| 185 | `osetico` | osético (u osetio, u oseta) |
| 186 | `panyabi` | panyabí (o penyabi) |
| 187 | `pali` | pali |
| 188 | `polaco` | polaco |
| 189 | `pastu` | pastú (o pastún, o pashto) |
| 190 | `portugues` | portugués |
| 191 | `quechua` | quechua |
| 192 | `romanche` | romanche |
| 193 | `kirundi` | kirundi |
| 194 | `rumano` | rumano |
| 195 | `ruso` | ruso |
| 196 | `ruandes` | ruandés (o kiñaruanda) |
| 197 | `sanscrito` | sánscrito |
| 198 | `sardo` | sardo |
| 199 | `sindhi` | sindhi |
| 200 | `sami_septentrional` | sami septentrional |
| 201 | `sango` | sango |
| 202 | `cingales` | cingalés |
| 203 | `eslovaco` | eslovaco |
| 204 | `esloveno` | esloveno |
| 205 | `samoano` | samoano |
| 206 | `shona` | shona |
| 207 | `somali` | somalí |
| 208 | `albanes` | albanés |
| 209 | `serbio` | serbio |
| 210 | `suazi` | suazi (o swati, o siSwati) |
| 211 | `sesotho` | sesotho |
| 212 | `sundanes` | sundanés (o sondanés) |
| 213 | `sueco` | sueco |
| 214 | `suajili` | suajili |
| 215 | `tamil` | tamil |
| 216 | `telugu` | télugu |
| 217 | `tayiko` | tayiko |
| 218 | `tailandes` | tailandés |
| 219 | `tigrina` | tigriña |
| 220 | `turcomano` | turcomano |
| 221 | `tagalo` | tagalo |
| 222 | `setsuana` | setsuana |
| 223 | `tongano` | tongano |
| 224 | `turco` | turco |
| 225 | `tsonga` | tsonga |
| 226 | `tartaro` | tártaro |
| 227 | `twi` | twi |
| 228 | `tahitiano` | tahitiano |
| 229 | `uigur` | uigur |
| 230 | `ucraniano` | ucraniano |
| 231 | `urdu` | urdu |
| 232 | `uzbeko` | uzbeko |
| 233 | `venda` | venda |
| 234 | `vietnamita` | vietnamita |
| 235 | `volapuk` | volapük |
| 236 | `valon` | valón |
| 237 | `wolof` | wolof |
| 238 | `xhosa` | xhosa |
| 239 | `yidish` | yídish (o yidis, o yiddish) |
| 240 | `yoruba` | yoruba |
| 241 | `chuan` | chuan (o chuang, o zhuang) |
| 242 | `chino` | chino |
| 243 | `zulu` | zulú |

Valores repetidos en el array del seeder que `firstOrCreate` (búsqueda por `descripcion`, colación MySQL insensible a mayúsculas/acentos) no vuelve a crear: 'dialecto canario'.

### 8.3 `mst_pautas`

| id | tipo | estado | texto |
|---|---|---|---|
| 1 | 1 | 1 | `Esto son las pautas generales desde la tabla maestra de pautas` |

`database/seeders/MstPautasSeeder.php:20-22`. El texto es un **marcador de posición**: el contenido real de producción se editaba en BD (no hay pantalla). Se usa el primer registro activo (`DicAulaController.php:94,249`). `tipo` no se usa en el código.

### 8.4 Roles

| `roles.name` | display | Uso | Evidencia |
|---|---|---|---|
| `admin` | traducción de Voyager «Administrador» | panel `/admin`; `dpHome` redirige a `/admin` | `database/seeders/RolesTableSeeder.php:15-20`; `DiccionarioPersonalController.php:1164-1167` |
| `user` | traducción de Voyager «Usuario normal» (usado como «Oficina técnica») | panel limitado (Logs, Vigencia) | `RolesTableSeeder.php:22-27`; `VoyagerCustomization.php:2105-2120` |
| `docente` | Docente | rol global; también rol en el aula (`dic_aula_participantes.rol_diccionario_id`) | `UsersRolesTablesSeeder.php:56-59` |
| `alumno` | Alumno | rol global y en el aula | `UsersRolesTablesSeeder.php:61-63` |

`config/ctes.php:64-72` fija `rol.docente = 1` y `rol.alumno = 2` («en producción»; en local serían 3 y 4). El código compara ids numéricos en las políticas (`app/Policies/DicAulaPolicy.php:49,118`).

Roles que devuelve CAUCE (`config/ctes.php:472-477`):

| Valor CAUCE | Significado | Rol LexiCán |
|---|---|---|
| `1` | docente de centros públicos (Pincel) | docente |
| `3` | alumnado (Pincel) | alumno |
| `4` | docente de centros del profesorado | docente |
| `5` | técnicos educativos | docente |
| otro (p. ej. `2`) | — | alumno |

### 8.5 Constantes técnicas (`config/ctes.php`): enums que deben ser tipos explícitos

| Concepto | Valores | Evidencia |
|---|---|---|
| `estados` (genérico) | `0` inactivo, `1` activo | `config/ctes.php:51-54` |
| `tipos_medios` | `1` imagen, `2` audio, `3` vídeo | `:82-86` |
| `estados_entrada` (entradas y acepciones) | `0` borrado lógico, `1` visible, `2` oculta | `:267-271` |
| `estados_envios` | `0` borrado lógico, `1` enviado, `2` borrado_estudiante (sin uso), `3` publicado | `:282-287` |
| `estado_envio_habilitado` | `0` inactivo, `1` activo, `2` planificado (sin UI) | `:334-338` |
| `comentarios_visibles` | `0` no visible, `1` visible, `2` anteriores a fecha | `:365-369` |
| `estado_comentario_entrada` | `0` no visible, `1` visible no leído, `2` visible leído | `:349-353` |
| `visibilidad` de campo | `0` no visible, `1` visible | `:446-449` |
| `origen` de `dic_aula_entradas` | `1` envío | `:418-420` |
| `dic_aula_destinatario_avisos.estado` | `3` = profesor invitado (literal en el código) | `DicAulaController.php:403` |
| Límites | entrada ≤ 150; definición ≤ 1000; «Más datos» y «Ejemplo de uso» ≤ 255; palabra en otro idioma ≤ 150; título del aula ≤ 255 (≤150 en BD); descripción del aula ≤ 255; temáticas en búsqueda ≤ `TEMATICAS_MAX` (10); scroll 10; listado del docente 200 | `:128-144,380,484`; `create_initial_structure.php:160-162,204-205`; `dicAula_helper.php:1749` |
| Abreviaturas en consulta y PDF | Sustantivo `sust.`, Adjetivo `adj.`, Adverbio `adv.`, Conjunción `conj.`, Determinante `det.`, Interjección `interj.`, Preposición `prep.`, Pronombre `pron.`, Verbo `v.`, Masculino `m.`, Femenino `f.`, Neutro `n.`, Masculino y femenino `m. y f.`, Singular `sing.`, Plural `pl.` («No tiene» no se imprime) | `config/ctes.php:196-213`; `app/Models/DiccionarioPersonalAcepcion.php:86-120` |
| Curso y vigencia | `INICIO_CURSO` (por defecto `30/8/2000`, solo cuentan día y mes), `VIGENCIA_MAX` (por defecto 10) | `config/ctes.php:482-484` |

Relaciones maestras **sin uso**: `mst_ensenanzas_estudios` y `mst_estudios_areas_materias` se rellenan con el producto cartesiano (todos con todos; `MasterTablesDataSeeder.php:345-369`) y ninguna pantalla las consulta. `fecha_baja` en `mst_*` tampoco se usa. Se proponen como **configuración del producto** (seeds versionados con `code`, `label`, `orden`, `activo`): tipos de medio, estados, campos de acepción, categoría gramatical, género y número. Como **datos administrables** (cambian con la normativa o el curso): niveles, áreas/materias, temáticas, idiomas y pautas maestras (§56).

## 9. Contenido rich-text (TinyMCE / HTML)

| Campo | Editor | Se imprime con | ¿Necesita HTML real? | Evidencia |
|---|---|---|---|---|
| `dic_aula_pautas.texto` (pautas específicas) y `mst_pautas.texto` | TinyMCE completo (listas, enlaces, **imágenes con subida**, tablas, media) | TinyMCE en solo lectura en `diccionario/aula/pautas` | Probablemente listas y negrita. Las imágenes subidas por `/upload` son el único uso de imágenes. Requiere revisar en los datos reales de producción si hay tablas o imágenes | `crearDicAula.blade.php:329-336`; `resources/js/dic_aula.js:19-58`; `resources/js/selectDicAula.js:31-33`; `diccionario/aula/pautas.blade.php:26` |
| `comentarios_entradas.comentario` | TinyMCE básico (`language: 'es'`) | `{!! … !!}` sin sanear (**XSS almacenado** del docente hacia el alumno) | No. Texto con saltos de línea, o Markdown mínimo (§43) | `modal-formulario-comentarEntrada.blade.php:25,69`; `resources/js/modales.js:216-222`; `modal-comentarosentrada.blade.php:103`; `botones/entrada/comentarios.blade.php:21` |
| `comentarios_generales.comentario` | TinyMCE básico | `{!! … !!}` | No | `comentariosDiccionarioEnviar.blade.php:94-95,184`; `comentariosDiccionarioVer.blade.php:143,208`; `resources/js/comentarios.js:25-40,107-135` |
| Definición, «Más datos», «Ejemplo de uso», entrada | `<textarea>`/`<input>` normales | escapado `{{ }}` | No (texto estructurado, §34) | `acepcionFormFieldComun.blade.php`; `pdf/dpMainPDFView.blade.php` |

Recomendación: eliminar TinyMCE, que ocupa la mayor parte del bundle (`resources/js/app.js:10-37`, `public/js/skins/*`). Las pautas pasan a texto con formato mínimo saneado y los comentarios a texto plano. Hay que migrar el HTML existente a texto (`strip_tags` + conversión de `<li>` y `<p>`) y revisar los casos con imágenes.

## 10. Medios

| Aspecto | Comportamiento legacy | Evidencia |
|---|---|---|
| Tipos | imagen (`1`), audio (`2`) y vídeo (`3`). Como máximo **uno de cada tipo por acepción**; al subir otro se reemplaza | `config/ctes.php:82-86`; `app/Helpers/dpMedios_helper.php:46-53` |
| URL externa | la columna `url_externa` existe, pero siempre se guarda `''`. **No existen medios por URL** | `dpMedios_helper.php:63`; `create_initial_structure.php:185` |
| Extensiones aceptadas (solo en el cliente: atributo `accept` y Dropify) | imagen `jpg jpeg jpe gif png bmp tif tiff ico`; audio `mp3 ogg wav`; vídeo `mp4 ogg webm`. Las grabaciones del navegador llegan como `audio/webm`/`video/webm` o PNG | `config/ctes.php:113-117`; `app/Helpers/global_helper.php:514-542`; `dpAcepcionFormulario.blade.php:140,198` |
| Validación en servidor | **ninguna** de MIME, tamaño ni contenido (los `Validator` no incluyen los ficheros). La extensión guardada sale de `$file->extension()` (por contenido) | `DiccionarioPersonalController.php:491-499,566-590`; `EnvioAcepcionController.php:92-97` |
| Tamaño máximo | `UPLOAD_MAXSIZE`: php.ini fijado al construir la imagen + Dropify (`env('UPLOAD_MAXSIZE','200M')`); la documentación cita 75 MB | `config/ctes.php:317-322`; `developers.md:319-329` |
| Ruta de almacenamiento | disco local `storage/app/public/` + `dp/medios/imagenes`, `dp/medios/audios`, `dp/medios/videos`; URL pública `/storage/dp/medios/...` (enlace simbólico `public/storage`) | `config/ctes.php:96-103`; `dpMedios_helper.php:222-305` |
| Nombre del fichero | `{persona_id}_{entrada_id}_{acepcion_id}_{random20}.{ext}`; el nombre original queda en `nombre` | `dpMedios_helper.php:105-131` |
| Miniaturas | imagen: GD, 250 px de ancho, `{nombre}_thumb.jpg` en la misma carpeta (también la usa el PDF). Vídeo: FFmpeg (`lakshmaji/thumbnail`) en el segundo 1, `{nombre}_thumb.jpg` | `config/ctes.php:256-257`; `app/Helpers/create-thumbnail.php:39-151`; `app/Helpers/video_thumbnail.php:8-39`; `config/thumbnail.php:25-47` |
| Copia al enviar | por referencia: el fichero es el mismo y las filas se duplican. El docente puede reemplazar el medio en la copia (fichero nuevo) | `app/Helpers/dpEnvios_helper.php:425-450`; `app/Helpers/envioAcepcion_helper.php:70-128` |
| Borrado | borra la fila. El fichero solo se borra si la entrada nunca se envió | `dpMedios_helper.php:134-218` |
| Avatares | SVG oficiales en `storage/app/public/avatares/oficiales` (carga manual en NFS). Por defecto `default.svg` | `DiccionarioPersonalController.php:1066-1080`; `global_helper.php:790`; `readme.md:374-380` |
| Subidas de TinyMCE | `storage/app/public/tinyUploads/{nombre original}`, sin validación | `HomeController.php:55-59` |
| Ficheros de ayuda | `public/imagenes/Qué_puedo_hacer_*.pdf` | `quehacer.blade.php:5,48` |
| NFS | `storage/app/public` y `storage/logs` son volúmenes NFS | `developers.md:197-278` |

## 11. CAS / CAUCE

**Flujo exacto:**

1. Cualquier ruta del grupo `auth` sin sesión pasa por `App\Http\Middleware\Authenticate`. Redirige a `route('cas.login')`, salvo en `APP_ENV=local`, donde va a `voyager.login` (`app/Http/Middleware/Authenticate.php:26-34`).
2. `GET /cas/login` → `cas()->authenticate()` → `phpCAS::forceAuthentication(url('/cas/callback'))` (`routes/web.php:396-398`; `app/Cas/CasManager.php:202-215`). Usa `apereo/phpcas 1.5.0` con protocolo CAS por defecto `3.0` (`config/cas.php:142`).
3. Validación del ticket: si `CAS_VALIDATION` es `ca` o `self`, se valida el certificado. En cualquier otro caso se llama a `phpCAS::setNoCasServerValidation()`, es decir, **no se valida el servidor CAS** (`app/Cas/CasManager.php:183-191`). Hay soporte de SAML y de logout único restringido a `cas_real_hosts` (`:133-139`).
4. `GET /cas/callback` → `CasController@callback` (`app/Http/Controllers/Auth/CasController.php:28-41`):
   1. `userValidatorCAUCE(cas()->user()->id)`. **El único atributo CAS que se consume es el identificador de usuario (`phpCAS::getUser()`)**; los atributos CAS se leen (`CasManager.php:250-253`) pero no se usan. Si devuelve `false` → `abort(403)`.
   2. `User::where('name', <id CAS>)->firstOrFail()` → `Auth::loginUsingId` → `setUserDataSession()` → redirige a `/personal`.
5. `userValidatorCAUCE` (`app/Helpers/global_helper.php:713-926`):
   - Petición `GET {CAUCE_WEBSERVICE}{idCAS}` con `Authorization: Bearer {CAUCE_BEARER}`, `verify => false` (**TLS sin verificar**) y `http_errors => false` (`:740-752`). La URL (host interno de Medusa) y el token están en variables de entorno y no se reproducen aquí.
   - Respuesta XML `CheckUsuarioAutorizadoResponse` con `Usuario/InfoUsuario/{Nombre, Apellidos, NifNie, Pasaporte, CIAL}`, `Centro/InfoCentro[]/{Codigo, Nombre, Rol}` y `MensajeError` (ver los XML de prueba en `HomeController.php:102-195`).
   - **Se registra en el log, en nivel info, la respuesta completa en JSON**, con nombre, NIF/NIE, CIAL y centros (`:762-767`). También la persona (`:800-805`) y los datos de centros (`:933-942`).
   - Si `MensajeError` no está vacío → `false` → 403 (`:770-777`).
   - Busca la persona por NIF/NIE; si no, por pasaporte; si no, por CIAL. Si no existe, la crea con `avatar_URL='default.svg'` y `estado=1`. **Siempre sobrescribe** `cial`, `NIF_NIE`, `pasaporte`, `nombre` y `apellidos` (`:779-798`).
   - Si la persona no tiene usuario: calcula el rol (cualquier `Rol` ∈ {1,4,5} → docente; si no, alumno) y crea `users` con `name = email = idCAS`, `role_id`, `avatar='users/default.png'` y un **hash de contraseña fijo** en el código (`:824-875`). Después crea `users_personas` (`:891-907`). **Si el usuario ya existe, el rol no se recalcula.**
   - `asignarCentroaUsuario`: hace `firstOrCreate` de cada `Centro` por `cod_centro` (o del comodín `00000000` «Sin centro educativo») y `sync` de `users_centros` (`:930-1113`).
   - Transacción `DB::beginTransaction/commit`. Los errores se registran y devuelven `false`, lo que da un 403 genérico sin mensaje al usuario.
   - Hay un `TODO` sobre entidades XML (`&aacute;`) que hacen fallar `simplexml_load_string` (`:754-758`).
6. `setUserDataSession()`: si falta persona o centros, devuelve una vista 403, pero **el callback ignora el valor devuelto** (`global_helper.php:472-480`; `CasController.php:37`).
7. Logout: `POST /cas/logout` → `auth()->logout()`, `session()->flush()` y `cas()->logout(url('/'))`, que redirige a `CAS_LOGOUT_URL` con `service`/`url` (`routes/web.php:402-407`; `CasManager.php:309-330`).

**Decisiones que dependen de CAUCE:**

- Si se permite el acceso (`MensajeError`).
- El rol global docente/alumno, que solo se fija al crear el usuario.
- Los centros asociados (solo para estadísticas).
- Nombre y apellidos que se muestran (créditos del PDF, listas de participantes, comentarios).

Ninguna regla de un aula consulta CAUCE en tiempo real.

**Qué se guarda en local:**

- `personas`: CIAL, NIF/NIE, pasaporte, nombre, apellidos, avatar, estado.
- `users`: `name` y `email` = id CAS, role, contraseña fija.
- `users_personas`.
- `centros` (código, denominación) y `users_centros`.
- Logs diarios `storage/logs/diccionario-YYYY-MM-DD.log` con PII (`config/logging.php:93-97`).

**TTL:** ninguno. Se llama a CAUCE en cada login y no hay caché.

Otras rutas: el modo de prueba `userValidatorCAUCE($id, $xmlTest)` está expuesto en `GET /test`.

Para el rediseño (§39):

- Adapter `InstitutionalDirectory` que devuelva `{subject, displayName, givenName, familyName, schools[{code,name,role}], error}`.
- No persistir NIF/CIAL. Usar como `auth_identities.subject` el id CAS.
- Recalcular el rol en cada login.
- Validar TLS.
- No registrar la respuesta.

## 12. Exportaciones

| Exportación | Formato y maquetación | Filtros | Evidencia |
|---|---|---|---|
| PDF del diccionario personal | dompdf, `stream('dp_{Nombre Apellidos}.pdf')`. **Portada**: título en mayúsculas sobre `pdf02_Portada.png`. **Cabecera** repetida: logo LexiCán + «DICCIONARIO: {TÍTULO}» + escudo del Gobierno. **Cuerpo**: por entrada (orden alfabético), el título de la entrada y las acepciones numeradas «n. {abreviaturas} {definición}»; debajo «Más datos. {frase_ejemplo}», «{ejemplo2}», «{Idioma}: {palabra}» y «Temáticas: …», con la miniatura de la imagen a la derecha (columnas 10/2). Paginación por script PHP de dompdf. Sin contenido: «No hay entradas para este diccionario». **Contraportada**: `pdf02_Contra_Portada.png`, título de contraportada personal, descripción, título y «Autor» + NOMBRE COMPLETO | «Exportar entradas y acepciones ocultas (N)» y «Solo etiquetadas» + selección de temáticas | `PDFController.php:36-74`; `app/Helpers/pdf_helper.php:24-139`; `resources/views/layouts/partials/pdf/dpMainPDFView.blade.php`, `dpPortada`, `dpHeader`, `dpContraPortada`, `css` |
| PDF del diccionario de aula | igual. Portada «Diccionario de Aula» + título (`pdf02_Portada_aula.png`); cabecera con el título. Entradas publicadas y visibles (`peticionEntradasAula`) con **todas** sus acepciones (incluidas las ocultas en el aula). Contraportada con «Coordinadores» (propietario + participantes docentes) y «Participantes» (resto) por nombre y apellidos. Sin entradas: error «no hay entradas» (código 204) | «Solo etiquetadas» + temáticas | `PDFController.php:85-125`; `pdf_helper.php:141-306`; `pdf/daMainPDFView`, `daPortada`, `daHeader`, `daContraPortada` |
| CSV de estadísticas (admin) | `fputcsv` a `public/exports/data_export_{Y-m-d}.csv` o `dataAll_export_{Y-m-d}.csv`, descarga y borrado de ficheros de días anteriores. Columnas = alias SQL: `ano_ini_curso_escolar,numDiccionarios`; `ano_ini_curso_escolar,numVigentes`; `rol_diccionario_id,numPersonas`; `ano_ini_curso,numDicsCreados`; `entradasPublicadaDicAulaVigentes`; `ano_ini_curso,numEntradas`; `centros,totalCentros,curso`. `getAllDatesCSV` concatena varios bloques, cada uno con su cabecera | `option` = `dicsCursos` / `dicsPersonales` / `centros` | `CSVController.php:21-156`; `VoyagerCompassController.php:95-241` |
| CSV o JSON de un diccionario | — | — | **no existe** |
| JSON interno | `/personal/entradas_json` y `/aula/entradas_json` (`{"entradas":[palabra,…]}` para el autocompletado), `/aula/{id}/entradas.json` (estado de envíos), `/admin/vigencia*.json` | — | `DiccionarioPersonalController.php:1527`; `DicAulaController.php:1114-1157`; `VoyagerCompassController.php:418-439` |

## 13. Correo

| Correo | Cuándo | Destinatario | Asunto / contenido | Evidencia |
|---|---|---|---|---|
| Invitación a docente | cuando un coordinador envía el modal «Invitar docente» | el email tecleado (solo se valida `required`, `max:150`; **no se valida el formato**) | Asunto «LexiCán: Invitación a unirse a un diccionario.». HTML con «Contenido automático no responda a este correo», nombre y apellido, código del aula, pasos para unirse con la URL `app.url` y aviso de confidencialidad. Remitente fijo, cuenta institucional escrita en el código («solo me deja enviar emails con este from») | `DicAulaController.php:349-420`; `resources/views/layouts/partials/mail/inviteDocenteMailView.blade.php` |
| Correo de prueba | `GET /mail/get` | direcciones externas fijas en el código | «este es un mail de prueba desde el helper» | `MailController.php:62-100` |
| Avisos de envíos de entradas al docente | — | — | **no existe**: el campo `correoAvisos` no se guarda y las tablas `avisos*` no se usan | §3 y §4 |
| Notificación al alumno de publicación, rechazo o comentario | — | — | **no existe** | — |
| Recuperación de contraseña de Laravel | `/password/email` | — | rutas de `Auth::routes()`; no aplican a usuarios CAS (legacy sin uso) | `routes/web.php:280` |

Un fallo de SMTP en la invitación se registra y muestra `invitarmail_error`, pero no rompe la edición del aula (`DicAulaController.php:385-397`).

## 14. Autorización: resumen de huecos (entrada para §26 y para el hardening)

| Acción | Comprobación actual | Evidencia |
|---|---|---|
| Borrar aula | ninguna | `DicAulaController.php:1598-1608` |
| Guardar edición de aula | ninguna (solo la ve el `edit` GET) | `DicAulaController.php:297-336` |
| Habilitar o deshabilitar participantes y cambiar su rol | solo `esDocente` global | `DicAulaController.php:702-707` |
| Editar o borrar comentario de entrada | ninguna | `DicAulaController.php:1173-1243` |
| Crear comentario general | ninguna (el GET sí exige coordinador) | `ComentariosController.php:287-364` |
| Editar acepción publicada / borrar medio | ninguna | `EnvioAcepcionController.php:36-224` |
| Editar texto de entrada publicada | ninguna | `DicAulaController.php:1377-1494` |
| Subir o bajar acepción personal | ninguna (`authorize` comentado) | `DiccionarioPersonalController.php:724-775` |
| PDF del diccionario personal | ninguna (IDOR) | `PDFController.php:36-74` |
| Seleccionar aula activa | ninguna | `DicAulaController.php:775-805` |
| `getCSV` | solo `auth` | `CSVController.php:101-106` |
| Comprobar vigencia de un aula | ninguna, ni siquiera sesión | `routes/web.php:298-299` |
| `/admin/sendFileJs`, `/admin/sendFotosJs` | ninguna, ni siquiera sesión | `routes/web.php:340-344` |
| `/upload` (TinyMCE) | solo `auth`, cualquier fichero | `HomeController.php:55-59` |
| SQL construido con texto del usuario | `whereRaw('LOWER(entrada) LIKE "…"')` con interpolación | `app/Helpers/dicAula_helper.php:1644` |

## 15. Preguntas abiertas para el equipo funcional

1. ¿Se usa en producción «Canarismos» o algún tipo de diccionario distinto del 1? Habría que hacer un `SELECT mst_tipo_dic_id, count(*)` en un volcado anonimizado.
2. ¿Se quiere de verdad un «máximo de acepciones por entrada»? Hoy no hace nada.
3. ¿La grabación con cámara o micrófono en el navegador se usa? Medible por medios con nombre original `grabacion_*`/`grabación_*` en `dp_acepciones_medios.nombre`.
4. Texto real de `mst_pautas` y existencia de imágenes o tablas en `dic_aula_pautas.texto`.
5. ¿Qué pantallas de Voyager usa la Oficina técnica además de Vigencia y Logs? Las vistas `compass.*` no están en el repositorio: hay que recuperarlas de la imagen desplegada si se quiere reproducir su maquetación.
6. ¿Debe un docente invitado entrar como docente directamente? Hoy entra como alumno y el propietario lo promueve.
7. ¿Hay que conservar los centros (`centros`, `users_centros`) más allá de las estadísticas?
8. Política de vigencia: ¿debe desactivarse automáticamente al cambiar de curso (tarea programada), o se mantiene manual?

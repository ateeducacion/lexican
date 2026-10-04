# Correspondencia LexiCán → OASIS DMLex 1.0

LexiCán exporta cualquier diccionario (personal o de aula) en la **serialización JSON de DMLex 1.0** (OASIS Standard, 29 de abril de 2025), módulo *Core* más las etiquetas del módulo *Controlled Values*. El fichero lo genera el navegador desde la pantalla **Imprimir / exportar** (botón «DMLex JSON») con la función pura `toDmlex` de `packages/core/src/export.ts`.

- Especificación: <https://docs.oasis-open.org/lexidma/dmlex/v1.0/os/dmlex-v1.0-os.html>
- Esquema usado (variante monolingüe, sin módulo *Crosslingual*): `dmlex_no-crosslingual.schema.json`, copiado en `packages/core/src/dmlex-1.0.schema.json`. La prueba `export.test.ts` valida la salida contra él con Ajv (JSON Schema 2020-12).
- Unidad de intercambio: un único objeto `lexicographicResource` por fichero.

## Tabla de correspondencias

| LexiCán | DMLex | Correspondencia | Pérdidas / Extensiones |
|---|---|---|---|
| Diccionario (título) | `lexicographicResource.title` | Directa | La descripción, el curso escolar y la configuración del aula no tienen sitio en el núcleo y no se exportan (sí en el JSON propio). |
| Idioma del diccionario | `lexicographicResource.langCode` | Siempre `"es"` | — |
| Entrada | `entry` | Una por entrada exportada, en orden alfabético | `uri` no se emite. |
| Id. de entrada | `entry.id` | UUID interno | — |
| Palabra (lema) | `entry.headword` | Directa | No hay `homographNumber`: LexiCán no admite homógrafos en un mismo diccionario. |
| Categoría gramatical (por acepción) | `entry.partsOfSpeech[]` + `partOfSpeechTags[]` | Se sube a la entrada: unión sin repetidos de las categorías de sus acepciones; la etiqueta es el código del vocabulario y la descripción, su nombre | Se pierde a qué acepción concreta pertenecía cada categoría (DMLex solo la admite en la entrada). «No tiene» no se exporta. |
| Acepción | `sense` | Una por acepción, en su orden | — |
| Id. de acepción | `sense.id` | UUID interno | — |
| Definición | `sense.definitions[0].text` | Directa | — |
| Más datos | `sense.definitions[n].text` con `definitionType: "mas-datos"` + `definitionTypeTags[]` | Segunda definición tipada | Extensión: el tipo `mas-datos` lo define LexiCán. |
| Ejemplo de uso | `sense.examples[0].text` | Directa | — |
| Género | `sense.labels[]` (`gender.<código>`) + `labelTags[]` con `typeTag: "gender"` | Etiqueta tipada | — |
| Número | `sense.labels[]` (`number.<código>`) + `labelTags[]` con `typeTag: "number"` | Etiqueta tipada | — |
| Temáticas | `sense.labels[]` (`topic.<código>`) + `labelTags[]` con `typeTag: "topic"` | Etiquetas tipadas | — |
| Tipos de etiqueta | `labelTypeTags[]` (`gender`, `number`, `topic`) | Solo los usados | — |
| Otras lenguas (lengua + palabra) | — | **No se exporta** | Requiere el módulo *Crosslingual* (`headwordTranslations`, `translationLanguages`), que no usamos. Queda en CSV y JSON. |
| Imagen, audio, vídeo | — | **No se exporta** | El audio no es una pronunciación del lema, así que no se usa `pronunciations.soundFile`. Los ficheros no van en el JSON. |
| Entrada / acepción oculta | — | Las ocultas solo se incluyen si quien exporta es docente/propietario y marca «Incluir ocultas»; no se marca su estado | El indicador de oculta se pierde (sí está en el JSON propio). |
| Autor, envíos, versión, fechas | — | **No se exporta** | Metadatos de flujo de trabajo, fuera del modelo lexicográfico. |
| Comentarios del profesorado | — | **No se exporta** | Privados del aula. |

## Qué contiene cada exportación

| Formato | Contenido |
|---|---|
| **CSV** | Una fila por acepción: Entrada, Acepción (nº), Categoría gramatical, Género, Número, Definición, Más datos, Ejemplo de uso, Lengua, Palabra en otra lengua, Temáticas (separadas por `; `). RFC 4180: comillas cuando hace falta, comillas dobladas, fin de línea CRLF, UTF-8 con BOM para Excel. |
| **JSON** (`lexican-export`, versión 1) | Datos del diccionario y entradas completas con los vocabularios resueltos a `{código, nombre}`, marcas de oculta, autor y nombre/tipo de los ficheros multimedia. Es el formato sin pérdidas de texto. |
| **DMLex JSON** | El recurso descrito en la tabla anterior. |

Las tres exportaciones respetan los filtros de la pantalla: «Incluir ocultas» y «Solo temáticas seleccionadas» (se conservan solo las acepciones con alguna de las temáticas marcadas y las entradas que conserven alguna acepción).

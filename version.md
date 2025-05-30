# Versiones Lexicán

## Lexicán 1.2.5

* #766162
  * Se actualiza la función que envía la invitación a participar en un diccionario de aula

### Imagen a desplegar

www.canariaseducacion.org/intercambiador/lexican:1.2.5

### Instalacion desde version anterior

No se requieren pasos adicionales aparte del despliegue de la imagen en openshift

## Lexicán 1.2.4

* #756208
  * Se añade funcionalidad que permite a un propietario modificar el rol de un participante en el diccionario de aula

### Imagen a desplegar

www.canariaseducacion.org/intercambiador/lexican:1.2.4

### Instalacion desde version anterior

No se requieren pasos adicionales aparte del despliegue de la imagen en openshift

## Lexicán 1.2.3

* #753585
  * Se añade control para que los profesores que no son los dueños de un diccionario de aula no puedan modificarlo ni eliminarlo

### Imagen a desplegar

www.canariaseducacion.org/intercambiador/lexican:1.2.3

### Instalacion desde version anterior

No se requieren pasos adicionales aparte del despliegue de la imagen en openshift

## Lexicán 1.2.2

* #711609
  * Añadir enlaces aviso legal y politica de privacidad

### Imagen a desplegar

www.canariaseducacion.org/intercambiador/lexican:1.2.2

### Instalacion desde version anterior

No se requieren pasos adicionales aparte del despliegue de la imagen en openshift

## Lexicán 1.2.1
 
* #688719 
  * Actualizar logos Gobierno De Canarias

## Lexicán 1.2.0

En esta versión se han realizado los siguientes cambios

* #603197 
  * Permitir importar entradas diccionario inactivo
* #603201 
  * Correcion calculo de la vigencia diccionarios atemporales 
* #590371
  * Proceso comprobar vigencia de aula en panel de control
* #578178 
  * Nuevo campo "frase ejemplo" en acepciones
* #578177
  * Al exportar PDF ahora podemos seleccionar:
    * Exportar entradas y acepciones ocultas
    * Exportar solo etiquetas seleccionadas
  * Diccionario personal no se permite enviar al diccionario de aula entradas sin acepciones (muestra mensaje)
  * Diccionario personal, si se borra la ultima acepción visible se oculta la entrada completa
* #610344
  * Crear menus estadisticas para usuario oficinatecnica
* #620169
  * Grabar audio/video y asociarlo a una acepción
* #659720
  * Evitar Error 500 al acceder al diccionario de aula provocado por el borrado de entrada en el dic personal
* #669974
  * Añadir opción para eliminar el envío de una entrada en dic. de aula
  
### Imagen a desplegar

www.canariaseducacion.org/intercambiador/lexican:1.2.0

### Instalacion desde version anterior

```bash
php artisan migrate 
composer dump-autoload -o
php artisan db:seed --class=NuevoCampoEjemploSeeder
php artisan db:seed --class=MenuEstadisticasSeeder
```

## Lexicán 1.1.2

En esta versión se han realizado los siguientes cambios:

* Se minimiza el tamaño de las imagenes en los pdf para evitar generar archivos exesivamente grandes

### Imagen a desplegar

www.canariaseducacion.org/lexican/lexican:1.1.2

## Lexicán 1.1.1 

En esta versión se han realizado los siguientes cambios:
* Corregir proceso de obtención del año escolar actual
  
### Imagen a desplegar

www.canariaseducacion.org/intercambiador/lexican:1.1.1

### Instalacion desde version anterior

```bash
php artisan migrate 
composer dump-autoload -o
php artisan db:seed --class=NuevoCampoEjemploSeeder
```

## Lexicán 1.1.0

En esta versión se han realizado los siguientes cambios

* Ahora Lexicán usa Laravel 8
* Se Actualiza a PHP 8

No es necesario ningún paso adicional aparte de importar la imagen para actualizar desde 1.0.4

## Lexicán 1.0.4

* Ahora en las entradas el texto se extiende pasando por debajo de la imagen.
* Mejoras en el aspecto de las entradas con distintas resoluciones, eliminados espacios a la derecha de la entrada en el diccionario de aula cuando se accedía como alumno.
* Ahora al editar un diccionario de aula también se puede deshabilitar el acceso a profesores y se muestra el listado de docentes con desplazamiento vertical cuando se extiende más de 5 filas.
* Se ha corregido la incidencia que hacia que el buscador predictivo no mostrase todas las entradas en el diccionario de aula.
* Eliminado texto 'test' en enviar entradas.
* Añadido manual de usuario.
* Cambios en textos.
* Ahora 'Infantil' aparece en primer lugar, antes de 'Primaria'.
* Agregado texto de tamaño maximo al editar acepcion
* Pantalla de aceptacion de cookies
* Vigencia de diccionarios y revisar vigencia en panel administración

### Instalación 1.0.4 desde la versión 1.0.3

Es necesario actualizar la BBDD entrando en la terminal de un pod y ejecutando:

```bash
cd $APACHE_DOCUMENT_ROOT$LOCATION
php artisan migrate
composer dump-autoload -o
php artisan db:seed --class=MasterTablesDataSeederUpdate
php artisan db:seed --class=VoyagerCustomization
```

## Lexicán 1.0.3

* Cambios textos y nuevos valores en tablas maestras
* Publicar múltiples entradas en el diccionario de aula
* Tablas para diccionarios atemporales
* Se muestran solo los diccionarios en los que esta registrado el coordinador al importar entradas en nuevo diccionario de aula
* Solucionado problema al la modificación medios en diccionario de aula afectaba a dic. personal
* Solucionado error 500 al acceder al lexicán con determinadas cuentas

### Instalación 1.0.3 desde la versión 1.0.2

Es necesario actualizar la BBDD entrar en la terminal de un pod y ejecutando


```bash
cd $APACHE_DOCUMENT_ROOT$LOCATION
php artisan migrate
composer dump-autoload -o
php artisan db:seed --class=MasterTablesDataSeederUpdate  
```

Se ha creado un seeder para realizar pruebas en la versión de pre, no se puede ejecutar en producción :

```bash
$  cd $APACHE_DOCUMENT_ROOT$LOCATION
$  composer dump-autoload -o
# Este comando puede tardar mas de dos horas en terminad de ejecutarse:
$  php artisan db:seed --class=StressTestUserDataSeeder
```

## Lexicán 1.0.2

En esta actualización se incorporan los cambios sugeridos:

* Correcciones de textos.
* Se agregan Etiquetas.
* Nuevo dialogo de publicar/despublicar entrada.
* Cambios en buscador envíos muestra la letra por la que se esta filtrando entre otros filtros
* Corregidos problemas en carga de entradas al desplazarse al final de la página en diccionario de aula.

### Instalación 1.0.2 desde la versión 1.0.1

Se utilizara la imagen de docker:
    "videoseducacion.gobiernodecanarias.org/Lexicán/Lexicán:1.0.2"

```bash
oc import-image Lexicán:1.0.2 --from=videoseducacion.gobiernodecanarias.org/Lexicán/Lexicán:1.0.2
```

Se necesita actualizar la base de datos entrando en un pod y ejecutando los siguiente comando:

```bash
cd $APACHE_DOCUMENT_ROOT$LOCATION
php artisan db:seed --class=MasterTablesDataSeederUpdate  
```

## Lexicán 1.0.1

### Instalación desde versión 1.0.0

Se utilizara la imagen de docker:
    "videoseducacion.gobiernodecanarias.org/Lexicán/Lexicán:1.0.1"

```bash
oc import-image Lexicán:1.0.a --from=videoseducacion.gobiernodecanarias.org/Lexicán/Lexicán:1.0.1
```

## Lexicán 1.0.0

Versión inicial

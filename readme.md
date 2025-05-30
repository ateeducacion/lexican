# Despliegue Lexicán 1.2.2

## Importar la imagen

* Conectarse a una de las máquinas master del Entorno de OpenShift

```shell
    ssh root@IP.NODO.MASTER.OpenShift
```

* Situarse en el proyecto intercambiador y lanzar el deploy


```shell
    oc project intercambiador
    oc new-app --docker-image=www.canariaseducacion.org/lexican/lexican:1.2.2
```

* En caso de que ya exista una versión desplegada, importar el nuevo tag

```shell
    oc project intercambiador
    oc import-image lexican:1.2.2 --from=www.canariaseducacion.org/lexican/lexican:1.2.2
```

## Creación de los volúmenes persistentes

* Conectarse a una de las máquinas master del Entorno de OpenShift

```shell
    ssh root@IP.NODO.MASTER.OpenShift
```

* Situarse en el proyecto intercambiador

```shell
    oc project intercambiador
```

* Crear los siguientes ficheros

> lexican-nfs-log-pv.yaml

```yaml
apiVersion: v1
kind: PersistentVolume
metadata:
    labels:
        app: intercambiador
    name: lexican-nfs-log-pv
spec:
    accessModes:
    - ReadWriteMany
    capacity:
        storage: 5Gi
    claimRef:
        apiVersion: v1
        kind: PersistentVolumeClaim
        name: lexican-nfs-log-pv
        namespace: intercambiador
    nfs:
        path: <ruta nfs>
        server: <servidor nfs>
    persistentVolumeReclaimPolicy: Recycle
```

> lexican-nfs-archivos-pv.yaml

```yaml
apiVersion: v1
kind: PersistentVolume
metadata:
    labels:
        app: intercambiador
    name: lexican-nfs-archivos-pv
spec:
    accessModes:
    - ReadWriteMany
    capacity:
        storage: 50Gi
    claimRef:
        apiVersion: v1
        kind: PersistentVolumeClaim
        name: lexican-nfs-archivos-pv
        namespace: intercambiador
    nfs:
        path: <ruta nfs>
        server: <servidor nfs>
    persistentVolumeReclaimPolicy: Recycle
```

* Ejecutar los comandos

```shell
    oc create -f lexican-nfs-log-pv.yaml
    oc create -f lexican-nfs-archivos-pv.yaml
```

* En OpenShift Origin crear Storages correspondientes a estos archivos

        Intercambiador > Storage > create Storage

completar con estos datos

    * logs:
        Name : lexican-nfs-log-pv
        Access mode: Shared Access (RWX)
        Size: 5GiB

    * Archivos
        Name : lexican-nfs-archivos-pv
        Access mode: Shared Access (RWX)
        Size: 50GiB

* Añadir los volúmenes persistentes creados para logs y archivos

En OpenShift Origin ir hasta :

    Deployments > lexican > Add Storage


y crear con estos datos:

    * Logs
        Storage:  lexican-nfs-log-pv
        Mount Path:  /var/www/html/medusa/apps/lexican/storage/logs
        Subpath:  <dejar en blanco>

    * Archivos
        Storage:  lexican-nfs-log-pv
        Mount Path:  /var/www/html/medusa/apps/lexican/storage/app/public
        Subpath:  <dejar en blanco>

## Creación de la ruta

Agregar la ruta lexican apuntando a la URL de produccion:

Ruta PRE: <http://www3-pre.gobiernodecanarias.org/medusa/apps/lexican>

Ruta PRO: <http://www3.gobiernodecanarias.org/medusa/apps/lexican>

En OpenShift Origin vamos a `Applications >> Routes >> Create Route`

* Nombre: lexican
* Hostname: www3.gobiernodecanarias.org
* Path: /medusa/apps/diccionario/public/
* Service: lexican
* Target Port: 8083

En PRE el hostname seria www3-pre.gobiernodecanarias.org

## Añadir las variables de entorno

Si no se especifican se obtendran los datos del fichero .env
revisar especialmente las variables que difieren segun la version de PRE y PRO

las variables esenciales que tienen que estar especificados en pre y pro son estas:

* APP_ENV
* APP_URL
* ASSET_URL
* CAS_HOSTNAME
* CAS_VALIDATION  
* CAS_LOGOUT_URL  
* CAUCE_WEBSERVICE
* CAUCE_BEARER
* DB_HOST
* DB_DATABASE
* DB_USERNAME
* DB_PASSWORD
* MAIL_PASSWORD

La aplicacion podria funcionar obteniendo los valores predeterminados del resto, pero es necesiaro se puedon cambiar en openshift

| Nombre            | Descripción                                                                                           | Valores, ejemplos                          |
| ----------------- | ----------------------------------------------------------------------------------------------------- | ------------------------------------------ |
| APP_VERSION       | Para visualizar la version en OpenShift                                                               | 1.2.2                                      |
| APP_ENV           | Entorno de aplicacion                                                                                 | local, develop, preproduction o production |
| APP_DEBUG         | Activa modo debug                                                                                     | true / false                               |
| APP_URL           | URL pagina                                                                                            | <https://url.ejemplo.org/lexican>          |
| ASSET_URL         | Se usa es para generar urls con comando artisan                                                       | <https://url.ejemplo.org//lexican/public>  |
| UPLOAD_MAXSIZE    | Tamaño máximo de archivos subidos por formularios, Numero de megas                                    | 75                                         |
| INICIO_CURSO      | Fecha en la que empieza el curso escolar, solo se tiene en cuenta mes y dia poner siempre el año 2000 | 30/8/200                                   |
| VIGENCIA_MAX      | Numero maximo de cursos que puede estar vigente un diccionario de aula                                | 10                                         |
|                   |                                                                                                       |                                            |
| CAS_HOSTNAME      | host del cas                                                                                          | url                                        |
| CAS_VALIDATION    | url de validacion del cas                                                                             | url                                        |
| CAS_VERSION       | version del cas                                                                                       | 3.0                                        |
| CAS_LOGOUT_URL    | url logout cas                                                                                        |                                            |
| CAUCE_WEBSERVICE  | url webservice cauce                                                                                  |                                            |
| CAUCE_BEARER      | token bearer del cauce                                                                                |                                            |
|                   |                                                                                                       |                                            |
| DB_HOST           | host base de datos                                                                                    |                                            |
| DB_DATABASE       | nombre base de datos                                                                                  |                                            |
| DB_USERNAME       | usuario                                                                                               |                                            |
| DB_PASSWORD       | clave                                                                                                 |                                            |
|                   |                                                                                                       |                                            |
| MAIL_DRIVER       | modo envio de correo                                                                                  | smtp, imap                                 |
| MAIL_HOST         | host servicio envio correo                                                                            |                                            |
| MAIL_PORT         | puerto                                                                                                |                                            |
| MAIL_USERNAME     | usuario correo                                                                                        | dic.canarismos@gmail.com                   |
| MAIL_PASSWORD     | clave correo                                                                                          |                                            |
| MAIL_ENCRYPTION   |                                                                                                       | tls / vacio                                |
| MAIL_FROM_ADDRESS | direccion remitente de los correos enviados                                                           | noreply@diccionarioCanarismos.es           |
| MAIL_FROM_NAME    | nombre remitente                                                                                      | Diccionario                                |
|                   |                                                                                                       |                                            |

Si no se especifican se obtendran los datos del fichero .env
revisar especialmente las variables que difieren segun la version de PRE y PRO

las variables esenciales que tienen que estar especificados en pre y pro son estas:
* APP_ENV         
* APP_URL         
* ASSET_URL       
* CAS_HOSTNAME    
* CAS_VALIDATION  
* CAS_LOGOUT_URL  
* CAUCE_WEBSERVICE
* CAUCE_BEARER    
* DB_HOST         
* DB_DATABASE     
* DB_USERNAME     
* DB_PASSWORD     
* MAIL_PASSWORD   

La aplicacion podria funcionar obteniendo los valores predeterminados del resto, pero es necesiaro se puedon cambiar en openshift



| Nombre            | Descripción                                                                                           | Valores, ejemplos                          |
| ----------------- | ----------------------------------------------------------------------------------------------------- | ------------------------------------------ |
| APP_VERSION       | Para visualizar la version en OpenShift                                                               | 1.2.2                                      |
| APP_ENV           | Entorno de aplicacion                                                                                 | local, develop, preproduction o production |
| APP_DEBUG         | Activa modo debug                                                                                     | true / false                               |
| APP_URL           | URL pagina                                                                                            | https://url.ejemplo.org/lexican            |
| ASSET_URL         | Se usa es para generar urls con comando artisan                                                       | https://url.ejemplo.org//lexican/public    |
| UPLOAD_MAXSIZE    | Tamaño máximo de archivos subidos por formularios, Numero de megas                                    | 75                                         |
| INICIO_CURSO      | Fecha en la que empieza el curso escolar, solo se tiene en cuenta mes y dia poner siempre el año 2000 | 30/8/200                                   |
| VIGENCIA_MAX      | Numero maximo de cursos que puede estar vigente un diccionario de aula                                | 10                                         |
|                   |                                                                                                       |                                            |
| CAS_HOSTNAME      | host del cas                                                                                          | url                                        |
| CAS_VALIDATION    | url de validacion del cas                                                                             | url                                        |
| CAS_VERSION       | version del cas                                                                                       | 3.0                                        |
| CAS_LOGOUT_URL    | url logout cas                                                                                        |                                            |
| CAUCE_WEBSERVICE  | url webservice cauce                                                                                  |                                            |
| CAUCE_BEARER      | token bearer del cauce                                                                                |                                            |
|                   |                                                                                                       |                                            |
| DB_HOST           | host base de datos                                                                                    |                                            |
| DB_DATABASE       | nombre base de datos                                                                                  |                                            |
| DB_USERNAME       | usuario                                                                                               |                                            |
| DB_PASSWORD       | clave                                                                                                 |                                            |
|                   |                                                                                                       |                                            |
| MAIL_DRIVER       | modo envio de correo                                                                                  | smtp, imap                                 |
| MAIL_HOST         | host servicio envio correo                                                                            |                                            |
| MAIL_PORT         | puerto                                                                                                |                                            |
| MAIL_USERNAME     | usuario correo                                                                                        | dic.canarismos@gmail.com                   |
| MAIL_PASSWORD     | clave correo                                                                                          |                                            |
| MAIL_ENCRYPTION   |                                                                                                       | tls / vacio                                |
| MAIL_FROM_ADDRESS | direccion remitente de los correos enviados                                                           | noreply@diccionarioCanarismos.es           |
| MAIL_FROM_NAME    | nombre remitente                                                                                      | Diccionario                                |
|                   |                                                                                                       |                                            |
### PREPRODUCCIÓN

| Nombre            | Valor                                                                                                                                       |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| APP_VERSION       | 1.2.2                                                                                                                                       |
| APP_ENV           | preproduction                                                                                                                               |
| APP_DEBUG         | true                                                                                                                                        |
| APP_URL           | <https://www3-pre.gobiernodecanarias.org/medusa/apps/lexican>                                                                               |
| ASSET_URL         | <https://www3-pre.gobiernodecanarias.org/medusa/apps/lexican/public>                                                                        |
| UPLOAD_MAXSIZE    | 75M                                                                                                                                         |
| INICIO_CURSO      | 31/08/2000                                                                                                                                  |
| VIGENCIA_MAX      | 10                                                                                                                                          |
|                   |                                                                                                                                             |
| CAS_HOSTNAME      | www3-pre.gobiernodecanarias.org/educacion/cau_ce                                                                                            |
| CAS_VALIDATION    | <https://www3-pre.gobiernodecanarias.org/educacion/cau_ce/cas/p3/serviceValidate>                                                           |
| CAS_VERSION       | 3.0                                                                                                                                         |
| CAS_LOGOUT_URL    | <https://www3.gobiernodecanarias.org/educacion/cau_ce/cas/logout>                                                                           |
|                   |                                                                                                                                             |
| CAUCE_WEBSERVICE  | <https://www3-pre.gobiernodecanarias.org/educacion/cau_ce/webservices/rest_checkusuarioautorizado/api/checkusuarioautorizadocanarismos?id>= |
| CAUCE_BEARER      | (consultar)                                                                                                                                 |
|                   |                                                                                                                                             |
| DB_HOST           | tfds0061.medusa.gobiernodecanarias.net                                                                                                      |
| DB_DATABASE       | lexican                                                                                                                                     |
| DB_USERNAME       | lexican_u                                                                                                                                   |
| DB_PASSWORD       | (consultar)                                                                                                                                 |
|                   |                                                                                                                                             |
| MAIL_DRIVER       | smtp                                                                                                                                        |
| MAIL_HOST         | correo.gobiernodecanarias.org                                                                                                               |
| MAIL_PORT         | 25                                                                                                                                          |
| MAIL_USERNAME     | eva.educacion@gobiernodecanarias.org                                                                                                        |
| MAIL_PASSWORD     | (consultar)                                                                                                                                 |
| MAIL_ENCRYPTION   | "" (vacio)                                                                                                                                  |
| MAIL_FROM_ADDRESS | noreply@diccionarioCanarismos.es                                                                                                            |
| MAIL_FROM_NAME    | Lexicán                                                                                                                                     |
|                   |                                                                                                                                             |

### PRODUCCIÓN

| Nombre            | Valor                                                                                                                                   |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| APP_VERSION       | 1.2.2                                                                                                                                   |
| APP_ENV           | production                                                                                                                              |
| APP_DEBUG         | false                                                                                                                                   |
| APP_URL           | <https://www3.gobiernodecanarias.org/medusa/apps/lexican>                                                                               |
| ASSET_URL         | <https://www3.gobiernodecanarias.org/medusa/apps/lexican/public>                                                                        |
| UPLOAD_MAXSIZE    | 75M                                                                                                                                     |
| INICIO_CURSO      | 31/08/2000                                                                                                                              |
| VIGENCIA_MAX      | 10                                                                                                                                      |
|                   |                                                                                                                                         |
| CAS_HOSTNAME      | www3.gobiernodecanarias.org/educacion/cau_ce                                                                                            |
| CAS_VALIDATION    | <https://www3.gobiernodecanarias.org/educacion/cau_ce/cas/p3/serviceValidate>                                                           |
| CAS_VERSION       | 3.0                                                                                                                                     |
| CAS_LOGOUT_URL    | <https://www3.gobiernodecanarias.org/educacion/cau_ce/cas/logout>                                                                       |
|                   |                                                                                                                                         |
| CAUCE_WEBSERVICE  | <https://www3.gobiernodecanarias.org/educacion/cau_ce/webservices/rest_checkusuarioautorizado/api/checkusuarioautorizadocanarismos?id>= |
| CAUCE_BEARER      | (consultar)                                                                                                                             |
|                   |                                                                                                                                         |
| DB_HOST           | ????                                                                                                                                    |
| DB_DATABASE       | ????                                                                                                                                    |
| DB_USERNAME       | ????                                                                                                                                    |
| DB_PASSWORD       | ????                                                                                                                                    |
|                   |                                                                                                                                         |
| MAIL_DRIVER       | smtp                                                                                                                                    |
| MAIL_HOST         | correo.gobiernodecanarias.org                                                                                                           |
| MAIL_PORT         | 25                                                                                                                                      |
| MAIL_USERNAME     | eva.educacion@gobiernodecanarias.org                                                                                                    |
| MAIL_PASSWORD     | (consultar)                                                                                                                             |
| MAIL_ENCRYPTION   | "" (vacio)                                                                                                                              |
| MAIL_FROM_ADDRESS | noreply@diccionarioCanarismos.es                                                                                                        |
| MAIL_FROM_NAME    | Lexicán                                                                                                                                 |
|                   |                                                                                                                                         |

## Añadir health check

en  `Deployments > lexican > Actions > Edit Health Checks` o `Deployments > lexican > Actions > Add Health Checks`

Introducir los siguientes datos

* Add Readiness Probe

* Type:   HTTP_GET
* Path:   /medusa/apps/lexican/public/hc
* Port:   8083
* Inital Delay:     30 seconds
* Timeout:           5 seconds

## Creación de tablas y sembrado en bdd

Conectarse a la terminal de uno de los pods y introducir los comando

```shell
cd $APACHE_DOCUMENT_ROOT$LOCATION
/bin/bash ./provision.sh
```

Al finalizar els proceso habrá creado la tablas en la base de datos y
los datos predeterminados necesarios en las tablas.

Mostrara el mensaje "provisioning sh fin"

## Copiar datos en volumenes persistentes

Conectarse a la terminal de uno de los pods y introducir los comando

```shell
cd $APACHE_DOCUMENT_ROOT$LOCATION
/bin/bash ./copytonfs.sh
```

Esto copiara los archivos de avatares predefinidos y creara los directorios que usa
la aplicación

### Agregar nuevos avatares o cambiar los existentes

Los avatares estan almacenados en **src/storage/app/public/avatares/oficiales** y solo se usan los archivos svg.

Estos se pueden agregar, borrar o modificar directamente en el directorio **src/storage/app/public/avatares/oficiales** en el **servidor**, tenga en cuenta que la carpeta **src/storage/app/public/avatares/oficiales** en local y en el git es ignorada al crear la imagen, ya que esta esta vinculada a la carpeta correspondiente NFS que .

Si se quieren guardar los ficheros modificados o agregados en el git se pueden modificar el directorio **/src/archivos/avatares/oficiales** y luego crear una nueva imagen, tras subirla copiar los datos persistentes como esta descrito en el punto anterior.

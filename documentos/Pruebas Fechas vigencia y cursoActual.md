# Pruebas

## Cuando falla me Aparece asi:

| fecha str      | date->format('d/m/Y') | getAnoIniCursoEscolar() | getFormattedCursoEscolar() |
| -------------- | --------------------- | ----------------------- | -------------------------- |
| HOY            |                       |                         |                            |
| new Datetime() | 05/09/2022            | 2021                    | 2021/2022                  |
| 1              |                       |                         |                            |
| 2022-1-1       | 01/01/2022            | 2021                    | 2021/2022                  |
| 2022-1-2       | 02/01/2022            | 2021                    | 2021/2022                  |
| 2022-1-30      | 30/01/2022            | 2021                    | 2021/2022                  |
| 2              |                       |                         |                            |
| 2022-2-1       | 01/02/2022            | 2021                    | 2021/2022                  |
| 2022-2-3       | 03/02/2022            | 2021                    | 2021/2022                  |
| 2022-2-30      | 02/03/2022            | 2021                    | 2021/2022                  |
| 3              |                       |                         |                            |
| 2022-3-1       | 01/03/2022            | 2021                    | 2021/2022                  |
| 2022-3-4       | 04/03/2022            | 2021                    | 2021/2022                  |
| 2022-3-30      | 30/03/2022            | 2021                    | 2021/2022                  |
| 4              |                       |                         |                            |
| 2022-4-1       | 01/04/2022            | 2021                    | 2021/2022                  |
| 2022-4-5       | 05/04/2022            | 2021                    | 2021/2022                  |
| 2022-4-30      | 30/04/2022            | 2021                    | 2021/2022                  |
| 5              |                       |                         |                            |
| 2022-5-1       | 01/05/2022            | 2021                    | 2021/2022                  |
| 2022-5-6       | 06/05/2022            | 2021                    | 2021/2022                  |
| 2022-5-30      | 30/05/2022            | 2021                    | 2021/2022                  |
| 6              |                       |                         |                            |
| 2022-6-1       | 01/06/2022            | 2021                    | 2021/2022                  |
| 2022-6-7       | 07/06/2022            | 2021                    | 2021/2022                  |
| 2022-6-30      | 30/06/2022            | 2021                    | 2021/2022                  |
| 7              |                       |                         |                            |
| 2022-7-1       | 01/07/2022            | 2021                    | 2021/2022                  |
| 2022-7-8       | 08/07/2022            | 2021                    | 2021/2022                  |
| 2022-7-30      | 30/07/2022            | 2021                    | 2021/2022                  |
| 8              |                       |                         |                            |
| 2022-8-1       | 01/08/2022            | 2021                    | 2021/2022                  |
| 2022-8-9       | 09/08/2022            | 2021                    | 2021/2022                  |
| 2022-8-30      | 30/08/2022            | 2022                    | 2022/2023                  |
| 9              |                       |                         |                            |
| 2022-9-1       | 01/09/2022            | 2021                    | 2021/2022                  |
| 2022-9-10      | 10/09/2022            | 2021                    | 2021/2022                  |
| 2022-9-30      | 30/09/2022            | 2022                    | 2022/2023                  |
| 10             |                       |                         |                            |
| 2022-10-1      | 01/10/2022            | 2021                    | 2021/2022                  |
| 2022-10-11     | 11/10/2022            | 2021                    | 2021/2022                  |
| 2022-10-30     | 30/10/2022            | 2022                    | 2022/2023                  |
| 11             |                       |                         |                            |
| 2022-11-1      | 01/11/2022            | 2021                    | 2021/2022                  |
| 2022-11-12     | 12/11/2022            | 2021                    | 2021/2022                  |
| 2022-11-30     | 30/11/2022            | 2022                    | 2022/2023                  |
| 12             |                       |                         |                            |
| 2022-12-1      | 01/12/2022            | 2021                    | 2021/2022                  |
| 2022-12-13     | 13/12/2022            | 2021                    | 2021/2022                  |
| 2022-12-30     | 30/12/2022            | 2022                    | 2022/2023                  |


## Corregido aparece asi:


| fecha str      | date->format('d/m/Y') | getAnoIniCursoEscolar() | getFormattedCursoEscolar() |
| -------------- | --------------------- | ----------------------- | -------------------------- |
| HOY            |                       |                         |                            |
| new Datetime() | 05/09/2022            | 2022                    | 2022/2023                  |
| 1              |                       |                         |                            |
| 2022-1-1       | 01/01/2022            | 2021                    | 2021/2022                  |
| 2022-1-2       | 02/01/2022            | 2021                    | 2021/2022                  |
| 2022-1-30      | 30/01/2022            | 2021                    | 2021/2022                  |
| 2              |                       |                         |                            |
| 2022-2-1       | 01/02/2022            | 2021                    | 2021/2022                  |
| 2022-2-3       | 03/02/2022            | 2021                    | 2021/2022                  |
| 2022-2-30      | 02/03/2022            | 2021                    | 2021/2022                  |
| 3              |                       |                         |                            |
| 2022-3-1       | 01/03/2022            | 2021                    | 2021/2022                  |
| 2022-3-4       | 04/03/2022            | 2021                    | 2021/2022                  |
| 2022-3-30      | 30/03/2022            | 2021                    | 2021/2022                  |
| 4              |                       |                         |                            |
| 2022-4-1       | 01/04/2022            | 2021                    | 2021/2022                  |
| 2022-4-5       | 05/04/2022            | 2021                    | 2021/2022                  |
| 2022-4-30      | 30/04/2022            | 2021                    | 2021/2022                  |
| 5              |                       |                         |                            |
| 2022-5-1       | 01/05/2022            | 2021                    | 2021/2022                  |
| 2022-5-6       | 06/05/2022            | 2021                    | 2021/2022                  |
| 2022-5-30      | 30/05/2022            | 2021                    | 2021/2022                  |
| 6              |                       |                         |                            |
| 2022-6-1       | 01/06/2022            | 2021                    | 2021/2022                  |
| 2022-6-7       | 07/06/2022            | 2021                    | 2021/2022                  |
| 2022-6-30      | 30/06/2022            | 2021                    | 2021/2022                  |
| 7              |                       |                         |                            |
| 2022-7-1       | 01/07/2022            | 2021                    | 2021/2022                  |
| 2022-7-8       | 08/07/2022            | 2021                    | 2021/2022                  |
| 2022-7-30      | 30/07/2022            | 2021                    | 2021/2022                  |
| 8              |                       |                         |                            |
| 2022-8-1       | 01/08/2022            | 2021                    | 2021/2022                  |
| 2022-8-9       | 09/08/2022            | 2021                    | 2021/2022                  |
| 2022-8-30      | 30/08/2022            | 2022                    | 2022/2023                  |
| 9              |                       |                         |                            |
| 2022-9-1       | 01/09/2022            | 2022                    | 2022/2023                  |
| 2022-9-10      | 10/09/2022            | 2022                    | 2022/2023                  |
| 2022-9-30      | 30/09/2022            | 2022                    | 2022/2023                  |
| 10             |                       |                         |                            |
| 2022-10-1      | 01/10/2022            | 2022                    | 2022/2023                  |
| 2022-10-11     | 11/10/2022            | 2022                    | 2022/2023                  |
| 2022-10-30     | 30/10/2022            | 2022                    | 2022/2023                  |
| 11             |                       |                         |                            |
| 2022-11-1      | 01/11/2022            | 2022                    | 2022/2023                  |
| 2022-11-12     | 12/11/2022            | 2022                    | 2022/2023                  |
| 2022-11-30     | 30/11/2022            | 2022                    | 2022/2023                  |
| 12             |                       |                         |                            |
| 2022-12-1      | 01/12/2022            | 2022                    | 2022/2023                  |
| 2022-12-13     | 13/12/2022            | 2022                    | 2022/2023                  |
| 2022-12-30     | 30/12/2022            | 2022                    | 2022/2023                  |

## Cambiando de version en el git entre Versiones antiguas y la actual

En principio para cambiar el codigo solo tenemes que hacer un git checkout a la rama a la que queremos cambiar pero podemos encontrarnos con estos problemas

Composer:

Si tenemos distitas versiones de Composer puede que nos este cargando el composer.lock y una version del cache de los paquetes de composer

borrar cache de laravel y composer

en las versiones mayores que la 8 basta con 

>php artisan optimize:clear

en anteriores 
>php artisan cache:clear
>php artisan view:clear
>
>

y para el cache del composer:

> composer dump-autoload -o



lo podemos solucionar borrando el lock y con composer install

> rm composer.lock
> composer install

despues de esto tendremos que volver a ejecutar el dump autoload

Voyager 

Si nos aparece un erro al entrar a la admistracion es probable que necesitemos reintalar voyager

php artisan voyager:install --with-dummy

si nos dice que no encuentra un seeder (como en VoyagerDatabaseSeeder despues de ejecutar esto )

tenemos que agregar "namespace Database\Seeders;" a el seeder que no encuentra y volver a ejecutar el composer dump-autoload 

Si con esto no funciona deberias comprobar que estas lineas estan en composer.json:

```json
....
"autoload": {
        "psr-4": {
            "App\\": "app/"
        },
        "classmap": [
            "database/seeds",
            "database/seeders",
            "database/factories",
            "database/migrations"
        ]

```

y nuevamente 

> composer dump-autoload -o
> php artisan voyager:install --with-dummy



Tras esto deberiamos poder volver a ejecutar el comando y funcionar



## Pruebas con diccionarios con vigencia

### Crear diccionario con feca anterior y vigencia hasta este año
### Crear diccionario con feca anterior y vigencia hasta el año anterior

Crear un diccionario cambiando la fecha a un año anterior y poniendole varios años de vigencia hasta el año actual y otro hasta el anterior

* por ejemplo 2018 y con vigencia hasta el actual 2022/23
* por ejemplo 2018 y con vigencia hasta el anterior 2021/22

Actualmente deberian aparecer los dos en el selectro de diccionarios

Volvemos a poner la fecha actual y vamos a administracion -> vigencias

Pulsamos verificar vigencia diccionarios activos, deberia desactivarnos el que terminaba en 2021/22 y no el de de 22/23

comprobar que la diferencia de años esta bien calculada


OK

### Crear una entrada y plubilcarla 

para probar que no afectan entos cambios


### Comprobar entradas diccionario personal

### crear un diccionario que termina dentro de 10 años
para ver si las fechas las pone bien en este caso en administracion

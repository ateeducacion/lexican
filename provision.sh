#!/bin/bash

# Carga la base de datos en la primera ejecucion

echo 'Inicio de provision.sh'
install=$APACHE_DOCUMENT_ROOT$LOCATION

echo 'Iniciar laravel, migrar base de datos'
cd $install

echo "composer install"
composer install
echo ''

echo "php artisan key:generate :"
php artisan key:generate
# echo '--'
echo ''
echo "php artisan migrate :"
php artisan migrate
# echo '--'
echo 'php artisan voyager:install --with-dummy'
php artisan voyager:install --with-dummy

# echo '--'
echo ''
# echo 'Cargar usuarios CAS del CAUCE'
# echo 'php artisan db:seed --class=UsersRolesTablesSeeder'
# --force para obligar a que se ejecute en produccion
echo 'Cargar informacion inicial en bbdd :'
php artisan db:seed --class=DatabaseSeeder --force

# echo "php artisan voyager:install :"
# php artisan voyager:install


# se esta creando el enlace en el Dockerifle y no me lo permite borrar aqui
# borar el enlace simbolico primero si ya exite
# rm ${install}public/storage
# storage link
# echo 'php artisan storage:link'
# php artisan storage:link

# Permisos usuario aplicacion a la aplciacion (lo usa php y nginx)
# chown -R  application.application *
. ./copytonfs.sh

cd $SRVDIR

# php artisan db:seed --class=MasterTablesDataSeederUpdate  -v
# Crear datos prueba stress 2000 usuarios 
echo "php artisan db:seed --class=MasterTablesDataSeederUpdate -v :"
php artisan db:seed --class=MasterTablesDataSeederUpdate -v
echo "php artisan db:seed --class=UsersRolesTablesSeeder -v"
php artisan db:seed --class=UsersRolesTablesSeeder -v
echo 'php artisan db:seed --class=VoyagerCustomization -v'
php artisan db:seed --class=VoyagerCustomization -v
echo 'provisioning sh fin'

# lanza start.sh
# /start.sh

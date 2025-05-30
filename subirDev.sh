#!/bin/bash

echo 'subir por ssh con rsync'
echo 'Asegurate estar conectado a la vpn'

echo 'Introduce clave de omvs0006:'
# -n --> dry run , simula la copia pero no hace nada , quitar la n para ejecutar esto
rsync -vae 'ssh ' --progress \
    --chown=fer:apache \
    --exclude=".*" \
    --exclude="*.md" \
    --exclude="./docs/*" \
    --exclude="./src/storage/debugbar" \
    --exclude="./src/storage/framework" \
    --exclude="./src/storage/logs" \
    --exclude="./src/storage/app/public/dp" \
    --exclude="./src/public/storage" \
    --exclude="./src/config/ctes.php" \
    --exclude="./src/storage/app/public/tinyUploads" \
    --exclude="./src/storage/framework/sessions" \
    ./src/ \
    root@omvs0006.medusa.gobiernodecanarias.net:/var/www/lexican/

# solucion rapida a los problemas de 404 en dev 
    rsync -vae 'ssh ' --progress \
    --chown=apache:apache \
    ./src/public/ \
    root@omvs0006.medusa.gobiernodecanarias.net:/var/www/lexican/public/public

rsync -vae 'ssh ' --progress \
    --chown=apache:apache \
    ./src/public/.htaccess \
    root@omvs0006.medusa.gobiernodecanarias.net:/var/www/lexican/public/.htaccess


echo "Si hay cambios en el archivo ctes.php, copiar a la carpeta src/config manualmente, se excluyen los siguientes directorios:"
echo " "
echo "./src/storage/app/public/dp/*"
echo "./src/storage/debugbar/*" 
echo "./src/storage/framework/*" 
echo "./src/storage/logs/*" 
echo "./src/storage/debugbar/*" 
echo "./src/storage/app/public/dp/*" 
echo "./src/config/ctes.php" 
echo "./src/storage/app/public/tinyUploads/*" 
echo " "


# a veces tengo que poner 
# chown fer.apache -R * 
# para actualizar npm y luego 
# chown apache.apache -R storage ""
echo "hay que cambiar los permisos en dev"
echo " "
echo "   cd /var/www/lexican/"
echo "   chown apache.apache storage/ public/exports/ -R"
echo "   php81 artisan storage:link"
echo " "
echo "   su -- fer "
echo "   yarn production "
echo "   php81 artisan optimize:clear"

#!/bin/bash -e

VERSION=1.2.5
CONTAINERNAME="lexican_web_local"
IMAGENAME="lexican-apache"
CONTAINERDB=database
DBROOTPASS=

# OJO QUE INGNORA LO QUE ESTA EN .ENV SI LO PONEMOS AQUI:
DB_NAME="lexican"
DB_USER="root"
DB_PASS=$DBROOTPASS
APP_ENV=local
DOCKERFILE=Dockerfile.apache81
LOCALHOST=localhost
# LOCALHOST=lexican.local

UPLOAD_MAXSIZE="200M"

# Cargar variables desde .env-deploy
load_env_file() {
    local envfile="./.env_deploy"
    if [ -e "$envfile" ]; then
        set -o allexport
        source "$envfile"
        set +o allexport
    else
        echo "No se ha definido $envfile , crea una copia de env_deploy.example con tus datos"
    fi
}

load_env_file

function check_docker_image_exists() {
  local image_name="$1"
  local image_tag="$2"

  if docker image inspect "$image_name:$image_tag" &>/dev/null; then
    echo "La imagen $image_name:$image_tag ya existe. Seguro que quieres crear una imagen con el mismo numero de version?"
    echo "¿Desea continuar? (y/n)"
    read answer
    if [ "$answer" != "${answer#[Yy]}" ]; then
      echo "Continuando con el proceso..."
    else
      echo "Abortando..."
      ayuda_subir_imagen
      exit 1
    fi
  else
    echo "La imagen $image_name:$image_tag no existe."
  fi
}


if [ "$buildenv" == "pre" ] ; then
    check_docker_image_exists "$IMAGENAME_OC" "$VERSION"
fi




# si se envia sin argumentos muestra la ayuda
if [ $# -eq 0 ]; then
    echo "Para usar este script:"
    echo "Crear una nueva imagen local y un contenedor nuevo:"
    echo "   ./deploy.sh local rebuild"
    echo " "
    echo "Crear una nueva imagen local y un contenedor nuevo y basededatos nueva:"
    echo "   ./deploy.sh local rebuilddb"
    
    echo "Crear una nueva imagen local y CON BBDD PRE:"
    echo "   ./deploy.sh local rebuild dbpre"
    
    echo " "
    echo "Crear la imagen pre"
    echo "   ./deploy.sh pre rebuild"
    echo " "
    echo " "
    echo "Tambien puedes usar la ultima imagen "
    echo "   ./deploy.sh local"
    echo " "    
    echo " Asegurate tener cerrados los servidores de bbdd que escuchen en el puerto de mysql y servidor web que escuche en 8083"
    echo " "
    exit 1
fi

rebuildImage=$2
buildenv=$1
NUEVADB=FALSE
TEMPDB=FALSE

TEMPDB=FALSE
if [ $TEMPDB == TRUE ]; then
    CONTAINERDB=tempdb
    DBROOTPASS="rootpass"
    DB_NAME="laravel"
    DB_USER="root"
    DB_PASS=$DBROOTPASS
    NUEVADB=TRUE
    
    if docker stop $CONTAINERNAME ; then
        if docker rm $CONTAINERNAME ; then
            prinf "se ha parado y borrado el container $CONTAINERNAME"
        fi
    fi
fi

if [ "$rebuildImage" == "rebuilddb" ]; then
    NUEVADB=TRUE
fi

# lo dejo igual en local tambien para que funicone el .htaccess y no tener que modificarlo
# OJO en la imagen esta usando el que esta en Dockerfile.apache...
APACHE_DOCUMENT_ROOT=/var/www/html
LOCATION=/medusa/apps/lexican
DEPLOY_EXTRA_PARAMS=""

# colors
RED=`tput setaf 1`
GREEN=`tput setaf 2`
BLUE=`tput setaf 4`
BOLD=`tput bold`
RESET=`tput sgr0`

# usamos la misma imagen de pre pero con el directorio local de ./src
# para poder trabajar con los cambios actualels sin rehacer la imagen
LOCALPORT=8083
CONTAINERPORT=8083

# esto lo podria borrar, no lo uso nunca
if [ $buildenv == "pretest" ]; then
    LOCALPORT=80
    CONTAINERPORT=80
fi


printf "\n* $BOLD Parando y borrando $CONTAINERNAME \n"
if docker stop $CONTAINERNAME ; then
    printf "... $BOLD Parado $CONTAINERNAME \n"
else
    ret=$?
    printf "\n$RED * Fallo al parar $CONTAINERNAME "
    # echo $ret >&2
    # exit $ret
fi
if docker rm $CONTAINERNAME ; then
    printf "... $BOLD Borrado $CONTAINERNAME \n"
else
    printf "\n$RED * Fallo al borrar $CONTAINERNAME "
fi
printf "$RESET \n"

if [ "$rebuildImage" == "rebuilddb" ]; then
    printf "\n* $BOLD Parando y borrando $CONTAINERDB \n"
    if docker stop $CONTAINERDB ; then
        printf "... $BOLD Parado $CONTAINERDB \n"
    else
        ret=$?
        printf "\n$RED * Fallo al parar $CONTAINERDB "
        # echo $ret >&2
        # exit $ret
    fi
    if docker rm $CONTAINERDB ; then
        printf "... $BOLD Borrado $CONTAINERDB \n"
    else
        printf "\n$RED * Fallo al borrar $CONTAINERDB "
    fi
    printf "$RESET \n"
else
    if docker start $CONTAINERDB ; then
        printf "... $BOLD Iniciando $CONTAINERDB \n"
    else
        ret=$?
        printf "\n$RED * Fallo al inicar $CONTAINERDB "
        printf "Existe el contenedor de la bbdd? "
        printf "Puedes crearlo con rebuilddb"
        echo ""
        echo $ret >&2
        exit $ret
    fi
fi

# Para probar en local la version pre pon en /etc/hosts
# 127.0.0.1	    www3.gobiernodecanarias.org

if [ "$buildenv" == "pre" ] || [ "$buildenv" == "pretest" ] ; then
    echo ' '
    echo 'camdiando .env a pre '
    
    APP_ENV=preproduction
    HOST=www3-pre.gobiernodecanarias.org
    APP_URL=https://$HOST$LOCATION
    tag=pre
    # borrar cache
    rm -Rf ./src/bootstrap/cache/*
    # cambian .env
    cp src/.env src/.env-backup.$(date +%F_%R)
    sed -i 's/APP_ENV=.*$/APP_ENV=preproduction/' src/.env
    sed -i 's,APP_URL=.*$,APP_URL='"$APP_URL"',' src/.env
    # cambiar ctes de pre/pro
    sed -i "s,'docente'   => '3','docente'   => '1'," src/config/ctes.php
    sed -i "s,'alumno'     => '4','alumno'   => '2'," src/config/ctes.php
fi

if [ "$buildenv" == "local" ] ; then
    echo ' '
    echo '*** Cambiando .env a local '
    APP_ENV=local
    HOST=$LOCALHOST
    APP_URL=http://$HOST:$LOCALPORT$LOCATION
    tag=local
    #  para desarrollo en local
    DEPLOY_EXTRA_PARAMS=" -v \"${PWD}/src\":\"/var/www/html$LOCATION\" "
    # cambian .env  / no es necesario ya que se envia como variable de entrono ya
    # echo 'sed1'
    sed -i 's/APP_ENV=.*$/APP_ENV=local/' src/.env
    # echo 'sed2'
    sed -i 's,APP_URL=.*$,APP_URL='"$APP_URL"',' src/.env
    # constantes en local
    sed -i "s,'docente'   => '1','docente'   => '3'," src/config/ctes.php
    sed -i "s,'alumno'   => '2','alumno'     => '4'," src/config/ctes.php
fi

if [ "$rebuildImage" == "rebuild" ] || [ "$rebuildImage" == "rebuilddb" ]; then

    # Crear un nuevo contenedor para la bbdd , estara vacia y sera necesario ejecutar los seeders
    if [ $NUEVADB == TRUE ]; then
        printf "$RED * $BOLD NUEVA BD \n"
        printf "$GREEN * $BOLD Iniciar contenedor $CONTAINERDB \n"
        printf "$RESET \n"
        docker run --name $CONTAINERDB \
        -p 3306:3306  \
        -e MYSQL_ROOT_PASSWORD=$DBROOTPASS \
        -e MYSQL_DATABASE=$DB_NAME \
        -e MYSQL_USER=$DB_USER \
        -e MYSQL_PASSWORD=$DB_PASS \
        -d mariadb:10.6
    else
        printf "$RED * $BOLD  BD $CONTAINERDB \n"
        docker start $CONTAINERDB
    fi
    
    
    if [ "$buildenv" != "local" ]; then
        printf "$GREEN * $BOLD Laravel mix: Minimizar y actualizar js y css $RESET\n"
        cd src
        npm run production
        # alternativa a npm 
        # bun run production
        # yarn production 
        cd ..
    fi
    
    printf "$GREEN * $BOLD Crear imagen $IMAGENAME $RESET\n"
    
    if docker build -f $DOCKERFILE -t $IMAGENAME --build-arg APP_VERSION=$VERSION .; then
        docker tag $IMAGENAME $IMAGENAME:$tag
        printf "\n $GREEN * $BOLD imagen $IMAGENAME creada \n $RESET"
    else
        ret=$?
        printf "\n$RED * Fallo al crear la imagen $BOLD  $IMAGENAME $RESET"
        echo $ret >&2
        exit $ret
    fi
fi

printf "\n # Crear e iniciar contenedores # \n"

# arracan la imagen existente de basedatos
printf "$GREEN * $BOLD Arracan la imagen existente de $CONTAINERDB $RESET\n"
docker start $CONTAINERDB

printf "$GREEN * $BOLD Iniciar contenedor $CONTAINERNAME \n"
printf "$RESET \n"

# Guardo el comando en una variable para poder imprimir el comando resultante
finalcommand="docker run -d \
-p $LOCALPORT:$CONTAINERPORT "

# if [ $3 != "dbpre" ]; then
finalcommand="${finalcommand} --link $CONTAINERDB:$CONTAINERDB -e CONTAINERDB=$CONTAINERDB "
DB_HOST=$CONTAINERDB

# fi

# printf "hasta hahora ${finalcommand} \n\n"

finalcommand="${finalcommand} \
-e LOCATION=${LOCATION} \
-e APP_ENV=\"${APP_ENV}\" \
-e APP_URL=\"${APP_URL}\" \
-e APP_VERSION=\"${VERSION}\" \
-e DBROOTPASS=$DBROOTPASS \
-e DB_HOST=$DB_HOST \
-e DB_DATABASE=$DB_NAME \
-e DB_USERNAME=$DB_USER \
-e DB_PASSWORD=$DB_PASS \
-e UPLOAD_MAXSIZE=$UPLOAD_MAXSIZE \
$DEPLOY_EXTRA_PARAMS \
--name=\"$CONTAINERNAME\" \
$IMAGENAME:$tag "

printf "\n --- ------ ------ --- \n"
#ejecuta el comando
(set -x;eval $finalcommand)
printf "\n --- ------ ------ --- \n"

printf "\n $GREEN$BOLD Acede a http://$HOST:${LOCALPORT}${LOCATION} para probar la web";
printf "\n Para acceder a la shell del contenedor:"
printf "\n $BLUE docker exec -ti ${CONTAINERNAME} /bin/bash"

if [ $NUEVADB == TRUE ]; then
echo ""
echo "-------------------------------------------------------------------"
    echo "recuerda ejecutar :"
    printf $RESET
    echo "\$  docker exec -ti ${CONTAINERNAME} /bin/bash "
    echo "y una vez dentro de la terminal del container: :"
    echo "\$ sh $APACHE_DOCUMENT_ROOT$LOCATION/provision.sh "
    printf $BLUE
    echo "para configurar la base de datos y realizar las migraciones"
    echo "-------------------------------------------------------------------"
fi

# printf "\n $BLUE docker exec -ti ${CONTAINERNAME} /var/www/php artisan cache:clear"

printf "\n $RESET \n Contenedores activos: \n----------------------------------------------------------------------------\n"

docker ps
echo ""
printf "\n* $BOLD Para crear la imagen $RESET \n"
echo "   docker tag $IMAGENAME:$tag www.canariaseducacion.org/lexican/lexican:$VERSION"
printf "\n* $BOLD Para enviarla $RESET \n"
echo "    docker push www.canariaseducacion.org/lexican/lexican:$VERSION"
printf "\n* $BOLD en ssh root@nodo.master.os $RESET \n"
echo "     oc project intercambiador"
echo "     oc import-image lexican:$VERSION --from=www.canariaseducacion.org/lexican/lexican:$VERSION"


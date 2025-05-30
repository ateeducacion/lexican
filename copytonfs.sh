#!/bin/bash


echo "Crear estructura directorios volumen archivos"
DIR_PUBLIC_STORAGE=${APACHE_DOCUMENT_ROOT}${LOCATION}/storage/app/public
cd $DIR_PUBLIC_STORAGE
mkdir  documentos tinyUploads users
mkdir -p dp/medios/audios dp/medios/imagenes dp/medios/videos 
mkdir -p avatares/oficiales

echo "Copiar avatares iniciales y otras imagenes a carpeta storage"


cp -R ${APACHE_DOCUMENT_ROOT}${LOCATION}/archivos/* $DIR_PUBLIC_STORAGE
# echo "Permisos archivos y directorios carpeta storage"
find ${APACHE_DOCUMENT_ROOT}${LOCATION}/archivos $DIR_PUBLIC_STORAGE -type f -exec chmod -R 0666 {} 2> /dev/null \;
find ${APACHE_DOCUMENT_ROOT}${LOCATION}/archivos $DIR_PUBLIC_STORAGE -type d -exec chmod -R 0777 {} 2> /dev/null \;
echo "fin copytonfs.sh"
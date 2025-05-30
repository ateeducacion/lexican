# Crear Vistas 

Hay varios archivo para crear vistas en la BBDD , en principio bastaria con ejecutar vistas.sql, pero nos hemos encontrado problemas y actualmente **es necesario crear las vistas los archivos vistas_10-1-40.sql y vistasPorCurso.sql** vistas sql se mantiene aqui para versiones futuras donde no es necesario crear las vistas auxiliares 


## vistas.sql 

Contiene una version de las siguientes vistas, el problema es que estas vistas dan errores en la base de datos mariadb 10.1.40 que es la que esta ahora mismo en pre 
  
  * diccionariosDatosCursoView
  * diccionariosDatosView
  * ensenanzasNivelAreaMateriaView
  * diccionarios21View
  * ensenanzasNivelAreaMateria21View
  
## vistas_10-1-40.sql

Contiene las mismas vistas adaptadas y unas vistas auxiliares para evitar los problemas que existian con la version 10.1.40, las vistas 'v_temp' son necesarias  para las siguientes vistas, no se pueden borrar tras crearlas.

* v_temp1
* diccionariosDatosCursoView
* v_temp2
* diccionariosDatosView
* diccionarios21View
* ensenanzasNivelAreaMateriaView
* ensenanzasNivelAreaMateria21View

## vistasPorCurso.sql

diccionariosDatosCursoView se crea correctamente pero en PRE vimos que no se podia ejecutar desde phpmyadmin, asi que se crearon estas vista para poder acceder a los datos que componen las vista aun que sea de manera independiente 

* v_diccionariosAula_curso
* v_diccionariosPersonales_curso
* v_centros_curso
* v_usuariosRolAula_curso
const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.copy('node_modules/tinymce/skins', 'public/js/skins');
mix.copy('resources/sass/fontawesome.5.6.3.all.css','public/css/fontawesome.css');
mix.copy('node_modules/bootstrap-table/dist/bootstrap-table.min.css', 'public/css/bootstrap-table.min.css');
mix.copy('node_modules/bootstrap/dist/css/bootstrap.min.css', 'public/css/bootstrap.min.css');
mix.copy('node_modules/bootstrap/dist/js/bootstrap.min.js', 'public/js/bootstrap.min.js');

// para agregar a admin/vigencia 
mix.copy('node_modules/bootstrap-table/dist/bootstrap-table.min.js', 'public/js/bootstrap-table.min.js');
mix.copy('node_modules/bootstrap-table/dist/bootstrap-table-locale-all.min.js', 'public/js/bootstrap-table-locale-all.min.js');
mix.copy('resources/js/langs', 'public/js/langs');

// para agregar vue o react ahora es asi:
// mix.js('resources/js/app.js', 'public/js').vue();
// mix.js('resources/js/app.js', 'public/js').react();

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css', {
        // prependData?
        prependData: '$envbaseurl:\'' + process.env.LOCATION + '/\';' 
    });

const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

 mix.js('resources/js/app.js', 'public/js')
 .styles([
     'resources/theme/css/bootstrap.min.css',
     'resources/theme/css/owl.carousel.min.css',
     'resources/theme/css/owl.theme.default.min.css',
     'resources/theme/css/style.css',
     'resources/theme/css/responsive.css',
     // Add more CSS files as needed
 ], 'public/css/theme.css')
 .scripts([
     'resources/theme/js/modernizr-2.8.3.min.js',
     'resources/theme/js/jquery-3.6.0.min.js',
     'resources/theme/js/bootstrap.min.js',
     'resources/theme/js/popper.min.js',
     'resources/theme/js/fontawesome.min.js',
     'resources/theme/js/owl.carousel.min.js',
     'resources/theme/js/custom.js',

 ], 'public/js/theme.js');

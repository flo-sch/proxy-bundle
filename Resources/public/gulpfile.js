// Gulp and utils
var path            = require('path'),
    pump            = require('pump'),
    gulp            = require('gulp'),
    gutil           = require('gulp-util'),
    debug           = require('gulp-debug'),
    concat          = require('gulp-concat'),
    rename          = require('gulp-rename'),
    notify          = require('gulp-notify'),
    // Styles [sass, css]
    sass            = require('gulp-sass'),
    cssnano         = require('gulp-cssnano'),
    autoprefixer    = require('gulp-autoprefixer'),
    // JS [js]
    uglify          = require('gulp-uglify'),
    paths           = {
        vendor: 'node_modules',
        fonts: 'fonts',
        images: 'images',
        sass: 'sass',
        css: 'css',
        js: 'js'
    };

/**
 * Styles
 *
 * Compile and optimise sass files to CSS stylesheets
 */
gulp.task('styles', function (callback) {
    pump([
        gulp.src(paths.sass + '/**/*.scss'),
        sass({
            outputStyle: 'compressed'
        }),
        autoprefixer({
            browsers: [
                'last 2 versions',
                '> 1%'
            ]
        }),
        cssnano({
            'zindex': false,
            'postcss-merge-idents': false,
            'postcss-reduce-idents': false
        }),
        rename({
            suffix: '.min'
        }),
        notify({
            title: '[FloschProxyBundle] Styles',
            subtitle: 'Stylesheets successfully compiled',
            sound: 'Pop',
            icon: path.join(__dirname, './images/logo.png'),
            onLast: false,
            wait: true,
            message: "Compiled <%= file.relative %> @ <%= options.date %>",
            templateOptions: {
                date: new Date()
            }
        }),
        gulp.dest(paths.css)
    ], callback);
});

/**
 * Scripts
 *
 * Compile, concatenate and optimise js files
 */
gulp.task('scripts', ['concatenate-scripts'], function (callback) {
    pump([
        gulp.src([
            paths.js + '/**/*.js',
            '!' + paths.js + '/**/*.min.js'
        ]),
        debug(),
        uglify(),
        rename({
            suffix: '.min'
        }),
        notify({
            title: '[FloschProxyBundle] Scripts',
            subtitle: 'JavaScripts successfully compiled',
            sound: 'Pop',
            icon: path.join(__dirname, './images/logo.png'),
            onLast: false,
            wait: true,
            message: "Compiled <%= file.relative %> @ <%= options.date %>",
            templateOptions: {
                date: new Date()
            }
        }),
        gulp.dest(paths.js)
    ], callback);
});

gulp.task('concatenate-scripts', function (callback) {
    pump([
        gulp.src([
            paths.vendor + '/jquery/dist/jquery.js',
            paths.vendor + '/tether/dist/js/tether.js',
            paths.vendor + '/bootstrap/dist/js/bootstrap.js',
            // paths.vendor + '/bootstrap-material-design/dist/js/material.js',
            // paths.vendor + '/bootstrap-material-design/dist/js/ripples.js'
        ]),
        debug(),
        concat('vendor.js'),
        notify({
            title: '[FloschProxyBundle] Concatenate Scripts',
            subtitle: 'JavaScripts successfully concatenated',
            sound: 'Pop',
            icon: path.join(__dirname, './images/logo.png'),
            onLast: false,
            wait: true,
            message: "Compiled <%= file.relative %> @ <%= options.date %>",
            templateOptions: {
                date: new Date()
            }
        }),
        gulp.dest(paths.js)
    ], callback);
})

// Watch
gulp.task('watch', function () {
    gulp.watch(paths.sass + '/**/*.scss', ['styles']);
    gulp.watch(paths.js + '/**/*.js', ['scripts']);
});
// Assets
gulp.task('assets', ['styles', 'scripts']);

// Dev
gulp.task('build', ['assets']);

// Default]
gulp.task('default', ['build', 'watch']);

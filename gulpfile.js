// базовый набор
var gulp = require('gulp'),
    sass = require('gulp-ruby-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    minifycss = require('gulp-minify-css'),
    jshint = require('gulp-jshint'),
    uglify = require('gulp-uglify'),
    imagemin = require('gulp-imagemin'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    cache = require('gulp-cache'),
    livereload = require('gulp-livereload'),
    del = require('del');

// общий конфиг
var conf = {
    browsersTarget: '> 5%',
    imagemin: {
        optimizationLevel: 5,
        progressive: true,
        interlaced: true
    },
    paths: {
        src: 'app/static/',
        dest: 'web/static/',
        materialize: 'lib/materialize-v0.97.0/',
        jquery: 'lib/jquery-2.1.4/',
        html: 'web/**/*.html'
    }
};

// компилируем sass
gulp.task('styles', function () {
    return sass(conf.paths.src + 'scss/main.scss', {
        style: 'expanded',
        container: 'gulp-ruby-sass-styles'
    })
        .pipe(autoprefixer(conf.browsersTarget))
        .pipe(gulp.dest(conf.paths.dest + 'css'))
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss())
        .pipe(gulp.dest(conf.paths.dest + 'css'));
});

// компилируем js
gulp.task('scripts', function () {
    return gulp.src([
        conf.paths.src + 'js/some/**/*.js',
        conf.paths.src + 'js/other.js',
        conf.paths.src + 'js/main.js'
    ])
        .pipe(jshint())
        .pipe(jshint.reporter('default'))
        .pipe(concat('main.js'))
        .pipe(gulp.dest(conf.paths.dest + 'js'))
        .pipe(rename({suffix: '.min'}))
        .pipe(uglify())
        .pipe(gulp.dest(conf.paths.dest + 'js'));
});

// минифицируем используемые изображения
gulp.task('images', function () {
    return gulp.src(conf.paths.src + 'img/**/*')
        .pipe(cache(imagemin(conf.imagemin)))
        .pipe(gulp.dest(conf.paths.dest + 'img'));
});

// компилируем Materialize sass
gulp.task('lib-materialize-css', function () {
    return sass(conf.paths.src + conf.paths.materialize + 'sass/materialize.scss', {
        style: 'expanded',
        container: 'gulp-ruby-sass-materialize'
    })
        .pipe(autoprefixer(conf.browsersTarget))
        .pipe(gulp.dest(conf.paths.dest + conf.paths.materialize + 'css'))
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss())
        .pipe(gulp.dest(conf.paths.dest + conf.paths.materialize + 'css'));
});

// компилируем (копируем) Materialize js
gulp.task('lib-materialize-js', function () {
    return gulp.src([
        conf.paths.src + conf.paths.materialize + 'js/bin/*.js'
    ])
        .pipe(gulp.dest(conf.paths.dest + conf.paths.materialize + 'js'));
});

// копируем прочие Materialize assets
gulp.task('lib-materialize-assets', function () {
    return gulp.src([
        conf.paths.src + conf.paths.materialize + 'font/**/*'
    ])
        .pipe(gulp.dest(conf.paths.dest + conf.paths.materialize + 'font'));
});

// целиком библиотека Materialize
gulp.task('lib-materialize', [
    'lib-materialize-css',
    'lib-materialize-js',
    'lib-materialize-assets'
]);

// библиотека jQuery
gulp.task('lib-jquery', function () {
    return gulp.src([
        conf.paths.src + conf.paths.jquery + '**/*'
    ])
        .pipe(gulp.dest(conf.paths.dest + conf.paths.jquery));
});

// все библиотеки
gulp.task('libs', [
    'lib-materialize',
    'lib-jquery'
]);

// очистка ассетов
gulp.task('clean', function (cb) {
    del([
        conf.paths.dest + 'css',
        conf.paths.dest + 'js',
        conf.paths.dest + 'img',
        conf.paths.dest + 'lib'
    ], cb)
});

// сборка
gulp.task('build', ['libs', 'styles', 'scripts', 'images']);

// полная пересборка
gulp.task('rebuild', ['clean'], function () {
    gulp.start('build');
});

// задача по умолчанию
gulp.task('default', ['build']);

// вотчер
gulp.task('watch', function () {
    // смотреть за библиотеками
    gulp.watch(conf.paths.src + 'lib/**/*', ['libs']);
    // смотреть за стилями
    gulp.watch(conf.paths.src + 'scss/**/*.scss', ['styles']);
    // смотреть за скриптами
    gulp.watch(conf.paths.src + 'js/**/*.js', ['scripts']);
    // смотреть за изображениями
    gulp.watch(conf.paths.src + 'img/**/*', ['images']);

    // инстанс сервера LiveReload
    livereload.listen();
    // как только меняется что-то — сказать браузеру перегрузить страничку
    gulp.watch([conf.paths.dest + '**/*', conf.paths.html]).on('change', livereload.changed);
});

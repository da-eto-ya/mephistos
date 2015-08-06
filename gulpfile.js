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

// компилируем sass
gulp.task('styles', function () {
    return sass('app/static/scss/main.scss', {style: 'expanded'})
        .pipe(autoprefixer('> 5%'))
        .pipe(gulp.dest('web/static/css'))
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss())
        .pipe(gulp.dest('web/static/css'));
});

// компилируем js
gulp.task('scripts', function () {
    return gulp.src([
        'app/static/js/some/**/*.js',
        'app/static/js/other.js',
        'app/static/js/main.js'
    ])
        .pipe(jshint())
        .pipe(jshint.reporter('default'))
        .pipe(concat('main.js'))
        .pipe(gulp.dest('web/static/js'))
        .pipe(rename({suffix: '.min'}))
        .pipe(uglify())
        .pipe(gulp.dest('web/static/js'));
});

// минифицируем используемые изображения
gulp.task('images', function () {
    return gulp.src('app/static/img/**/*')
        .pipe(cache(imagemin({optimizationLevel: 5, progressive: true, interlaced: true})))
        .pipe(gulp.dest('web/static/img'));
});

// очистка ассетов
gulp.task('clean', function (cb) {
    del(['web/static/css', 'web/static/js', 'web/static/img'], cb)
});

// сборка
gulp.task('build', ['styles', 'scripts', 'images']);

// полная пересборка
gulp.task('rebuild', ['clean'], function () {
    gulp.start('build');
});

// задача по умолчанию
gulp.task('default', ['build']);

// вотчер
gulp.task('watch', function () {
    // смотреть за стилями
    gulp.watch('app/static/scss/**/*.scss', ['styles']);
    // смотреть за скриптами
    gulp.watch('app/static/js/**/*.js', ['scripts']);
    // смотреть за изображениями
    gulp.watch('app/static/img/**/*', ['images']);

    // инстанс сервера LiveReload
    livereload.listen();
    // как только меняется что-то — сказать браузеру перегрузить страничку
    gulp.watch(['web/static/**/*', 'web/*.html']).on('change', livereload.changed);
});

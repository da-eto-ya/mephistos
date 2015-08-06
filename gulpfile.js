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
    notify = require('gulp-notify'),
    cache = require('gulp-cache'),
    livereload = require('gulp-livereload'),
    del = require('del');

// компилируем sass
gulp.task('styles', function () {
    return sass('app/static/sass/main.scss', {style: 'expanded'})
        .pipe(autoprefixer('> 5%'))
        .pipe(gulp.dest('web/static/css'))
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss())
        .pipe(gulp.dest('web/static/css'))
        .pipe(notify({message: 'Styles task complete'}));
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
        .pipe(gulp.dest('web/static/js'))
        .pipe(notify({message: 'Scripts task complete'}));
});

// минифицируем используемые изображения
gulp.task('images', function () {
    return gulp.src('app/static/img/**/*')
        .pipe(cache(imagemin({optimizationLevel: 5, progressive: true, interlaced: true})))
        .pipe(gulp.dest('web/static/img'))
        .pipe(notify({message: 'Images task complete'}));
});

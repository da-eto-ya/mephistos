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
gulp.task('styles', function() {
    return sass('app/sass/main.scss', { style: 'expanded' })
        .pipe(autoprefixer('> 5%'))
        .pipe(gulp.dest('web/css'))
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss())
        .pipe(gulp.dest('web/css'))
        .pipe(notify({ message: 'Styles task complete' }));
});

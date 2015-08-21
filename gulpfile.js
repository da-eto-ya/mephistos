// базовый набор
var gulp = require('gulp'),
    sass = require('gulp-ruby-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    minifycss = require('gulp-minify-css'),
    jshint = require('gulp-jshint'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    cache = require('gulp-cache'),
    livereload = require('gulp-livereload'),
    del = require('del');

// общий конфиг
var conf = {
    browsersTarget: '> 5%',
    paths: {
        src: 'app/static/',
        dest: 'web/static/',
        materialize: 'node_modules/materialize-css/',
        handlebars: 'node_modules/handlebars/',
        form: 'node_modules/jquery-form/',
        validation: 'node_modules/jquery-validation/',
        moment: 'node_modules/moment/',
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
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss())
        .pipe(gulp.dest(conf.paths.dest + 'css'));
});

// компилируем js
gulp.task('scripts', function () {
    return gulp.src([
        conf.paths.src + 'js/*.js'
    ])
        .pipe(jshint())
        .pipe(jshint.reporter('default'))
        .pipe(concat('main.js'))
        .pipe(rename({suffix: '.min'}))
        .pipe(uglify().on('error', function (e) {
            console.log(e.message);
            return this.end();
        }))
        .pipe(gulp.dest(conf.paths.dest + 'js'));
});

// компилируем js-библиотеки
gulp.task('libs', function () {
    return gulp.src([
        conf.paths.materialize + "js/jquery.easing.1.3.js",
        conf.paths.materialize + "js/animation.js",
        conf.paths.materialize + "js/velocity.min.js",
        conf.paths.materialize + "js/hammer.min.js",
        conf.paths.materialize + "js/jquery.hammer.js",
        conf.paths.materialize + "js/global.js",
        //conf.paths.materialize + "js/collapsible.js",
        //conf.paths.materialize + "js/dropdown.js",
        //conf.paths.materialize + "js/leanModal.js",
        //conf.paths.materialize + "js/materialbox.js",
        //conf.paths.materialize + "js/parallax.js",
        //conf.paths.materialize + "js/tabs.js",
        //conf.paths.materialize + "js/tooltip.js",
        conf.paths.materialize + "js/waves.js",
        conf.paths.materialize + "js/toasts.js",
        conf.paths.materialize + "js/sideNav.js",
        //conf.paths.materialize + "js/scrollspy.js",
        //conf.paths.materialize + "js/slider.js",
        //conf.paths.materialize + "js/cards.js",
        //conf.paths.materialize + "js/pushpin.js",
        //conf.paths.materialize + "js/buttons.js",
        //conf.paths.materialize + "js/transitions.js",
        //conf.paths.materialize + "js/scrollFire.js",
        //conf.paths.materialize + "js/date_picker/picker.js",
        //conf.paths.materialize + "js/date_picker/picker.date.js",
        //conf.paths.materialize + "js/character_counter.js",
        conf.paths.materialize + "js/forms.js",

        conf.paths.handlebars + 'dist/handlebars.js',
        conf.paths.moment + 'moment.js',
        conf.paths.moment + 'locale/ru.js',
        conf.paths.form + 'jquery.form.js',
        conf.paths.validation + 'dist/jquery.validate.js',
        conf.paths.validation + 'dist/localization/messages_ru.js'
    ])
        .pipe(concat('lib.js'))
        .pipe(rename({suffix: '.min'}))
        .pipe(uglify().on('error', function (e) {
            console.log(e.message);
            return this.end();
        }))
        .pipe(gulp.dest(conf.paths.dest + 'js'));
});

// копируем Materialize fonts
gulp.task('fonts', function () {
    return gulp.src([
        conf.paths.materialize + 'font/**/*'
    ])
        .pipe(gulp.dest(conf.paths.dest + 'font'));
});

// очистка ассетов
gulp.task('clean', function (cb) {
    del([
        conf.paths.dest + 'font',
        conf.paths.dest + 'css',
        conf.paths.dest + 'js'
    ], cb)
});

// сборка
gulp.task('build', ['fonts', 'styles', 'libs', 'scripts']);

// полная пересборка
gulp.task('rebuild', ['clean'], function () {
    gulp.start('build');
});

// задача по умолчанию
gulp.task('default', ['watch']);

// вотчер
gulp.task('watch', function () {
    // смотреть за стилями
    gulp.watch(conf.paths.src + 'scss/**/*.scss', ['styles']);
    // смотреть за скриптами
    gulp.watch(conf.paths.src + 'js/**/*.js', ['scripts']);

    // инстанс сервера LiveReload
    livereload.listen();
    // как только меняется что-то — сказать браузеру перегрузить страничку
    gulp.watch([conf.paths.dest + '**/*', conf.paths.html]).on('change', livereload.changed);
});

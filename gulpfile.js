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
        materialize: {
            src: 'node_modules/materialize-css/',
            dest: 'web/static/lib/materialize-v0.97.0/'
        },
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

// компилируем Materialize sass
gulp.task('lib-materialize-css', function () {
    return sass(conf.paths.materialize.src + 'sass/materialize.scss', {
        style: 'expanded',
        container: 'gulp-ruby-sass-materialize'
    })
        .pipe(autoprefixer(conf.browsersTarget))
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss())
        .pipe(gulp.dest(conf.paths.materialize.dest + 'css'));
});

// компилируем (копируем) Materialize js
// TODO: удалить неиспользуемые модули из сборки
gulp.task('lib-materialize-js', function () {
    return gulp.src([
        conf.paths.materialize.src + "js/jquery.easing.1.3.js",
        conf.paths.materialize.src + "js/animation.js",
        conf.paths.materialize.src + "js/velocity.min.js",
        conf.paths.materialize.src + "js/hammer.min.js",
        conf.paths.materialize.src + "js/jquery.hammer.js",
        conf.paths.materialize.src + "js/global.js",
        //conf.paths.materialize.src + "js/collapsible.js",
        //conf.paths.materialize.src + "js/dropdown.js",
        //conf.paths.materialize.src + "js/leanModal.js",
        //conf.paths.materialize.src + "js/materialbox.js",
        //conf.paths.materialize.src + "js/parallax.js",
        //conf.paths.materialize.src + "js/tabs.js",
        //conf.paths.materialize.src + "js/tooltip.js",
        conf.paths.materialize.src + "js/waves.js",
        conf.paths.materialize.src + "js/toasts.js",
        conf.paths.materialize.src + "js/sideNav.js",
        //conf.paths.materialize.src + "js/scrollspy.js",
        //conf.paths.materialize.src + "js/slider.js",
        //conf.paths.materialize.src + "js/cards.js",
        //conf.paths.materialize.src + "js/pushpin.js",
        //conf.paths.materialize.src + "js/buttons.js",
        //conf.paths.materialize.src + "js/transitions.js",
        //conf.paths.materialize.src + "js/scrollFire.js",
        //conf.paths.materialize.src + "js/date_picker/picker.js",
        //conf.paths.materialize.src + "js/date_picker/picker.date.js",
        //conf.paths.materialize.src + "js/character_counter.js",
        conf.paths.materialize.src + "js/forms.js"
    ])
        .pipe(concat('materialize.js'))
        .pipe(rename({suffix: '.min'}))
        .pipe(uglify().on('error', function (e) {
            console.log(e.message);
            return this.end();
        }))
        .pipe(gulp.dest(conf.paths.materialize.dest + 'js'));
});

// копируем прочие Materialize assets
gulp.task('lib-materialize-assets', function () {
    return gulp.src([
        conf.paths.materialize.src + 'font/**/*'
    ])
        .pipe(gulp.dest(conf.paths.materialize.dest + 'font'));
});

// целиком библиотека Materialize
gulp.task('lib-materialize', [
    'lib-materialize-css',
    'lib-materialize-js',
    'lib-materialize-assets'
]);

// все библиотеки
gulp.task('libs', [
    'lib-materialize'
]);

// очистка ассетов
gulp.task('clean', function (cb) {
    del([
        conf.paths.dest + 'css',
        conf.paths.dest + 'js',
        conf.paths.dest + 'lib'
    ], cb)
});

// сборка
gulp.task('build', ['libs', 'styles', 'scripts']);

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

    // инстанс сервера LiveReload
    livereload.listen();
    // как только меняется что-то — сказать браузеру перегрузить страничку
    gulp.watch([conf.paths.dest + '**/*', conf.paths.html]).on('change', livereload.changed);
});

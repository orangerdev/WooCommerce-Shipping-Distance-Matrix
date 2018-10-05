var gulp = require('gulp');
var rename = require('gulp-rename');
var uglify = require('gulp-uglify');
var iife = require('gulp-iife');
var sass = require('gulp-sass');
var cleanCSS = require('gulp-clean-css');
var autoprefixer = require('gulp-autoprefixer');
var plumber = require('gulp-plumber');
var notify = require('gulp-notify');
var gulpPhpCS = require('gulp-phpcs');
var wpPot = require('gulp-wp-pot');
var browserSync = require('browser-sync').create();
var argv = require('yargs').argv;
var zip = require('gulp-zip');

var scriptsSrc = ['assets/src/js/*.js'];
var scriptsDest = 'assets/js';

var minifyScriptsSrc = ['assets/js/*.js', '!assets/js/*.min.js'];
var minifyScriptsDest = 'assets/js';

var sassSrc = ['assets/src/scss/*.scss'];
var sassDest = 'assets/css';

var minifyCssSrc = ['assets/css/*.css', '!assets/css/*.min.css'];
var minifyCssDest = 'assets/css';

var phpcsSrc = ['*.php', '**/*.php', '!vendor/*', '!node_modules/*', '!index.php', '!**/index.php'];

// Custom error handler
var errorHandler = function () {
    return plumber(function (err) {
        notify.onError({
            title: 'Gulp error in ' + err.plugin,
            message: err.toString()
        })(err);
    });
};

// Scripts
gulp.task('scripts', function () {
    return gulp.src(scriptsSrc)
        .pipe(errorHandler())
        .pipe(iife({
            useStrict: true,
            trimCode: true,
            prependSemicolon: true,
            params: ['$'],
            args: ['jQuery']
        }))
        .pipe(gulp.dest(scriptsDest));
});

// Minify scripts
gulp.task('minify-scripts', function () {
    return gulp.src(minifyScriptsSrc)
        .pipe(errorHandler())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(uglify())
        .pipe(gulp.dest(minifyScriptsDest));
});

// SASS
gulp.task('sass', function () {
    return gulp.src(sassSrc)
        .pipe(errorHandler())
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer(
            'last 2 version',
            '> 1%',
            'safari 5',
            'ie 8',
            'ie 9',
            'opera 12.1',
            'ios 6',
            'android 4'))
        .pipe(gulp.dest(sassDest))
        .pipe(browserSync.stream());
});

// Minify CSS
gulp.task('minify-css', function () {
    return gulp.src(minifyCssSrc)
        .pipe(errorHandler())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(cleanCSS({ compatibility: 'ie8' }))
        .pipe(gulp.dest(minifyCssDest));
});

gulp.task('phpcs', function () {
    return gulp.src(phpcsSrc)
        .pipe(errorHandler())
        .pipe(gulpPhpCS({
            bin: '/usr/local/bin/phpcs',
            standard: 'WordPress',
            warningSeverity: 0
        }))
        // Log all problems that was found
        .pipe(gulpPhpCS.reporter('log'));
});

gulp.task('i18n', function () {
    return gulp.src(phpcsSrc)
        .pipe(wpPot({
            'domain': 'wcsdm',
            'package': 'WooCommerce-Shipping-Distance-Matrix'
        }))
        .pipe(gulp.dest('languages/wcsdm.pot'));
});

// Dev task with watch
gulp.task('default', ['sass', 'scripts', 'phpcs'], function () {
    browserSync.init({
        proxy: argv.proxy
    });
    gulp.watch([sassSrc], ['sass']);
    gulp.watch([scriptsSrc], ['scripts']).on('change', browserSync.reload);
    gulp.watch([phpcsSrc], ['phpcs']).on('change', browserSync.reload);
});

// Build task
gulp.task('build', ['sass', 'minify-css', 'scripts', 'minify-scripts', 'i18n']);

// Export files task
gulp.task('export', ['build'], function () {
    gulp.src(['./**', '!dist/', '!dist/**', '!node_modules/', '!node_modules/**', '!assets/src/', '!assets/src/**', '!gulpfile.js', '!package-lock.json', '!package.json'])
        .pipe(zip('wcsdm.zip'))
        .pipe(gulp.dest('dist'));
});
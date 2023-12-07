const gulp = require('gulp');
const concat = require('gulp-concat');

gulp.task('css:prod', () => {
    const postcss = require('gulp-postcss')
    const cleanCSS = require('gulp-clean-css');
    const cssimport = require("gulp-cssimport");
    const sourcemaps = require('gulp-sourcemaps')

    const sass = require('gulp-sass')(require('sass'));

    const files = [
        './node_modules/jquery-modal/jquery.modal.css',
        './css/Companies.scss',
        './css/Legacy.scss',
        './css/Login.scss',
        './css/Logs.scss',
        './css/Settings.scss',
        './css/Utilities.scss',
    ];

    return gulp.src(files)
        .pipe(sourcemaps.init())
        .pipe(sass({includePaths: ['node_modules']}))
        .pipe(cssimport())
        .pipe(postcss([
            require('autoprefixer'),
            require('postcss-combine-media-query')
        ]))
        .pipe(cleanCSS({level: {1: {specialComments: 0}}}))
        .pipe(concat("moloni.min.css"))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('../assets/css/'));
});

gulp.task('js:prod', () => {
    const babel = require("gulp-babel");
    const plumber = require("gulp-plumber");
    const uglify = require('gulp-uglify');

    const files = [
        './node_modules/jquery-modal/jquery.modal.js',
        './js/Logs.js',
        './js/OrdersBulkAction.js',
        './js/Settings.js',
    ];

    return (
        gulp.src(files)
            .pipe(plumber())
            .pipe(babel({
                presets: [
                    ["@babel/env", {modules: false}],
                ]
            }))
            .pipe(uglify())
            .pipe(concat("moloni.min.js"))
            .pipe(gulp.dest("../assets/js/"))
    )
});

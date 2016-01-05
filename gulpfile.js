var gulp = require('gulp');
var paths = {
    css: './dev/*.css'
}

gulp.task('default', ['css']);

gulp.task('watch', function(){
    gulp.watch(paths.css, ['css']);
});

gulp.task('css', function () {
    var postcss = require('gulp-postcss');
    var sourcemaps = require('gulp-sourcemaps');
    var autoprefixer = require('autoprefixer');
    var customProperties = require("postcss-custom-properties");
    var colorFunction = require("postcss-color-function");
    var selector = require('postcss-custom-selectors');
    var nested = require('postcss-nested');

    var processors = [
        nested,
        autoprefixer({browsers: ['last 2 version']}),
        customProperties(),
        colorFunction(),
        selector()
    ];

    return gulp.src(paths.css)
        .pipe(sourcemaps.init())
        .pipe(postcss(processors))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./'));
});
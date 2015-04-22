var gulp = require('gulp');
var paths = {
    css: './dev/*.css'
}

gulp.task('default', ['css']);

gulp.task('watch', function(){
    gulp.watch(paths.css, ['css']);
});

gulp.task('less', function () {
    var less = require('gulp-less');
    var path = require('path');

    return gulp.src('./dev/*.less')
        .pipe(less({
            paths: [ path.join(__dirname, 'less', 'includes') ]
        }))
        .pipe(gulp.dest('./'));
});

gulp.task('css', function () {
    var postcss = require('gulp-postcss');
    var sourcemaps = require('gulp-sourcemaps');
    var autoprefixer = require('autoprefixer-core');
    var csswring = require('csswring');
    var customProperties = require("postcss-custom-properties");
    var colorFunction = require("postcss-color-function");
    var selector = require('postcss-custom-selectors');

    var processors = [
        autoprefixer({browsers: ['last 2 version']}),
        csswring,
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
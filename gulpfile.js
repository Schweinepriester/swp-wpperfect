var gulp = require('gulp');
var paths = {
    css: './dev/*.css'
}

gulp.task('default', ['autoprefixer']);

gulp.task('watch', function(){
    gulp.watch(paths.css, ['autoprefixer']);
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

gulp.task('autoprefixer', function () {
    var postcss      = require('gulp-postcss');
    var sourcemaps   = require('gulp-sourcemaps');
    var autoprefixer = require('autoprefixer-core');

    return gulp.src(paths.css)
        .pipe(sourcemaps.init())
        .pipe(postcss([ autoprefixer({ browsers: ['last 2 version'] }) ]))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./'));
});
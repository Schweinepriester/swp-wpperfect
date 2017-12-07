const gulp = require('gulp');

const paths = {
    css: './src/*.css',
    vendor: {
        src: './css/vendor/*.css',
        dist: './css/dist',
    },
};

gulp.task('default', ['css', 'vendorCSS']);

gulp.task('watch', () => {
    gulp.watch(paths.css, ['css']);
});

gulp.task('css', () => {
    const postcss = require('gulp-postcss');
    const sourcemaps = require('gulp-sourcemaps');
    const autoprefixer = require('autoprefixer');
    const customProperties = require('postcss-custom-properties');
    const colorFunction = require('postcss-color-function');
    const selector = require('postcss-custom-selectors');
    const nested = require('postcss-nested');

    const processors = [
        nested,
        // autoprefixer({ browsers: ['last 1 version'] }), // TODO real definition
        customProperties(),
        colorFunction(),
        selector(),
        require('postcss-strip-inline-comments'),
    ];

    return gulp.src(paths.css)
        .pipe(sourcemaps.init())
        .pipe(postcss(processors, { syntax: require('postcss-scss') }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./'));
});

gulp.task('vendorCSS', () => {
    const cssnano = require('gulp-cssnano');
    const rename = require('gulp-rename');

    return gulp.src(paths.vendor.src)
        .pipe(rename({
            suffix: '.min',
        }))
        .pipe(cssnano())
        .pipe(gulp.dest(paths.vendor.dist));
});

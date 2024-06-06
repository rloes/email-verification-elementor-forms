const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const babel = require('gulp-babel');
const rename = require('gulp-rename');

const paths = {
    styles: {
        src: 'assets/src/css/**/*.scss',
        dest: 'assets/dist/css/'
    },
    scripts: {
        src: 'assets/src/js/**/*.js',
        dest: 'assets/dist/js/'
    }
};

// Compile SCSS into CSS
gulp.task('styles', () => {
    return gulp.src(paths.styles.src)
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest(paths.styles.dest));
});

// Compile ES6+ into ES5
gulp.task('scripts', () => {
    return gulp.src(paths.scripts.src)
        .pipe(babel({
            presets: ['@babel/env']
        }))
        .pipe(gulp.dest(paths.scripts.dest));
});

// Watch for changes and recompile
gulp.task('watch', () => {
    gulp.watch(paths.styles.src, gulp.series('styles'));
    gulp.watch(paths.scripts.src, gulp.series('scripts'));
});

// Default task
gulp.task('default', gulp.series('styles', 'scripts', 'watch'));

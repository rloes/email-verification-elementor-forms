import gulp from 'gulp';
import dartSass from 'sass';
import gulpSass from 'gulp-sass';
import babel from 'gulp-babel';
import rename from 'gulp-rename';
import uglify from 'gulp-uglify';
import cleanCSS from 'gulp-clean-css';

const sass = gulpSass(dartSass);

const paths = {
    styles: {
        src: 'assets/src/css/**/*.scss',
        dest: 'assets/dist/css/'
    },
    scripts: {
        src: 'assets/src/js/**/*.js',
        dest: 'assets/dist/js/'
    },
    release: {
        src: [
            '**/*',
            '!node_modules/**',
            '!gulpfile.mjs',
            '!package-lock.json',
            '!assets/src/**',
            '!*.zip'
        ],
        dest: 'C:\\Users\\robin\\svn\\email-verification-elementor-forms\\trunk' // New directory for SVN
    }
};

// Compile SCSS into CSS and minify
const styles = () => {
    return gulp.src(paths.styles.src)
        .pipe(sass().on('error', sass.logError))
        .pipe(cleanCSS())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.styles.dest));
};

// Compile ES6+ into ES5 and minify
const scripts = () => {
    return gulp.src(paths.scripts.src)
        .pipe(babel({
            presets: ['@babel/env']
        }))
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest(paths.scripts.dest));
};

// Watch for changes and recompile
const watch = () => {
    gulp.watch(paths.styles.src, styles);
    gulp.watch(paths.scripts.src, scripts);
};

// Copy necessary files to SVN directory instead of zipping
const release = gulp.series(styles, scripts, () => {
    return gulp.src(paths.release.src)
        .pipe(gulp.dest(paths.release.dest)); // Copy to svn directory
});

// Default task
gulp.task('default', gulp.series(styles, scripts, watch));

// Export tasks for external usage
export {
    styles,
    scripts,
    watch,
    release
};

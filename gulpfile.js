/**
 * Gulpfile
 *
 * @since 1.0.0
 */


/**
 * Load Plugins.
 *
 * Load gulp plugins and assing them semantic names.
 */
var gulp 	= require('gulp');
var zip 	= require('gulp-zip');
var notify 	= require('gulp-notify');

/**
 * Build Plugin Zip
 */
gulp.task('zip', function () {
    return gulp.src( [
        // Include
        './**/*',

        // Exclude
        '!./prepros.cfg',
        '!./**/.DS_Store',
        '!./sass/**/*.scss',
        '!./sass',
        '!./node_modules/**',
        '!./node_modules',
        '!./package.json',
        '!./gulpfile.js',
        '!./*.sublime-project',
        '!./*.sublime-workspace'
    ])
    .pipe ( zip ( 'inspiry-memberships.zip' ) )
    .pipe ( gulp.dest ( '../' ) )
    .pipe ( notify ( {
        message : 'Permanent Password plugin zip is ready.',
        onLast : true
    } ) );
});

'use strict';

var gulp         = require('gulp');
var sass         = require('gulp-sass')(require('sass'));
var rename       = require('gulp-rename');
var uglify       = require('gulp-uglify');
var concat       = require('gulp-concat');

var paths = {
	scss_core : [ 'css/scss/**/*.scss', 'css/' ],
	js_backend: [ 'js/dolismq.js', 'js/modules/*.js' ]
};

/** Core */
gulp.task( 'scss_core', function() {
	return gulp.src( paths.scss_core[0] )
		.pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
		.pipe(rename('./dolismq.min.css'))
		.pipe(gulp.dest( paths.scss_core[1]));
});


gulp.task('js_backend', function () {
	return gulp.src(paths.js_backend)
		.pipe(concat('dolismq.min.js'))
		.pipe(uglify())
		.pipe(gulp.dest('./js/'))
});

/** Watch */
gulp.task('default', function() {
	gulp.watch(paths.scss_core[0], gulp.series('scss_core'));
	gulp.watch(paths.js_backend[1], gulp.series('js_backend'));
});

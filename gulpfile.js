var gulp = require('gulp');

gulp.task('default', function() {	 
	console.log('Use the following commands');
	console.log('--------------------------');
	console.log('gulp compile-sass      to compile the to-search.scss to to-search.css');
	console.log('gulp js				to compile the to-search.js to to-search.min.js');
	console.log('gulp compile-js		to compile both JS files above');
	console.log('gulp watch				to continue watching all files for changes, and build when changed');
	console.log('gulp wordpress-pot		to compile the to-search.pot');
	console.log('gulp reload-node-js	Copy over the .js files from teh various node modules');
});

var sass = require('gulp-sass');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sort = require('gulp-sort');
var wppot = require('gulp-wp-pot');

gulp.task('compile-sass', function () {
    gulp.src('assets/css/to-search.scss')
        .pipe(sass())
        .pipe(gulp.dest('assets/css/'));
});

gulp.task('js', function () {
	gulp.src('assets/js/to-search.js')
		.pipe(concat('to-search.min.js'))
		.pipe(uglify())
		.pipe(gulp.dest('assets/js'));
});
gulp.task('compile-js', (['js']));

gulp.task('watch', function() {
	gulp.watch('assets/js/to-search.js', ['js']);
});

gulp.task('wordpress-pot', function () {
	gulp.src('**/*.php')
		.pipe(sort())
		.pipe(wppot({
			domain: 'to-search',
			destFile: 'to-search.pot',
			package: 'to-search',
			bugReport: 'https://www.lsdev.biz/product/tour-operator-search/issues',
			team: 'LightSpeed <webmaster@lsdev.biz>'
		}))
		.pipe(gulp.dest('languages'));
});
// Theme assets (assets/css, assets/js). The Gutenberg block in src/ is built
// by @wordpress/scripts - see `npm run build`.
const gulp         = require('gulp');
const sass         = require('gulp-sass')(require('sass'));
const postcss      = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const rtlcss       = require('gulp-rtlcss');
const rename       = require('gulp-rename');
const terser       = require('gulp-terser');
const plumber      = require('gulp-plumber');
const sort         = require('gulp-sort');
const wppot        = require('gulp-wp-pot');

const onError = function (err) {
	console.error(err.toString());
	this.emit('end');
};

const sassOptions = {
	style: 'compressed',
	loadPaths: ['assets/css/scss']
};

const potOptions = {
	domain: 'to-search',
	package: 'to-search',
	bugReport: 'https://github.com/lightspeedwp/to-search/issues',
	team: 'LightSpeed <webmaster@lsdev.biz>'
};

// Sourcemaps come from gulp 5's built-in support rather than gulp-sourcemaps,
// which is unmaintained.
function styles() {
	return gulp.src('assets/css/scss/*.scss', { sourcemaps: true })
		.pipe(plumber({ errorHandler: onError }))
		.pipe(sass.sync(sassOptions).on('error', sass.logError))
		.pipe(postcss([autoprefixer()]))
		.pipe(gulp.dest('assets/css', { sourcemaps: 'maps' }));
}

function stylesRtl() {
	return gulp.src('assets/css/scss/*.scss')
		.pipe(plumber({ errorHandler: onError }))
		.pipe(sass.sync(sassOptions).on('error', sass.logError))
		.pipe(postcss([autoprefixer()]))
		.pipe(rtlcss())
		.pipe(rename({ suffix: '-rtl' }))
		.pipe(gulp.dest('assets/css'));
}

function js() {
	return gulp.src('assets/js/src/**/*.js')
		.pipe(plumber({ errorHandler: onError }))
		.pipe(terser())
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('assets/js'));
}

function wordpressPot() {
	return gulp.src('**/*.php')
		.pipe(sort())
		.pipe(wppot(potOptions))
		.pipe(gulp.dest('languages/to-search.pot'));
}

const compileCss = gulp.parallel(styles, stylesRtl);
const buildAll = gulp.parallel(compileCss, js);

function watchFiles() {
	gulp.watch('assets/css/**/*.scss', compileCss);
	gulp.watch('assets/js/src/**/*.js', js);
}

function help(cb) {
	console.log('Theme asset tasks (the block is built with `npm run build`)');
	console.log('----------------------------------------------------------');
	console.log('gulp compile-css    to compile the scss to css');
	console.log('gulp compile-js     to compile the js to min.js');
	console.log('gulp build          to compile both');
	console.log('gulp watch          to keep watching the files for changes');
	console.log('gulp wordpress-pot  to regenerate languages/to-search.pot');
	cb();
}

exports.styles = styles;
exports['styles-rtl'] = stylesRtl;
exports['compile-css'] = compileCss;
exports['compile-js'] = js;
exports.js = js;
exports.build = buildAll;
exports.watch = watchFiles;
exports['wordpress-pot'] = wordpressPot;
exports.default = help;

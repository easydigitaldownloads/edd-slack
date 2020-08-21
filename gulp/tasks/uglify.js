var $			= require( 'gulp-load-plugins' )();
var config		= require( '../util/loadConfig' ).javascript;
var gulp		= require( 'gulp' );
var gulpif		= require( 'gulp-if' );
var foreach	  	= require( 'gulp-foreach' );
var notify		= require( 'gulp-notify' );
var fs			= require( 'fs' );
var pkg		  	= JSON.parse( fs.readFileSync( './package.json' ) );
var onError	  	= notify.onError( {
   title:	pkg.name,
   message:  '<%= error.name %> <%= error.message %>'
} );

// This needs defined here too to prevent errors on default task
isRelease = false;

gulp.task( 'uglify:front', function() {

	return gulp.src( config.front.src, { allowEmpty: true } )
		.pipe( $.plumber( { errorHandler: onError } ) )
		.pipe( $.sourcemaps.init() )
		.pipe( $.concat( config.front.filename ) )
		.pipe( $.uglify() )
		.pipe( gulpif( ! isRelease, $.sourcemaps.write( '.' ) ) )
		.pipe( gulp.dest( config.front.root ) )
		.pipe( $.plumber.stop() )
		.pipe( notify( {
			title: pkg.name,
			message: 'JS Complete',
			onLast: true
		} ) );

} );

gulp.task( 'uglify:admin', function() {

	return gulp.src( config.admin.vendor.concat( config.admin.src ), { allowEmpty: true } )
		.pipe( $.plumber( { errorHandler: onError } ) )
		.pipe( $.sourcemaps.init() )
		.pipe( $.concat( config.admin.filename ) )
		.pipe( $.uglify() )
		.pipe( gulpif( ! isRelease, $.sourcemaps.write( '.' ) ) )
		.pipe( gulp.dest( config.admin.root ) )
		.pipe( $.plumber.stop() )
		.pipe( notify( {
			title: pkg.name,
			message: 'Admin JS Complete',
			onLast: true
		} ) );

} );

gulp.task( 'uglify:tinymce', function() {

	return gulp.src( config.tinymce.src, { allowEmpty: true } )
		.pipe( foreach( function( stream, file ) {
			return stream
				.pipe( $.plumber( { errorHandler: onError } ) )
				.pipe( $.uglify() )
				.pipe( gulp.dest( config.tinymce.dest ) )
				.pipe( $.plumber.stop() )
		} ) )
		.pipe( notify( {
			title: pkg.name,
			message: 'TinyMCE JS Complete',
			onLast: true
		} ) );

} );

gulp.task( 'uglify', gulp.series( 'uglify:front', 'uglify:admin', 'uglify:tinymce', function( done ) {
	done();
} ) );
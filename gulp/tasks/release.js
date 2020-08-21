var $			 	= require( 'gulp-load-plugins' )();
var config			= require( '../util/loadConfig' ).release;
var gulp		  	= require( 'gulp' );
var notify			= require( 'gulp-notify' );
var fs				= require( 'fs' );
var pkg		  		= JSON.parse( fs.readFileSync( './package.json' ) );
var packageName		= pkg.name.toLowerCase().replace( /_/g, '-' ).replace( /\s/g, '-' ).trim();

require( 'gulp-grunt' )( gulp, {
	prefix: 'release:grunt-',
} ); // add all the gruntfile tasks to gulp

gulp.task( 'release:localization', function( done ) {

	// Set as a Release build, important for Source Files
	isRelease = true;

	return gulp.src( './**/*.php' )
		.pipe( $.sort() )
		.pipe( $.wpPot( {
			domain: packageName,
			destFile: packageName + '.pot',
			package: pkg.name,
		} ) )
		.pipe( gulp.dest( config.languagesDir + packageName + '.pot' ) );

} );

gulp.task( 'release:copy', function( done ) {

	return gulp.src( config.files )
		.pipe( gulp.dest( './' + packageName ) );

} );

gulp.task( 'release:rename', function( done ) {

	// Grab Version from the appropriate file. This way it doesn't matter if I forget to update package.json
	var sourceFile = '';
	if ( config.type == 'plugin' ) {
		sourceFile = './' + packageName + '.php';
	}
	else {
		sourceFile = './style.css';
	}

	var mainFile = fs.readFileSync( sourceFile, 'utf8' ),
		versionLine = mainFile.match( /^\s\*\sversion:(?:\s)+(?:\S)+/im ),
		version = versionLine[0].replace( /\s\*\sversion:(?:\s)+/i, '' );

	fs.renameSync( './' + packageName + '.zip', './' + packageName + '-' + version + '.zip' );

	// We need to recreate those files with Source Maps now
	isRelease = false;

	return done();

} );

gulp.task( 'release:cleanup', function( done ) {

	return gulp.src( './' + packageName, { read: false } )
		.pipe( $.clean() )
		.pipe( notify( {
			title: pkg.name,
			message: 'Release Built'
		} ) );

} );

gulp.task( 'release', gulp.series( 'release:localization', 'sass', 'uglify', 'release:copy', 'release:grunt-compress', 'release:rename', 'sass', 'uglify', 'release:cleanup', function( done ) {
	done();
} ) );
var gulp		  = require( 'gulp' );

gulp.task( 'default', gulp.series('sass', 'uglify', 'watch', function( done ) {
	done();
}));
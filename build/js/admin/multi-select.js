( function( $ ) {
	
	$( document ).ready( function() {
		
		$( '.edd-slack-multi-select' ).each( function( index, select ) {
			
			console.log( 'test' );
			
			$( select ).attr( 'multiple', true ).chosen( {
				inherit_select_classes: true,
			} );
			
		} );
		
	} );
	
} )( jQuery );
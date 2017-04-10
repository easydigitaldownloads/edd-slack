( function( $ ) {
	
	$( document ).ready( function() {
		
		$( '#edd_slack_team_customer_invite_form' ).submit( function( event ) {
			
			event.preventDefault();
			
			console.log( 'submitted' );
			
		} );
		
	} );
	
} )( jQuery );
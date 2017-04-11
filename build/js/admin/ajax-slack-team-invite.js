( function( $ ) {

	/**
	 * Sends a Slack Team Invite via Ajax
	 * 
	 * @param		{integer} id   EDD_Customer ID or FES_Vendor ID
	 * @param 		{string}  type 'customer' or 'vendor'
	 *                         
	 * @since		1.1.0
	 * @return		object	  JSON
	 */
	var sendInvite = function( id, type ) {
		
		var output,
			noAsync = {};
		
		// This forces the AJAX to complete on our time rather than its own
		// This way we can reliably have its results returned before executing another action in sequence rather than via a promise
		noAsync = function( id, type ) {
			
			var result = {};
			$.ajax( {
				async: false,
				type: 'POST',
				url: location.origin + ajaxurl,
				data: {
					id: id,
					type: type,
					_ajax_nonce: $( '#edd_slack_team_invite_nonce' ).val(),
					action: 'edd_slack_app_team_invite',
				},
				success: function( response ) {

					var message = '';

					// There's an Error
					if ( response.ok === false ) {

						switch ( response.error ) {
							case 'channel_not_found':
								message = eddSlack.i18n.slackInvite.channel_not_found;
								break;
							case 'no_perms':
								message = eddSlack.i18n.slackInvite.no_perms;
								break;
							case 'already_in_team':
								message = eddSlack.i18n.slackInvite.already_in_team;
								break;
							default:
								message = response.error;
						}

					}
					else {

						message = eddSlack.i18n.slackInvite[ type + '_invite_successful' ];

					}

					result = {
						ok: response.ok,
						message: message,
					};

				},
				error : function( request, status, error ) {
					console.error( request.responseText );
					console.error( error );
				}
			} );
			
			return result;
			
		}( id, type );
		
		output = noAsync;
		
		return output;
		
	};

	/**
	 * Grabs GET Parameter from the URL
	 * 
	 * @param		{string} variable Variable Name
	 * @param 		{string} url      URL. Defaults to the current URL
	 *                            
	 * @since		1.1.0
	 * @return 		{string} Parameter Value
	 */
	function getUrlParameter( variable, url = undefined ) {

		if ( url === undefined ) {
			url = window.location.href;
		}
		
		// Remove Hash from URL
		url = url.replace( /#.*$/, '' );

		var vars = url.split( '&' );

		for ( var i = 0; i < vars.length; i++ ) {

			var pair = vars[ i ].split( '=' );

			if ( pair[0] == variable ) {
				return pair[1];
			}

		}

		return false;

	}

	$( document ).ready( function() {

		$( '#edd_slack_team_customer_invite_form, #edd_slack_team_vendor_invite_form' ).submit( function( event ) {

			event.preventDefault();
			
			var $form = $( this ),
				$submitButton = $( document.activeElement );
			
			$submitButton.attr( 'disabled', true );
			
			// Copy EDD's notice for AJAX sub-form submission because it is awesome
			$form.find( '.notice-wrap' ).remove();
			$form.append( '<div class="notice-wrap"><span class="spinner is-active"></span></div>' );

			var id = getUrlParameter( 'id' ),
				type = 'customer';
			
			if ( event.currentTarget.id == 'edd_slack_team_vendor_invite_form' ) type = 'vendor';

			var result = sendInvite( id, type );
			
			if ( ! result.ok ) {
				$form.find( '.notice-wrap' ).append( '<div class="update error"><p>' + result.message + '</p></div>' );
				$submitButton.attr( 'disabled', false );
			}
			else {
				$form.find( '.notice-wrap' ).append( '<div class="updated"><p>' + result.message + '</p></div>' );
			}
			
			$form.find( '.notice-wrap .spinner' ).remove();

		} );

	} );

} )( jQuery );
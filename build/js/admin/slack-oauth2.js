( function ( $ ) {

    /**
     * Modified version of https://www.sitepoint.com/oauth-popup-window/
     * 
     * @param {object} options Options to create the Popup
     */
    $.EDDSlackOauthPopup = function( options ) {
        
        options.windowName = options.windowName ||  'ConnectWithOAuth'; // should not include space for IE
        options.windowOptions = options.windowOptions || 'location=0,status=0,width=800,height=400';
        options.callback = options.callback || function() { window.location.reload(); };
        options.redirectURI = options.redirectURI || false;
        
        var that = this;
        that._oauthWindow = window.open( options.path, options.windowName, options.windowOptions );
        that._oauthInterval = setInterval( function() {
            
            try {
                
                if ( that._oauthWindow.location.href.indexOf( options.redirectURI ) !== -1 &&
                    that._oauthWindow.location.href.indexOf( '&error=access_denied' ) == -1
                   ) {
                    
                    clearInterval( that._oauthInterval );
                    
                    // Setting State now rather than before ensures that we only Save the OAUTH Token once the Popup is closed out
                    // Otherwise it Saves and immediately closes the Popup and the User doesn't get the Success Message
                    options.redirectURI = that._oauthWindow.location.href.replace( '&state=', '&state=' + 'saving' );
                    
                    // Pass back our Temporary Code
                    options.callback( options.redirectURI );
                    
                    that._oauthWindow.close();
                    
                }
                
            }
            catch ( e ) {
                 // No errors for Cross Origin Policy
            }
            
        }, 100 );
        
    };

    $( document ).ready( function() {
        
        if ( $( '.edd-slack-app-auth' ).length > 0 ) {

            $( '.edd-slack-app-auth' ).on( 'click', function( event) {

                // Don't actually go anywhere... yet
                event.preventDefault();

                var oAuthURL = $( this ).attr( 'href' ),
                    regex = /[?&]([^=#]+)=([^&#]*)/g,
                    match,
                    redirectURI = '';

                while ( ( match = regex.exec( oAuthURL ) ) !== null ) {

                    if ( match[1] !== 'redirect_uri' ) continue;

                    redirectURI = decodeURIComponent( match[2] );

                }
                
                // Fixes dual-screen position                         Most browsers      Firefox
                var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left,
                    dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;
                
                // Set Popup Height/Width and the Height/Width of the Source Browser Window
                var popupWidth = 700,
                    popupHeight = 650,
                    browserWidth = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width,
                    browserHeight = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

                var left = ( ( browserWidth / 2 ) - ( popupWidth / 2 ) ) + dualScreenLeft;
                var top = ( ( browserHeight / 2 ) - ( popupHeight / 2 ) ) + dualScreenTop;

                var popup = $.EDDSlackOauthPopup( {
                    path: oAuthURL,
                    redirectURI: redirectURI,
                    windowOptions: 'location=0,status=0,width=' + popupWidth + ',height=' + popupHeight + ',top=' + top + ',left=' + left,
                    callback: function( newURL ) {
                        window.location = newURL; // "Refresh" the page with the new URL in order to save our OAUTH Token
                    }
                } );

            } );
            
        }

    } );

} )( jQuery );
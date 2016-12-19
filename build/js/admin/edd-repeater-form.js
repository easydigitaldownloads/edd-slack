( function( $ ) {
    
    /**
     * Submit the Form for Creating/Updating Notifications via their Modals
     * 
     * @param       {object}  event JavaScript Event Object
     *                              
     * @since       1.0.0
     * @returns     {boolean} Validity of Form
     */
    var attachNotificationSubmitEvent = function( event ) {
        
        var row = event.currentTarget,
            $form = $( row ).find( '.edd-rbm-repeater-form' ).wrap( '<form method="POST"></form>' ).parent();
        
        if ( ! $( row ).hasClass( 'has-form' ) ) {
            
            $( row ).addClass( 'has-form' );

            // Normally HTML doesn't like us having nested Forms, so we force it like this
            // By the time the Modal opens and this code runs, the Form isn't nested anymore
            $form.submit( function( event ) {

                event.preventDefault(); // Don't submit the form via PHP

                // Used to construct HTML Name Attribute
                var repeaterList = $( '.edd-rbm-repeater-list' ).data( 'repeater-list' ),
                    regex = new RegExp( repeaterList.replace( /[-\/\\^$*+?.()|[\]{}]/g, '\\$&' ) + '\\[\\d\\]\\[(.*)\\]', 'gi' ),
                    data = [];

                $( this ).find( '.edd-slack-field' ).each( function( index, field ) {

                    if ( $( field ).parent().hasClass( 'hidden' ) ) return true;

                    var name = $( field ).attr( 'name' ),
                        match = regex.exec( name );

                    data.push( {
                        'key' : match[1],
                        'value' : $( field ).val(),
                    } );

                    // Reset Interal Pointer for Regex
                    regex.lastIndex = 0;

                } );

                console.log( data );

            } );
            
        }
        
    }
    
    $( document ).ready( function() {
        
        // When a Modal opens, attach the Form Submission Event
        $( document ).on( 'open.zf.reveal', '.edd-rbm-repeater-content.reveal', function( event ) {
            attachNotificationSubmitEvent( event );
        } );
        
    } );
    
} )( jQuery );
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
        
        var modal = event.currentTarget,
            $form = $( modal ).find( 'form' );
        
        if ( ! $( modal ).hasClass( 'has-form' ) ) {
            
            // We need to create the Form
            // "novalidate" so that HTML5 doesn't try to take over before we can do our thing
            $form = $( modal ).find( '.edd-rbm-repeater-form' ).wrap( '<form method="POST" novalidate></form>' ).parent();
            
            $( modal ).addClass( 'has-form' );

            // Normally HTML doesn't like us having nested Forms, so we force it like this
            // By the time the Modal opens and this code runs, the Form isn't nested anymore
            $form.submit( function( event ) {

                event.preventDefault(); // Don't submit the form via PHP
                
                $form[0].reportValidity(); // Report Validity via HTML5 stuff
                
                if ( $form[0].checkValidity() ) { // Only run our code if we've got a Valid Form

                    // Used to construct HTML Name Attribute
                    var repeaterList = $( '.edd-rbm-repeater-list' ).data( 'repeater-list' ),
                        regex = new RegExp( repeaterList.replace( /[-\/\\^$*+?.()|[\]{}]/g, '\\$&' ) + '\\[\\d\\]\\[(.*)\\]', 'gi' ),
                        data = {};

                    $( this ).find( '.edd-slack-field' ).each( function( index, field ) {

                        if ( $( field ).parent().hasClass( 'hidden' ) ) return true;

                        var name = $( field ).attr( 'name' ),
                            match = regex.exec( name );

                        data[ match[1] ] = $( field ).val();

                        // Reset Interal Pointer for Regex
                        regex.lastIndex = 0;

                    } );
                    
                    data.action = 'insert_edd_rbm_slack_notification';

                    $.ajax( {
                        'type' : 'POST',
                        'url' : eddSlack.ajax,
                        'data' : data,
                        success : function( response ) {
                            
                            var uuid = $( modal ).data( 'reveal' ),
                                $row = $( '[data-open="' + uuid + '"]' ).closest( '.edd-rbm-repeater-item' );
                            
                            // If the Modal started as a New Notification, we need to update the Post ID value to ensure it can be updated
                            $form.find( '.edd-slack-post-id' ).val( response.data.post_id );
                            
                            closeModal( uuid );
                            
                            // Highlight Green
                            $row.effect( 'highlight', { color : '#DFF2BF' }, 1000 );
                            
                        },
                        error : function( request, status, error ) {
                        }
                    } );
                    
                }

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
( function( $ ) {
    
    var attachNotificationSubmitEvent = function( event, row = null ) {
        
        var uuid = $( row ).find( '[data-repeater-edit]' ).data( 'open' ),
            $modal = $( '[data-reveal="' + uuid + '"]' ),
            $form = $modal.find( '.edd-rbm-repeater-form' ).wrap( '<form method="POST"></form>' ).parent();

        // Normally HTML doesn't like us having nested Forms, so we force it like so
        $form.submit( function( event ) {
            
            console.log( 'test' );
        
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
    
    $( document ).ready( function() {

        //attachSubmitModalForm();
        
        // This JavaScript only loads on our custom Page, so we're fine doing this
        var $repeaters = $( '[data-edd-rbm-repeater]' );

        if ( $repeaters.length ) {
            $repeaters.on( 'edd-rbm-repeater-add', function( event, row ) { setTimeout( function() { attachNotificationSubmitEvent( event, row ) }, 100 ) } );
        }
        
    } );
    
} )( jQuery );
( function( $ ) {
    
    var attachSubmitModalForm = function() {
        
        $( document ).on( 'submit', '.edd-rbm-repeater-form', function( event ) {
        
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
            $repeaters.on( 'edd-rbm-repeater-add', setTimeout( function() { attachSubmitModalForm() }, 100 ) );
        }
        
    } );
    
} )( jQuery );
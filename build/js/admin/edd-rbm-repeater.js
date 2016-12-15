// Initialize special fields if they exist
function init_edd_repeater_colorpickers() {

    var regex = /value="(#(?:[0-9a-f]{3}){1,2})"/i;

    // Only try to run if there are any Color Pickers within an EDD Repeater
    if ( jQuery( '.edd-rbm-repeater .edd-color-picker' ).length ) {

        // Check Each Repeater
        jQuery( '.edd-rbm-repeater' ).each( function( repeaterIndex, repeater ) {

            jQuery( repeater ).find( '.edd-rbm-repeater-item' ).each( function( rowIndex, row ) {

                // Hit each colorpicker individually to ensure its settings are properly used
                jQuery( row ).find( '.edd-color-picker' ).each( function( index, colorPicker ) {

                    // Value exists in HTML but is inaccessable via JavaScript. No idea why.
                    var value = regex.exec( $( colorPicker )[0].outerHTML )[1];

                    jQuery( colorPicker ).val( value ).attr( 'value', value ).wpColorPicker();

                } );

            } );

        } );

    }

}

function init_edd_repeater_chosen() {

    // Only try to run if there are any Chosen Fields within an EDD Repeater
    if ( jQuery( '.edd-rbm-repeater .edd-chosen' ).length ) {

        jQuery( '.edd-rbm-repeater' ).each( function( repeaterIndex, repeater ) {

            jQuery( repeater ).find( '.edd-rbm-repeater-item' ).each( function( rowIndex, row ) {

                // Init Chosen Fields as a Glob per-row
                jQuery( row ).find( '.edd-chosen' ).chosen();

            } );

        } );

    }

}

// Repeaters
( function ( $ ) {

    var $repeaters = $( '[data-edd-rbm-repeater]' );

    if ( ! $repeaters.length ) {
        return;
    }

    var edd_repeater_show = function() {

        // Hide current title for new item and show default title
        $( this ).find( '.repeater-header span.title' ).html( $( this ).find( '.repeater-header span.title' ).data( 'repeater-default-title' ) );
        
        // For some reason Select Fields don't show correctly despite the HTML being correct
        $( this ).find( 'select' ).each( function( index, select ) {
            
            var selected = $( select ).find( 'option[selected]' ).val();
            
            $( select ).val( selected );
            
        } );
        
        $( this ).find( 'input[type="checkbox"].default-checked' ).each( function( index, checkbox ) {
            
            $( checkbox ).prop( 'checked', true );
            
        } );

        $( this ).stop().slideDown();
        
        var repeater = $( this ).closest( '[data-edd-rbm-repeater]' );

        $( repeater ).trigger( 'edd-rbm-repeater-add', [$( this )] );

    }

    var edd_repeater_hide = function() {

        var repeater = $( this ).closest( '[data-edd-rbm-repeater]' );

        $( this ).stop().slideUp( 300, function () {
            $(this).remove();
        } );

        $( repeater ).trigger( 'edd-rbm-repeater-remove', [$( this )] );

    }

    $repeaters.each( function () {

        var $repeater = $( this ),
            $dummy = $repeater.find( '[data-repeater-dummy]' );

        // Repeater
        $repeater.repeater( {

            repeaters: [ {
                show: edd_repeater_show,
                hide: edd_repeater_hide,
            } ],
            show: edd_repeater_show,
            hide: edd_repeater_hide,
            ready: function ( setIndexes ) {
                $repeater.find( 'tbody' ).on( 'sortupdate', setIndexes );
            }

        } );

        if ( $dummy.length ) {
            $dummy.remove();
        }

        $( document ).on( 'keyup change', '.edd-rbm-repeater .edd-rbm-repeater-content td:first-of-type *[type!="hidden"]', function() {
            
            if ( $( this ).val() !== '' ) {
                $( this ).closest( '.edd-rbm-repeater-item' ).find( '.repeater-header span.title' ).html( $( this ).val() );
            }
            else {
                var defaultValue = $( this ).closest( '.edd-rbm-repeater-item' ).find( '.repeater-header h2' ).data( 'repeater-default-title' );
                $( this ).closest( '.edd-rbm-repeater-item' ).find( '.repeater-header span.title' ).html( defaultValue );
            }
            
        } );

    } );

} )( jQuery );
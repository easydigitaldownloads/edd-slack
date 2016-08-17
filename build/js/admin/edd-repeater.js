// Repeaters
( function ( $ ) {

    // Initialize special fields if they exist
    function init_edd_repeater_colorpickers() {

        var regex = /value="(#(?:[0-9a-f]{3}){1,2})"/i;

        // Only try to run if there are any Color Pickers within an EDD Repeater
        if ( $( '.edd-repeater .edd-color-picker' ).length ) {

            // Check Each Repeater
            $( '.edd-repeater' ).each( function( repeaterIndex, repeater ) {

                // Check only Open Repeater Rows
                $( repeater ).find( '.edd-repeater-item.opened' ).each( function( rowIndex, row ) {

                    // Hit each colorpicker individually to ensure its settings are properly used
                    $( row ).find( '.edd-color-picker' ).each( function( index, colorPicker ) {

                        // Value exists in HTML but is inaccessable via JavaScript. No idea why.
                        var value = regex.exec( $( colorPicker )[0].outerHTML )[1];

                        $( colorPicker ).val( value ).attr( 'value', value ).wpColorPicker();

                    } );

                } );

            } );

        }

    }

    var $repeaters = $( '[data-edd-repeater]' );

    if ( ! $repeaters.length ) {
        return;
    }

    init_edd_repeater_colorpickers();

    var edd_repeater_show = function() {

        // Hide current title for new item and show default title
        $( this ).find( '.repeater-header h2 span.title' ).html( $( this ).find( '.repeater-header h2' ).data( 'repeater-collapsable-default' ) );

        // Nested Repeaters always inherit the number of Rows from the previous Repeater, so this will fix that.
        var repeater = $( this ).closest( '[data-edd-repeater]' ),
            nestedRepeaters = $( this ).find( '.nested-repeater' );

        $( nestedRepeaters ).each( function( index, nestedRepeater ) {

            var items = $( nestedRepeater ).find( '.edd-repeater-item' ).get().reverse();

            if ( items.length == 1 ) return true; // Continue

            $( items ).each( function( row, nestedRow ) {

                if ( row == ( items.length - 1 ) ) return false; // Break

                $( nestedRow ).stop().slideUp( 300, function() {
                    $( this ).remove();
                } );

                $( repeater ).trigger( 'edd-nested-repeater-cleanup', [$( nestedRow )] );

            } );

        } );

        $( this ).addClass( 'opened' ).removeClass( 'closed' ).stop().slideDown();

        init_edd_repeater_colorpickers();

        $( repeater ).trigger( 'edd-repeater-add', [$( this )] );

    }

    var edd_repeater_hide = function() {

        var repeater = $( this ).closest( '[data-edd-repeater]' );

        $( this ).stop().slideUp( 300, function () {
            $(this).remove();
        } );

        $( repeater ).trigger( 'edd-repeater-remove', [$( this )] );

    }

    $repeaters.each( function () {

        var $repeater = $( this ),
            $dummy = $repeater.find( '[data-repeater-dummy]' );

        // Repeater
        $repeater.repeater( {

            repeaters: [ {
                // (Required)
                // Specify the jQuery selector for this nested repeater
                selector: '.nested-repeater',
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

        // Sortable
        if ( typeof $repeater.attr( 'data-repeater-sortable' ) !== 'undefined' ) {

            $repeater.find( '.edd-repeater-list' ).sortable( {
                axis: 'y',
                handle: '[data-repeater-item-handle]',
                forcePlaceholderSize: true,
                update: function ( e, ui ) {
                    init_edd_repeater_colorpickers();
                }

            } );

        }

        // Collapsable
        if ( typeof $repeater.attr( 'data-repeater-collapsable' ) !== 'undefined' ) {
            $repeater.find( '.edd-repeater-content' ).first().hide();
        }

        $( document ).on( 'click touchend', '.edd-repeater[data-repeater-collapsable] [data-repeater-collapsable-handle]', function () {

            var $repeater_field = $( this ).closest( '.edd-repeater-item' ),
                $content = $repeater_field.find( '.edd-repeater-content' ).first(),
                status = $repeater_field.hasClass( 'opened' ) ? 'closing' : 'opening';

            if ( status == 'opening' ) {

                $content.stop().slideDown();
                $repeater_field.addClass( 'opened' );
                $repeater_field.removeClass( 'closed' );

            }
            else {

                $content.stop().slideUp();
                $repeater_field.addClass( 'closed' );
                $repeater_field.removeClass( 'opened' );
            }

        } );

        $( document ).on( 'keyup change', '.edd-repeater .edd-repeater-content td:first-of-type *[type!="hidden"]', function() {
            
            if ( $( this ).val() !== '' ) {
                $( this ).closest( '.edd-repeater-item' ).find( '.repeater-header h2 span.title' ).html( $( this ).val() );
            }
            else {
                var defaultValue = $( this ).closest( '.edd-repeater-item' ).find( '.repeater-header h2' ).data( 'repeater-collapsable-default' );
                $( this ).closest( '.edd-repeater-item' ).find( '.repeater-header h2 span.title' ).html( defaultValue );
            }
            
        } );

    } );

} )( jQuery );
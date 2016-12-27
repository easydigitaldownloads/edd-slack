// Initialize special fields if they exist
function init_edd_repeater_colorpickers( modal ) {

    var regex = /value="(#(?:[0-9a-f]{3}){1,2})"/i;

    // Only try to run if there are any Color Pickers within an EDD Repeater
    if ( jQuery( modal ).find( '.edd-color-picker' ).length ) {

        // Hit each colorpicker individually to ensure its settings are properly used
        jQuery( modal ).find( '.edd-color-picker' ).each( function( index, colorPicker ) {

            // Value exists in HTML but is inaccessable via JavaScript. No idea why.
            var value = regex.exec( jQuery( colorPicker )[0].outerHTML )[1];

            jQuery( colorPicker ).val( value ).attr( 'value', value ).wpColorPicker();

        } );

    }

}

function init_edd_repeater_chosen( modal ) {

    // Only try to run if there are any Chosen Fields within an EDD Repeater
    if ( jQuery( modal ).find( '.edd-chosen' ).length ) {

        // Init Chosen Fields as a Glob per-row
        jQuery( modal ).find( '.edd-chosen' ).chosen();

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
        $( this ).find( '.repeater-header div.title' ).html( $( this ).find( '.repeater-header div.title' ).data( 'repeater-default-title' ) );

        $( this ).stop().slideDown();
        
        var repeater = $( this ).closest( '[data-edd-rbm-repeater]' );

        $( repeater ).trigger( 'edd-rbm-repeater-add', [$( this )] );

    }

    var edd_repeater_hide = function() {

        var repeater = $( this ).closest( '[data-edd-rbm-repeater]' ),
            confirmDeletion = confirm( eddSlack.i18n.confirmDeletion );
            
        if ( confirmDeletion ) {

            var $row = $( this ),
                uuid = $row.find( '[data-repeater-edit]' ).data( 'open' ),
                $modal = $( '[data-reveal="' + uuid + '"]' ),
                postID = $modal.find( '.edd-slack-post-id' ).val();
            
            $.ajax( {
                'type' : 'POST',
                'url' : eddSlack.ajax,
                'data' : {
                    'action' : 'delete_edd_rbm_slack_notification',
                    'post_id' : postID,
                },
                success : function( response ) {
                    
                    // Remove whole DOM tree for the Modal.
                    $modal.parent().remove();

                    // Remove DOM Tree for the Notification "Header"
                    $row.stop();
                    setTimeout( function() {
                        
                        $row.effect( 'highlight', { color : '#FFBABA' }, 300 ).dequeue().slideUp( 300, function () {
                            $row.remove();
                        } );
                        
                    } );

                    $( repeater ).trigger( 'edd-rbm-repeater-remove', [$row] );
                    
                },
                error : function( request, status, error ) {
                    
                }
            } );

        }

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
        
        $( document ).on( 'closed.zf.reveal', '.edd-rbm-repeater-content.reveal', function() {
            
            var title = $( this ).find( 'td:first-of-type *[type!="hidden"]' ),
                uuid = $( this ).closest( '.edd-rbm-repeater-content.reveal' ).data( 'reveal' ),
                $row = $( '[data-open="' + uuid + '"]' );
            
            if ( $( title ).val() !== '' ) {
                $row.closest( '.edd-rbm-repeater-item' ).find( '.repeater-header div.title' ).html( $( title ).val() );
            }
            else {
                var defaultValue = $row.closest( '.edd-rbm-repeater-item' ).find( '.repeater-header div.title' ).data( 'repeater-default-title' );
                $row.closest( '.edd-rbm-repeater-item' ).find( '.repeater-header div.title' ).html( defaultValue );
            }
            
        } );

    } );

} )( jQuery );
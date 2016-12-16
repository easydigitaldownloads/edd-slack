( function( $ ) {
    'use strict';
    
    var edd_slack_conditional_fields = function( row, option_class ) {
        
        // Handle newly created Rows
        if ( row.type == 'edd-rbm-repeater-add' ) {
            row = option_class;
            option_class = 0;
        }
        else {
            row = $( row ).closest( '.edd-rbm-repeater-content' );
        }
        
        if ( option_class == 0 ) {
            
            $( row ).find( 'select' ).each( function( index, select ) {
                
                $( select ).val( 0 );
                
                if ( $( select ).hasClass( 'edd-chosen' ) ) {
                    $( select ).trigger( 'chosen:updated' );
                }
                
            } );
            
            $( row ).find( '.edd-slack-conditional' ).closest( 'td' ).addClass( 'hidden' );
            $( row ).find( '.edd-slack-replacement-instruction' ).closest( 'td' ).addClass( 'hidden' );
            
        }
        else {

            $( row ).find( '.edd-slack-conditional.' + option_class ).closest( 'td.hidden' ).removeClass( 'hidden' );
            $( row ).find( '.edd-slack-conditional' ).not( '.' + option_class ).closest( 'td' ).addClass( 'hidden' );
            
            // Also do this for the Replacement Hints
            $( row ).find( '.edd-slack-replacement-instruction.' + option_class ).removeClass( 'hidden' );
            $( row ).find( '.edd-slack-replacement-instruction' ).not( '.' + option_class ).addClass( 'hidden' );
            
        }

    }
    
    var delete_edd_slack_feed = function( e, $item ) {
        
        var uuid = $item.find( '[data-repeater-edit]' ).data( 'open' ),
            $modal = $( '[data-reveal="' + uuid + '"]' );

        var post_ID = $modal.find( 'input[name$="[slack_post_id]"]' ).val(),
            $delete_feeds = $( 'input[type="hidden"][name^="edd_slack_deleted_"]' ),
            deleted = $delete_feeds.val();

        if ( ! post_ID ) {
            return;
        }

        if ( ! deleted ) {
            deleted = post_ID;
        }
        else {
            deleted = deleted + ',' + post_ID;
        }

        $delete_feeds.val( deleted );

    }
    
    var init_edd_slack_repeater_functionality = function() {
        
        // This JavaScript only loads on our custom Page, so we're fine doing this
        var $repeaters = $( '[data-edd-rbm-repeater]' );
        
        if ( $repeaters.length ) {
            $repeaters.on( 'edd-rbm-repeater-add', edd_slack_conditional_fields );
            $repeaters.on( 'repeater-show', edd_slack_conditional_fields );
            $repeaters.on( 'edd-rbm-repeater-remove', delete_edd_slack_feed );
        }
        
    }
    
    init_edd_slack_repeater_functionality();
    
    $( document ).ready( function() {
        
        // Handle conditional fields on Page Load
        $( '.edd-slack-trigger' ).each( function( index, trigger ) {
            edd_slack_conditional_fields( trigger, $( trigger ).val() );
        } );
        
        $( '.repeater-header span[data-repeater-default-title]' ).each( function( index, header ) {
            
            var active = true,
                repeaterItem = $( header ).closest( 'div[data-repeater-item]' );
            
            // Select Fields need to be filled out. No other field is really required
            $( repeaterItem ).find( 'select' ).each( function( valueIndex, select ) {
                
                if ( ! $( select ).closest( 'td' ).hasClass( 'hidden' ) && 
                    $( select ).val() == 0 ) {
                    active = false;
                    return false; // Break out of execution, we already know we're invalid
                }
                
            } );
            
            if ( active === true ) {
                
                $( header ).find( '.title' ).append( '<span class="active dashicons dashicons-yes" aria-label="' + eddSlack.i18n.inactiveText + '"></span>' );
                
            }
            else {
                
                $( header ).find( '.title' ).append( '<span class="inactive dashicons dashicons-no" aria-label="' + eddSlack.i18n.inactiveText + '"></span>' );
                
            }
            
        } );
        
        // And toggle them on Change
        $( document ).on( 'change', '.edd-slack-trigger', function() {
            edd_slack_conditional_fields( $( this ), $( this ).val() );
        } );
        
    } );

} )( jQuery );
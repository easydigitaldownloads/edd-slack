( function( $ ) {
    'use strict';
    
    var edd_slack_conditional_fields = function( element, option_class ) {
        
        // Handle newly created Rows
        if ( element.type == 'edd-repeater-add' ) {
            element = option_class;
            option_class = 0;
        }
        
        var row = $( element ).closest( '.edd-repeater-item' );
        
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

        var post_ID = $item.find( 'input[name$="[slack_post_id]"]' ).val(),
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
        
        // This JavaScript only loads on our custom Page, so we're find doing this
        var $repeaters = $( '[data-edd-repeater]' );
        
        if ( typeof EDD_Slack_Admin !== undefined ) {
            
        }
        
        if ( $repeaters.length ) {
            $repeaters.on( 'edd-repeater-add', edd_slack_conditional_fields );
            $repeaters.on( 'repeater-show', edd_slack_conditional_fields );
            $repeaters.on( 'edd-repeater-remove', delete_edd_slack_feed );
        }
        
    }
    
    init_edd_slack_repeater_functionality();
    
    $( document ).ready( function() {
        
        // Handle conditional fields on Page Load
        $( '.edd-slack-trigger' ).each( function( index, trigger ) {
            edd_slack_conditional_fields( trigger, $( trigger ).val() );
        } );
        
        $( '.repeater-header h2[data-repeater-collapsable-default]' ).each( function( index, header ) {
            
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
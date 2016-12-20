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
        
        $( row ).find( '.required' ).each( function( index, field ) {
            
            if ( $( field ).closest( 'td' ).hasClass( 'hidden' ) ) {
                $( field ).attr( 'required', false );
            }
            else {
                $( field ).attr( 'required', true );
            }
            
            // Fix Tab Ordering Bug 
            if ( $( field ).hasClass( 'edd-chosen' ) ) {
                
                // Ensure the Chosen Container has been built
                $( field ).chosen();
                
                // No Tab index for the "hidden" Select field
                $( field ).attr( 'tabindex', -1 );
                
                // Why would you be unable to tab into it by default?!?!
                $( field ).siblings( '.chosen-container' ).find( '.chosen-single' ).attr( 'tabindex', 0 );
                
            }
            
        } );

    }
    
    var eddSlackNotificationIndicators = function() {
        
        $( '.repeater-header div[data-repeater-default-title]' ).each( function( index, header ) {
            
            var active = true,
                $repeaterItem = $( header ).closest( 'div[data-repeater-item]' ),
                uuid = $repeaterItem.find( '[data-repeater-edit]' ).data( 'open' ),
                $modal = $( '[data-reveal="' + uuid + '"]' );
            
            // Ensure Required Fields are Filled Out
            // This should only apply to Non-Saved Notifications, but if someone gets cheeky and attempts to get around my form validation this will tell them that they dun goof'd
            $modal.find( '.required' ).each( function( valueIndex, field ) {
                
                if ( ! $( field ).closest( 'td' ).hasClass( 'hidden' ) && 
                    $( field ).val() === null ) {
                    active = false;
                    return false; // Break out of execution, we already know we're invalid
                }
                
            } );
            
            // If we're not saved yet
            if ( $modal.find( '.edd-slack-post-id' ).val() == '' ) {
                active = false;
            }
            
            $( header ).siblings( '.status-indicator' ).remove();
            
            if ( active === true ) {
                
                $( header ).after( '<span class="active status-indicator dashicons dashicons-yes" aria-label="' + eddSlack.i18n.inactiveText + '"></span>' );
                
            }
            else {
                
                $( header ).after( '<span class="inactive status-indicator dashicons dashicons-no" aria-label="' + eddSlack.i18n.inactiveText + '"></span>' );
                
            }
            
        } );
        
    }
    
    var init_edd_slack_repeater_functionality = function() {
        
        // This JavaScript only loads on our custom Page, so we're fine doing this
        var $repeaters = $( '[data-edd-rbm-repeater]' );
        
        if ( $repeaters.length ) {
            
            $repeaters.on( 'edd-rbm-repeater-add', edd_slack_conditional_fields );
            $repeaters.on( 'repeater-show', edd_slack_conditional_fields );
            
            $( document ).on( 'closed.zf.reveal', '.edd-rbm-repeater-content.reveal', function() {
                eddSlackNotificationIndicators();
            } );
            
        }
        
    }
    
    init_edd_slack_repeater_functionality();
    
    $( document ).ready( function() {
        
        // Handle conditional fields on Page Load
        $( '.edd-slack-trigger' ).each( function( index, trigger ) {
            edd_slack_conditional_fields( trigger, $( trigger ).val() );
        } );
        
        eddSlackNotificationIndicators();
        
        // And toggle them on Change
        $( document ).on( 'change', '.edd-slack-trigger', function() {
            edd_slack_conditional_fields( $( this ), $( this ).val() );
        } );
        
    } );

} )( jQuery );
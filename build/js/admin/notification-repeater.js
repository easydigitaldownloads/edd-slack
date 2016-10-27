( function( $ ) {
    'use strict';
    
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
            $repeaters.on( 'edd-repeater-remove', delete_edd_slack_feed );
        }
        
    }
    
    init_edd_slack_repeater_functionality();

} )( jQuery );
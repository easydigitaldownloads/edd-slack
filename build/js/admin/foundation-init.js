( function( $ ) {
    'use strict';

    /**
     * Normally something like this would be handled by $( document ).foundation(), but doing it manually lets us call this whenever we'd like and dynamically create Button->Modal associations
     * 
     * @since       1.0.0
     * @return      void
     */
    var initModals = function() {

        jQuery( '.edd-rbm-repeater .edd-rbm-repeater-item' ).each( function( index, item ) {

            var $modal = jQuery( item ).find( '.edd-rbm-repeater-content.reveal' );

            if ( $modal.attr( 'data-reveal' ) !== '' ) return true;

            // Copy of how Foundation creates UUIDs
            var uuid = Math.round( Math.pow( 36, 7 ) - Math.random() * Math.pow( 36, 6 ) ).toString( 36 ).slice( 1 ) + '-reveal';

            $modal.attr( 'data-reveal', uuid );

            var $editButton = jQuery( item ).find( 'input[data-repeater-edit]' ).attr( 'data-open', uuid );

            $modal = new Foundation.Reveal( $modal );

        } );

    }

    /**
     * Opens a Modal because Foundation isn't able to do things quite how I need
     * 
     * @param       {Event|String} uuid Either the Event from creating a new Row or a UUID
     * @param       {object}       row  DOM Object of the Row if called from an Event
     *                            
     * @since       1.0.0
     * @return      void
     */
    var openModal = function( uuid, row = undefined ) {

        // Handle newly created Rows
        if ( uuid.type == 'edd-rbm-repeater-add' ) {

            var $row = $( row );

            uuid = $row.find( 'input[data-repeater-edit]' ).data( 'open' );

        }
        
        $( '[data-reveal="' + uuid + '"]' ).foundation( 'open' );

    }

    $( document ).ready( function() {

        initModals();

        // This JavaScript only loads on our custom Page, so we're fine doing this
        var $repeaters = $( '[data-edd-rbm-repeater]' );

        if ( $repeaters.length ) {
            $repeaters.on( 'edd-rbm-repeater-add', initModals );
            $repeaters.on( 'edd-rbm-repeater-add', openModal );
        }

    } );
    
    $( document ).on( 'click touched', '[data-repeater-edit]', function() {
        
        openModal( $( this ).data( 'open' ) );
        
    } );
    
    $( document ).on( 'open.zf.reveal', '.reveal', function() {
        
        init_edd_repeater_colorpickers();
        init_edd_repeater_chosen();
        
    } );

} )( jQuery );
if ( ! HTMLFormElement.prototype.reportValidity ) {

    /**
     * Wait, people use IE and Safari outside of downloading Chrome?
     * 
     * @since       1.0.0
     * @return      void
     */
    HTMLFormElement.prototype.reportValidity = function () {
        
        var error = eddSlack.i18n.validationError,
            valid = true;
        
        // Remove all old Validation Errors
        jQuery( this ).find( '.validation-error' ).remove();
        
        jQuery( this ).find( '.required' ).each( function( index, element ) {
            
            // Reset Custom Validity Message
            element.setCustomValidity( '' );
            
            if ( ! jQuery( element ).closest( 'td' ).hasClass( 'hidden') && 
                jQuery( element ).val().length == 0 ) {
                
                element.setCustomValidity( error );
                jQuery( element ).before( '<span class="validation-error">' + error + '</span>' );
                
                valid = false;
                
            }
            
        } );
        
        if ( ! valid ) {
            
            jQuery( this ).closest( '.reveal-overlay' ).scrollTop( jQuery( this ).find( '.validation-error:first-of-type' ) );
            return valid;
            
        }
        
        return valid;
        
    };
    
};
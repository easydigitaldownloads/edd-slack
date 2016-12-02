<?php
/**
 * Adds SSL-Only Admin Settings
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_OAUTH_Settings {

    /**
	 * EDD_Slack_OAUTH_Settings constructor.
	 *
	 * @since 1.0.0
	 */
    function __construct() {
        
        // Add SSL-only settings for OAUTH
        add_filter( 'edd_slack_settings', array( $this, 'add_oauth_settings' ) );
        
        // Add the OAUTH Registration Button
        add_action( 'edd_slack_oauth_register', array( $this, 'add_oauth_registration_button' ) );
        
        // Grab the OAUTH Key as part of the handshake process
        add_action( 'edd_settings_tab_top_extensions_edd-slack-settings', array( $this, 'store_oauth_token' ) );
        
        // Delete the OAUTH Key
        add_action( 'admin_init', array( $this, 'delete_oauth_token' ) );
        
        // Remove weird duplicate notices
        add_action( 'admin_notices', array( $this, 'remove_duplicate_notices' ) );
        
    }
    
    /**
     * Add our OAUTH Settings Fields only if we have SSL
     * 
     * @param       array $settings EDD Slack Settings Fields
     *                                                 
     * @access      public
     * @since       1.0.0
     * @return      array Modified Settings Fields
     */
    public function add_oauth_settings( $settings ) {
        
        $oauth_settings = array(
            array(
                'type' => 'header',
                'name' => '<h3>' . _x( 'Enable Interactive Buttons and Slash Commands', 'SSL-Only Settings Header', EDD_Slack_ID ),
                'id' => 'edd-slack-ssl-only-header',
            ),
            array(
                'type' => 'text',
                'name' => _x( 'Client ID', 'Client ID Label', EDD_Slack_ID ),
                'id' => 'slack_app_client_id',
                'desc' => sprintf(
                    _x( 'Enter the Client ID found after %screating your Slack App%s.', 'Client ID Help Text', EDD_Slack_ID ),
                    '<a href="//api.slack.com/apps" target="_blank">',
                    '</a>'
                )
            ),
            array(
                'type' => 'text',
                'name' => _x( 'Client Secret', 'Client Secret Label', EDD_Slack_ID ),
                'id' => 'slack_app_client_secret',
                'desc' => sprintf(
                    _x( 'Enter the Client Secret found after %screating your Slack App%s.', 'Client Secret Help Text', EDD_Slack_ID ),
                    '<a href="//api.slack.com/apps" target="_blank">',
                    '</a>'
                )
            ),
            array(
                'type' => 'hook',
                'id' => 'slack_oauth_register',
            )
        );
        
        $settings = array_merge( $settings, $oauth_settings );
        
        return $settings;
        
    }
    
    /**
     * Adds our Button Link to Authorize/Deauthorize the Slack App
     * 
     * @param       array $args EDD Settings API $args
     *                                           
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function add_oauth_registration_button( $args ) {
        
        $client_id = edd_get_option( 'slack_app_client_id' );
        $client_secret = edd_get_option( 'slack_app_client_secret' );
        
        /**
         * In case Scope needs to be changed by 3rd Party Integrations
         *
         * @since 1.0.0
         */
        $scope = apply_filters( 'edd_slack_app_scope', array(
            'chat:write:user',
            'commands',
        ) );
        
        $scope = implode( ',', $scope );
        
        $redirect_uri = urlencode_deep( admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-slack-settings' ) );
        
        $oauth_token = edd_get_option( 'slack_app_oauth_token' );
        
        if ( $client_id && $client_secret ) : 

            if ( ! $oauth_token ) : ?>
            
                <a href="//slack.com/oauth/authorize?client_id=<?php echo $client_id; ?>&scope=<?php echo $scope; ?>&redirect_uri=<?php echo $redirect_uri; ?>" target="_self" class="button button-primary">
                    <?php echo _x( 'Link Slack App', 'OAUTH Register Buton Label', EDD_Slack_ID ); ?>
                </a>

            <?php else : ?>

                <input type="submit" name="edd_slack_app_deauth" class="button" value="<?php echo _x( 'Unlink Slack App', 'OAUTH Deregister Button Label', EDD_Slack_ID ); ?>"/>

            <?php endif; ?>
            
        <?php else : ?>

            <p class="description">
                <?php echo _x( 'Fill out the above fields and Save the Settings to Connect your Slack App to your site.', 'OAUTH Registration Help Text', EDD_Slack_ID ); ?>
            </p>
        
        <?php endif;
        
    }
    
    /**
     * Store the OAUTH Access Token after the Temporary Code is received
     * 
     * @access          public
     * @since           1.0.0
     * @return          void
     */
    public function store_oauth_token() {
        
        echo ( function_exists( 'edd_delete_option' ) ) ? 'exists' : 'does not exist';
        $test = edd_delete_option( 'slack_app_oauth_token' );
        var_dump( $test );
        edd_update_option( 'slack_app_oauth_token', '' );
        
        $options = get_option( 'edd_settings' );
        unset( $options['slack_app_oauth_token'] );
        $options = update_option( 'edd_settings', $options );
        
        var_dump( get_option( 'edd_settings') );
        
        var_dump( edd_get_option( 'slack_app_oauth_token' ) );
        
        // If we need to get an OAUTH Token
        if ( isset( $_GET['code'] ) && ! edd_get_option( 'slack_app_oauth_token' ) ) {
            
            $client_id = edd_get_option( 'slack_app_client_id' );
            $client_secret = edd_get_option( 'slack_app_client_secret' );
        
            $redirect_uri = urlencode_deep( admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-slack-settings' ) );
            
            $oauth_access_url = add_query_arg(
                array(
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'code' => $_GET['code'],
                    'redirect_uri' => $redirect_uri,
                ),
                'oauth.access'
            );
            
            $oauth_request = EDDSLACK()->slack_api->post( 
                $oauth_access_url
            );
            
            if ( $oauth_request->ok == 'true' ) {
                
                $oauth_token = $oauth_request->access_token;
                EDDSLACK()->slack_api->set_oauth_token( $oauth_token );
                
                add_settings_error(
                    'edd-notices',
                    'edd_slack_app_auth',
                    _x( 'Slack App Linked Successfully.', 'EDD Slack App Auth Successful', EDD_Slack_ID ),
                    'updated'
                );
                
            }
            
        }
        
    }
    
    /**
     * Revoke the OAUTH Token
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function delete_oauth_token() {
        
        // If we're deauth-ing
        if ( isset( $_POST['edd_slack_app_deauth'] ) ) {
            
            EDDSLACK()->slack_api->revoke_oauth_token();
            
            add_settings_error(
                'edd-notices',
                'edd_slack_app_deauth',
                _x( 'Slack App Unlinked Successfully.', 'EDD Slack App Deauth Successful', EDD_Slack_ID ),
                'updated'
            );
            
        }
        
    }
    
    /**
     * Remove weird duplicate notices that happen on deauth
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function remove_duplicate_notices() {
        
        global $wp_settings_errors;
        
        echo 'test';
        
        //if ( isset( $_POST['edd_slack_app_deauth'] ) ) {
        
            //array_unique( $wp_settings_errors );
            
        //}
        
    }
    
}
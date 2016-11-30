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
        
        add_filter( 'edd_slack_settings', array( $this, 'add_oauth_settings' ) );
        
        add_action( 'edd_slack_oauth_register', array( $this, 'add_oauth_registration_button' ) );
        
        add_action( 'edd_settings_tab_top_extensions_edd-slack-settings', array( $this, 'store_oauth_token' ) );
        
    }
    
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
    
    public function add_oauth_registration_button( $args ) {
        
        $client_id = edd_get_option( 'slack_app_client_id' );
        $client_secret = edd_get_option( 'slack_app_client_secret' );
        
        $oauth_token = edd_get_option( 'slack_app_oauth_token' );
        
        $redirect_uri = urlencode_deep( admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-slack-settings' ) );
        
        if ( $client_id && $client_secret ) : 

            if ( ! $oauth_token ) : ?>
            
                <a href="//slack.com/oauth/authorize?client_id=<?php echo $client_id; ?>&scope=chat:write:user,commands&redirect_uri=<?php echo $redirect_uri; ?>" target="_self" class="button button-primary">
                    <?php echo _x( 'Link Slack App', 'OAUTH Register Buton Label', EDD_Slack_ID ); ?>
                </a>

            <?php else : ?>

                <?php var_dump( $oauth_token ); ?>

            <?php endif; ?>
            
        <?php else : ?>

            <p class="description">
                <?php echo _x( 'Fill out the above fields and Save the Settings to Connect your Slack App to your site.', 'OAUTH Registration Help Text', EDD_Slack_ID ); ?>
            </p>
        
        <?php endif;
        
    }
    
    public function store_oauth_token() {
        
        if ( isset( $_GET['code'] ) ) {
            
            $oauth_token = edd_update_option( 'slack_app_oauth_token', $_GET['code'] );
            
        }
        
    }
    
}
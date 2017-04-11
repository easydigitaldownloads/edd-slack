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
	 * @var		 EDD_Slack_OAUTH_Settings $admin_notices Allows Admin Notices to be ran when possible despite our Hook
	 * @since	  1.0.0
	 */
	private $admin_notices = array();

	/**
	 * EDD_Slack_OAUTH_Settings constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Add SSL-only settings for OAUTH
		add_filter( 'edd_slack_settings', array( $this, 'add_oauth_settings' ) );
		
		// Display which Triggers support Interactive Notifications
		add_action( 'edd_slack_interactive_notification_support', array( $this, 'add_interactive_button_support_list' ) );
		
		// Add the OAUTH Registration Button
		add_action( 'edd_slack_oauth_register', array( $this, 'add_oauth_registration_button' ) );
		
		// Add the OAUTH Registration Button for Slack Team Invites
		add_action( 'edd_slack_invites_oauth_register', array( $this, 'add_slack_invites_oauth_register' ) );
		
		// Grab the OAUTH Key as part of the handshake process
		add_action( 'admin_init', array( $this, 'store_oauth_token' ) );
		
		// Delete the OAUTH Key
		add_action( 'init', array( $this, 'delete_oauth_token' ) );
		
		// Display Admin Notices
		add_action( 'admin_init', array( $this, 'display_admin_notices' ) );
		
		//add_action( 'admin_init', array( $this, 'test_invite' ) );
		
	}
	
	/**
	 * Add our OAUTH Settings Fields only if we have SSL
	 * 
	 * @param	  array $settings EDD Slack Settings Fields
	 *												 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array Modified Settings Fields
	 */
	public function add_oauth_settings( $settings ) {
		
		$oauth_settings = apply_filters( 'edd_slack_oauth_settings', array(
			array(
				'type' => 'header',
				'name' => '<h3>' . _x( 'Enable Interactive Notifications and Slash Commands', 'Interactive Notifications Settings Header', 'edd-slack' ),
				'id' => 'edd-slack-ssl-only-header',
			),
			array(
				'type' => 'hook',
				'id' => 'slack_interactive_notification_support',
			),
			array(
				'type' => 'text',
				'name' => _x( 'Client ID', 'Client ID Label', 'edd-slack' ),
				'id' => 'slack_app_client_id',
				'desc' => sprintf(
					_x( 'Enter the Client ID found after %screating your Slack App%s.', 'Client ID Help Text', 'edd-slack' ),
					'<a href="//api.slack.com/apps" target="_blank">',
					'</a>'
				)
			),
			array(
				'type' => 'text',
				'name' => _x( 'Client Secret', 'Client Secret Label', 'edd-slack' ),
				'id' => 'slack_app_client_secret',
				'desc' => sprintf(
					_x( 'Enter the Client Secret found after %screating your Slack App%s.', 'Client Secret Help Text', 'edd-slack' ),
					'<a href="//api.slack.com/apps" target="_blank">',
					'</a>'
				)
			),
			array(
				'type' => 'hook',
				'id' => 'slack_oauth_register',
			),
			array(
				'type' => 'text',
				'name' => _x( 'Verification Token', 'Verification Token Label', 'edd-slack' ),
				'id' => 'slack_app_verification_token',
				'desc' => sprintf(
					_x( 'Enter the Verification Token found after %ssetting up your Slack App%s.', 'Verification Token Help Text', 'edd-slack' ),
					'<a href="//api.slack.com/apps" target="_blank">',
					'</a>'
				)
			),
			array(
				'type' => 'text',
				'name' => _x( 'Default Channel for Interactive Notifications', 'Default Channel for Interactive Notifications Label', 'edd-slack' ),
				'id' => 'slack_app_channel_default',
				'desc' => sprintf( _x( "Interactive Notifications don't use the Default Webhook URL, so they need to know which Channel they should default to if one for the Notification isn't defined. If this is left blank, it will default to <code>#%s</code>.", 'Default Channel for Interactive Notifications Help Text', 'edd-slack' ), apply_filters( 'edd_slack_general_channel', 'general' ) ),
				'placeholder' => sprintf( '#%s', apply_filters( 'edd_slack_general_channel', 'general' ) ),
			),
			array(
				'type' => 'text',
				'name' => _x( 'Default Icon Emoji or Image URL for Interactive Notifications', 'Default Icon Emoji or Image URL for Interactive Notifications Label', 'edd-slack' ),
				'id' => 'slack_app_icon_default',
				'desc' => _x( "Interactive Notifications don't use the Default Webhook URL, so they can't utilize the Default Icon Emoji or Image URL you set for the Webhook URL if one for the Notification isn't defined. If this is left blank, it will use the Icon added to your Slack App if one exists.", 'Default Icon Emoji or Image URL for Interactive Notifications Help Text', 'edd-slack' ),
			),
			array(
				'type' => 'header',
				'name' => '<h3>' . _x( 'Enable Auto-Inviting Users to your Slack Team', 'Slack Team Invite Settings Header', 'edd-slack' ),
				'id' => 'edd-slack-slack-team-invite-header',
				'desc' => _x( 'This uses the same Client ID, Client Secret, and Verification Code above, but a different OAUTH Token. This is because it needs special permissions.', 'Slack Team Invite Description', 'edd-slack' ),
			),
			array(
				'type' => 'hook',
				'id' => 'slack_invites_oauth_register',
			),
			array(
				'type' => 'hook',
				'id' => 'slack_invites_oauth_register',
			),
		) );
		
		$settings = array_merge( $settings, $oauth_settings );
		
		return $settings;
		
	}
	
	/**
	 * Show a list of Triggers that support Interactive Notifications
	 * 
	 * @param	  array $args EDD Settings API $args
	 *										  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function add_interactive_button_support_list( $args ) {
		
		$triggers = EDDSLACK()->get_slack_triggers();
		
		$interactive_triggers = apply_filters( 'edd_slack_interactive_triggers', array() );
		
		sort( $interactive_triggers );
		
		if ( ! empty ( $interactive_triggers ) ) {
			
			// Holds HTML representation of Triggers that Support Interactive Notifications
			$supported = array();
			
			foreach ( $triggers as $trigger => $label ) {
				
				if ( in_array( $trigger, $interactive_triggers ) ) {
					
					$supported[] = '<li>' . $label . '</li>';
					
				}
				
			}
			
			ob_start();
			?>

			<ul>
				<?php echo implode( '', $supported ); ?>
			</ul>

			<?php 
			
			$supported_list = ob_get_clean();
			
			printf( _x( 'The following Triggers support Interactive Notifications on your Site: %s', 'Triggers Supporting Interactive Notifications Text', 'edd-slack' ), $supported_list );
			
		}
		else {
			echo _x( 'None of available Triggers on your Site currently provide support for Interactive Notifications, but you will still have access to the included Slash Commands by linking a Slack App!', 'No Triggers Supporting Interactive Notifications Text', 'edd-slack' );
		}
		
	}
	
	/**
	 * Adds our Button Link to Authorize/Deauthorize the Slack App
	 * 
	 * @param	  array $args EDD Settings API $args
	 *										  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
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
			'chat:write:bot',
			'commands',
		) );
		
		$scope = implode( ',', $scope );
		
		$redirect_uri = urlencode_deep( admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-slack-settings' ) );
		
		$oauth_token = edd_get_option( 'slack_app_oauth_token' );
		
		if ( $client_id && $client_secret ) : 

			if ( ! $oauth_token ) : ?>
			
				<a href="//slack.com/oauth/authorize?client_id=<?php echo $client_id; ?>&scope=<?php echo $scope; ?>&redirect_uri=<?php echo $redirect_uri; ?>" target="_self" class="edd-slack-app-auth button button-primary" data-token_type="main">
					<?php echo _x( 'Link Slack App', 'OAUTH Register Buton Label', 'edd-slack' ); ?>
				</a>

			<?php else : ?>

				<input type="submit" name="edd_slack_app_deauth" class="button" value="<?php echo _x( 'Unlink Slack App', 'OAUTH Deregister Button Label', 'edd-slack' ); ?>" data-token_type="main" />

			<?php endif; ?>
			
		<?php else : ?>

			<p class="description">
				<?php echo _x( 'Fill out the above fields and Save the Settings to Connect your Slack App to your site.', 'OAUTH Registration Help Text', 'edd-slack' ); ?>
			</p>
		
		<?php endif;
		
	}
	
	/**
	 * Adds our Button Link to Authorize/Deauthorize the Slack App for Inviting Users to the Team
	 * 
	 * @param	  array $args EDD Settings API $args
	 *										  
	 * @access	  public
	 * @since	  1.1.0
	 * @return	  void
	 */
	public function add_slack_invites_oauth_register( $args ) {
		
		$client_id = edd_get_option( 'slack_app_client_id' );
		$client_secret = edd_get_option( 'slack_app_client_secret' );
		
		/**
		 * Most other scopes are not compatible with "client", but just in case
		 *
		 * @since 1.01.0
		 */
		$scope = apply_filters( 'edd_slack_app_team_invites_scope', array(
			'client',
		) );
		
		$scope = implode( ',', $scope );
		
		$redirect_uri = urlencode_deep( admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-slack-settings' ) );
		
		$oauth_token = edd_get_option( 'slack_app_oauth_token' );
		$granted_client_scope = edd_get_option( 'slack_app_has_client_scope' );
		
		if ( $client_id && $client_secret ) : 

			if ( ! $oauth_token ) : ?>

				<p class="description">
					<?php echo _x( 'You need to link your Slack App above to enable this feature.', 'Slack App not linked Error.', 'edd-slack' ); ?>
				</p>

			<?php elseif ( ! $granted_client_scope ) : ?>
			
				<a href="//slack.com/oauth/authorize?client_id=<?php echo $client_id; ?>&scope=<?php echo $scope; ?>&redirect_uri=<?php echo $redirect_uri; ?>" target="_self" class="edd-slack-app-auth button button-primary" data-token_type="team_invites">
					<?php echo _x( 'Allow Slack App to Invite Users to your Team', 'OAUTH Register Team Invites Buton Label', 'edd-slack' ); ?>
				</a>

			<?php else : ?>

				<input type="submit" name="edd_slack_app_deauth" class="button" value="<?php echo _x( 'Unlink Slack App', 'OAUTH Deregister Button Label', 'edd-slack' ); ?>" data-token_type="team_invites" />

			<?php endif; ?>
			
		<?php else : ?>

			<p class="description">
				<?php echo _x( 'Fill out the above fields and Save the Settings to Connect your Slack App to your site.', 'OAUTH Registration Help Text', 'edd-slack' ); ?>
			</p>
		
		<?php endif;
		
	}
	
	/**
	 * Store the OAUTH Access Token after the Temporary Code is received
	 * 
	 * @access		  public
	 * @since		  1.0.0
	 * @return		  void
	 */
	public function store_oauth_token() {
		
		// If we need to get an OAUTH Token
		// $_GET['state'] is set by the JavaScript for the OAUTH2 Popup
		// $_GET['section'] is set properly by our redirect_uri
		if ( isset( $_GET['code'] ) && 
			isset( $_GET['state'] ) && 
			$_GET['state'] == 'saving' && 
			isset( $_GET['token_type'] ) &&
			isset( $_GET['section'] ) && 
			$_GET['section'] == 'edd-slack-settings' && 
			( ! edd_get_option( 'slack_app_oauth_token' ) && $_GET['token_type'] == 'main' || 
			 ! edd_get_option( 'slack_app_has_client_scope' ) && $_GET['token_type'] == 'team_invites' ) ) {
			
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
				
				if ( $_GET['token_type'] == 'main' ) {
				
					$this->admin_notices[] = array(
						'edd-notices',
						'edd_slack_app_auth',
						_x( 'Slack App Linked Successfully.', 'EDD Slack App Auth Successful', 'edd-slack' ),
						'updated'
					);
					
				}
				else if ( $_GET['token_type'] == 'team_invites' ) {
					
					$granted_client_scope = edd_update_option( 'slack_app_has_client_scope', true );
					
					$this->admin_notices[] = array(
						'edd-notices',
						'edd_slack_app_team_invites_auth',
						_x( 'Slack App Team Invites Enabled Successfully.', 'EDD Slack App Team Invites Auth Successful', 'edd-slack' ),
						'updated'
					);
					
				}
				
			}
			
		}
		
	}
	
	/**
	 * Revoke the OAUTH Token
	 * 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function delete_oauth_token() {
		
		// For some reason we can't hook into admin_init within a production environment. Yeah, I have no idea either
		// It only effects DELETING things from wp_options. Storing works just fine.
		if ( is_admin() ) {
			
			if ( isset( $_POST['edd_slack_app_deauth'] ) ) {

				EDDSLACK()->slack_api->revoke_oauth_token();
				
				$revoked_client_scope = edd_delete_option( 'slack_app_has_client_scope' );
				
				delete_transient( 'edd_slack_channels_list' );

				$this->admin_notices[] = array(
					'edd-notices',
					'edd_slack_app_deauth',
					_x( 'Slack App Unlinked Successfully.', 'EDD Slack App Deauth Successful', 'edd-slack' ),
					'updated'
				);

			}
			
		}
		
	}
	
	/**
	 * Sometimes we need to add Admin Notices when add_settings_error() isn't accessable yet
	 * 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function display_admin_notices() {
			
		foreach( $this->admin_notices as $admin_notice ) {
			
			// Pass array as Function Parameters
			call_user_func_array( 'add_settings_error', $admin_notice );
			
		}
		
		// Clear out Notices
		$this->admin_notices = array();
		
	}
	
}
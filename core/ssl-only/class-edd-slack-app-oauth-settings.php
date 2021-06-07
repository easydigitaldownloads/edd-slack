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
	 * Whether the Slack extension is actually authorized.
	 *
	 * @var boolean $is_authorized
	 */
	private $is_authorized = false;

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
				'id'   => 'edd-slack-ssl-only-header',
			),
			array(
				'type' => 'hook',
				'id'   => 'slack_interactive_notification_support',
			),
			array(
				'type' => 'text',
				'name' => _x( 'Client ID', 'Client ID Label', 'edd-slack' ),
				'id'   => 'slack_app_client_id',
				'desc' => sprintf(
					_x( 'Enter the Client ID found after %screating your Slack App%s.', 'Client ID Help Text', 'edd-slack' ),
					'<a href="//api.slack.com/apps" target="_blank">',
					'</a>'
				)
			),
			array(
				'type' => 'text',
				'name' => _x( 'Client Secret', 'Client Secret Label', 'edd-slack' ),
				'id'   => 'slack_app_client_secret',
				'desc' => sprintf(
					_x( 'Enter the Client Secret found after %screating your Slack App%s.', 'Client Secret Help Text', 'edd-slack' ),
					'<a href="//api.slack.com/apps" target="_blank">',
					'</a>'
				)
			),
			array(
				'type' => 'hook',
				'id'   => 'slack_oauth_register',
			),
			array(
				'type' => 'text',
				'name' => _x( 'Verification Token', 'Verification Token Label', 'edd-slack' ),
				'id'   => 'slack_app_verification_token',
				'desc' => sprintf(
					_x( 'Enter the Verification Token found after %ssetting up your Slack App%s.', 'Verification Token Help Text', 'edd-slack' ),
					'<a href="//api.slack.com/apps" target="_blank">',
					'</a>'
				)
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

			<ul class="edd-slack-triggers">
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
			'users:read',
			'commands',
		) );

		$scope = implode( ',', $scope );

		$redirect_uri = urlencode_deep(
			add_query_arg(
				array(
					'post_type' => 'download',
					'page'      => 'edd-settings',
					'tab'       => 'extensions',
					'section'   => 'edd-slack-settings',
				),
				admin_url( 'edit.php' )
			)
		);

		if ( $client_id && $client_secret ) :

			if ( ! $this->is_authorized() ) :
				$slack_uri = add_query_arg(
					array(
						'client_id'    => $client_id,
						'scope'        => $scope,
						'redirect_uri' => $redirect_uri,
					),
					'https://slack.com/oauth/authorize'
				);
				?>
				<a href="<?php echo esc_url( $slack_uri ); ?>" target="_self" class="edd-slack-app-auth button button-primary" data-token_type="main">
					<?php esc_html_e( 'Link Slack App', 'edd-slack' ); ?>
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
	 * Checks to see if the extension is authorized with Slack.
	 *
	 * @return boolean
	 */
	private function is_authorized() {
		if ( $this->is_authorized ) {
			return $this->is_authorized;
		}
		$oauth_token = edd_get_option( 'slack_app_oauth_token', false );
		if ( ! $oauth_token || '-1' == $oauth_token ) {
			return $this->is_authorized;
		}
		$oauth_request = EDDSLACK()->slack_api->post(
			'auth.test',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $oauth_token,
				),
			)
		);
		if ( ! empty( $oauth_request->ok ) ) {
			$this->is_authorized = true;
		} else {
			edd_delete_option( 'slack_app_oauth_token' );
		}

		return $this->is_authorized;
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

		$granted_client_scope = edd_get_option( 'slack_app_has_client_scope' );

		if ( $client_id && $client_secret ) :

			if ( ! $this->is_authorized() ) : ?>

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
	 * Store the OAUTH Access Token after the Temporary Code is received.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function store_oauth_token() {

		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Return if this is not the EDD Slack Settings screen.
		$section = isset( $_GET['section'] ) && 'edd-slack-settings' === $_GET['section'];
		if ( ! $section ) {
			return;
		}

		// The code provided by Slack.
		$code = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : false;

		// The token type to validate (should be `main` or `team_invites`).
		$token_type = isset( $_GET['token_type'] ) ? sanitize_text_field( $_GET['token_type'] ) : false;

		// Return if the code is missing or the token type is missing.
		if ( ! $code || ! $token_type ) {
			return;
		}

		$redirect_uri = add_query_arg(
			array(
				'post_type' => 'download',
				'page'      => 'edd-settings',
				'tab'       => 'extensions',
				'section'   => 'edd-slack-settings',
			),
			admin_url( 'edit.php' )
		);

		// If we need to get an OAUTH Token
		if ( ( ! $this->is_authorized() && 'main' === $token_type ) || ( ! edd_get_option( 'slack_app_has_client_scope' ) && 'team_invites' === $token_type ) ) {

			$client_id     = edd_get_option( 'slack_app_client_id' );
			$client_secret = edd_get_option( 'slack_app_client_secret' );
			$oauth_request = EDDSLACK()->slack_api->post(
				'oauth.access',
				array(
					'body' => array(
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
						'code'          => $code,
						'redirect_uri'  => $redirect_uri,
					),
				)
			);

			if ( empty( $oauth_request->ok ) ) {
				$redirect_uri = add_query_arg(
					array(
						'edd_slack_oauth' => 'fail',
						'edd_slack_type'  => 'error',
					),
					$redirect_uri
				);

				wp_safe_redirect( $redirect_uri );
				exit;
			}

			$oauth_token = $oauth_request->access_token;
			EDDSLACK()->slack_api->set_oauth_token( $oauth_token );

			if ( 'main' === $token_type ) {

				$redirect_uri = add_query_arg(
					array(
						'edd_slack_oauth' => 'success',
						'edd_slack_type'  => 'updated',
					),
					$redirect_uri
				);

			} elseif ( 'team_invites' === $token_type ) {

				$granted_client_scope = edd_update_option( 'slack_app_has_client_scope', true );
				$redirect_uri         = add_query_arg(
					array(
						'edd_slack_oauth' => 'teams',
						'edd_slack_type'  => 'updated',
					),
					$redirect_uri
				);
			}
			wp_safe_redirect( $redirect_uri );
			exit;
		}
	}

	/**
	 * Revoke the OAUTH Token
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function delete_oauth_token() {

		// For some reason we can't hook into admin_init within a production environment. Yeah, I have no idea either
		// It only effects DELETING things from wp_options. Storing works just fine.
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['edd_slack_app_deauth'] ) ) {
			return;
		}

		$revoked = EDDSLACK()->slack_api->revoke_oauth_token();

		if ( ! $revoked ) {
			$this->admin_notices[] = array(
				'edd-notices',
				'edd_slack_app_deauth',
				_x( 'The Slack App could not be unlinked.', 'EDD Slack App Deauth Not Successful', 'edd-slack' ),
				'error',
			);

			return;
		}

		$revoked_client_scope = edd_delete_option( 'slack_app_has_client_scope' );

		delete_transient( 'edd_slack_channels_list' );

		$this->admin_notices[] = array(
			'edd-notices',
			'edd_slack_app_deauth',
			_x( 'Slack App Unlinked Successfully.', 'EDD Slack App Deauth Successful', 'edd-slack' ),
			'updated',
		);
	}

	/**
	 * Sometimes we need to add Admin Notices when add_settings_error() isn't accessable yet
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function display_admin_notices() {

		$section = ! empty( $_GET['section'] ) && 'edd-slack-settings' === $_GET['section'];
		$message = ! empty( $_GET['edd_slack_oauth'] ) ? $this->get_oauth_message( $_GET['edd_slack_oauth'] ) : false;
		if ( empty( array_filter( $this->admin_notices ) ) && $section && $message ) {
			$this->admin_notices[] = array(
				'edd-notices',
				'edd_slack_app_auth',
				esc_html( $message ),
				esc_attr( $_GET['edd_slack_type'] ),
			);
		}

		foreach ( $this->admin_notices as $admin_notice ) {

			// Pass array as Function Parameters
			call_user_func_array( 'add_settings_error', $admin_notice );

		}

		// Clear out Notices
		$this->admin_notices = array();

	}

	/**
	 * Gets the Oauth message for the admin notice.
	 *
	 * @since 1.1.3
	 * @param string $type The type of message to retrieve.
	 * @return string
	 */
	private function get_oauth_message( $type ) {
		$message = '';
		switch ( $type ) {
			case 'fail':
				$message = __( 'The Slack App authorization failed.', 'edd-slack' );
				break;

			case 'success':
				$message = __( 'Slack App linked successfully.', 'edd-slack' );
				break;

			case 'teams':
				$message = __( 'Slack App Team Invites enabled successfully.', 'edd-slack' );
				break;
		}

		return $message;
	}

}

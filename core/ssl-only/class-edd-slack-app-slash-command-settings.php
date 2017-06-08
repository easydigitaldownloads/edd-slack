<?php
/**
 * Adds SSL-Only Settings for Slash Commands
 *
 * @since 1.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Slash_Commands_Settings {
	
	/**
	 * EDD_Slack_Slash_Commands_Settings constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Updates our Slack Users List and sets/updates our Transient
		add_action( 'admin_init', array( $this, 'get_users' ) );
		
		// Adds our Slash Command Settings
		add_filter( 'edd_slack_oauth_settings', array( $this, 'add_slack_slash_commands_settings' ) );
		
	}
	
	/**
	 * Adds the Slash Command Settings to the OAUTH Section
	 * 
	 * @param		array $oauth_settings OAUTH Settings
	 *                                     
	 * @access		public
	 * @since		1.1.0
	 * @return		array Modified OAUTH Settings
	 */
	public function add_slack_slash_commands_settings( $oauth_settings ) {
		
		$slack_slash_commands_settings = array(
			array(
				'type' => 'header',
				'name' => '<h3>' . _x( 'Slash Command Settings', 'Slack Slash Commands Settings Header', 'edd-slack' ),
				'id' => 'edd-slack-slash-commands-header',
			),
			array(
				'type' => 'rbm_multi_select',
				'name' => _x( 'Restrict Slash Command Usage To:', 'Slash Command Users Label', 'edd-slack' ),
				'id' => 'slack_app_slash_command_users',
				'field_class' => array(
					'edd-slack-multi-select',
					'regular-text',
					'edd-slack-slash-command-users',
				),
				'chosen' => true,
				'options' => $this->get_users(),
				'placeholder' => _x( 'All Slack Team Admins', 'All Slack Team Admins Slash Commands', 'edd-slack' ),
				'std' => array(),
				'desc' => _x( 'If left empty, all Slack Team Admins have access to Slash Commands. Use this field to restrict it to specific Slack Users.', 'Slack Slash Commands Users Description', 'edd-slack' ),
			),
		);
		
		$oauth_settings = array_merge( $oauth_settings, $slack_slash_commands_settings );
		
		return $oauth_settings;
		
	}
	
	/**
	 * Returns all Users from the Slack API
	 * 
	 * @access		public
	 * @since		1.1.0
	 * @return		array Slack Users
	 */
	public function get_users() {
		
		// Don't bother if we don't have an OAUTH Token
		if ( ! edd_get_option( 'slack_app_oauth_token', false ) ) return array();
		
		if ( ! $users_array = maybe_unserialize( get_transient( 'edd_slack_users' ) ) ) {
		
			$result = EDDSLACK()->slack_api->get( 'users.list' );

			$users = $result->members;

			$users_array = array();
			foreach ( $users as $user ) {
				
				// Let's not bother with Deleted Users
				if ( $user->deleted ) continue;
				
				// No need for Slackbot to be in the list
				if ( $user->id == 'USLACKBOT' ) continue;

				$users_array[ $user->id ] = '@' . $user->name . ' (' . $user->real_name . ')';
				
				if ( $user->is_admin ) {
					$users_array[ $user->id ] .= ' - ' . __( 'Admin', 'edd-slack' );
				}

			}
			
			set_transient( 'edd_slack_users', $users_array, DAY_IN_SECONDS );
			
		}
		
		return $users_array;
		
	}
	
}

$integrate = new EDD_Slack_Slash_Commands_Settings();
<?php
/**
 * Adds SSL-Only Settings for Slash Commands
 *
 * @since 1.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only/slash-commands
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
		add_action( 'admin_init', 'edd_slack_get_users' );
		
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
				'type' => 'select',
				'name' => _x( 'Restrict Slash Command Usage To:', 'Slash Command Users Label', 'edd-slack' ),
				'id' => 'slack_app_slash_command_users',
				'field_class' => array(
					'regular-text',
					'edd-slack-slash-command-users',
				),
				'multiple' => true,
				'chosen' => true,
				'options' => edd_slack_get_users(),
				'placeholder' => _x( 'All Slack Team Admins', 'All Slack Team Admins Slash Commands', 'edd-slack' ),
				'std' => array(),
				'desc' => _x( 'If left empty, all Slack Team Admins have access to Slash Commands. Use this field to restrict it to specific Slack Users.', 'Slack Slash Commands Users Description', 'edd-slack' ),
			),
		);
		
		$oauth_settings = array_merge( $oauth_settings, $slack_slash_commands_settings );
		
		return $oauth_settings;
		
	}
	
}

$integrate = new EDD_Slack_Slash_Commands_Settings();
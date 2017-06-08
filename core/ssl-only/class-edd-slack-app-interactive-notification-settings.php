<?php
/**
 * Adds SSL-Only Settings for Interactive Notifications
 *
 * @since 1.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Interactive_Notifications_Settings {
	
	/**
	 * EDD_Slack_Interactive_Notifications_Settings constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Adds our Slash Command Settings
		add_filter( 'edd_slack_oauth_settings', array( $this, 'add_slack_interactive_notifications_settings' ) );
		
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
	public function add_slack_interactive_notifications_settings( $oauth_settings ) {
		
		$slack_interactive_notifications_settings = array(
			array(
				'type' => 'header',
				'name' => '<h3>' . _x( 'Interactive Notification Settings', 'Slack Interactive Notifications Settings Header', 'edd-slack' ),
				'id' => 'edd-slack-interactive-notifications-header',
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
		);
		
		$oauth_settings = array_merge( $oauth_settings, $slack_interactive_notifications_settings );
		
		return $oauth_settings;
		
	}
	
}

$integrate = new EDD_Slack_Interactive_Notifications_Settings();
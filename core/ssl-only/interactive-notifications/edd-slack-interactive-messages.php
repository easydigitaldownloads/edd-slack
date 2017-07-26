<?php
/**
 * Callback Functions for Interactive Messages that aren't dependent on other Integrations
 *
 * @since	  1.1.0
 *
 * @package	EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only/interactive-notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! function_exists( 'edd_slack_interactive_message_edd_version' ) ) {
	
	/**
	 * EDD Slack Rest Change EDD Version
	 * 
	 * @param	  object $choice		name and value from the Interactive Dropdown
	 * @param	  string $response_url	Webhook to send the Response Message to
	 * @param	  object $payload		POST'd data from the Slack Client
	 *														
	 * @since	  1.1.0
	 * @return	  void
	 */
	function edd_slack_interactive_message_edd_version( $choice, $response_url, $payload ) {
		
		$value = $choice->selected_options[0]->value; // This will match the Key in our Transient of EDD Versions with Download URLs
		
		$message = $value;
		
		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'text' => $message,
			)
		);
		
	}
	
}
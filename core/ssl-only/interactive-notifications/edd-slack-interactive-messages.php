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

register_shutdown_function( "fatal_handler" );

function fatal_handler() {
  $errfile = "unknown file";
  $errstr  = "shutdown";
  $errno   = E_CORE_ERROR;
  $errline = 0;

  $error = error_get_last();

  if( $error !== NULL) {
	  
	  ob_start();
	  var_dump( $error );
	  $result = ob_get_clean();

    file_put_contents( EDD_Slack_DIR . 'error.txt', $result );
  }
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
		
		$edd_versions = EDDSLACK()->slack_rest_api->get_edd_versions();
		
		$download_url = $edd_versions[ $value ];
		
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}
		
		if ( ! function_exists( 'show_message' ) ) {
			require_once ABSPATH . '/wp-admin/includes/misc.php';
		}
		
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
		}
		
		$upgrader = new WP_Upgrader();
		
		$test = $upgrader->run( array(
			'package' => $download_url,
			'destination' => EDD_PLUGIN_DIR, // This allows us to change the version of EDD even if it wasn't installed from the WP Repo
			'clear_destination' => true, // Overwrite directory
			'abort_if_destination_exists' => false, // Do not abort, we're overwriting
			'clear_working' => false,
		) );
		
		$message = '';
		
		ob_start();
		var_dump( $test );
		$message .= ob_get_clean();
		$message = ob_get_clean();
		
		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'text' => $message,
			)
		);
		
	}
	
}
<?php
/**
 * EDD Fraud Monitor Integration for the Slack App
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only/integrations/edd-fraud-monitor
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_App_Fraud_Monitor {
	
	/**
	 * EDD_Slack_App_Fraud_Monitor constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		$oauth_token = edd_get_option( 'slack_app_oauth_token', false );
		
		// If we've got a linked Slack App
		if ( $oauth_token && $oauth_token !== '-1' ) {
		
			// Set the new Notification API Endpoint
			add_filter( 'edd_slack_notification_webhook', array( $this, 'override_webhook' ), 10, 4 );

			// Add our Interaction Buttons
			add_filter( 'edd_slack_notification_args', array( $this, 'override_arguments' ), 10, 5 );
			
		}
		
		// Add our Trigger(s) to the Interactive Triggers Array
		add_filter( 'edd_slack_interactive_triggers', array( $this, 'add_support' ), 1, 1 );
		
	}
	
	/**
	 * Override Webhook URL with chat.postMessage for Slack App if appropriate
	 * 
	 * @param	  string $webhook_url	 The Webhook URL provided for the Slack Notification
	 * @param	  string $trigger		 Notification Trigger
	 * @param	  string $notification_id ID used for Notification Hooks
	 * @param	  array  $args			$args Array passed from the original Trigger of the process
	 *															  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  string Altered URL
	 */
	public function override_webhook( $webhook_url, $trigger, $notification_id, $args ) {
		
		if ( $notification_id !== 'rbm' ) return $webhook_url;
		
		// Allow Webhook URL overrides to bail Interactive Notifications
		if ( ( $webhook_url !== '' ) &&
			( $webhook_url !== edd_get_option( 'slack_webhook_default' ) ) ) return $webhook_url;
		
		// If our Trigger doesn't an applicable Trigger, bail
		if ( $trigger !== 'edd_fraud_purchase' ) return $webhook_url;

		return 'chat.postMessage';
		
	}
	
	/**
	 * Override Notification Args for Slack App if appropriate
	 * 
	 * @param	  string $notification_args   Args for creating the Notification
	 * @param	  string $webhook_url		 The Webhook URL provided for the Slack Notification
	 * @param	  string $trigger			 Notification Trigger
	 * @param	  string $notification_id	 ID used for Notification Hooks
	 * @param	  array  $args				$args Array passed from the original Trigger of the process
	 *															  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array  Altered Notification Args
	 */
	public function override_arguments( &$notification_args, $webhook_url, $trigger, $notification_id, $args ) {
		
		if ( $notification_id !== 'rbm' ) return $notification_args;
		
		// Allow Webhook URL overrides to bail Interactive Notifications
		if ( strpos( $webhook_url, 'hooks.slack.com' ) &&
			$webhook_url !== edd_get_option( 'slack_webhook_default' ) ) return $notification_args;
		
		// If our Trigger doesn't an applicable Trigger, bail
		if ( $trigger !== 'edd_fraud_purchase' ) return $notification_args;
		
		$notification_args['attachments'][0]['actions'] = array(
			array(
				'name' => 'valid',
				'text' => __( 'Accept as Valid', 'edd-fm' ),
				'type' => 'button',
				'style' => 'primary',
				'value' => json_encode( $args ),
			),
			array(
				'name' => 'fraud',
				'text' => __( 'Confirm as Fraud', 'edd-fm' ),
				'type' => 'button',
				'style' => 'danger',
				'value' => json_encode( $args ),
			),
		);
		
		/**
		 * Allow the Notification Args for the Slack App Integration to be overriden
		 *
		 * @since 1.0.0
		 */
		$notification_args = apply_filters( 'edd_slack_app_' . $trigger . '_notification_args', $notification_args, $notification_id, $args );
		
		return $notification_args;
		
	}
	
	/**
	 * Add our Trigger(s) to the Interactive Triggers Array
	 * 
	 * @param	  array $interactive_triggers Array holding the Triggers that support Interactive Buttons
	 *																							  
	 * @return	  array Array with our added Triggers
	 */
	public function add_support( $interactive_triggers ) {
		
		$interactive_triggers[] = 'edd_fraud_purchase';
		
		return $interactive_triggers;
		
	}
	
}

$integrate = new EDD_Slack_App_Fraud_Monitor();

if ( ! function_exists( 'edd_slack_interactive_message_edd_fraud_purchase' ) ) {
	
	/**
	 * EDD Slack Rest Possible Fraudulent Purchase Endpoint
	 * 
	 * @param	  object $button	  name and value from the Interactive Button. value should be json_decode()'d
	 * @param	  string $response_url Webhook to send the Response Message to
	 * @param	  object $payload	  POST'd data from the Slack Client
	 *														
	 * @since	  1.0.0
	 * @return	  void
	 */
	function edd_slack_interactive_message_edd_fraud_purchase( $button, $response_url, $payload ) {
		
		$action = $button->name;
		$value = json_decode( $button->value );
		
		// Set depending on the Action
		$message = '';
		
		if ( strtolower( $action ) == 'valid' ) {
			
			// Copied from EDD_Fraud_Monitor()->remove_fraud_flag()
			// This is because it attempts to grab a WP User and in this case, it makes more sense to modify that String to show a Slack User
			// If one day that String can be manipulated, this can be much cleaner

			if ( function_exists( 'edd_delete_order_meta' ) ) {
				edd_delete_order_meta( $value->payment_id, '_edd_maybe_is_fraud' );
				edd_delete_order_meta( $value->payment_id, '_edd_maybe_is_fraud_reason' );
				edd_add_order_meta( $value->payment_id, '_edd_not_fraud', '1' );
			} else {
				delete_post_meta( $value->payment_id, '_edd_maybe_is_fraud' );
				delete_post_meta( $value->payment_id, '_edd_maybe_is_fraud_reason' );
				add_post_meta( $value->payment_id, '_edd_not_fraud', '1' );
			}

			edd_update_payment_status( $value->payment_id );

			// Log a note about possible fraud
			edd_insert_payment_note( $value->payment_id, sprintf( __( 'This payment was cleared as legitimate by %s via %s.', 'edd-slack' ), $payload->user->name, EDDSLACK()->plugin_data['Name'] ) );

			// clear IP from blacklist
			$user_ip   = edd_get_payment_user_ip( $value->payment_id );
			$blacklist =  EDD_Fraud_Monitor()->banned_ips();

			// check if IP in in blacklist
			if ( in_array( $user_ip, $blacklist ) ) {

				// find key which includes the IP address
				$key_to_remove = array_search( $user_ip, $blacklist );
				// unset the key
				if ( isset( $key_to_remove ) ) {
					unset( $blacklist[ $key_to_remove ] );
				}

				// update blacklist
				update_option( '_edd_ip_blacklist', $blacklist );
				
			}
			
			$message = sprintf( _x( "%s has Accepted %s's Payment as Valid", 'Accepted Payment as Valid Response Text', 'edd-slack' ), $payload->user->name, $value->name );
			
		}
		else if ( strtolower( $action ) == 'fraud' ) {

			if ( function_exists( 'edd_delete_order_meta' ) ) {
				edd_delete_order_meta( $value->payment_id, '_edd_maybe_is_fraud' );
				edd_delete_order_meta( $value->payment_id, '_edd_maybe_is_fraud_reason' );
			} else {
				delete_post_meta( $value->payment_id, '_edd_maybe_is_fraud' );
				delete_post_meta( $value->payment_id, '_edd_maybe_is_fraud_reason' );
			}

			edd_update_payment_status( $value->payment_id, 'revoked' );
			
			edd_insert_payment_note( $value->payment_id, sprintf( __( 'This payment was confirmed as fraud by %s via %s.', 'edd-slack' ), $payload->user->name, EDDSLACK()->plugin_data['Name'] ) );
			
			$message = sprintf( _x( "%s has Confirmed %s's Payment as Fraud", 'Confirmed Payment as Fraud Response Text', 'edd-slack' ), $payload->user->name, $value->name );
			
		}
		
		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'text' => $message,
			)
		);
		
	}
	
}

<?php
/**
 * EDD Purchase Limit Integration
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-purchase-limit
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Purchase_Limit {
	
	/**
	 * EDD_Slack_Purchase_Limit constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Add New Triggers
		add_filter( 'edd_slack_triggers', array( $this, 'add_triggers' ) );
		
		// Add new Conditional Fields
		add_filter( 'edd_slack_notification_fields', array( $this, 'add_extra_fields' ) );
		
		add_action( 'edd_complete_purchase', array( $this, 'edd_purchase_limit' ) );
		
		// Inject some Checks before we do Replacements or send the Notification
		add_action( 'edd_slack_before_replacements', array( $this, 'before_notification_replacements' ), 10, 5 );
		
		// Add our own Replacement Strings
		add_filter( 'edd_slack_notifications_replacements', array( $this, 'custom_replacement_strings' ), 10, 5 );
		
		// Add our own Hints for the Replacement Strings
		add_filter( 'edd_slack_text_replacement_hints', array( $this, 'custom_replacement_hints' ), 10, 3 );
		
	}
	
	/**
	 * Add our Triggers
	 * 
	 * @param	  array $triggers EDD Slack Triggers
	 *										
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array Modified EDD Slack Triggers
	 */
	public function add_triggers( $triggers ) {

		$triggers['edd_purchase_limit'] = _x( 'Purchase Limit Reached', 'Purchase Limit Reached Trigger', 'edd-slack' );

		return $triggers;

	}
	
	/**
	 * Conditionally Showing Fields within the Notification Repeater works by adding the Trigger as a HTML Class Name
	 * 
	 * @param	  array $repeater_fields Notification Repeater Fields
	 *												  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array Notification Repeater Fields
	 */
	public function add_extra_fields( $repeater_fields ) {
		
		// Make the Download Field Conditionally shown for our Triggers
		$repeater_fields['download']['field_class'][] = 'edd_purchase_limit';
		$repeater_fields['exclude_download']['field_class'][] = 'edd_purchase_limit';
		
		return $repeater_fields;
		
	}
	
	/**
	 * Fires on Payment Completion, checking for Purchase Limits being reached
	 * 
	 * @param	  integer $payment_id Payment ID
	 *										  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_purchase_limit( $payment_id ) {
		
		$customer_id = get_post_meta( $payment_id, '_edd_payment_customer_id', true );
		$customer = new EDD_Customer( $customer_id );
		
		$scope = edd_get_option( 'edd_purchase_limit_scope' ) ? edd_get_option( 'edd_purchase_limit_scope' ) : 'site-wide';
		
		// Basic payment meta
		$payment_meta = edd_get_payment_meta( $payment_id );
		
		// Cart details
		$cart_items = edd_get_payment_meta_cart_details( $payment_id );
		
		// FIX CART
		
		foreach ( $cart_items as $item ) {
			
			if ( ! isset( $item['item_number']['options']['price_id'] ) ) $item['item_number']['options']['price_id'] = null;
			
			// Gives us access to the public methods for the EDD_Download class
			$download = new EDD_Download( $item['id'] );
			
			if ( $scope == 'site-wide' ) {
			
				// Total Purchases for this Item including the just now processed one
				$purchases = edd_pl_get_file_purchases( $item['id'], $item['item_number']['options']['price_id'] );
				
			}
			else {
				
				// Only check the purchases for the current User
				if ( is_user_logged_in() ) {
					// Doesn't properly take into account Price ID.
					// https://github.com/easydigitaldownloads/EDD-Purchase-Limit/issues/28
					$purchases = edd_pl_get_user_purchase_count( get_current_user_id(), $item['id'], $item['item_number']['options']['price_id'] );
				}
				else {
					$purchases = 0; // Failsafe
				}
				
			}
			
			$purchase_limit = edd_pl_get_file_purchase_limit( $item['id'], $download->get_type(), $item['item_number']['options']['price_id'] );
			
			// If the Purchase Limit for the Item within the Cart has been met
			if ( $purchases == $purchase_limit ) {
			
				do_action( 'edd_slack_notify', 'edd_purchase_limit', array(
					'user_id' => $customer->user_id, // If the User isn't a proper WP User, this will be 0
					'name' => $customer->name,
					'email' => $customer->email,
					'download_id' => $item['id'],
					'price_id' => $item['item_number']['options']['price_id'],
					'purchase_limit' => $purchase_limit,
				) );
				
			}
			
		}
		
	}
	
	/**
	 * Inject some checks on whether or not to bail on the Notification
	 * 
	 * @param	  object  $post			WP_Post Object for our Saved Notification Data
	 * @param	  array   $fields		  Fields used to create the Post Meta
	 * @param	  string  $trigger		 Notification Trigger
	 * @param	  string  $notification_id ID Used for Notification Hooks
	 * @param	  array   $args			$args Array passed from the original Trigger of the process
	 *			  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function before_notification_replacements( $post, $fields, $trigger, $notification_id, &$args ) {
		
		if ( $notification_id == 'rbm' ) {
		
			$args = wp_parse_args( $args, array(
				'user_id' => 0,
				'name' => '',
				'email' => '',
				'bail' => false,
			) );
			
			if ( $trigger == 'edd_purchase_limit' ) {
				
				$download = EDDSLACK()->notification_integration->check_for_price_id( $fields['download'] );
				
				$download_id = $download['download_id'];
				$price_id = $download['price_id'];

				// Download doesn't match our Notification, bail
				if ( $download_id !== 'all' && (int) $download_id !== $args['download_id'] ) {
					$args['bail'] = true;
					return false;
				}
				
				// Price ID doesn't match our Notification, bail
				if ( $price_id !== null && $price_id !== $args['price_id'] ) {
					$args['bail'] = true;
					return false;
				}

			}
			
		}
		
	}
	
	/**
	 * Based on our Notification ID and Trigger, use some extra Replacement Strings
	 * 
	 * @param	  array  $replacements	Notification Fields to check for replacements in
	 * @param	  array  $fields		  Fields used to create the Post Meta
	 * @param	  string $trigger		 Notification Trigger
	 * @param	  string $notification_id ID used for Notification Hooks
	 * @param	  array  $args			$args Array passed from the original Trigger of the process
	 * 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array  Replaced Strings within each Field
	 */
	public function custom_replacement_strings( $replacements, $fields, $trigger, $notification_id, $args ) {

		if ( $notification_id == 'rbm' ) {

			switch ( $trigger ) {
					
				case 'edd_purchase_limit':
					
					$replacements['%download%'] = get_the_title( $args['download_id'] );
					if ( edd_has_variable_prices( $args['download_id'] ) && false !== $args['price_id'] ) {
						$replacements['%download%'] .= ' - ' . edd_get_price_option_name( $args['download_id'], $args['price_id'] );
					}
					
					$replacements['%purchase_limit%'] = $args['purchase_limit'];
					
					break;
					
				default:
					break;

			}
			
		}
		
		return $replacements;
		
	}
	
	/**
	 * Add Replacement String Hints for our Custom Trigger
	 * 
	 * @param	  array $hints		 The main Hints Array
	 * @param	  array $user_hints	General Hints for a User. These apply to likely any possible Trigger
	 * @param	  array $payment_hints Payment-Specific Hints
	 *													
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array The main Hints Array
	 */
	public function custom_replacement_hints( $hints, $user_hints, $payment_hints ) {
		
		$purchase_limit_hints = array(
			'%download%' => sprintf( _x( 'The %s that has reached its Purchase Limit', '%download% Hint Text', 'edd-slack' ), edd_get_label_singular() ),
			'%purchase_limit%' => sprintf( _x( 'The Purchase Limit for the %s', '%purchase_limit% Hint Text', 'edd-slack' ), edd_get_label_singular() ),
		);
		
		$hints['edd_purchase_limit'] = array_merge( $user_hints, $purchase_limit_hints );
		
		return $hints;
		
	}
	
}

$integrate = new EDD_Slack_Purchase_Limit();
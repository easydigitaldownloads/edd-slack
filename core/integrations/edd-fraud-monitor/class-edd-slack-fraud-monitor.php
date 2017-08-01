<?php
/**
 * EDD Fraud_Monitor Integration
 *
 * @since 1.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-fraud-monitor
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Fraud_Monitor {
	
	/**
	 * EDD_Slack_Fraud_Monitor constructor.
	 *
	 * @since 1.1.0
	 */
	function __construct() {
		
		// Add New Comment Trigger
		add_filter( 'edd_slack_triggers', array( $this, 'add_triggers' ) );
		
		// Add new Conditional Fields for the Comment Trigger
		add_filter( 'edd_slack_notification_fields', array( $this, 'add_extra_fields' ) );
		
		// Fires when a potentially Fraudulent Payment is detected
		add_action( 'edd_insert_payment', array( $this, 'check_for_fraud_on_payment' ), 10, 2 );
		
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
	 * @since	  1.1.0
	 * @return	  array Modified EDD Slack Triggers
	 */
	public function add_triggers( $triggers ) {

		$triggers['edd_fraud_purchase'] = _x( 'Suspected Fraudulent Purchase', 'Suspected Fraudulent Purchase Trigger', 'edd-slack' );

		return $triggers;

	}
	
	/**
	 * Conditionally Showing Fields within the Notification Repeater works by adding the Trigger as a HTML Class Name
	 * 
	 * @param	  array $repeater_fields Notification Repeater Fields
	 *												  
	 * @access	  public
	 * @since	  1.1.0
	 * @return	  array Notification Repeater Fields
	 */
	public function add_extra_fields( $repeater_fields ) {
		
		$repeater_fields['download']['field_class'][] = 'edd_fraud_purchase';
		$repeater_fields['exclude_download']['field_class'][] = 'edd_fraud_purchase';
		
		return $repeater_fields;
		
	}
	
	/**
	 * Send a Slack Notification when a Payment is suspected of Fraud
	 * 
	 * @param		integer $payment_id   The Payment ID to check for fraud on
	 * @param		array   $payment_data The Payment Data from the insertion of the payment
	 *                                                                          
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function check_for_fraud_on_payment( $payment_id, $payment_data ) {

		if ( ! empty( $_POST['edd-gateway'] ) && 'manual_purchases' == $_POST['edd-gateway'] ) {
			return; // Never check for fraud on manual purchases
		}

		$fraud_check = new EDD_Fraud_Monitor_Check( $payment_id );
		if ( true === $fraud_check->is_fraud ) {
			
			$customer_id = get_post_meta( $payment_id, '_edd_payment_customer_id', true );
			$customer = new EDD_Customer( $customer_id );
			
			// Cart details
			$cart_items = edd_get_payment_meta_cart_details( $payment_id );

			do_action( 'edd_slack_notify', 'edd_fraud_purchase', array(
				'user_id' => $customer->user_id, // If the User isn't a proper WP User, this will be 0
				'payment_id' => $payment_id,
				'name' => $customer->name,
				'email' => $customer->email,
				'discount_code' => $payment_data['user_info']['discount'],
				'ip_address' => get_post_meta( $payment_id, '_edd_payment_user_ip', true ),
				'cart' => $cart_items,
				'subtotal' => edd_get_payment_subtotal( $payment_id ),
				'total' => edd_get_payment_amount( $payment_id ),
			) );
			
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
	 * @since	  1.1.0
	 * @return	  void
	 */
	public function before_notification_replacements( $post, $fields, $trigger, $notification_id, &$args ) {
		
		if ( $notification_id == 'rbm' ) {
		
			$args = wp_parse_args( $args, array(
				'user_id' => 0,
				'download_id' => 0,
				'payment_id' => 0,
				'bail' => false,
			) );
			
			if ( $trigger == 'edd_fraud_purchase' ) {
				
				$download = EDDSLACK()->notification_integration->check_for_price_id( $fields['download'] );
				
				$download_id = $download['download_id'];
				$price_id = $download['price_id'];
				
				// Cart doesn't match have our Download ID, bail
				if ( $download_id !== 'all' && ! array_key_exists( $download_id, $args['cart'] ) ) {
					$args['bail'] = true;
					return false;
				}
				
				// Cart doesn't have our Price ID, bail
				if ( $price_id !== null ) {
					
					if ( $price_id !== $args['cart'][ $download_id ]['options']['price_id'] ) {
						$args['bail'] = true;
						return false;
					}
					
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
	 * @since	  1.1.0
	 * @return	  array  Replaced Strings within each Field
	 */
	public function custom_replacement_strings( $replacements, $fields, $trigger, $notification_id, $args ) {

		if ( $notification_id == 'rbm' ) {

			switch ( $trigger ) {

				case 'edd_fraud_purchase':
					
					// Display a nicer message in the event of no Discount Code being used
					if ( $args['discount_code'] == 'none' ) {
						$args['discount_code'] = _x( 'No Discount Code Applied', 'No Discount Code Applied Text', 'edd-slack' );
					}
					
					$replacements['%discount_code%'] = $args['discount_code'];
					$replacements['%ip_address%'] = $args['ip_address'];
					$replacements['%subtotal%'] = edd_currency_filter( number_format( $args['subtotal'], 2 ) );
					$replacements['%total%'] = edd_currency_filter( number_format( $args['total'], 2 ) );
					
					$payment_link = add_query_arg( 'id', $args['payment_id'], admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details' ) );
					
					$replacements['%payment_link%'] = '<' . $payment_link . '|' . _x( 'View Payment Details', 'View Payment Details Link', 'edd-slack' ) . '>'; // No function to get this?
					
					$replacements['%cart%'] = '';
					foreach ( $args['cart'] as $post_id => $item_number ) {
						
						// If it is not a variable download
						if ( ! edd_has_variable_prices( $post_id ) ) {
							
							$replacements['%cart%'] .= "&bull; " . get_the_title( $post_id ) . "\n";
							$replacements['%cart%'] .= "\t&bull; " . edd_currency_filter( edd_get_download_price( $post_id ) ) . "\n";
							
						}
						else {
							
							$replacements['%cart%'] .= "&bull; " . get_the_title( $post_id ) . "\n";
							$replacements['%cart%'] .= "\t&bull; " . edd_get_price_option_name( $post_id, $item_number['options']['price_id'] ) . " - " . edd_currency_filter( edd_get_price_option_amount( $post_id, $item_number['options']['price_id'] ) ) . "\n";
							
						}
						
					}
					
					// This shouldn't happen, but I guess you never know
					if ( empty( $replacements['%cart%'] ) ) {
						$replacements['%cart%'] = _x( 'There was nothing in the Cart', 'Empty Cart Replacement Text', 'edd-slack' );
					}
					
					$replacements['%fraud_reason%'] = get_post_meta( $args['payment_id'], '_edd_maybe_is_fraud_reason', true );
					
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
	 * @since	  1.1.0
	 * @return	  array The main Hints Array
	 */
	public function custom_replacement_hints( $hints, $user_hints, $payment_hints ) {
		
		$fraud_hints = array(
			'%fraud_reason%' => _x( 'The reason(s) a Payment was detected as Fraud', '%fraud_reason% Hint Text', 'edd-slack' ),
		);
		
		$hints['edd_fraud_purchase'] = array_merge( $user_hints, $payment_hints, $fraud_hints );
		
		return $hints;
		
	}
	
}

$integrate = new EDD_Slack_Fraud_Monitor();
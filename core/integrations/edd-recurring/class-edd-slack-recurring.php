<?php
/**
 * EDD Recurring Integration
 *
 * @since 1.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-recurring
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Recurring {
	
	/**
	 * EDD_Slack_Recurring constructor.
	 *
	 * @since 1.1.0
	 */
	function __construct() {
		
		// Add New Comment Trigger
		add_filter( 'edd_slack_triggers', array( $this, 'add_triggers' ) );
		
		// Add new Conditional Fields for the Comment Trigger
		add_filter( 'edd_slack_notification_fields', array( $this, 'add_extra_fields' ) );
		
		// Fires when a Recurring Subscription is created
		add_action( 'edd_subscription_post_create', array( $this, 'edd_subscription_post_create' ), 10, 2 );
		
		// Fires when a Recurring Subscription is cancelled
		add_action( 'edd_subscription_cancelled', array( $this, 'edd_subscription_cancelled' ), 10, 2 );
		
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

		$triggers['edd_subscription_post_create'] = _x( 'New Subscription Created', 'New Subscription Created Trigger', 'edd-slack' );
		$triggers['edd_subscription_cancelled'] = _x( 'Subscription Cancelled', 'Subscription Cancelled Trigger', 'edd-slack' );

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
		
		$repeater_fields['download']['field_class'][] = 'edd_subscription_post_create';
		$repeater_fields['download']['field_class'][] = 'edd_subscription_cancelled';
		
		$repeater_fields['exclude_download']['field_class'][] = 'edd_subscription_post_create';
		$repeater_fields['exclude_download']['field_class'][] = 'edd_subscription_cancelled';
		
		return $repeater_fields;
		
	}
	
	/**
	 * Send a Slack Notification when a Subscription is Created
	 * 
	 * @param		integer $subscription_id Subscription ID
	 * @param		array   $args            Subscription Args
	 *                                               
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function edd_subscription_post_create( $subscription_id, $args ) {
		
		$customer = new EDD_Customer( $args['customer_id'] );
		
		// Cart details
		$cart_items = edd_get_payment_meta_cart_details( $args['parent_payment_id'] );
		
		// FIX CART
		
		$price_id = null;
		foreach ( $cart_items as $item ) {
			
			if ( edd_has_variable_prices( $item['id'] ) ) {
				
				$price_id = $item['item_number']['options']['price_id'];
				break;
				
			}
			
		}
		
		// Need this for an accurate representation of Times Billed
		$subscription = new EDD_Subscription( $subscription_id );
			
		do_action( 'edd_slack_notify', 'edd_subscription_post_create', array(
			'subscription_id' => $subscription_id,
			'user_id' => $customer->user_id, // Subscriptions require a Log in, regardless of EDD Settings
			'name' => $customer->name,
			'email' => $customer->email,
			'period' => $args['period'], // Default empty string
			'initial_amount' => $args['initial_amount'], // Default empty string
			'recurring_amount' => $args['recurring_amount'], // Default empty string
			'times_billed' => $subscription->get_times_billed(),
			'times_to_bill' => $subscription->bill_times, // Defaults to 0, which is "Until cancelled"
			'parent_payment_id' => $args['parent_payment_id'], // Default 0
			'download_id' => $args['product_id'], // Default 0
			'price_id' => $price_id,
			'created' => $args['created'], // Default empty string
			'expiration' => $args['expiration'], // Default empty string
			'status' => $args['status'], // Default empty string
			'profile_id' => $args['profile_id'], // Default empty string
		) );
		
	}
	
	/**
	 * Send a Slack Notification when a Subscription is Cancelled
	 * 
	 * @param		integer $subscription_id Subscription ID
	 * @param		object  $subscription    EDD_Subscription Object
	 *                                                   
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function edd_subscription_cancelled( $subscription_id, $subscription ) {
		
		$customer = new EDD_Customer( $subscription->customer_id );
		
		// Cart details
		$cart_items = edd_get_payment_meta_cart_details( $subscription->parent_payment_id );
		
		// FIX CART
		
		$price_id = null;
		foreach ( $cart_items as $item ) {
			
			if ( edd_has_variable_prices( $item['id'] ) ) {
				
				$price_id = $item['item_number']['options']['price_id'];
				break;
				
			}
			
		}
		
		do_action( 'edd_slack_notify', 'edd_subscription_cancelled', array(
			'subscription_id' => $subscription_id,
			'user_id' => $customer->user_id, // Subscriptions require a Log in, regardless of EDD Settings
			'name' => $customer->name,
			'email' => $customer->email,
			'period' => $subscription->period, // Default empty string
			'initial_amount' => $subscription->initial_amount, // Default empty string
			'recurring_amount' => $subscription->recurring_amount, // Default empty string
			'times_billed' => $subscription->get_times_billed(),
			'times_to_bill' => $subscription->bill_times, // Defaults to 0, which is "Until cancelled"
			'parent_payment_id' => $subscription->parent_payment_id, // Default 0
			'download_id' => $subscription->product_id, // Default 0
			'price_id' => $price_id,
			'created' => $subscription->created, // Default empty string
			'expiration' => $subscription->expiration, // Default empty string
			'status' => $subscription->status, // Default empty string
			'profile_id' => $subscription->profile_id, // Default empty string
		) );
		
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
				'subscription_id' => 0,
				'download_id' => 0,
				'parent_payment_id' => 0,
				'bail' => false,
			) );
			
			if ( $trigger == 'edd_subscription_post_create' ||
				$trigger == 'edd_subscription_cancelled'
			   ) {
				
				$download = EDDSLACK()->notification_integration->check_for_price_id( $fields['download'] );
				
				$download_id = $download['download_id'];
				$price_id = $download['price_id'];
				
				// Download Subscribed to doesn't match our Notification, bail
				if ( $download_id !== 'all' && $download_id !== $args['download_id'] ) {
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
	 * @since	  1.1.0
	 * @return	  array  Replaced Strings within each Field
	 */
	public function custom_replacement_strings( $replacements, $fields, $trigger, $notification_id, $args ) {

		if ( $notification_id == 'rbm' ) {

			switch ( $trigger ) {

				case 'edd_subscription_post_create':
				case 'edd_subscription_cancelled':
					
					$replacements['%download%'] = get_the_title( $args['download_id'] );
					
					if ( edd_has_variable_prices( $args['download_id'] ) ) {
						$replacements['%download%'] .= ' - ' . edd_get_price_option_name( $args['download_id'], $args['price_id'] );
					}
					
					// Array of Key=>Value
					$periods = EDD_Recurring()->periods();
					$replacements['%period%'] = $periods[ $args['period'] ];
					
					$date_format = get_option( 'date_format', 'F j, Y' );
					$replacements['%created%'] = date_i18n( $date_format, strtotime( $args['created'] ) );
					$replacements['%expiration%'] = date_i18n( $date_format, strtotime( $args['expiration'] ) );
					
					$replacements['%times_billed%'] = $args['times_billed'];
					$replacements['%times_to_bill%'] = ( $args['times_to_bill'] == 0 ) ? __( 'Until cancelled', 'edd-recurring' ) : $args['times_to_bill'];
					
					$replacements['%initial_amount%'] = edd_currency_filter( number_format( $args['initial_amount'], 2 ) );
					$replacements['%recurring_amount%'] = edd_currency_filter( number_format( $args['recurring_amount'], 2 ) );
					
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
		
		$recurring_hints = array(
			'%download%' => sprintf( _x( 'The %s the Subscription is for', '%download% Hint Text', 'edd-slack' ), edd_get_label_singular() ),
			'%period%' => _x( 'The Subscription Period', '%period% Hint Text', 'edd-slack' ),
			'%created%' => _x( 'The date the Subscription started', '%created% Hint Text', 'edd-slack' ),
			'%expiration%' => _x( 'The date the Subscription ends', '%expiration% Hint Text', 'edd-slack' ),
			'%initial_amount%' => _x( 'The initial Subscription cost', '%initial_amount% Hint Text', 'edd-slack' ),
			'%recurring_amount%' => _x( 'The cost of the Subscription each time it is renewed', '%recurring_amount% Hint Text', 'edd-slack' ),
			'%times_billed%' => _x( 'Number of times the Subscription has been billed', '%times_billed% Hint Text', 'edd-slack' ),
			'%times_to_bill%' => _x( 'Number of times the Subscription will be billed in total', '%times_to_bill% Hint Text', 'edd-slack' ),
		);
		
		$hints['edd_subscription_post_create'] = array_merge( $user_hints, $recurring_hints );
		
		// Change the hint text slightly for Cancellation
		// Overall they do the same thing
		$recurring_hints['%expiration%'] = _x( 'The date the Subscription would have ended', '%expiration% Hint Text', 'edd-slack' );
		$recurring_hints['%times_to_bill%'] = _x( 'Number of times the Subscription would have been billed in total', '%times_to_bill% Hint Text', 'edd-slack' );
		
		$hints['edd_subscription_cancelled'] = array_merge( $user_hints, $recurring_hints );
		
		return $hints;
		
	}
	
}

$integrate = new EDD_Slack_Recurring();
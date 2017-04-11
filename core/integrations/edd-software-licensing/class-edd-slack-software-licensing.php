<?php
/**
 * EDD Software Licensing Integration
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-software-licensing
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Software_Licensing {
	
	/**
	 * EDD_Slack_Software_Licensing constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Add New Triggers
		add_filter( 'edd_slack_triggers', array( $this, 'add_triggers' ) );
		
		// Add new Conditional Fields
		add_filter( 'edd_slack_notification_fields', array( $this, 'add_extra_fields' ) );
		
		// Fires when a License is Generated
		add_action( 'edd_sl_store_license', array( $this, 'edd_sl_store_license' ), 10, 4 );
		
		// Fires when a License is Activated
		add_action( 'edd_sl_activate_license', array( $this, 'edd_sl_activate_license' ), -999, 2 );
		
		// Fires when a License is Deactivated
		add_action( 'edd_sl_deactivate_license', array( $this, 'edd_sl_deactivate_license' ), -999, 2 );
		
		// Fires when a License is Upgraded
		add_action( 'edd_sl_license_upgraded', array( $this, 'edd_sl_license_upgraded' ), 10, 2 );
		
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

		$triggers['edd_sl_store_license'] = _x( 'New License Key Generated', 'New License Key Generated Trigger', 'edd-slack' );
		$triggers['edd_sl_activate_license'] = _x( 'License Key Activated', 'License Key Activated Trigger', 'edd-slack' );
		$triggers['edd_sl_deactivate_license'] = _x( 'License Key Deactivated', 'License Key Deactivated Trigger', 'edd-slack' );
		$triggers['edd_sl_license_upgraded'] = _x( 'License Upgraded', 'License Upgraded Trigger', 'edd-slack' );

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
		$repeater_fields['download']['field_class'][] = 'edd_sl_store_license';
		$repeater_fields['download']['field_class'][] = 'edd_sl_activate_license';
		$repeater_fields['download']['field_class'][] = 'edd_sl_deactivate_license';
		
		return $repeater_fields;
		
	}
	
	/**
	 * Send a Slack Notification whenever a License Key is Generated. This does not trigger for Upgrades or Renewals.
	 * 
	 * @param	  integer $license_id  License ID
	 * @param	  integer $download_id Post ID of the associated Download
	 * @param	  integer $payment_id  Payment ID
	 * @param	  string  $type		'default' for Single, 'bundle' for Bundle
	 *																	  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_sl_store_license( $license_id, $download_id, $payment_id, $type ) {
		
		// This is the EDD Customer ID. This is not necessarily the same as the WP User ID
		$customer_id = get_post_meta( $payment_id, '_edd_payment_customer_id', true );
		$customer = new EDD_Customer( $customer_id );
		
		do_action( 'edd_slack_notify', 'edd_sl_store_license', array(
			'user_id' => $customer->user_id, // If the User isn't a proper WP User, this will be 0
			'name' => $customer->name,
			'email' => $customer->email,
			'license_id' => $license_id,
			'license_key' => edd_software_licensing()->get_license_key( $license_id ),
			'download_id' => $download_id,
			'price_id' => edd_software_licensing()->get_price_id( $license_id ),
			'expiration' => get_post_meta( $license_id, '_edd_sl_expiration', true ),
			'license_limit' => edd_software_licensing()->license_limit( $license_id ),
		) );
		
	}
	
	/**
	 * Fires when a License is activated
	 * 
	 * @param	  integer $license_id  License ID
	 * @param	  string  $download_id Download ID
	 *										  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_sl_activate_license( $license_id, $download_id ) {
		
		// We need the Payment ID to get accurate Customer Data
		$payment_id = get_post_meta( $license_id, '_edd_sl_payment_id', true );
		
		// This is the EDD Customer ID. This is not necessarily the same as the WP User ID
		$customer_id = get_post_meta( $payment_id, '_edd_payment_customer_id', true );
		$customer = new EDD_Customer( $customer_id );
		
		do_action( 'edd_slack_notify', 'edd_sl_activate_license', array(
			'user_id' => $customer->user_id, // If the User isn't a proper WP User, this will be 0
			'name' => $customer->name,
			'email' => $customer->email,
			'license_id' => $license_id,
			'license_key' => edd_software_licensing()->get_license_key( $license_id ),
			'download_id' => $download_id,
			'price_id' => edd_software_licensing()->get_price_id( $license_id ),
			'expiration' => get_post_meta( $license_id, '_edd_sl_expiration', true ),
			'active_site' => $_GET['url'], // EDD_SL_License has some methods to get this, but they are private and have the potential of giving us invalid data if URLs get filtered out. This ensures the shown URL is the one being activated, not just the last one in the stack
			'site_count' => edd_software_licensing()->get_site_count( $license_id ),
			'license_limit' => edd_software_licensing()->license_limit( $license_id ),
		) );
		
	}
	
	/**
	 * Fires when a License is deactivated
	 * 
	 * @param	  integer $license_id  License ID
	 * @param	  string  $download_id Download ID
	 *										  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_sl_deactivate_license( $license_id, $download_id ) {
		
		// We need the Payment ID to get accurate Customer Data
		$payment_id = get_post_meta( $license_id, '_edd_sl_payment_id', true );
		
		// This is the EDD Customer ID. This is not necessarily the same as the WP User ID
		$customer_id = get_post_meta( $payment_id, '_edd_payment_customer_id', true );
		$customer = new EDD_Customer( $customer_id );
		
		do_action( 'edd_slack_notify', 'edd_sl_deactivate_license', array(
			'user_id' => $customer->user_id, // If the User isn't a proper WP User, this will be 0
			'name' => $customer->name,
			'email' => $customer->email,
			'license_id' => $license_id,
			'license_key' => edd_software_licensing()->get_license_key( $license_id ),
			'download_id' => $download_id,
			'price_id' => edd_software_licensing()->get_price_id( $license_id ),
			'expiration' => get_post_meta( $license_id, '_edd_sl_expiration', true ),
			'site_count' => edd_software_licensing()->get_site_count( $license_id ),
			'license_limit' => edd_software_licensing()->license_limit( $license_id ),
		) );
		
	}
	
	/**
	 * Send a Slack Notification when a User Upgrades their License
	 * 
	 * @param	  integer $license_id License ID of the License being Upgraded
	 * @param	  array   $args	  Upgrade Arguments
	 *										  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_sl_license_upgraded( $license_id, $args ) {
		
		// This is the EDD Customer ID. This is not necessarily the same as the WP User ID
		$customer_id = get_post_meta( $args['payment_id'], '_edd_payment_customer_id', true );
		$customer = new EDD_Customer( $customer_id );
		
		do_action( 'edd_slack_notify', 'edd_sl_license_upgraded', array(
			'user_id' => $customer->user_id, // If the User isn't a proper WP User, this will be 0
			'name' => $customer->name,
			'email' => $customer->email,
			'license_id' => $license_id,
			'license_key' => edd_software_licensing()->get_license_key( $license_id ),
			'download_id' => $args['download_id'],
			'upgrade_price_id' => $args['upgrade_price_id'], // Variable Download ID for the Upgrade
			'old_download_id' => $args['old_download_id'],
			'old_price_id' => $args['old_price_id'], // Variable Download ID for the original Download
			'expiration' => get_post_meta( $license_id, '_edd_sl_expiration', true ),
			'license_limit' => edd_software_licensing()->license_limit( $license_id ),
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
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function before_notification_replacements( $post, $fields, $trigger, $notification_id, &$args ) {
		
		if ( $notification_id == 'rbm' ) {
		
			$args = wp_parse_args( $args, array(
				'license_id' => 0,
				'download_id' => 0,
				'payment_id' => 0,
				'bail' => false,
			) );
			
			if ( $trigger == 'edd_sl_store_license' ||
				$trigger == 'edd_sl_activate_license' ||
				$trigger == 'edd_sl_deactivate_license' ) {
				
				$download = EDDSLACK()->notification_integration->check_for_price_id( $fields['download'] );
				
				$download_id = $download['download_id'];
				$price_id = $download['price_id'];
				
				// Download commented on doesn't match our Notification, bail
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
	 * @since	  1.0.0
	 * @return	  array  Replaced Strings within each Field
	 */
	public function custom_replacement_strings( $replacements, $fields, $trigger, $notification_id, $args ) {

		if ( $notification_id == 'rbm' ) {

			switch ( $trigger ) {

				case 'edd_sl_store_license':
				case 'edd_sl_activate_license':
				case 'edd_sl_deactivate_license':
				case 'edd_sl_license_upgraded':
					
					// In an effort to not repeat this code for multiple triggers that only have minor differences, 
					// We're going to have some interior conditionals for the small differences.
					
					$replacements['%license_key%'] = $args['license_key'];
					$replacements['%expiration%'] = date_i18n( get_option( 'date_format', 'F j, Y' ), $args['expiration'] );
					$replacements['%license_limit%'] = $args['license_limit'];
					
					if ( $trigger !== 'edd_sl_store_license' ) {
						
						$replacements['%license_link%'] = '<' . admin_url( 'edit.php?post_type=download&page=edd-licenses&view=overview&license=' . $args['license_id'] ) . '|' . _x( 'View this License', 'View this License Link Text', 'edd-slack' ) . '>';
						
					}
					
					if ( $trigger == 'edd_sl_activate_license' ) {
						
						// In case there is no protocol, add one
						$link = ( preg_match( '/http(s)?:\/\/', $args['active_site'] ) == 0 ) ? 'http://' . $args['active_site'] : $active_site;
						
						$replacements['%active_site%'] = '<' . $link . '|' . $args['active_site'] . '>';
						
					}
					
					if ( $trigger == 'edd_sl_activate_license' ||
					  $trigger == 'edd_sl_deactivate_license' ) {
						$replacements['%site_count%'] = $args['site_count']; // This doesn't make sense for the other Triggers
					}
					
					if ( $trigger !== 'edd_sl_license_upgraded' ) {
						$replacements['%download%'] = get_the_title( $args['download_id'] );
					}
					else {
						
						$replacements['%old_download%'] = get_the_title( $args['old_download_id'] );
						if ( edd_has_variable_prices( $args['old_download_id'] ) && false !== $args['old_price_id'] ) {
							$replacements['%old_download%'] .= ' - ' . edd_get_price_option_name( $args['old_download_id'], $args['old_price_id'] );
						}

						$replacements['%new_download%'] = get_the_title( $args['download_id'] );
						if ( edd_has_variable_prices( $args['download_id'] ) ) {
							$replacements['%new_download%'] .= ' - ' . edd_get_price_option_name( $args['download_id'], $args['upgrade_price_id'] );
						}
						
					}
					
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
		
		$licensing_hints = array(
			'%license_key%' => _x( 'The License Key', '%license_key% Hint Text', 'edd-slack' ),
			'%download%' => sprintf( _x( 'The %s the License Key is for', '%download% Hint Text', 'edd-slack' ), edd_get_label_singular() ),
			'%expiration%' => _x( 'The date when the License expires', '%expiration% Hint Text', 'edd-slack' ),
			'%license_limit%' => _x( 'The number of sites the License can be active on', '%license_limit% Hint Text', 'edd-slack' ),
			'%license_link%' => _x( 'A link to the License', '%license_link% Hint Text', 'edd-slack' ),
		);
		
		$hints['edd_sl_store_license'] = array_merge( $user_hints, $licensing_hints );
		$hints['edd_sl_activate_license'] = array_merge( $user_hints, $licensing_hints );
		$hints['edd_sl_deactivate_license'] = array_merge( $user_hints, $licensing_hints );
		$hints['edd_sl_license_upgraded'] = array_merge( $user_hints, $licensing_hints );
		
		// Similarly here, we're going to have some interior conditionals for the small differences to avoid repeating ourselves
		
		$hints['edd_sl_activate_license']['%active_site%'] = _x( 'The Site URL this License was just activated on', '%active_site% Hint Text', 'edd-slack' );
		
		unset( $hints['edd_sl_store_license']['%site_count%'] ); // This one doesn't make sense in this context
		unset( $hints['edd_sl_license_upgraded']['%site_count%'] ); // This one doesn't make sense in this context
		
		unset( $hints['edd_sl_license_upgraded']['%download%'] );
		
		unset( $hints['edd_sl_store_license']['%license_link%'] ); // Not applicable
		
		$hints['edd_sl_license_upgraded']['%old_download%'] = sprintf( _x( 'The %s being upgraded from', '%old_download% Hint Text', 'edd-slack' ), edd_get_label_singular() );
		$hints['edd_sl_license_upgraded']['%new_download%'] = sprintf( _x( 'The %s being upgraded to', '%new_download% Hint Text', 'edd-slack' ), edd_get_label_singular() );
		
		return $hints;
		
	}
	
}

$integrate = new EDD_Slack_Software_Licensing();
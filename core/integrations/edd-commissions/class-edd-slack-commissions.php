<?php
/**
 * EDD Commissions Integration
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-commissions
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Commissions {
	
	/**
	 * EDD_Slack_Commissions constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Add New Triggers
		add_filter( 'edd_slack_triggers', array( $this, 'add_triggers' ) );
		
		// Add new Conditional Fields
		add_filter( 'edd_slack_notification_fields', array( $this, 'add_extra_fields' ) );
		
		// Inject some Checks before we do Replacements or send the Notification
		add_action( 'edd_slack_before_replacements', array( $this, 'before_notification_replacements' ), 10, 5 );
		
		// New Commission
		add_action( 'eddc_insert_commission', array( $this, 'eddc_insert_commission' ), 10, 6 );
		
		// Add our own Replacement Strings
		add_filter( 'edd_slack_notifications_replacements', array( $this, 'custom_replacement_strings' ), 10, 5 );
		
		// Add our own Hints for the Replacement Strings
		add_filter( 'edd_slack_text_replacement_hints', array( $this, 'custom_replacement_hints' ), 10, 3 );
		
		// Conditionally hide Download Variant Values
		add_filter( 'edd_slack_localize_admin_script', array( $this, 'add_variant_exclusion' ) );
		
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

		$triggers['eddc_insert_commission'] = _x( 'New Commission', 'New Commision Trigger', 'edd-slack' );

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
		$repeater_fields['download']['field_class'][] = 'eddc_insert_commission';
		$repeater_fields['exclude_download']['field_class'][] = 'eddc_insert_commission';
		
		return $repeater_fields;
		
	}
	
	/**
	 * Fires on a new Commission being made
	 * 
	 * @param	  integer $recipient		 Recipient User ID
	 * @param	  float   $commission_amount Commission Amount
	 * @param	  float   $rate			  Commission Rate
	 * @param	  integer $download_id	  Download Post ID
	 * @param	  integer $commission_id	 Commission Post ID
	 * @param	  integer $payment_id		Payment Post ID
	 *													  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function eddc_insert_commission( $recipient, $commission_amount, $rate, $download_id, $commission_id, $payment_id ) {
		
		do_action( 'edd_slack_notify', 'eddc_insert_commission', array(
			'user_id' => $recipient,
			'download_id' => $download_id,
			'commission_amount' => $commission_amount,
			'commission_rate' => $rate,
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
				'user_id' => 0,
				'name' => '',
				'email' => '',
				'bail' => false,
			) );
			
			$download = EDDSLACK()->notification_integration->check_for_price_id( $fields['download'] );

			$download_id = $download['download_id'];
			
			if ( $trigger == 'eddc_insert_commission' ) {

				// Download doesn't match our Notification, bail
				if ( $download_id !== 'all' && (int) $download_id !== $args['download_id'] ) {
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
					
				case 'eddc_insert_commission':
					
					$replacements['%download%'] = get_the_title( $args['download_id'] );
					$replacements['%commission_amount%'] = edd_currency_filter( number_format( $args['commission_amount'], 2 ) );
					
					if ( eddc_get_commission_type( $args['download_id'] ) == 'percentage' ) {
						
						$replacements['%commission_rate%'] = $args['commission_rate'] . '%';
						
					}
					else {
						$replacements['%commission_rate%'] = edd_currency_filter( number_format( $args['commission_rate'], 2 ) );
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
		
		$commission_hints = array(
			'%download%' => sprintf( _x( 'The %s that the Commission is for', '%download% Hint Text', 'edd-slack' ), edd_get_label_singular() ),
			'%commission_amount%' => _x( 'The amount of Commission awarded for the sale', '%commission_amount% Hint Text', 'edd-slack' ),
			'%commission_rate%' => _x( 'Either the Flat Rate or Percentage that the Commission is calculated based on', '%commission_rate% Hint Text', 'edd-slack' ),
		);
		
		$hints['eddc_insert_commission'] = array_merge( $user_hints, $commission_hints );
		
		return $hints;
		
	}
	
	/**
	 * Add our Trigger(s) to the Variant Exclusion Array. This prevents Variants from being selectable in the Downloads dropdown.
	 *
	 * @param	  array $localized_script PHP Localized values for JavaScript
	 *															  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array Modified Localized values
	 */
	public function add_variant_exclusion( $localized_script ) {
		
		$localized_script['variantExclusion'][] = 'eddc_insert_commission';
		
		return $localized_script;
		
	}
	
}

$integrate = new EDD_Slack_Commissions();
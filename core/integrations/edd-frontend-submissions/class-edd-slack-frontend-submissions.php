<?php
/**
 * EDD Frontend Submissions Integration
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-frontend-submissions
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Frontend_Submissions {
	
	/**
	 * EDD_Slack_Frontend_Submissions constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Add New Triggers
		add_filter( 'edd_slack_triggers', array( $this, 'add_triggers' ) );
		
		// Inject some Checks before we do Replacements or send the Notification
		add_action( 'edd_slack_before_replacements', array( $this, 'before_notification_replacements' ), 10, 5 );
		
		// New Vendor Application
		add_action( 'edd_post_insert_vendor', array( $this, 'edd_fes_vendor_registered' ), 10, 2 );
		
		// New Vendor Product
		add_action( 'fes_save_submission_form_values_after_save', array( $this, 'edd_fes_new_vendor_product' ), 10, 3 );
		
		// Edit Vendor Product
		add_action( 'fes_save_submission_form_values_after_save', array( $this, 'edd_fes_edit_vendor_product' ), 10, 3 );
		
		// New Vendor Contact Form
		add_action( 'fes_save_vendor-contact_form_values_after_save', array( $this, 'edd_fes_vendor_contact' ), 10, 3 );
		
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

		$triggers['edd_fes_vendor_registered'] = _x( 'New Vendor Application', 'New Vendor Application Trigger', 'edd-slack' );
		
		// By default, Vendors cannot create their own Products
		if ( (bool) EDD_FES()->helper->get_option( 'fes-allow-vendors-to-create-products', false ) ) {
			$triggers['edd_fes_new_vendor_product'] = _x( 'New Vendor Product', 'New Vendor Product Trigger', 'edd-slack' );
		}
		
		// By default, Vendors cannot edit their own Products
		if ( (bool) EDD_FES()->helper->get_option( 'fes-allow-vendors-to-edit-products', false ) ) {
			$triggers['edd_fes_edit_vendor_product'] = _x( 'Vendor Product Edited', 'Vendor Product Edited Trigger', 'edd-slack' );
		}
		
		$triggers['edd_fes_vendor_contact'] = _x( 'New Vendor Contact Form Submitted', 'New Vendor Contact Form Submitted Trigger', 'edd-slack' );

		return $triggers;

	}
	
	/**
	 * Fires when a new Vendor Registers
	 * 
	 * @param	  integer $insert_id $wpdb Insert ID
	 * @param	  array   $args	  $wpdb Column Key/Value Pairs
	 *														
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_fes_vendor_registered( $insert_id, $args ) {
		
		do_action( 'edd_slack_notify', 'edd_fes_vendor_registered', array(
			'user_id' => $args['user_id'],
		) );
		
	}
	
	/**
	 * Triggers on a New Vendor Product
	 * 
	 * @param	  object  $form		EDD FES Form Object that was submitted
	 * @param	  integer $user_id	 User ID Submitting the Form
	 * @param	  integer $download_id The newly created Vendor Product
	 *															
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_fes_new_vendor_product( $form, $user_id, $download_id ) {
		
		$is_vendor = EDD_FES()->vendors->user_is_status( 'approved', $user_id );
		
		if ( ! $is_vendor ) return false;
		
		$new = EDD()->session->get( 'fes_is_new' );
		
		// If it isn't a new Product, bail
		if ( ! $new ) return false;

		do_action( 'edd_slack_notify', 'edd_fes_new_vendor_product', array(
			'user_id' => $user_id,
			'download_id' => $download_id,
			'form_values' => $form->get_form_values(), // This gives us direct access to the values from the Form Submission
		) );
		
	}
	
	/**
	 * Triggers on a Edited Vendor Product
	 * 
	 * @param	  object  $form		EDD FES Form Object that was submitted
	 * @param	  integer $user_id	 User ID Submitting the Form
	 * @param	  integer $download_id The newly edited Vendor Product
	 *															
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_fes_edit_vendor_product( $form, $user_id, $download_id ) {
		
		$is_vendor = EDD_FES()->vendors->user_is_status( 'approved', $user_id );
		
		if ( ! $is_vendor ) return false;
		
		$new = EDD()->session->get( 'fes_is_new' );
		
		// If is is a new Product, bail
		if ( $new ) return false;

		do_action( 'edd_slack_notify', 'edd_fes_edit_vendor_product', array(
			'user_id' => $user_id,
			'download_id' => $download_id,
			'form_values' => $form->get_form_values(), // This gives us direct access to the values from the Form Submission
		) );
		
	}
	
	/**
	 * Triggers on a New Vendor Contact Submission
	 * 
	 * @param	  object  $form		EDD FES Form Object that was submitted
	 * @param	  integer $user_id	 User ID Submitting the Form
	 * @param	  integer $save_id	 The ID of the submitted Form
	 *															
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function edd_fes_vendor_contact( $form, $user_id, $save_id ) {
		
		// We need to grab these from $_POST
		$form_values = array();
		foreach ( $form->get_form_values() as $key => $value ) {
			$form_values[ $key ] = ( isset( $_POST[ $key ] ) ) ? $_POST[ $key ] : '';
		}

		do_action( 'edd_slack_notify', 'edd_fes_vendor_contact', array(
			'user_id' => $user_id,
			'form_values' => $form_values, // This gives us direct access to the values from the Form Submission
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
					
				case 'edd_fes_new_vendor_product':
				case 'edd_fes_edit_vendor_product':
					
					$download_link = get_edit_post_link( $args['download_id'], '' );
					
					$replacements['%download_link%'] = '<' . $download_link . '|' . sprintf( _x( "View this Vendor's %s", "View this Vendor's Download Link", 'edd-slack' ) . '>', edd_get_label_singular() );
					
					// Intentionally not break-ing to include the next Case
					
				case 'edd_fes_vendor_contact':
					
					// Grab all Post Meta from the Form's Fields
					foreach ( $args['form_values'] as $key => $value ) {
						$replacements[ '%' . $key . '%' ] = $value;
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
		
		$hints['edd_fes_vendor_registered'] = $user_hints;
		
		$vendor_product_hints = array(
			'%download_link%' => sprintf( _x( 'Show a link to the Admin Edit screen for this %s', '%download_link% Hint Text', 'edd-slack' ), edd_get_label_singular() ),
		);
		
		$vendor_product_fields = $this->get_fields_help_text( 'submission' );
		
		foreach ( $vendor_product_fields as $key => $help_text ) {
			
			if ( empty( $help_text ) ) {
				$help_text = _x( 'No Hint Text Provided for this Form Field', 'No Hint Text Notice', 'edd-slack' );
			}
			
			$vendor_product_hints[ '%' . $key . '%' ] = $help_text;
			
		}
		
		$hints['edd_fes_new_vendor_product'] = array_merge( $user_hints, $vendor_product_hints );
		$hints['edd_fes_edit_vendor_product'] = array_merge( $user_hints, $vendor_product_hints );
		
		$vendor_contact_fields = $this->get_fields_help_text( 'vendor-contact' );
		
		$vendor_contact_hints = array();
		foreach ( $vendor_contact_fields as $key => $help_text ) {
			
			if ( empty( $help_text ) ) {
				$help_text = _x( 'No Hint Text Provided for this Form Field', 'No Hint Text Notice', 'edd-slack' );
			}
			
			$vendor_contact_hints[ '%' . $key . '%' ] = $help_text;
			
		}
		
		$hints['edd_fes_vendor_contact'] = $vendor_contact_hints;
		
		unset( $hints['vendor_contact_hints']['%recaptcha%'] );
		
		return $hints;
		
	}
	
	/**
	 * Grab all the Fields within an FES Form and make them something that EDD Slack can more easily consume
	 * 
	 * @param	  string $name Form Name
	 *								
	 * @access	  private
	 * @since	  1.0.0
	 * @return	  array  Field Keys and Hints
	 */
	private function get_fields_help_text( $name ) {
		
		$form = new FES_Form( $name, 'name' );
		$field_objects = $form->get_fields();

		$fields = array();
		foreach ( $field_objects as $key => $field ) {

			$fields[ $key ] = ( property_exists( $field, 'help' ) ) ? $field->help : '';

		}

		return $fields;
		
	}
	
}

$integrate = new EDD_Slack_Frontend_Submissions();
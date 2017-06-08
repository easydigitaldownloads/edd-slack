<?php
/**
 * EDD Reviews Integration
 *
 * @since 1.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-reviews
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Reviews {
	
	/**
	 * @var			EDD_Slack_Reviews $support_fes If FES is also enabled, set to true. This prevents things from getting added when not needed that could be potentially confusing for other developers, like extra unused data in an Array
	 * @since		1.1.0
	 */
	private $support_fes = false;
	
	/**
	 * EDD_Slack_Reviews constructor.
	 *
	 * @since 1.1.0
	 */
	function __construct() {
		
		// Add New Comment Trigger
		add_filter( 'edd_slack_triggers', array( $this, 'add_triggers' ) );
		
		// Add new Conditional Fields for the Comment Trigger
		add_filter( 'edd_slack_notification_fields', array( $this, 'add_extra_fields' ) );
		
		// Fires when a Review is created
		add_action( 'wp_insert_comment', array( $this, 'edd_insert_review' ), 10, 2 );
		
		// If FES exists and it is a supported version
		if ( class_exists( 'EDD_Front_End_Submissions' ) ) {
			
			if ( defined( 'fes_plugin_version' ) &&
				  version_compare( fes_plugin_version, '2.4.2' ) >= 0 ) {
				
				// This way we only check this once
				$this->support_fes = true;
				
				// Fires when a Vendor Review is made
				add_action( 'wp_insert_comment', array( $this, 'edd_vendor_feedback' ), 10, 2 );
				
			}
			
		}
		
		// Inject some Checks before we do Replacements or send the Notification
		add_action( 'edd_slack_before_replacements', array( $this, 'before_notification_replacements' ), 10, 5 );
		
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
	 * @since	  1.1.0
	 * @return	  array Modified EDD Slack Triggers
	 */
	public function add_triggers( $triggers ) {

		$triggers['edd_insert_review'] = sprintf( _x( 'New Review on %s', 'New Review on Download Created Trigger', 'edd-slack' ), edd_get_label_singular() );
		
		if ( $this->support_fes ) {
			$triggers['edd_vendor_feedback'] = sprintf( _x( 'New %s Feedback', 'New Vendor Feedback Created Trigger', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( false, true ) );
		}

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
		
		$repeater_fields['download']['field_class'][] = 'edd_insert_review';
		
		if ( $this->support_fes ) {
			
			$repeater_fields['download']['field_class'][] = 'edd_vendor_feedback';
			
			$index = 0;
			foreach ( $repeater_fields as $key => $value ) {

				// Find the Numeric Index of the Download Select Field
				if ( $key == 'download' ) {
					break;
				}

				$index++;

			}
			
			$vendors = new FES_DB_Vendors();
			$vendors = $vendors->get_vendors();
			
			$vendors_array = wp_list_pluck( $vendors, 'name', 'id' );

			// Create a new Repeater Field for selecting a Vendor
			$vendor_select = array(
				'vendor' => array(
					'type' => 'select',
					'desc' => EDD_FES()->helper->get_vendor_constant_name( false, true ),
					'field_class' => array(
						'edd-select-chosen',
						'edd-slack-field',
						'edd-slack-vendor',
						'edd-slack-conditional',
						'edd_vendor_feedback',
						'required',
					),
					'std' => '',
					'options' => array(
						'' => sprintf( _x( '-- Select %s --', 'Select Field Default', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( false, true ) ),
						'all' => sprintf( _x( 'Any %s', 'All Vendor in a Select Field', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( false, true ) ),
					) + $vendors_array,
					'placeholder' => sprintf( _x( '-- Select %s --', 'Select Field Default', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( false, true ) ),
					'chosen' => true,
					'multiple' => false,
				),
			);

			// Insert the new field just after the "Download" Select Field
			EDDSLACK()->array_insert( $repeater_fields, $index + 1, $vendor_select );
			
		}
		
		return $repeater_fields;
		
	}
	
	/**
	 * Send a Slack Notification when a Review is Created
	 * 
	 * @param		integer $comment_id     Comment ID
	 * @param		object  $comment_object WP_Comment Object
	 *                                            
	 * @access		public
	 * @since		1.1.0
	 * @return 		void
	 */
	public function edd_insert_review( $comment_id, $comment_object ) {
		
		// We only want results for EDD Reviews
		if ( $comment_object->comment_type !== 'edd_review' ) return false;
		
		// For a Reviews trigger, it doesn't make sense to listen for Replies
		// The regular Comments trigger could listen for these though
		if ( (int) $comment_object->comment_parent !== 0 ) return false;
		
		// EDD Reviews does not let me hook in late enough to benefit from the Sanitization on the Meta Data, so we'll just re-do it
		// Since we're hooking into wp_insert_comment(), the Meta Data has not been saved yet so I can't just grab it from the DB
		
		$rating = ( isset( $_POST['edd-reviews-review-rating'] ) ) ? trim( $_POST['edd-reviews-review-rating'] ) : null;
		$rating = wp_filter_nohtml_kses( $rating );
		
		$review_title = ( isset( $_POST['edd-reviews-review-title'] ) ) ? trim( $_POST['edd-reviews-review-title'] ) : null;
		$review_title = sanitize_text_field( wp_filter_nohtml_kses( esc_html( $review_title ) ) );
			
		do_action( 'edd_slack_notify', 'edd_insert_review', array(
			'user_id' => $comment_object->user_id,
			'name' => $comment_object->comment_author,
			'email' => $comment_object->comment_author_email,
			'comment_id' => $comment_id,
			'comment_approved' => $comment_object->comment_approved,
			'comment_post_id' => $comment_object->comment_post_ID,
			'comment_content' => $comment_object->comment_content,
			'review_rating' => $rating,
			'review_title' => $review_title,
		) );
		
	}
	
	/**
	 * Send a Slack Notification when a Vendor Review is Created
	 * 
	 * @param		integer $comment_id     Comment ID
	 * @param		object  $comment_object WP_Comment Object
	 *                                            
	 * @access		public
	 * @since		1.1.0
	 * @return 		void
	 */
	public function edd_vendor_feedback( $comment_id, $comment_object ) {
		
		// We only want results for Vendor Feedback
		if ( $comment_object->comment_type !== 'edd_vendor_feedback' ) return false;
		
		// For a Vendor Feedback trigger, it doesn't make sense to listen for Replies
		// The regular Comments trigger could listen for these though
		if ( (int) $comment_object->comment_parent !== 0 ) return false;
		
		// EDD Reviews does not let me hook in late enough to benefit from the Sanitization on the Meta Data, so we'll just re-do it
		// Since we're hooking into wp_insert_comment(), the Meta Data has not been saved yet so I can't just grab it from the DB
		
		$rating = wp_filter_nohtml_kses( $_POST['edd-reviews-review-rating'] );
		$item_as_described = wp_filter_nohtml_kses( $_POST['edd-reviews-item-as-described'] );
		
		// Grab the Vendor
		$vendor_user_id = get_post_field( 'post_author', $comment_object->comment_post_ID );
		$vendor = new FES_Vendor( $vendor_user_id, true );
			
		do_action( 'edd_slack_notify', 'edd_vendor_feedback', array(
			'user_id' => $comment_object->user_id,
			'name' => $comment_object->comment_author,
			'email' => $comment_object->comment_author_email,
			'comment_id' => $comment_id,
			'comment_approved' => $comment_object->comment_approved,
			'comment_post_id' => $comment_object->comment_post_ID,
			'comment_content' => $comment_object->comment_content,
			'review_rating' => $rating,
			'review_item_as_described' => $item_as_described,
			'vendor_id' => $vendor->id,
			'vendor_user_id' => $vendor_user_id,
			'vendor_name' => $vendor->name,
			'vendor_email' => $vendor->email,
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
				'user_id' => null,
				'comment_post_id' => 0,
				'comment_parent' => 0,
				'bail' => false,
			) );
			
			if ( $trigger == 'edd_insert_review' ||
			   $trigger == 'edd_vendor_feedback' ) {
				
				$download = EDDSLACK()->notification_integration->check_for_price_id( $fields['download'] );
				
				$download_id = $download['download_id'];
				
				// Download Reviewed to doesn't match our Notification, bail
				if ( $download_id !== 'all' && $download_id !== $args['comment_post_id'] ) {
					$args['bail'] = true;
					return false;
				}
				
			}
			
			if ( $trigger == 'edd_vendor_feedback' ) {
				
				// Vendor does not match our notification, bail
				if ( $fields['vendor'] !== 'all' && $fields['vendor'] !== $args['vendor_id'] ) {
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

				case 'edd_insert_review':
				case 'edd_vendor_feedback':
					
					$replacements['%download%'] = get_the_title( $args['download_id'] );
					
					if ( $args['user_id'] == 0 ) {
						$replacements['%username%'] = _x( 'This Reviewer does not have an account', 'No Username Replacement Text', 'edd-slack' );
					}
					
					$replacements['%download%'] = get_the_title( $args['comment_post_id'] );
					
					$replacements['%review_content%'] = $args['comment_content'];
					$replacements['%review_link%'] = '<' . get_comment_link( $args['comment_id'] ) . '|' . _x( 'View this Review', 'View this Review Link Text', 'edd-slack' ) . '>';
					
					$replacements['%review_rating%'] = $args['review_rating'];
					
					// Since the above stuff is exactly the same, no sense in repeating it
					if ( $trigger == 'edd_vendor_feedback' ) {
						
						$vendor_userdata = get_userdata( $args['vendor_user_id'] );

						$replacements['%vendor_name%'] = $vendor_userdata->display_name;
						$replacements['%vendor_username%'] = $vendor_userdata->user_login;
						$replacements['%vendor_email%'] = $vendor_userdata->user_email;
						
						$replacements['%review_item_as_described%'] = ( $args['review_item_as_described'] == 0 ) ? __( 'No', 'edd-reviews' ) : __( 'Yes', 'edd-reviews' );
						
					}
					else {
						
						// This is exclusive to regular Reviews
						$replacements['%review_title%'] = $args['review_title'];
						
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
	 * @since	  1.1.0
	 * @return	  array The main Hints Array
	 */
	public function custom_replacement_hints( $hints, $user_hints, $payment_hints ) {
		
		$reviews_hints = array(
			'%download%' => sprintf( _x( 'The %s the Review was made on', '%download% Hint Text', 'edd-slack' ), edd_get_label_singular() ),
			'%review_title%' => _x( 'The Review title', '%review_title% Hint Text', 'edd-slack' ),
			'%review_rating%' => _x( 'The Review Rating out of 5 Stars', '%review_rating% Hint Text', 'edd-slack' ),
			'%review_content%' => _x( 'The Review itself', '%review_content% Hint Text', 'edd-slack' ),
			'%review_link%' => _x( 'A link to the Review', '%review_link% Hint Text', 'edd-slack' ),
		);
		
		$vendor_feedback_hints = array(
			'%review_item_as_described%' => sprintf( 'Was the Item as Described?', '%review_item_as_described% Hint Text', 'edd-slack' ),
			'%vendor_username%' => _x( 'Display the Vendor\'s username', '%vendor_username% Hint Text', 'edd-slack' ),
			'%vendor_email%' => _x( 'Display the Vendor\'s email', '%vendor_email% Hint Text', 'edd-slack' ),
			'%vendor_name%' => _x( 'Display the Vendor\'s display name', '%vendor_name% Hint Text', 'edd-slack' ),
		);
		
		$hints['edd_insert_review'] = array_merge( $user_hints, $reviews_hints );
		
		if ( $this->support_fes ) {
			
			$hints['edd_vendor_feedback'] = array_merge( $user_hints, $reviews_hints, $vendor_feedback_hints );
			
			unset( $hints['edd_vendor_feedback']['%review_title%'] );
			
		}
		
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
		
		$localized_script['variantExclusion'][] = 'edd_insert_review';
		
		if ( $this->support_fes ) {
			$localized_script['variantExclusion'][] = 'edd_vendor_feedback';
		}
		
		return $localized_script;
		
	}
	
}

$integrate = new EDD_Slack_Reviews();
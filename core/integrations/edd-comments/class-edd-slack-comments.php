<?php
/**
 * EDD normally doesn't support Comments, but if it gets added by someone/something, lets make triggers
 * This does NOT conflict with EDD Reviews.
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-comments
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Comments {
	
	/**
	 * EDD_Slack_Comments constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Add New Comment Trigger
		add_filter( 'edd_slack_triggers', array( $this, 'add_comment_post_trigger' ) );
		
		// Add new Conditional Fields for the Comment Trigger
		add_filter( 'edd_slack_notification_fields', array( $this, 'add_comment_post_extra_fields' ) );
		
		// Fires when a Comment is made on a Post
		add_action( 'comment_post', array( $this, 'comment_post' ), 10, 3 );
		
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
	 * By Default EDD does not support Comments, but if they have been enabled show a Trigger
	 * 
	 * @param	  array $triggers EDD Slack Triggers
	 *										
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array Modified EDD Slack Triggers
	 */
	public function add_comment_post_trigger( $triggers ) {

		$triggers['comment_post'] = sprintf( _x( 'New Comment on %s', 'New Comment on Download Trigger', 'edd-slack' ), edd_get_label_singular() );

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
	public function add_comment_post_extra_fields( $repeater_fields ) {
		
		// Make the Download Field Conditionally shown for the Comment Trigger
		$repeater_fields['download']['field_class'][] = 'comment_post';
		
		$index = 0;
		foreach ( $repeater_fields as $key => $value ) {
			
			// Find the Numeric Index of the Download Select Field
			if ( $key == 'download' ) {
				break;
			}
			
			$index++;
			
		}
		
		// Create a new Repeater Field for a Checkbox to toggle Top Level only Notifications for Comments
		$top_level_only = array(
			'comments_top_level_only' => array(
				'type' => 'checkbox',
				'desc' => _x( 'Only Trigger on New, Top-Level Comments', 'Top Level Comments Checkbox Label', 'edd-slack' ),
				'field_class' => array(
					'edd-slack-field',
					'edd-slack-comments-top-level-only',
					'edd-slack-conditional',
					'comment_post',
				),
				'std' => 1,
			),
		);
		
		// Insert the new field just after the "Download" Select Field
		EDDSLACK()->array_insert( $repeater_fields, $index + 1, $top_level_only );
		
		return $repeater_fields;
		
	}
	
	/**
	 * Send a Slack Notification on a Comment added to a Download
	 *
	 * @param	  integer $comment_id	  The Comment ID
	 * @param	  integer $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
	 * @param	  array   $commentdata	  Comment Data
	 *												
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function comment_post( $comment_id, $comment_approved, $commentdata ) {
		
		$comment_post = get_post( $commentdata['comment_post_ID'] );
		
		// EDD Reviews normally doesn't trigger this, but if it is paired with EDD FES it does
		if ( $commentdata['comment_type'] == 'edd_vendor_feedback' ) return false;
		
		// Just in case EDD Reviews gets updated to trigger `comment_post`
		if ( $commentdata['comment_type'] == 'edd_review' ) return false;
		
		if ( get_post_type( $comment_post ) == 'download' && $comment_approved !== 'spam' ) {
			
			$comment = get_comment ( $comment_id );
			
			do_action( 'edd_slack_notify', 'comment_post', array(
				'user_id' => $commentdata['user_ID'],
				'name' => $comment->comment_author,
				'email' => $comment->comment_author_email,
				'comment_id' => $comment_id,
				'comment_approved' => $comment_approved,
				'comment_post_id' => $commentdata['comment_post_ID'],
				'comment_content' => $commentdata['comment_content'],
				'comment_parent' => $commentdata['comment_parent'],
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
	 * @since	  1.0.0
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
			
			if ( $trigger == 'comment_post' ) {
				
				$download = EDDSLACK()->notification_integration->check_for_price_id( $fields['download'] );
				
				$download_id = $download['download_id'];
				
				// Download commented on doesn't match our Notification, bail
				if ( $download_id !== 'all' && (int) $download_id !== $args['comment_post_id'] ) {
					$args['bail'] = true;
					return false;
				}
				
				// If we're only going to check for Top Level Comments, bail on Replies
				if ( (int) $fields['comments_top_level_only'] == 1 && $args['comment_parent'] !== 0 ) {
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

				case 'comment_post':
					
					if ( $args['user_id'] == 0 ) {
						$replacements['%username%'] = _x( 'This Commenter does not have an account', 'No Username Replacement Text', 'edd-slack' );
					}
					
					$replacements['%download%'] = get_the_title( $args['comment_post_id'] );
					$replacements['%comment_content%'] = $args['comment_content'];
					$replacements['%comment_link%'] = '<' . get_comment_link( $args['comment_id'] ) . '|' . _x( 'View this Comment', 'View this Comment Link Text', 'edd-slack' ) . '>';
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
		
		$comment_hints = array(
			'%download%' => sprintf( _x( 'The %s the Comment was made on', '%download% Hint Text', 'edd-slack' ), edd_get_label_singular() ),
			'%comment_content%' => _x( 'The Comment itself', '%comment_content% Hint Text', 'edd-slack' ),
			'%comment_link%' => _x( 'A link to the Comment', '%comment_link% Hint Text', 'edd-slack' ),
		);
		
		$hints['comment_post'] = array_merge( $user_hints, $comment_hints );
		
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
		
		$localized_script['variantExclusion'][] = 'comment_post';
		
		return $localized_script;
		
	}
	
}

$integrate = new EDD_Slack_Comments();
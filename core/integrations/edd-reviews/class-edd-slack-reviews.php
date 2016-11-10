<?php
/**
 * Includes support for EDD Reviews
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-reviews
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Reviews {
    
    /**
	 * EDD_Slack_Reviews constructor.
	 *
	 * @since 1.0.0
	 */
    function __construct() {
        
        // Add Triggers for EDD Reviews
        add_filter( 'edd_slack_triggers', array( $this, 'add_edd_reviews_triggers' ) );
        
        // Ensure Conditional Fields within the Repeater work for the new Triggers
        add_filter( 'edd_slack_notification_fields', array( $this, 'add_conditional_fields' ) );
        
        // New Review Notification
        // We have to listen for the Comment Meta since it is added after the Comment is created
        add_action( 'added_comment_meta', array( $this, 'new_review_added' ), 10, 4 );
        
        // Inject some Checks before we do Replacements or send the Notification
        add_action( 'edd_slack_before_replacements', array( $this, 'before_notification_replacements' ), 10, 5 );
        
        // Add our own Replacement Strings
        //add_filter( 'edd_slack_notifications_replacements', array( $this, 'custom_replacement_strings' ), 10, 4 );
        
    }
    
    /**
     * Add a Trigger for new Reviews
     * 
     * @param       array $triggers EDD Slack Triggers
     *                                        
     * @access      public
     * @since       1.0.0
     * @return      array EDD Slack Triggers
     */
    public function add_edd_reviews_triggers( $triggers ) {
        
        $triggers['edd_review'] = sprintf( _x( 'New Review on %s', 'New Review on Download Trigger', EDD_Slack_ID ), edd_get_label_singular() );
        
        return $triggers;
        
    }
    
    /**
     * Conditionally Showing Fields within the Notification Repeater works by adding the Trigger as a HTML Class Name
     * 
     * @param       array $repeater_fields Notification Repeater Fields
     *                                                  
     * @access      public
     * @since       1.0.0
     * @return      array Notification Repeater Fields
     */
    public function add_conditional_fields( $repeater_fields ) {
        
        $repeater_fields['download']['field_class'][] = 'edd_review';
        
        return $repeater_fields;
        
    }
    
    /**
     * Send a Slack Notification on a Review added to a Download
     *
     * @param       integer $comment_id       The Comment ID
     * @param       integer $comment_object 1 The Comment Object
     *                                                
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function new_review_added( $row_id, $comment_id, $meta_key, $meta_value ) {
        
        // This is the last bit of Comment Meta that is added to a Review
        // This ensures we only do this once.
        if ( $meta_key !== 'edd_review_approved' ) return false;
        
        $comment_object = get_comment( $comment_id );
        
        // Bail if it is a Comment Reply
        if ( ! empty( $comment_object->comment_parent ) ) return false;
        
        // Only trigger for EDD Reviews
        if ( $comment_object->comment_type !== 'edd_review' ) return false;
        
        do_action( 'edd_slack_notify', 'edd_review', array(
            'user_id' => $comment_object->user_id,
            'comment_id' => $comment_id,
            'comment_post_id' => $comment_object->comment_post_ID,
            'comment_content' => $comment_object->comment_content,
            'review_title' => get_comment_meta( $comment_object->comment_id, 'edd_review_title', true ),
            'review_rating' => get_comment_meta( $comment_object->comment_id, 'edd_rating', true ),
            'review_approved' => get_comment_meta( $comment_object->comment_id, 'edd_review_approved', true ),
        ) );
        
    }
    
    /**
     * Inject some checks on whether or not to bail on the Notification
     * 
     * @param       object  $post            WP_Post Object for our Saved Notification Data
     * @param       array   $fields          Fields used to create the Post Meta
     * @param       string  $trigger         Notification Trigger
     * @param       string  $notification_id ID Used for Notification Hooks
     * @param       array   $args            $args Array passed from the original Trigger of the process
     *              
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function before_notification_replacements( $post, $fields, $trigger, $notification_id, &$args ) {
        
        if ( $notification_id == 'rbm' ) {
        
            $args = wp_parse_args( $args, array(
                'user_id' => null,
                'cart' => array(),
                'discount_code' => 'all',
                'bail' => false,
            ) );
            
            if ( $trigger == 'comment_post' ) {
                
                // Don't trigger on New Comment if it is actually a Review
                if ( get_comment_meta( $args['comment_id'], 'edd_rating', true ) ) {
                    $args['bail'] = true;
                    return false;
                }
                
            }
            
            if ( $trigger == 'edd_review' ) {
                
                echo '<pre>';
                var_dump( $fields );
                var_dump( $args );
                echo '</pre>';
                die();
                
                // Reviewed Download doesn't match our Notification, bail
                if ( $fields['download'] !== 'all' && $fields['download'] !== $args['comment_post_id'] ) {
                    $args['bail'] = true;
                    return false;
                }
                
            }
            
        }
        
    }
    
}

new EDD_Slack_Reviews();
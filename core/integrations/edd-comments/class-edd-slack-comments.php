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
        add_filter( 'edd_slack_notifications_replacements', array( $this, 'custom_replacement_strings' ), 10, 4 );
        
        // Add our own Hints for the Replacement Strings
        add_filter( 'edd_slack_text_replacement_hints', array( $this, 'custom_replacement_hints' ), 10, 3 );
        
    }
    
    /**
     * By Default EDD does not support Comments, but if they have been enabled show a Trigger
     * 
     * @param       array $triggers EDD Slack Triggers
     *                                        
     * @access      public
     * @since       1.0.0
     * @return      array Modified EDD Slack Triggers
     */
    public function add_comment_post_trigger( $triggers ) {

        $triggers['comment_post'] = sprintf( _x( 'New Comment on %s', 'New Comment on Download Trigger', EDD_Slack_ID ), edd_get_label_singular() );

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
                'desc' => _x( 'Only Trigger on New, Top-Level Comments', 'Top Level Comments Checkbox Label', EDD_Slack_ID ),
                'field_class' => array(
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
     * @param       integer $comment_id       The Comment ID
     * @param       integer $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
     * @param       array   $commentdata      Comment Data
     *                                                
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function comment_post( $comment_id, $comment_approved, $commentdata ) {
        
        $comment_post = get_post( $commentdata['comment_post_ID'] );
        
        if ( get_post_type( $comment_post ) == 'download' && $comment_approved == 1 ) {
            
            do_action( 'edd_slack_notify', 'comment_post', array(
                'user_id' => $commentdata['user_ID'],
                'comment_id' => $comment_id,
                'comment_post_id' => $commentdata['comment_post_ID'],
                'comment_content' => $commentdata['comment_content'],
                'comment_parent' => $commentdata['comment_parent'],
            ) );
            
        }
        
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
                'comment_post_id' => 0,
                'comment_parent' => 0,
                'bail' => false,
            ) );
            
            if ( $trigger == 'comment_post' ) {
                
                // Reviewed Download doesn't match our Notification, bail
                if ( $fields['download'] !== 'all' && (int) $fields['download'] !== $args['comment_post_id'] ) {
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
     * @param       array  $replacements    Notification Fields to check for replacements in
     * @param       string $trigger         Notification Trigger
     * @param       string $notification_id ID used for Notification Hooks
     * @param       array  $args            $args Array passed from the original Trigger of the process
     * 
     * @access      public
     * @since       1.0.0
     * @return      array  Replaced Strings within each Field
     */
    public function custom_replacement_strings( $replacements, $trigger, $notification_id, $args ) {

        if ( $notification_id == 'rbm' ) {

            switch ( $trigger ) {

                case 'comment_post':
                    $replacements['%comment_content%'] = $args['comment_content'];
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
     * @param       array $hints         The main Hints Array
     * @param       array $user_hints    General Hints for a User. These apply to likely any possible Trigger
     * @param       array $payment_hints Payment-Specific Hints
     *                                                    
     * @access      public
     * @since       1.0.0
     * @return      array The main Hints Array
     */
    public function custom_replacement_hints( $hints, $user_hints, $payment_hints ) {
        
        $comment_hints = array(
            '%comment_content%' => _x( 'The Comment itself', '%comment_content% Hint Text', EDD_Slack_ID ),
        );
        
        $hints['comment_post'] = array_merge( $user_hints, $comment_hints );
        
        return $hints;
        
    }
    
}

$integrate = new EDD_Slack_Comments();
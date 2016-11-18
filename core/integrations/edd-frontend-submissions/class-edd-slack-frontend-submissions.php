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
        
        // Add our own Replacement Strings
        add_filter( 'edd_slack_notifications_replacements', array( $this, 'custom_replacement_strings' ), 10, 4 );
        
        // Add our own Hints for the Replacement Strings
        add_filter( 'edd_slack_text_replacement_hints', array( $this, 'custom_replacement_hints' ), 10, 3 );
        
    }
    
    /**
     * Add our Triggers
     * 
     * @param       array $triggers EDD Slack Triggers
     *                                        
     * @access      public
     * @since       1.0.0
     * @return      array Modified EDD Slack Triggers
     */
    public function add_triggers( $triggers ) {

        $triggers['edd_fes_vendor_registered'] = _x( 'New Vendor Application', 'New Vendor Application Trigger', EDD_Slack_ID );

        return $triggers;

    }
    
    /**
     * Fires when a new Vendor Registers
     * 
     * @param       integer $insert_id $wpdb Insert ID
     * @param       array   $args      $wpdb Column Key/Value Pairs
     *                                                        
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function edd_fes_vendor_registered( $insert_id, $args ) {
        
        // If we are auto-approving Vendors
        if ( (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-vendors', false ) ) {
            
        }
        else {
            
        }
        
        do_action( 'edd_slack_notify', 'edd_fes_vendor_registered', array(
            'user_id' => $args['user_id'],
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
        
        $hints['edd_fes_vendor_registered'] = $user_hints;
        
        return $hints;
        
    }
    
}

$integrate = new EDD_Slack_Frontend_Submissions();
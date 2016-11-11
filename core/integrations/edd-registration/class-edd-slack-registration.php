<?php
/**
 * These settings are only added if the "Show Register / Login Form" field under Downloads->Setttings->Misc->Checkout Settings is set accordingly
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/integrations/edd-registration
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Registration {
    
    /**
     * EDD_Slack_Registration constructor.
     *
     * @since 1.0.0
     */
    function __construct() {
        
        // Add New Comment Trigger
        add_filter( 'edd_slack_triggers', array( $this, 'add_edd_insert_user_trigger' ) );
        
        // Fires when a Comment is made on a Post
        add_action( 'edd_insert_user', array( $this, 'edd_insert_user' ), 10, 2 );
        
        // Add our own Hints for the Replacement Strings
        add_filter( 'edd_slack_text_replacement_hints', array( $this, 'custom_replacement_hints' ), 10, 3 );
        
    }
    
    /**
     * Add a Trigger for New User Registrations
     * 
     * @param       array $triggers EDD Slack Triggers
     *                                        
     * @access      public
     * @since       1.0.0
     * @return      array Modified EDD Slack Triggers
     */
    public function add_edd_insert_user_trigger( $triggers ) {

        $triggers['edd_insert_user'] = _x( 'New User Registration via EDD', 'New User Registration Trigger Label', EDD_Slack_ID );

        return $triggers;

    }
    
    /**
     * Sends a Slack Notification when a New User is registered via EDD
     * 
     * @param       integer $user_id   The User ID
     * @param       array   $user_data User Data (Not User Meta)
     *                                                     
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function edd_insert_user( $user_id, $user_data ) {
        
        do_action( 'edd_slack_notify', 'edd_insert_user', array(
            'user_id' => $user_id,
        ) );
        
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
        
        $hints['edd_insert_user'] = $user_hints;
        
        return $hints;
        
    }
    
}

$integrate = new EDD_Slack_Registration();
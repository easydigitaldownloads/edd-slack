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
        
        // The Vendor Registration Trigger hooks into an admin-ajax.php function so it is hooks in statically outside of the Class
        
        
        
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

        $triggers['fes_admin_submit_registration_form'] = _x( 'New Vendor Application', 'New Vendor Application Trigger', EDD_Slack_ID );

        return $triggers;

    }
    
    /**
     * Fires whenever a Vendor submits a Registration Form
     * 
     * @param       integer $id     User id of the user currently being edited.
     * @param       array   $values Values to save
     * @param       array   $args   Args for the save function. Deprecated.
     *                                                          
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public static function fes_admin_submit_registration_form( $id = 0, $values = array(), $args = array() ) {

        $user_id = ! empty( $values ) && isset( $values['user_id'] ) ? absint( $values['user_id'] ) : ( isset( $_REQUEST['user_id'] )   ? absint( $_REQUEST['user_id'] ) : get_current_user_id() );
        
        $username = '';
        $name = '';
        $email = '';
        
        // Fallbacks in case $user_id == 0, the Vendor is a new User
        if ( $user_id == 0 ) {
            
            // This should never, ever be blank
            $username = $_REQUEST['user_login'];
            
            // First try a prefered Display Name, if it doesn't exist, create one
            if ( isset( $_REQUEST['display_name'] ) && ! empty( $_REQUEST['display_name'] ) ) {
                $name = $_REQUEST['display_name'];
            }
            else {
                $name = $_REQUEST['first_name'] . ' ' . $_REQUEST['last_name'];
            }
            
            // If $name is blank, just use their Username
            if ( trim( $name ) == '' ) {
                $name = $_REQUEST['user_login'];
            }
            
            if ( isset( $_REQUEST['user_email'] ) && ! empty( $_REQUEST['user_email'] ) ) {
                $email = $_REQUEST['user_email'];
            }
            
        }
        
        do_action( 'edd_slack_notify', 'fes_admin_submit_registration_form', array(
            'user_id' => $user_id, // If the User isn't a proper WP User, this will be 0
            'username' => $username,
            'name' => $name,
            'email' => $email,
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

                case 'fes_admin_submit_registration_form':
                    
                    // In the case of Vendors, a User Account is actually created
                    if ( $args['user_id'] == 0 ) {
                        
                        $replacements['%username%'] = $args['username'];
                        
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
     * @param       array $hints         The main Hints Array
     * @param       array $user_hints    General Hints for a User. These apply to likely any possible Trigger
     * @param       array $payment_hints Payment-Specific Hints
     *                                                    
     * @access      public
     * @since       1.0.0
     * @return      array The main Hints Array
     */
    public function custom_replacement_hints( $hints, $user_hints, $payment_hints ) {
        
        $hints['fes_admin_submit_registration_form'] = $user_hints;
        
        return $hints;
        
    }
    
}

$integrate = new EDD_Slack_Frontend_Submissions();

// New Vendor Application
// Since this uses WP AJAX, we need to use a Static Function with a High Priority to run before theirs
add_action( 'wp_ajax_fes_submit_registration_form', array( 'EDD_Slack_Frontend_Submissions', 'fes_admin_submit_registration_form' ), 1, 3 );
add_action( 'wp_ajax_nopriv_fes_submit_registration_form', array( 'EDD_Slack_Frontend_Submissions', 'fes_admin_submit_registration_form' ), 1, 3 );
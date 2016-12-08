<?php
/**
 * EDD Frontend Submissions Integration for the Slack App
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only/integrations/edd-frontend-submissions
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_App_Frontend_Submissions {
    
    /**
     * EDD_Slack_App_Frontend_Submissions constructor.
     *
     * @since 1.0.0
     */
    function __construct() {
        
        // Set the new Notification API Endpoint
        add_filter( 'edd_slack_notification_webhook', array( $this, 'override_webhook' ), 10, 4 );
        
        // Add our Interaction Buttons
        add_filter( 'edd_slack_notification_args', array( $this, 'override_arguments' ), 10, 4 );
        
    }
    
    /**
     * Override Webhook URL with chat.postMessage for Slack App if appropriate
     * 
     * @param       string $webhook_url     The Webhook URL provided for the Slack Notification
     * @param       string $trigger         Notification Trigger
     * @param       string $notification_id ID used for Notification Hooks
     * @param       array  $args            $args Array passed from the original Trigger of the process
     *                                                               
     * @access      public
     * @since       1.0.0
     * @return      string Altered URL
     */
    public function override_webhook( $webhook_url, $trigger, $notification_id, $args ) {
        
        if ( $notification_id !== 'rbm' ) return $webhook_url;
        
        if ( $trigger == 'edd_fes_vendor_registered' ) {
        
            // If we are NOT auto-approving Vendors
            if ( ! (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-vendors', false ) ) {
                return 'chat.postMessage';
            }
            
        }
        
        return $webhook_url;
        
    }
    
    /**
     * Override Notification Args for Slack App if appropriate
     * 
     * @param       string $webhook_url     The Webhook URL provided for the Slack Notification
     * @param       string $trigger         Notification Trigger
     * @param       string $notification_id ID used for Notification Hooks
     * @param       array  $args            $args Array passed from the original Trigger of the process
     *                                                               
     * @access      public
     * @since       1.0.0
     * @return      string Altered URL
     */
    public function override_arguments( $notification_args, $trigger, $notification_id, $args ) {
        
        if ( $notification_id !== 'rbm' ) return $notification_args;
        
        if ( $trigger == 'edd_fes_vendor_registered' ) {
        
            // If we are NOT auto-approving Vendors
            if ( ! (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-vendors', false ) ) {
                
                $notification_args['attachments'][0]['actions'] = array(
                    array(
                        'name' => 'approve',
                        'text' => _x( 'Approve', 'Approve FES Vendor Interactive Button Text', EDD_Slack_ID ),
                        'type' => 'button',
                        'style' => 'primary',
                        'value' => json_encode( $args ),
                    ),
                    array(
                        'name' => 'deny',
                        'text' => _x( 'Deny', 'Deny FES Vendor Interactive Button Text', EDD_Slack_ID ),
                        'type' => 'button',
                        'style' => 'default',
                        'value' => json_encode( $args ),
                    )
                );
                
            }
            
        }
        
        return $notification_args;
        
    }
    
}

$integrate = new EDD_Slack_App_Frontend_Submissions();
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
        add_filter( 'edd_slack_notification_webhook', array( $this, 'override_webhook' ), 10, 3 );
        
        // Add our Interaction Buttons
        add_filter( 'edd_slack_notification_args', array( $this, 'override_arguments' ), 10, 3 );
        
    }
    
    /**
     * Override Webhook URL with chat.postMessage for Slack App if appropriate
     * 
     * @param       string $webhook_url     The Webhook URL provided for the Slack Notification
     * @param       string $trigger         Notification Trigger
     * @param       string $notification_id ID used for Notification Hooks
     *                                                               
     * @access      public
     * @since       1.0.0
     * @return      string Altered URL
     */
    public function override_webhook( $webhook_url, $trigger, $notification_id ) {
        
        if ( $notification_id !== 'rbm' ) return $webhook_url;
        
        if ( $trigger == 'edd_fes_vendor_registered' ) {
        
            // If we are NOT auto-approving Vendors
            if ( ! (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-vendors', false ) ) {
                return 'chat.postMessage';
            }
            
        }
        
        return $webhook_url;
        
    }
    
    public function override_arguments( $args, $trigger, $notification_id ) {
        
        if ( $notification_id !== 'rbm' ) return $args;
        
        if ( $trigger == 'edd_fes_vendor_registered' ) {
        
            // If we are NOT auto-approving Vendors
            if ( ! (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-vendors', false ) ) {
                
                $args['attachments'][0]['fallback'] = $args['attachments'][0]['pretext'];
                $args['attachments'][0]['actions'] = array(
                    array(
                        'name' => 'chess',
                        'text' => 'Chess',
                        'type' => 'button',
                        'value' => 'chess'
                    ),
                    array(
                        'name' => 'chess',
                        'text' => 'Chess',
                        'type' => 'button',
                        'value' => 'chess'
                    )
                );
                
            }
            
        }
        
        return $args;
        
    }
    
}

$integrate = new EDD_Slack_App_Frontend_Submissions();
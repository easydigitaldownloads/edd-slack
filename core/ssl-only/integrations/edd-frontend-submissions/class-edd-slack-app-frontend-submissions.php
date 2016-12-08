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

if ( ! function_exists( 'edd_slack_rest_edd_fes_vendor_registered' ) ) {
    
    /**
     * EDD Slack Rest Vendor Registered Endpoint
     * 
     * @param       object $button       name and value from the Interactive Button. value should be json_decode()'d
     * @param       string $response_url Webhook to send the Response Message to
     * @param       object $payload      POST'd data from the Slack Client
     *                                                        
     * @since       1.0.0
     * @return      void
     */
    function edd_slack_rest_edd_fes_vendor_registered( $button, $response_url, $payload ) {
        
        $action = $button->name;
        $value = json_decode( $button->value );
        
        // Set depending on the Action
        $message = '';
        
        // Grab Vendor Object by User ID
        $vendor = new FES_Vendor( $value->user_id, true );
        
        if ( strtolower( $action ) == 'approve' ) {
            
            $vendor->change_status( 'approved', false, false );
            
            $message = sprintf( _x( "%s has Approved %s's Request to be a Vendor", 'Vendor Approved Response Text', EDD_Slack_ID ), $payload->user->name, $vendor->name );
            
        }
        else if ( strtolower( $action ) == 'deny' ) {
            
            // EDD FES doesn't normally let you Decline Vendors outside of the Admin, but that won't stop us
            $user = new WP_User( $vendor->user_id );

            if ( ! ( user_can( $vendor->user_id, 'subscriber' ) ) ) {
                $user->add_role( 'subscriber' ); // in case pending_vendor is the only role they have. Puts a hose onto a world that might otherwise be on fire.
            }

            if ( user_can( $vendor->user_id, 'pending_vendor' ) ) {
                $user->remove_role( 'pending_vendor' );
            }
            
            $vendor_db = new FES_DB_Vendors();
            $vendor_db->delete( $vendor->id ); // delete vendor row
            
            $message = sprintf( _x( "%s has Denied %s's Request to be a Vendor", 'Vendor Denied Response Text', EDD_Slack_ID ), $payload->user->name, $vendor->name );
            
        }
        
        // Response URLs are Incoming Webhooks
        $response_message = EDDSLACK()->slack_api->push_incoming_webhook(
            $response_url,
            array(
                'text' => $message,
            )
        );
        
    }
    
}
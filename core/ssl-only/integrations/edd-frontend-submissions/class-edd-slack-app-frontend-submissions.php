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
        
        // If we've got a linked Slack App
        if ( edd_get_option( 'slack_app_oauth_token' ) ) {
            
            // Set the new Notification API Endpoint
            add_filter( 'edd_slack_notification_webhook', array( $this, 'override_webhook' ), 10, 4 );

            // Add our Interaction Buttons
            add_filter( 'edd_slack_notification_args', array( $this, 'override_arguments' ), 10, 5 );
            
        }
        
        // Add our Trigger(s) to the Interactive Triggers Array
        add_filter( 'edd_slack_interactive_triggers', array( $this, 'add_support' ), 1, 1 );
        
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
        
        // Allow Webhook URL overrides to bail Interactive Notifications
        if ( ( $webhook_url !== '' ) &&
            ( $webhook_url !== edd_get_option( 'slack_webhook_default' ) ) ) return $webhook_url;
        
        // If our Trigger doesn't an applicable FES Trigger, bail
        if ( $trigger !== 'edd_fes_vendor_registered' && 
            $trigger !== 'edd_fes_new_vendor_product' &&
           $trigger !== 'edd_fes_edit_vendor_product' ) return $webhook_url;
        
        // If we are auto-approving Vendors, bail
        if ( $trigger == 'edd_fes_vendor_registered' && 
            (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-vendors', false ) ) return $webhook_url;
        
        // If we are auto-approving new Product Submissions, bail
        if ( $trigger == 'edd_fes_new_vendor_product' && 
           (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-submissions', false ) ) return $webhook_url;
        
        // If we are auto-approving editted Product Submissions, bail
        if ( $trigger == 'edd_fes_edit_vendor_product' && 
           (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-edits', false ) ) return $webhook_url;
        
        // If the product isn't pending, bail
        if ( ( $trigger == 'edd_fes_new_vendor_product' || $trigger == 'edd_fes_edit_vendor_product' ) && 
           isset( $args['download_id'] ) && 
           get_post_status( $args['download_id'] ) !== 'pending' ) return $webhook_url;

        return 'chat.postMessage';
        
    }
    
    /**
     * Override Notification Args for Slack App if appropriate
     * 
     * @param       string $notification_args   Args for creating the Notification
     * @param       string $webhook_url         The Webhook URL provided for the Slack Notification
     * @param       string $trigger             Notification Trigger
     * @param       string $notification_id     ID used for Notification Hooks
     * @param       array  $args                $args Array passed from the original Trigger of the process
     *                                                               
     * @access      public
     * @since       1.0.0
     * @return      array  Altered Notification Args
     */
    public function override_arguments( &$notification_args, $webhook_url, $trigger, $notification_id, $args ) {
        
        if ( $notification_id !== 'rbm' ) return $notification_args;
        
        // Allow Webhook URL overrides to bail Interactive Notifications
        if ( strpos( $webhook_url, 'hooks.slack.com' ) &&
            $webhook_url !== edd_get_option( 'slack_webhook_default' ) ) return $notification_args;
        
        // If our Trigger doesn't an applicable FES Trigger, bail
        if ( $trigger !== 'edd_fes_vendor_registered' && 
            $trigger !== 'edd_fes_new_vendor_product' && 
           $trigger !== 'edd_fes_edit_vendor_product' ) return $notification_args;
        
        // If we are auto-approving Vendors, bail
        if ( $trigger == 'edd_fes_vendor_registered' && 
            (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-vendors', false ) ) return $notification_args;
        
        // If we are auto-approving new Product Submissions, bail
        if ( $trigger == 'edd_fes_new_vendor_product' && 
           (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-submissions', false ) ) return $notification_args;
        
        // If we are auto-approving new Product Submissions, bail
        if ( $trigger == 'edd_fes_edit_vendor_product' && 
           (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-edits', false ) ) return $notification_args;
        
        // If the product isn't pending, bail
        if ( ( $trigger == 'edd_fes_new_vendor_product' || $trigger == 'edd_fes_edit_vendor_product' ) && 
           isset( $args['download_id'] ) && 
           get_post_status( $args['download_id'] ) !== 'pending' ) return $notification_args;
        
        $notification_args['attachments'][0]['actions'] = array(
            array(
                'name' => 'approve',
                'text' => _x( 'Approve', 'Approve Button Text', EDD_Slack_ID ),
                'type' => 'button',
                'style' => 'primary',
                'value' => json_encode( $args ),
            ),
            array(
                'name' => 'deny',
                'text' => _x( 'Deny', 'Deny Button Text', EDD_Slack_ID ),
                'type' => 'button',
                'style' => 'default',
                'value' => json_encode( $args ),
            )
        );
        
        if ( $trigger == 'edd_fes_vendor_registered' ) {
        
            $notification_args['attachments'][0]['actions'][1]['confirm'] = array(
                'title' => _x( 'Are you sure?', 'Confirmation Title Text', EDD_Slack_ID ),
                'text' => _x( 'They will need to re-apply to be a Vendor if this was a mistake.', 'EDD FES Vendor Denial Confirmation Explaination Text', EDD_Slack_ID ),
                'ok_text' => _x( 'Yes, I understand the risks', 'Confirmation "Yes" Button', EDD_Slack_ID ),
                'dismiss_text' => _x( 'No, I changed my mind', 'Confirmation "No" Button', EDD_Slack_ID ),
            );
            
        }
        
        if ( $trigger == 'edd_fes_edit_vendor_product' ) {
        
            $notification_args['attachments'][0]['actions'][1]['confirm'] = array(
                'title' => _x( 'Are you sure?', 'Confirmation Title Text', EDD_Slack_ID ),
                'text' => _x( 'EDD Frontend Submissions does not store old copies of Vendor Products. This will delete their Product.', 'EDD FES Vendor Product Edit Denial Confirmation Explaination Text', EDD_Slack_ID ),
                'ok_text' => _x( 'Yes, I understand the risks', 'Confirmation "Yes" Button', EDD_Slack_ID ),
                'dismiss_text' => _x( 'No, I changed my mind', 'Confirmation "No" Button', EDD_Slack_ID ),
            );
            
        }
        
        /**
         * Allow the Notification Args for the Slack App Integration to be overriden
         *
         * @since 1.0.0
         */
        $notification_args = apply_filters( 'edd_slack_app_' . $trigger . '_notification_args', $notification_args, $notification_id, $args );
        
        return $notification_args;
        
    }
    
    /**
     * Add our Trigger(s) to the Interactive Triggers Array
     * 
     * @param       array $interactive_triggers Array holding the Triggers that support Interactive Buttons
     *                                                                                              
     * @return      array Array with our added Triggers
     */
    public function add_support( $interactive_triggers ) {
        
        if ( ! (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-vendors', false ) ) {
            $interactive_triggers[] = 'edd_fes_vendor_registered';
        }
        
        // By default, Vendors cannot create their own Products
        if ( (bool) EDD_FES()->helper->get_option( 'fes-allow-vendors-to-create-products', false ) &&
           ! (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-submissions', false ) ) {
            $interactive_triggers[] = 'edd_fes_new_vendor_product';
        }
        
        // By default, Vendors cannot edit their own Products
        if ( (bool) EDD_FES()->helper->get_option( 'fes-allow-vendors-to-edit-products', false ) &&
           ! (bool) EDD_FES()->helper->get_option( 'fes-auto-approve-edits', false ) ) {
            $interactive_triggers[] = 'edd_fes_edit_vendor_product';
        }
        
        return $interactive_triggers;
        
    }
    
}

$integrate = new EDD_Slack_App_Frontend_Submissions();

if ( ! function_exists( 'edd_slack_interactive_message_edd_fes_vendor_registered' ) ) {
    
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
    function edd_slack_interactive_message_edd_fes_vendor_registered( $button, $response_url, $payload ) {
        
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

if ( ! function_exists( 'edd_slack_interactive_message_edd_fes_new_vendor_product' ) ) {
    
    /**
     * EDD Slack Rest New Vendor Product Endpoint
     * 
     * @param       object $button       name and value from the Interactive Button. value should be json_decode()'d
     * @param       string $response_url Webhook to send the Response Message to
     * @param       object $payload      POST'd data from the Slack Client
     *                                                        
     * @since       1.0.0
     * @return      void
     */
    function edd_slack_interactive_message_edd_fes_new_vendor_product( $button, $response_url, $payload ) {
        
        $action = $button->name;
        $value = json_decode( $button->value );
        
        // Set depending on the Action
        $message = '';
        
        // Grab Vendor Object by User ID
        $vendor = new FES_Vendor( $value->user_id, true );
        
        if ( strtolower( $action ) == 'approve' ) {
            
            $publish = wp_update_post( array(
                'ID' => $value->download_id,
                'post_status' => 'publish',
            ) );
            
            $message = sprintf( _x( "%s has Approved %s's Product Submission titled \"%s\"", 'Vendor Product Approved Response Text', EDD_Slack_ID ), $payload->user->name, $vendor->name, get_the_title( $value->download_id ) );
            
        }
        else if ( strtolower( $action ) == 'deny' ) {
            
            $trash = wp_update_post( array(
                'ID' => $value->download_id,
                'post_status' => 'trash',
            ) );
            
            $message = sprintf( _x( "%s has Denied %s's Product Submission titled \"%s\"", 'Vendor Product Denied Response Text', EDD_Slack_ID ), $payload->user->name, $vendor->name, get_the_title( $value->download_id ) );
            
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

if ( ! function_exists( 'edd_slack_interactive_message_edd_fes_edit_vendor_product' ) ) {
    
    /**
     * EDD Slack Rest Edited Vendor Product Endpoint
     * 
     * @param       object $button       name and value from the Interactive Button. value should be json_decode()'d
     * @param       string $response_url Webhook to send the Response Message to
     * @param       object $payload      POST'd data from the Slack Client
     *                                                        
     * @since       1.0.0
     * @return      void
     */
    function edd_slack_interactive_message_edd_fes_edit_vendor_product( $button, $response_url, $payload ) {
        
        $action = $button->name;
        $value = json_decode( $button->value );
        
        // Set depending on the Action
        $message = '';
        
        // Grab Vendor Object by User ID
        $vendor = new FES_Vendor( $value->user_id, true );
        
        if ( strtolower( $action ) == 'approve' ) {
            
            $publish = wp_update_post( array(
                'ID' => $value->download_id,
                'post_status' => 'publish',
            ) );
            
            $message = sprintf( _x( "%s has Approved %s's Product Edit for \"%s\"", 'Vendor Product Edit Approved Response Text', EDD_Slack_ID ), $payload->user->name, $vendor->name, get_the_title( $value->download_id ) );
            
        }
        else if ( strtolower( $action ) == 'deny' ) {
            
            $trash = wp_update_post( array(
                'ID' => $value->download_id,
                'post_status' => 'trash',
            ) );
            
            $message = sprintf( _x( "%s has Trashed %s's Product titled \"%s\"", 'Vendor Product Edit Denied Response Text', EDD_Slack_ID ), $payload->user->name, $vendor->name, get_the_title( $value->download_id ) );
            
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
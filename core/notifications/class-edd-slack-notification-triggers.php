<?php
/**
 * Notification Triggers for EDD Slack
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/notifications
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Notification_Triggers {

    /**
	 * EDD_Slack_Notification_Triggers constructor.
	 * 
	 * @since 1.0.0
	 */
    function __construct() {

        // Fires Successful Purchase Completed-related triggers
        add_action( 'edd_complete_purchase', array( $this, 'edd_complete_purchase' ) );
        
        // Fires when a Purchase Failed for whatever reason
        add_action( 'edd_update_payment_status', array( $this, 'edd_failed_purchase' ), 10, 3 );
        
        // Fires when a User Register's via EDD's Registration Form
        add_action( 'edd_insert_user', array( $this, 'edd_insert_user' ), 10, 2 );

    }
    
    /**
     * Send a Slack Notification on Payment Completion
     * 
     * @param       integer $payment_id Payment ID
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function edd_complete_purchase( $payment_id ) {
        
        // Basic payment meta
        $payment_meta = edd_get_payment_meta( $payment_id );
        
        // Cart details
        $cart_items = edd_get_payment_meta_cart_details( $payment_id );
        
        // This shouldn't happen, but may as well ensure it is there.
        $payment_meta['user_info']['discount'] = ( isset( $payment_meta['user_info']['discount'] ) ) ? $payment_meta['user_info']['discount'] : 'none';
        
        // Passing the same data no matter what, so here we go
        $purchase_args = array(
            'user_id' => $payment_meta['user_info']['id'],
            'payment_id' => $payment_id,
            'discount_code' => $payment_meta['user_info']['discount'],
            'cart' => wp_list_pluck( $cart_items, 'item_number', 'id' ),
            'subtotal' => edd_get_payment_subtotal( $payment_id ),
            'total' => edd_get_payment_amount( $payment_id ),
        );
        
        // If a Discount Code is Used
        if ( $payment_meta['user_info']['discount'] !== 'none' ) {
            
            do_action( 'edd_slack_notify', 'edd_discount_code_applied', $purchase_args );
            
        }
        
        // Completed Purchase always fires
        do_action( 'edd_slack_notify', 'edd_complete_purchase', $purchase_args );
        
    }
    
    /**
     * Fire a Notification on Purchase Failure
     * 
     * @param       integer $payment_id Post ID of the Payment
     * @param       string  $status     New Post Status
     * @param       string  $old_status Old Post Status
     *                                           
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function edd_failed_purchase( $payment_id, $status, $old_status ) {
            
        if ( $status == 'failed' ) {

            $customer_id = get_post_meta( $payment_id, '_edd_payment_customer_id', true );
            $customer = new EDD_Customer( $customer_id );

            // Some stuff is in a big serialized array and some stuff isn't
            $payment_meta = get_post_meta( $payment_id, '_edd_payment_meta', true );
            
            // Cart details
            $cart_items = edd_get_payment_meta_cart_details( $payment_id );

            do_action( 'edd_slack_notify', 'edd_failed_purchase', array(
                'user_id' => $customer->user_id, // If the User isn't a proper WP User, this will be 0
                'payment_id' => $payment_id,
                'name' => $payment_meta['user_info']['first_name'] . ' ' . $payment_meta['user_info']['last_name'],
                'email' => $payment_meta['user_info']['email'],
                'discount_code' => $payment_meta['user_info']['discount'],
                'ip_address' => get_post_meta( $payment_id, '_edd_payment_user_ip', true ),
                'cart' => wp_list_pluck( $cart_items, 'item_number', 'id' ),
                'subtotal' => edd_get_payment_subtotal( $payment_id ),
                'total' => edd_get_payment_amount( $payment_id ),
            ) );

        }
        
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

}
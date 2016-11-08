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

        // Fires Purchase Completed-related triggers
        add_action( 'edd_complete_purchase', array( $this, 'edd_complete_purchase' ) );
        
        // Fires when a Comment is made on a Post
        add_action( 'comment_post', array( $this, 'comment_post' ), 10, 3 );

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
            'discount_code' => $payment_meta['user_info']['discount'],
            'cart' => wp_list_pluck( $cart_items, 'quantity', 'id' ),
        );
        
        // If a Discount Code is Used
        if ( $payment_meta['user_info']['discount'] !== 'none' ) {
            
            do_action( 'edd_slack_notify', 'edd_discount_code_applied', $purchase_args );
            
        }
        
        // Completed Purchase always fires
        do_action( 'edd_slack_notify', 'edd_complete_purchase', $purchase_args );
        
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
        
        if ( get_post_type( $comment_post ) == 'download' ) {
            
            do_action( 'edd_slack_notify', 'comment_post', array(
                'comment_id' => $comment_id,
            ) );
            
        }
        
    }

}
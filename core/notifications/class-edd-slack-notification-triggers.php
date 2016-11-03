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

        add_action( 'edd_complete_purchase', array( $this, 'edd_complete_purchase' ) );

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
        
        do_action( 'edd_slack_notify', 'edd_complete_purchase', array(
            
        ) );
        
    }

}
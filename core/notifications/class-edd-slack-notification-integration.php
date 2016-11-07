<?php
/**
 * Integrating into our own Notification System. Serves as an example on how to utilize it.
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/notifications
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Notification_Integration {

    /**
	 * EDD_Slack_Notification_Integration constructor.
	 * 
	 * @since 1.0.0
	 */
    function __construct() {
        
        // Ensure we've got our own Notifications Args in the Global
        add_filter( 'edd_slack_notifications', array( $this, 'init_global_notifications' ) );
        
        // Create Notitfication to Push to Slack
        add_action( 'edd_slack_do_notification_rbm', array( $this, 'create_notification' ), 10, 5 );
        
        // Inject some Checks before we do Replacements or send the Notification
        add_action( 'edd_slack_before_replacements', array( $this, 'before_notification_replacements' ), 10, 5 );
        
        // Add our own Replacement Strings
        add_filter( 'edd_slack_notifications_replacements', array( $this, 'custom_replacement_strings' ), 10, 4 );
        
    }
    
    /**
     * Allows some flexibility in what Fields get passed
     * 
     * @param       array $notifications Global $edd_slack_notifications
     *                                          
     * @access      public
     * @since       1.0.0
     * @return      array Modified Global Array
     */
    public function init_global_notifications( $notifications ) {
        
        $notifications['rbm'] = array(
            'name' => _x( 'EDD Slack', 'Notification Feed CPT', EDD_Slack_ID ),
            'default_feed_title' => _x( 'New Slack Notification', 'Default Post Title for CPT', EDD_Slack_ID ),
            'fields' => EDDSLACK()->get_notification_fields( false ),
        );
        
        return $notifications;
        
    }
    
    /**
     * Formats the Notification Data to be passed to Slack
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
    public function create_notification( $post, $fields, $trigger, $notification_id, $args ) {

        $defaults = edd_get_option( 'slack_webhook_default' );
        
        // This allows the chance to possibly alter $args if needed
        do_action_ref_array( 'edd_slack_before_replacements', array( $post, $fields, $trigger, $notification_id, &$args ) );
        
        /**
         * Allows Notification Sending to properly Bail
         *
         * @since 1.0.0
         */
        if ( $args['bail'] ) return false;
        
		$fields = wp_parse_args( array_filter( $fields ), array(
			'webhook_url'     => ( $webhook = edd_get_option( 'slack_webhook_default') ) ? $webhook : '',
			'channel'         => '',
			'message_text'    => '',
			'message_title'   => $post->post_title,
			'message_pretext' => '',
			'color'           => '',
			'username'        => get_bloginfo( 'name' ),
			'icon'            => function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 270 ) : '',
		) );

		$replacements = EDDSLACK()->notification_handler->notifications_replacements(
			array(
				'message_text'    => $fields['message_text'],
				'message_title'   => $fields['message_title'],
				'message_pretext' => $fields['message_pretext'],
			),
			$trigger,
            $notification_id,
			$args
		);
        
		$fields['message_text']    = $replacements['message_text'];
		$fields['message_title']   = $replacements['message_title'];
		$fields['message_pretext'] = $replacements['message_pretext'];
        
        do_action( 'edd_slack_after_replacements', $post, $fields, $trigger, $notification_id, $args );

		$this->push_notification( $fields );
        
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
                'user_id' => null,
                'cart' => array(),
                'discount_code' => 'all',
                'bail' => false,
            ) );
            
            if ( $trigger == 'edd_complete_purchase' ||
               $trigger == 'edd_discount_code_applied' ) {
                
                // Cart doesn't match our Notification, bail
                if ( $fields['download'] !== 'all' && ! array_key_exists( $fields['download'], $args['cart'] ) ) {
                    $args['bail'] = true;
                    return false;
                }
                
            }
            
            if ( $trigger == 'edd_discount_code_applied' ) {
                
                // Discount Code doesn't match our Notification, bail
                if ( $fields['discount_code'] !== 'all' && $fields['discount_code'] !== $args['discount_code'] ) {
                    $args['bail'] = true;
                    return false;
                }
                
            }
            
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

                case 'edd_complete_purchase':
                case 'edd_discount_code_applied': 
                    $replacements['%discount_code%'] = $args['discount_code'];
                    break;
                    
                default:
                    break;

            }
            
        }
        
        return $replacements;
        
    }
    
    /**
     * Sends the Data to Slack
     * 
     * @param       array $fields Fully Transformed Notification Fields
     *                                                     
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function push_notification( $fields ) {
        
        // Allow Users to possibly be targeted
        if ( $fields['channel'] !== '' && strpos( $fields['channel'], '#' ) !== 0 && strpos( $fields['channel'], '@' ) !== 0 ) {
            $fields['channel'] = '#' . $fields['channel'];
        }
        
        $args = array(
			'channel'     => $fields['channel'] ? $fields['channel'] : '',
			'username'    => $fields['username'],
			'icon_emoji'  => strpos( $fields['icon'], 'http' ) === false ? $fields['icon'] : '',
			'icon_url'    => strpos( $fields['icon'], 'http' ) !== false ? $fields['icon'] : '',
			'attachments' => array(
				array(
					'text'    => $fields['message_text'],
					'title'   => $fields['message_title'],
					'pretext' => $fields['message_pretext'],
					'color'   => $fields['color'],
				),
			),
		);
        
		EDDSLACK()->slack_api->push_incoming_webhook( $fields['webhook_url'], $args );
        
    }
    
}
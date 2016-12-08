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
        add_filter( 'edd_slack_notifications_replacements', array( $this, 'custom_replacement_strings' ), 10, 5 );
        
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
        
        /**
         * Allow the Webhook URL to be overriden. Useful for Slack App Integration
         *
         * @since 1.0.0
         */
        $fields['webhook_url'] = apply_filters( 'edd_slack_notification_webhook', $fields['webhook_url'], $trigger, $notification_id, $args );
        
        // This allows the chance to possibly alter $args if needed
        do_action_ref_array( 'edd_slack_before_replacements', array( $post, $fields, $trigger, $notification_id, &$args ) );
        
        /**
         * Allows Notification Sending to properly Bail
         *
         * @since 1.0.0
         */
        if ( $args['bail'] ) return false;

		$replacements = EDDSLACK()->notification_handler->notifications_replacements(
			array(
				'message_text'    => $fields['message_text'],
				'message_title'   => $fields['message_title'],
				'message_pretext' => $fields['message_pretext'],
			),
            $fields,
			$trigger,
            $notification_id,
			$args
		);
        
		$fields['message_text']    = $replacements['message_text'];
		$fields['message_title']   = $replacements['message_title'];
		$fields['message_pretext'] = $replacements['message_pretext'];
        
        do_action( 'edd_slack_after_replacements', $post, $fields, $trigger, $notification_id, $args );

		$this->push_notification( $fields, $trigger, $notification_id, $args );
        
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
                'comment_id' => 0,
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
     * @param       array  $fields          Fields used to create the Post Meta
     * @param       string $trigger         Notification Trigger
     * @param       string $notification_id ID used for Notification Hooks
     * @param       array  $args            $args Array passed from the original Trigger of the process
     * 
     * @access      public
     * @since       1.0.0
     * @return      array  Replaced Strings within each Field
     */
    public function custom_replacement_strings( $replacements, $fields, $trigger, $notification_id, $args ) {
        
        // If this customer did not create an Account
        if ( $args['user_id'] == 0 ) {
            $replacements['%email%'] = $args['email'];
            $replacements['%name%'] = $args['name'];
            $replacements['%username%'] = _x( 'This Customer does not have an account', 'No Username Replacement Text', EDD_Slack_ID );
        }

        if ( $notification_id == 'rbm' ) {

            switch ( $trigger ) {

                case 'edd_complete_purchase':
                case 'edd_discount_code_applied': 
                case 'edd_failed_purchase':
                    
                    // Display a nicer message in the event of no Discount Code being used
                    if ( $args['discount_code'] == 'none' ) {
                        $args['discount_code'] = _x( 'No Discount Code Applied', 'No Discount Code Applied Text', EDD_Slack_ID );
                    }
                    
                    $replacements['%discount_code%'] = $args['discount_code'];
                    $replacements['%ip_address%'] = $args['ip_address'];
                    $replacements['%subtotal%'] = edd_currency_filter( number_format( $args['subtotal'], 2 ) );
                    $replacements['%total%'] = edd_currency_filter( number_format( $args['total'], 2 ) );
                    
                    $payment_link = add_query_arg( 'id', $args['payment_id'], admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details' ) );
                    
                    // If we're using a regular Webhook
                    if ( strpos( $fields['webhook_url'], 'hooks.slack.com' ) ) {
                        $payment_link = urlencode_deep( add_query_arg( 'id', $args['payment_id'], admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details' ) ) );
                    }
                    
                    $replacements['%payment_link%'] = '<' . $payment_link . '|' . _x( 'View Payment Details', 'View Payment Details Link', EDD_Slack_ID ) . '>'; // No function to get this?
                    
                    $replacements['%cart%'] = '';
                    foreach ( $args['cart'] as $post_id => $item_number ) {
                        
                        // If it is not a variable download
                        if ( ! edd_has_variable_prices( $post_id ) ) {
                            
                            $replacements['%cart%'] .= "&bull; " . get_the_title( $post_id ) . "\n";
                            $replacements['%cart%'] .= "\t&bull; " . edd_currency_filter( edd_get_download_price( $post_id ) ) . "\n";
                            
                        }
                        else {
                            
                            $replacements['%cart%'] .= "&bull; " . get_the_title( $post_id ) . "\n";
                            $replacements['%cart%'] .= "\t&bull; " . edd_get_price_option_name( $post_id, $item_number['options']['price_id'] ) . " - " . edd_currency_filter( edd_get_price_option_amount( $post_id, $item_number['options']['price_id'] ) ) . "\n";
                            
                        }
                        
                    }
                    
                    // This shouldn't happen, but I guess you never know
                    if ( empty( $replacements['%cart%'] ) ) {
                        $replacements['%cart%'] = _x( 'There was nothing in the Cart', 'Empty Cart Replacement Text', EDD_Slack_ID );
                    }
                    
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
     * @param       array  $fields          Fully Transformed Notification Fields
     * @param       string $trigger         Notification Trigger
     * @param       string $notification_id ID used for Notification Hooks
     * @param       array  $args            $args Array passed from the original Trigger of the process
     *                                                     
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function push_notification( $fields, $trigger, $notification_id, $args ) {
        
        // Allow Users to possibly be targeted
        if ( $fields['channel'] !== '' && strpos( $fields['channel'], '#' ) !== 0 && strpos( $fields['channel'], '@' ) !== 0 ) {
            $fields['channel'] = '#' . $fields['channel'];
        }
        
        $notification_args = array(
			'channel'     => $fields['channel'] ? $fields['channel'] : '',
			'username'    => $fields['username'],
			'icon_emoji'  => strpos( $fields['icon'], 'http' ) === false ? $fields['icon'] : '',
			'icon_url'    => strpos( $fields['icon'], 'http' ) !== false ? $fields['icon'] : '',
			'attachments' => array(
				array(
					'text'    => html_entity_decode( $fields['message_text'] ),
					'title'   => html_entity_decode( $fields['message_title'] ),
					'pretext' => html_entity_decode( $fields['message_pretext'] ),
					'color'   => $fields['color'],
				),
			),
		);
        
        /**
         * Allow the Notification Args to be overriden. Useful for Slack App Integration
         *
         * @since 1.0.0
         */
        $notification_args = apply_filters( 'edd_slack_notification_args', $notification_args, $trigger, $notification_id, $args );
        
        // If we're using a regular Webhook
        if ( strpos( $fields['webhook_url'], 'hooks.slack.com' ) ) {
        
            $message = EDDSLACK()->slack_api->push_incoming_webhook( $fields['webhook_url'], $notification_args );
            var_dump( $message );
            die();
            
        }
        else { // Send it via Slack's Web API
                
            $default_channel = edd_get_option( 'slack_app_channel_default' );
            $default_channel = ( empty( $default_channel ) ) ? '#general' : $default_channel; // Since it can be saved as an empty value

            $default_icon = edd_get_option( 'slack_app_icon_default' );

            // Remove keys with empty strings as their value
            $notification_args = array_filter( $notification_args );

            $notification_args = wp_parse_args( $notification_args, array(
                'channel' => $default_channel,
                'icon_emoji' => strpos( $default_icon, 'http' ) === false ? $default_icon : '',
                'icon_url' => strpos( $default_icon, 'http' ) !== false ? $default_icon : '',
                'as_user' => 'false', // Posts as a "Bot" which allows customization of the Username and Icon
                'text' => '', // We are defining Text as an Attachment, but the API requires SOMETHING here
            ) );
            
            // You can't use wp_parse_args() to target nested Array Indices apparently
            $notification_args['attachments'][0] = wp_parse_args( $notification_args['attachments'][0], array(
                'callback_id' => $trigger, // Constructs the Routing function for the WP REST API
                'fallback' => $notification_args['attachments'][0]['pretext'],
            ) );
            
            // Construct the URL using the $args from the Notification that have been filtered
            $message_url = add_query_arg( 
                EDDSLACK()->slack_api->encode_arguments( $notification_args ),
                $fields['webhook_url']
            );
            
            // Doing it this way also automagically includes our OAUTH Token
            $message = EDDSLACK()->slack_api->post(
                $message_url
            );
            
        }
        
    }
    
}
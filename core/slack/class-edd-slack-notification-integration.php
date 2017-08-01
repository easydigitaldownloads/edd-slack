<?php
/**
 * Integrating into our own Notification System. Serves as an example on how to utilize it.
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/slack
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
	 * @param	  array $notifications Global $edd_slack_notifications
	 *										  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array Modified Global Array
	 */
	public function init_global_notifications( $notifications ) {
		
		$notifications['rbm'] = array(
			'name' => _x( 'EDD Slack', 'Notification Feed CPT', 'edd-slack' ),
			'default_feed_title' => _x( 'New Slack Notification', 'Default Post Title for CPT', 'edd-slack' ),
			'fields' => EDDSLACK()->get_notification_fields( false ),
		);
		
		return $notifications;
		
	}
	
	/**
	 * Formats the Notification Data to be passed to Slack
	 * 
	 * @param	  object  $post			WP_Post Object for our Saved Notification Data
	 * @param	  array   $fields		  Fields used to create the Post Meta
	 * @param	  string  $trigger		 Notification Trigger
	 * @param	  string  $notification_id ID Used for Notification Hooks
	 * @param	  array   $args			$args Array passed from the original Trigger of the process
	 *			  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function create_notification( $post, $fields, $trigger, $notification_id, $args ) {
		
		// Ensure we don't end up with non-set errors
		$fields['webhook_url'] = ( isset( $fields['webhook'] ) ) ? $fields['webhook'] : '';
		
		/**
		 * Allow the Webhook URL to be overriden. Useful for Slack App Integration
		 * Do this before parse_args so we have more control over whether or not this Filter should change things
		 *
		 * @since 1.0.0
		 */
		$fields['webhook_url'] = apply_filters( 'edd_slack_notification_webhook', $fields['webhook_url'], $trigger, $notification_id, $args );
		
		// Throw in some defaults
		$fields = wp_parse_args( array_filter( $fields ), array(
			'webhook_url'	 => ( $webhook = edd_get_option( 'slack_webhook_default') ) ? $webhook : '',
			'channel'		 => '',
			'message_text'	=> '',
			'message_title'   => $post->post_title,
			'message_pretext' => '',
			'color'		  => '',
			'username'		=> get_bloginfo( 'name' ),
			'icon'			=> function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 270 ) : '',
		) );
		
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
				'message_text'	=> $fields['message_text'],
				'message_title'   => $fields['message_title'],
				'message_pretext' => $fields['message_pretext'],
			),
			$fields,
			$trigger,
			$notification_id,
			$args
		);
		
		$fields['message_text']	= $replacements['message_text'];
		$fields['message_title']   = $replacements['message_title'];
		$fields['message_pretext'] = $replacements['message_pretext'];
		
		do_action( 'edd_slack_after_replacements', $post, $fields, $trigger, $notification_id, $args );

		$this->push_notification( $fields, $trigger, $notification_id, $args );
		
	}
	
	/**
	 * Inject some checks on whether or not to bail on the Notification
	 * 
	 * @param	  object  $post			WP_Post Object for our Saved Notification Data
	 * @param	  array   $fields		  Fields used to create the Post Meta
	 * @param	  string  $trigger		 Notification Trigger
	 * @param	  string  $notification_id ID Used for Notification Hooks
	 * @param	  array   $args			$args Array passed from the original Trigger of the process
	 *			  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
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
				$trigger == 'edd_failed_purchase' ||
			  $trigger == 'edd_discount_code_applied' ) {
				
				// Support for EDD Slack v1.0.X
				if ( ! is_array( $fields['download'] ) ) $fields['download'] = array( $fields['download'] );

				// If all are allowed, it doesn't matter what the other settings are
				if ( $fields['download'][0] !== 'all' ) {

					// Make Array of Download ID => Price ID for each Selection in the Notification
					$downloads_array = array();
					foreach ( $fields['download'] as $item ) {

						$item = $this->check_for_price_id( $item );

						if ( ! isset( $downloads_array[ $item['download_id'] ] ) ) $downloads_array[ $item['download_id'] ] = array();

						if ( $item['price_id'] === null ) {
							$item['price_id'] = 0; // Match output from the Cart
						}

						$downloads_array[ $item['download_id'] ][] = $item['price_id'];

					}

					// Make Array of Download ID => Price ID for each Exclusion for the Notification
					$exclude_downloads_array = array();
					foreach ( $fields['exclude_download'] as $item ) {

						$item = $this->check_for_price_id( $item );

						if ( ! isset( $exclude_downloads_array[ $item['download_id'] ] ) ) $exclude_downloads_array[ $item['download_id'] ] = array();

						if ( $item['price_id'] === null ) {
							$item['price_id'] = 0; // Match output from the Cart
						}

						$exclude_downloads_array[ $item['download_id'] ][] = $item['price_id'];

					}

					// We don't care about the number of each item in the cart, we only care about the Download and Price IDs
					$cart_contents = array();
					foreach ( $args['cart'] as $item ) {

						if ( ! isset( $cart_contents[ $item['id'] ] ) ) $cart_contents[ $item['id'] ] = array();

						$cart_contents[ $item['id'] ][] = (int) $item['item_number']['options']['price_id'];

					}
					
					// Cart doesn't have our Download ID, bail
					if ( empty( array_intersect_key( $downloads_array, $cart_contents ) ) ) {
						$args['bail'] = true;
						return false;
					}
					
					// While it is discouraged through how the Checkout Process works, it IS POSSIBLE to have two Variants of the same Download in your Cart at once
					// I have had a hard time reproducing it, but I did manage it once. This code takes into account the possiblity of that happening

					// Cart doesn't have our Price ID, bail
					// This can't be done as fancily as the Download ID
					$price_id_bail = true;
					foreach ( $downloads_array as $download_id => $price_ids ) {
						
						if ( isset( $cart_contents[ $download_id ] ) ) {
							
							// If there's a difference between the two arrays of Price IDs, then we know it exists in the Cart
							if ( $price_ids !== array_diff( $price_ids, $cart_contents[ $download_id ] ) ) {
								$price_id_bail = false;
							}
							
						}

					}
					
					if ( $price_id_bail ) {
						
						$args['bail'] = true;
						return false;
						
					}
					
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
	 * @param	  array  $replacements	Notification Fields to check for replacements in
	 * @param	  array  $fields		  Fields used to create the Post Meta
	 * @param	  string $trigger		 Notification Trigger
	 * @param	  string $notification_id ID used for Notification Hooks
	 * @param	  array  $args			$args Array passed from the original Trigger of the process
	 * 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array  Replaced Strings within each Field
	 */
	public function custom_replacement_strings( $replacements, $fields, $trigger, $notification_id, $args ) {
		
		// If this customer did not create an Account
		if ( $args['user_id'] == 0 ) {
			$replacements['%email%'] = $args['email'];
			$replacements['%name%'] = $args['name'];
			$replacements['%username%'] = _x( 'This Customer does not have an account', 'No Username Replacement Text', 'edd-slack' );
		}

		if ( $notification_id == 'rbm' ) {

			switch ( $trigger ) {

				case 'edd_complete_purchase':
				case 'edd_discount_code_applied': 
				case 'edd_failed_purchase':
					
					// Display a nicer message in the event of no Discount Code being used
					if ( $args['discount_code'] == 'none' ) {
						$args['discount_code'] = _x( 'No Discount Code Applied', 'No Discount Code Applied Text', 'edd-slack' );
					}
					
					$replacements['%discount_code%'] = $args['discount_code'];
					$replacements['%ip_address%'] = $args['ip_address'];
					$replacements['%subtotal%'] = edd_currency_filter( number_format( $args['subtotal'], 2 ) );
					$replacements['%total%'] = edd_currency_filter( number_format( $args['total'], 2 ) );
					
					$payment_link = add_query_arg( 'id', $args['payment_id'], admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details' ) );
					
					$replacements['%payment_link%'] = '<' . $payment_link . '|' . _x( 'View Payment Details', 'View Payment Details Link', 'edd-slack' ) . '>'; // No function to get this?
					
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
						$replacements['%cart%'] = _x( 'There was nothing in the Cart', 'Empty Cart Replacement Text', 'edd-slack' );
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
	 * @param	  array  $fields		  Fully Transformed Notification Fields
	 * @param	  string $trigger		 Notification Trigger
	 * @param	  string $notification_id ID used for Notification Hooks
	 * @param	  array  $args			$args Array passed from the original Trigger of the process
	 *													 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function push_notification( $fields, $trigger, $notification_id, $args ) {
		
		// Allow Users to possibly be targeted
		if ( $fields['channel'] !== '' && strpos( $fields['channel'], '#' ) !== 0 && strpos( $fields['channel'], '@' ) !== 0 ) {
			$fields['channel'] = '#' . $fields['channel'];
		}
		
		$fields['icon'] = $this->format_icon_emoji( $fields['icon'] );
		
		$notification_args = array(
			'channel'	 => $fields['channel'] ? $fields['channel'] : '',
			'username'	=> $fields['username'],
			'icon_emoji'  => strpos( $fields['icon'], 'http' ) === false ? $fields['icon'] : '',
			'icon_url'	=> strpos( $fields['icon'], 'http' ) !== false ? $fields['icon'] : '',
			'attachments' => array(
				array(
					'text'	=> html_entity_decode( $fields['message_text'] ),
					'title'   => html_entity_decode( $fields['message_title'] ),
					'pretext' => html_entity_decode( $fields['message_pretext'] ),
					'color'   => $fields['color'],
				),
			),
		);
		
		/**
		 * Allow the Notification Args to be overriden. Useful for Slack App Integration
		 * Passing $notification_args by reference to help ensure that each Integration doesn't override another
		 *
		 * @since 1.0.0
		 */
		$notification_args = apply_filters_ref_array( 'edd_slack_notification_args', array( &$notification_args, $fields['webhook_url'], $trigger, $notification_id, $args ) );
		
		// If we're using a regular Webhook
		if ( strpos( $fields['webhook_url'], 'hooks.slack.com' ) ) {
		
			$message = EDDSLACK()->slack_api->push_incoming_webhook( $fields['webhook_url'], $notification_args );
			
		}
		else { // Send it via Slack's Web API
			
			$general_channel = '#' . apply_filters( 'edd_slack_general_channel', 'general' );
			
			$default_channel = edd_get_option( 'slack_app_channel_default' );
			$default_channel = ( empty( $default_channel ) ) ? $general_channel : $default_channel; // Since it can be saved as an empty value

			$default_icon = $this->format_icon_emoji( edd_get_option( 'slack_app_icon_default' ) );

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
				'fallback' => ( isset( $notification_args['attachments'][0]['pretext'] ) ) ? $notification_args['attachments'][0]['pretext'] : $notification_args['attachments'][0]['title'],
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
	
	/**
	 * Ensures an Emoji passed to Slack is formatted correctly
	 * 
	 * @param	  string $icon_emoji Image/Emoji String
	 *											 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  string Image/Emoji String
	 */
	public function format_icon_emoji( $icon_emoji ) {
		
		// If an image was passed through
		if ( strpos( $icon_emoji, 'http' ) !== false ) return $icon_emoji;
		
		// Sanitize the emoji string somewhat
		$icon_emoji = preg_replace( '/\W/i', '', $icon_emoji );
		
		// If it is empty, pass it as empty so the Webhook can handle it
		if ( empty( $icon_emoji ) ) return $icon_emoji;
		
		// Otherwise ensure it is wrapped by colons
		return ':' . $icon_emoji . ':';
		
	}
	
	/**
	 * One day HTML will let me reliably pass more than one value from a single field, but until then...
	 * 
	 * @param	  string $download String Download ID with a Price ID or just a Download ID
	 *																					 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array  Download ID and Price ID, if applicable
	 */
	public function check_for_price_id( $download ) {
		
		// Fallbacks for Downloads with no Price ID defined
		$download_id = $download;
		$price_id = null;
		
		// If there's a Price ID
		if ( strpos( $download, '-' ) !== false ) {
					
			$download = explode( '-', $download );

			// First half is Download ID
			$download_id = $download[0];

			// Second half is Price ID
			$price_id = $download[1];

		}
	  
		return array(
			'download_id' => $download_id,
			'price_id' => $price_id,
		);
		
	}
	
	/**
	 * Create/Update Notification Feed Posts via your Settings Interface
	 *																										  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public static function update_feed() {
		
		if ( is_admin() && current_user_can( 'manage_shop_settings' ) ) {
		
			global $edd_slack_notifications;

			$edd_slack_notifications = apply_filters( 'edd_slack_notifications', array() );

			$notification_id = apply_filters( 'edd_slack_notification_id', 'rbm' );
			$notification_args = $edd_slack_notifications[ $notification_id ];

			$notification_args = wp_parse_args( $notification_args, array(
				'default_feed_title' => _x( 'New Slack Notification', 'New Slack Notification Header', 'edd-slack' ),
				'fields'			 => array(),
			) );

			$post_args = array(
				'ID'		  => (int) $_POST['slack_post_id'] > 0 ? (int) $_POST['slack_post_id'] : 0,
				'post_type'   => "edd-slack-{$notification_id}-feed",
				'post_title'  => '',
				'post_status' => 'publish',
			);

			$notification_meta = array();

			foreach ( $notification_args['fields'] as $field_name => $field ) {

				if ( isset( $_POST[ $field_name ] ) ) {

					if ( $field_name == 'post_id' || $field_name == 'admin_title' ) continue;

					$notification_meta["edd_slack_{$notification_id}_feed_$field_name"] = $_POST[ $field_name ];

				}

			}

			if ( $_POST['admin_title'] ) {
				$post_args['post_title'] = $_POST['admin_title'];
			}
			else {
				$post_args['post_title'] = $notification_args['default_feed_title'];
			}

			$post_id = wp_insert_post( $post_args );

			if ( $post_id !== 0 && ! is_wp_error( $post_id ) ) {

				foreach ( $notification_meta as $field_name => $field_value ) {

					if ( $field_name == 'slack_post_id' || $field_name == 'admin_title' ) continue;

					update_post_meta( $post_id, $field_name, $field_value );

				}

			}
			else {

				return wp_send_json_error( array(
					'error' => $post_id, // $post_id holds WP_Error object in this case
				) );

			}

			return wp_send_json_success( array(
				'post_id' => $post_id,
			) );
			
		}
		
		return wp_send_json_error( array(
			'error' => _x( 'Access Denied', 'Current User Cannot Create Notications Error', 'edd-slack' ),
		) );
		
	}
	
	/**
	 * Delete Feed Posts via ID
	 * 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public static function delete_feed() {
		
		if ( is_admin() && current_user_can( 'manage_shop_settings' ) ) {

			$post_id = $_POST['post_id'];

			$success = wp_delete_post( $post_id, true );

			if ( $success ) {
				return wp_send_json_success();
			}
			else {
				return wp_send_json_error();
			}
			
		}
		
		return wp_send_json_error( array(
			'error' => _x( 'Access Denied', 'Current User Cannot Delete Notications Error', 'edd-slack' ),
		) );

	}
	
}

// AJAX Hook for Inserting new/updating Notifications
add_action( 'wp_ajax_insert_edd_rbm_slack_notification', array( 'EDD_Slack_Notification_Integration', 'update_feed' ) );

// AJAX Hook for Deleting Notifications
add_action( 'wp_ajax_delete_edd_rbm_slack_notification', array( 'EDD_Slack_Notification_Integration', 'delete_feed' ) );
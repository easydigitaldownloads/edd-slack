<?php
/**
 * Callback Functions for Slash Commands that aren't dependent on other Integrations
 *
 * @since	  1.0.0
 *
 * @package	EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only/slash-commands
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! function_exists( 'edd_slack_slash_command_help' ) ) {
	
	/**
	 * Return Slash Command Documentation via /edd help
	 * 
	 * @param	  string $scope		Which Command to show Help for
	 * @param	  string $response_url Webhook to send the Response Message to
	 * @param	  array  $request_body POST'd data from the Slack Client
	 *															  
	 * @since	  1.0.0
	 * @return	  void
	 */
	function edd_slack_slash_command_help( $scope = 'all', $response_url, $request_body ) {
		
		/**
		 * Allows Integrations to add their own Help Text for their Slash Command Callbacks
		 * 
		 * If does not have Options, don't set a Default either
		 *
		 * @since 1.0.0
		 */
		$commands = apply_filters( 'edd_slack_slash_command_help_response', array(
			'sales' => array(
				'description' => _x( 'Show an Earnings Report for the Selected Time Period', '/edd sales Description', 'edd-slack' ),
				'options' => array( 
					'today',
					'yesterday',
					'this_week',
					'last_week',
					'this_month',
					'last_month',
					'this_quarter',
					'last_quarter',
					'this_year',
					'last_year',
				),				 
				'default' => 'this_month',
			),
			'version' => array(
				'description' => _x( 'Outputs the current version of Easy Digital Downloads.', '/edd version Description', 'edd-slack' ),
			),
			'discount' => array(
				'description' => _x( 'Outputs information about a Discount Code. This can also be used to create new Discount Codes.', '/edd discount Description', 'edd-slack' ),
				'examples' => array(
					_x( 'DISCOUNTCODE', '/edd discount Default Example', 'edd-slack' ),
					sprintf( _x( 'DISCOUNTCODE %s', '/edd discount Dollar Amount Example', 'edd-slack' ), html_entity_decode( edd_currency_filter( '3.50' ) ) ),
					sprintf( _x( 'DISCOUNTCODE 42%%', '/edd discount Percentage Example', 'edd-slack' ) ),
				),
			),
		) );
		
		$commands['help'] = array(
			'description' => _x( 'Shows this Dialog. Optionally can show the Help Dialog for a Single Command.', '/edd help Description', 'edd-slack' ),
			'options' => array_keys( $commands ),
		);
		
		// Allow Users to specify which Slash Command Callback they want to see help for
		if ( ! empty( $scope ) &&
		  isset( $commands[ $scope ] ) ) {
			
			$commands = array(
				$scope => $commands[ $scope ]
			);
			
		}
		
		$message_text = '';
		foreach ( $commands as $command => $args ) {
			
			// If it isn't the first loop, add a space between Commands
			if ( ! empty ( $message_text ) ) $message_text .= "\n\n";
			
			$title = sprintf( '*%s*', $request_body['command'] . ' ' . $command );
			
			if ( isset( $args['description'] ) && ! empty( $args['description'] ) ) {
				$description = "\n" . sprintf( _x( 'Description: %s', 'Slash Command Description', 'edd-slack' ), $args['description'] );
			}
			else {
				$description = '';
			}
			
			// Allow Examples to be explicitly provided for more complex commands
			if ( isset( $args['examples'] ) && 
			   ! empty ( $args['examples'] ) ) {
				
				$example = "\n" . _x( 'Examples:', 'Slash Command Multiple Examples', 'edd-slack' );
				
				foreach ( $args['examples'] as $example_text ) {
					
					$example .= "\n\t`" . $request_body['command'] . ' ' . $command . ' ' . $example_text . '`';
					
				}
				
			}
			else {
			
				$example = "\n" . sprintf( _x( 'Example: `%s', 'Slash Command Example', 'edd-slack' ), $request_body['command'] . ' ' . $command );

				// If there's a default set, make some strings
				if ( isset( $args['default'] ) && ! empty( $args['default'] ) ) {
					$example .= ' ' . $args['default'] . '`';
					$default = "\n" . sprintf( _x( 'Default: `%s`', 'Slash Command Default', 'edd-slack' ), $args['default'] );
				}
				else {
					$example .= '`';
					$default = '';
				}
				
			}
			
			if ( isset( $args['options'] ) && ! empty( $args['options'] ) ) {
				
				// Ensure we've got an Array
				if ( ! is_array( $args['options'] ) ) $args['options'] = array( $args['options'] );
				
				$options = '';
				foreach ( $args['options'] as $option ) {
					
					$options .= sprintf( '`%s`, ', $option );
					
				}
				
				// Append our Options
				$options = "\n" . sprintf( _x( 'Option(s): %s', 'Slash Command Options', 'edd-slack' ), rtrim( $options, ', ' ) );
				
			}
			else {
				$options = '';
			}
			
			$message_text .= $title . $description . $example . $options . $default;
			
		}
		
		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'text' => $message_text,
				'username' => get_bloginfo( 'name' ),
				'icon' => function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 270 ) : '',
			)
		);
		
	}
	
}

if ( ! function_exists( 'edd_slack_slash_command_sales' ) ) {

	/**
	 * Return an Earnings Report via /edd sales
	 * 
	 * @param	  string $date_range   The Date range to return Earnings for
	 * @param	  string $response_url Webhook to send the Response Message to
	 * @param	  array  $request_body POST'd data from the Slack Client
	 *															  
	 * @since	  1.0.0
	 * @return	  void
	 */
	function edd_slack_slash_command_sales( $date_range, $response_url, $request_body ) {

		// Get dates based on our Date Range
		$dates = EDDSLACK()->slack_rest_api->edd_get_report_dates( $date_range );

		// Get values based on our dates
		$values = EDDSLACK()->slack_rest_api->edd_get_report_values( $dates );

		$date_options = apply_filters( 'edd_report_date_options', array(
			'today'		=> __( 'Today', 'easy-digital-downloads' ),
			'yesterday'	=> __( 'Yesterday', 'easy-digital-downloads' ),
			'this_week'	=> __( 'This Week', 'easy-digital-downloads' ),
			'last_week'	=> __( 'Last Week', 'easy-digital-downloads' ),
			'this_month'   => __( 'This Month', 'easy-digital-downloads' ),
			'last_month'   => __( 'Last Month', 'easy-digital-downloads' ),
			'this_quarter' => __( 'This Quarter', 'easy-digital-downloads' ),
			'last_quarter' => __( 'Last Quarter', 'easy-digital-downloads' ),
			'this_year'	=> __( 'This Year', 'easy-digital-downloads' ),
			'last_year'	=> __( 'Last Year', 'easy-digital-downloads' ),
			'other'		=> __( 'Custom', 'easy-digital-downloads' )
		) );

		// Gets a Human Readable String for the Response
		$human_date_range = $date_options[ $dates['range'] ];

		$attachments = array(
			array(
				'title' => sprintf( _x( 'Earnings Report for %s', 'Earnings Report for this Period /edd sales', 'edd-slack' ), $human_date_range ),
				'text' => html_entity_decode( sprintf( _x( 'Total Earnings for %s: %s', 'Total Earnings for this Period /edd sales', 'edd-slack' ), $human_date_range, edd_currency_filter( edd_format_amount( $values['earnings_totals'] ) ) ) ) . "\n"
					. html_entity_decode( sprintf( _x( 'Total Sales for %s: %s', 'Total Sales for this Period /edd sales', 'edd-slack' ), $human_date_range, edd_format_amount( $values['sales_totals'], false ) ) ),
			),
		);

		// If we're checking for the Month, also show Estimations
		if ( $dates['range'] == 'this_month' ) {

			$attachments[0]['text'] .= "\n" . html_entity_decode( sprintf( _x( 'Estimated Monthly Earnings: %s', 'Estimated Montly Earnings /eddsales', 'edd-slack' ), edd_currency_filter( edd_format_amount( $values['estimated']['earnings'] ) ) ) ) . "\n"
				. html_entity_decode( sprintf( _x( 'Estimated Monthly Sales: %s', 'Estimated Montly Sales /edd sales', 'edd-slack' ), edd_format_amount( $values['estimated']['sales'], false ) ) );

		}

		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'username' => get_bloginfo( 'name' ),
				'icon' => function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 270 ) : '',
				'attachments' => $attachments,
			)
		);

	}
	
}

if ( ! function_exists( 'edd_slack_slash_command_version' ) ) {

	/**
	 * Return the version of EDD via /edd version
	 * 
	 * @param	  string $change	   If this String is non-empty, we're changing the Version (Unused)
	 * @param	  string $response_url Webhook to send the Response Message to
	 * @param	  array  $request_body POST'd data from the Slack Client
	 *															  
	 * @since	  1.1.0
	 * @return	  void
	 */
	function edd_slack_slash_command_version( $change, $response_url, $request_body ) {
		
		$attachments = array(
			array(
				'title' => _x( 'EDD Version', 'Title for /edd version', 'edd-slack' ),
				'text' => _x( 'EDD is currently at v', '"v" prefix for /edd version', 'edd-slack' ) . EDD_VERSION,
			),
		);
		
		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'username' => get_bloginfo( 'name' ),
				'icon' => function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 270 ) : '',
				'attachments' => $attachments,
			)
		);
		
	}
	
}

if ( ! function_exists( 'edd_slack_slash_command_discount' ) ) {

	/**
	 * Return information about a discount code
	 * Alternatively, create one if it does not exist
	 * 
	 * @param	  string $values	   If this String is non-empty, we're changing the Version (Unused)
	 * @param	  string $response_url Webhook to send the Response Message to
	 * @param	  array  $request_body POST'd data from the Slack Client
	 *															  
	 * @since	  1.1.0
	 * @return	  void
	 */
	function edd_slack_slash_command_discount( $values, $response_url, $request_body ) {
		
		$values = explode( ' ', trim( $values ) );
		
		// Alphanumeric Only
		$discount_code = trim( $values[0] );
		$discount_code = preg_replace( '/([^A-Z0-9])+/i', '', $discount_code );
		
		// Determine whether we're creating a Discount Code or not
		$amount = ( isset( $values[1] ) && ! empty ( $values[1] ) ) ? trim( $values[1] ) : false;
		
		$response_text = '';
		
		// If amount was passed, then we are creating a Discount
		if ( $amount ) {
			
			$discount = new EDD_Discount( $discount_post_id, false, false );
			
			$discount->__set( 'name', $discount_code );
			$discount->__set( 'code', $discount_code );
			$discount->__set( 'status', 'active' );
			
			if ( strpos( $amount, '%' ) !== false ) {
				$discount->__set( 'type', 'percent' );
			}
			else {
				$discount->__set( 'type', 'flat' );
			}
			
			// Send in "pure" numbers
			$discount->__set( 'amount', preg_replace( '/([^0-9|.])+/', '', $amount ) );
			
			$discount->save();
			
			$response_title = sprintf( _x( 'Discount Code Created: %s', 'Title for /edd discount Creation', 'edd-slack' ), $discount_code );
			
		}
		else {
			
			$discount = new EDD_Discount( $discount_code, true );
			$response_title = sprintf( _x( 'Discount Code: %s', 'Title for /edd discount Data', 'edd-slack' ), $discount->get_code() );
			
		}
		
		$success = false;
		
		// Discount exists, either previously or was just created
		if ( $discount->get_ID() !== 0 ) {
			
			$success = true;
		
			$date_format = get_option( 'date_format', 'F j, Y' );
			$time_format = get_option( 'time_format', 'g:i a' );

			$discount_amount = ( $discount->get_type() == 'flat' ) ? html_entity_decode( edd_currency_filter( number_format( $discount->get_amount(), 2 ) ) ) : $discount->get_amount() . '%';
			$discount_start = ( $discount->get_start() ) ? date_i18n( $date_format . ' ' . $time_format, strtotime( $discount->get_start() ) ) : _x( 'No Start Date', 'Discount No Start Date', 'edd-slack' );
			$discount_expiration = ( $discount->get_expiration() ) ? date_i18n( $date_format . ' ' . $time_format, strtotime( $discount->get_expiration() ) ) : _x( 'No Expiration Date', 'Discount No Expiration Date', 'edd-slack' );
			$discount_status = ( $discount->get_status() == 'active' ) ? __( 'Active', 'edd-slack' ) : __( 'Inactive', 'edd-slack' );

			$response_text .= _x( 'Name: ', 'Discount Name', 'edd-slack' ) . $discount->get_name();
			$response_text .= "\n" . _x( 'Amount: ', 'Discount Amount', 'edd-slack' ) . $discount_amount;
			$response_text .= "\n" . _x( 'Uses: ', 'Discount Uses', 'edd-slack' ) . $discount->get_uses();
			$response_text .= "\n" . _x( 'Start Date: ', 'Discount Start Date', 'edd-slack' ) . $discount_start;
			$response_text .= "\n" . _x( 'Expiration: ', 'Discount Expiration', 'edd-slack' ) . $discount_expiration;
			$response_text .= "\n" . _x( 'Status: ', 'Discount Status', 'edd-slack' ) . $discount_status;
			$response_text .= "\n\n" . '<' . $discount->edit_url() . '|' . _x( 'Edit this Discount Code', 'Edit Discount Code Link Text', 'edd-slack' ) . '>';
			
		}
		else {
			
			$response_text = '*' . sprintf( _x( 'Discount Code %s Not Found', 'Title for /edd discount Creation Failure', 'edd-slack' ), $discount_code ) . '*';
			
			$response_text .= "\n" . sprintf( _x( "Were you trying to create a Discount Code? You need to include an Amount to do so. Run %s for examples.", 'Were you trying to create a Discount Code helper text', 'edd-slack' ), '`' . $request_body['command'] . ' help discount' . '`' );
			
		}
		
		$notification_args = array(
			'username' => get_bloginfo( 'name' ),
			'icon' => function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 270 ) : '',
		);
		
		// On Failure we want to use `` code designators, but that does not work with Attachments
		if ( $success ) {
			
			$attachments = array(
				array(
					'title' => $response_title,
					'text' => $response_text,
				),
			);
			
			$notification_args['attachments'] = $attachments;
			
		}
		else {
			$notification_args['text'] = $response_text;
		}
		
		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			$notification_args
		);
		
	}
	
}
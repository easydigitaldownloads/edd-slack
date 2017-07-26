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
				'description' => _x( 'Outputs the current version of Easy Digital Downloads. Alternatively, allows you to change the version of Easy Digital Downloads', '/edd version Description', 'edd-slack' ),
				'options' => array(
					'change'
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
	 * Alternatively, provide "change" as a parameter to allow changing the version of EDD
	 * 
	 * @param	  string $change	   If this String is non-empty, we're changing the Version
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
		
		if ( ! empty( $change ) ) { // "Change" is just passed as a string. Since there is only one parameter for this Slash Command, we can just assume any extra parameter means we're changing the Version
		
			$edd_versions = EDDSLACK()->slack_rest_api->get_edd_versions();
			$edd_versions_array = array();

			foreach ( $edd_versions as $version => $download_url ) {
				$edd_versions_array[] = array(
					'text' => 'v' . $version,
					'value' => $version,
				);
			}
			
			$attachments[0]['callback_id'] = 'edd_version';
			$attachments[0]['fallback'] = $attachments[0]['title'];
			
			$attachments[0]['actions'] = array(
				array(
					'name' => 'versions_list',
					'text' => _x( 'Pick a version...', 'Pick a EDD Version Text', 'edd-slack' ),
					'type' => 'select',
					'options' => $edd_versions_array
				),
			);
			
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
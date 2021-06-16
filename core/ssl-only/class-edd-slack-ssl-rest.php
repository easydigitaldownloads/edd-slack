<?php
/**
 * Creates SSL REST Endpoints
 *
 * @since 0.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only
 */

defined( 'ABSPATH' ) || die();

// Slash Commands in another file
require_once EDD_Slack_DIR . '/core/ssl-only/slash-commands/edd-slack-slash-commands.php';

class EDD_Slack_SSL_REST {

	/**
	 * EDD_Slack_SSL_REST constructor.
	 * @since 1.0.0
	 */
	function __construct() {

		add_action( 'rest_api_init', array( $this, 'create_routes' ) );

	}

	/**
	 * Creates a WP REST API route that Listens for the Slack App's Response to the User's Action within their Slack Client
	 * 
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void
	 */
	public function create_routes() {

		// REST Route for all Interactive Buttons
		register_rest_route( 'edd-slack/v1', '/slack-app/interactive-message/submit', array(
			'methods' => 'POST',
			'callback' => array( $this, 'route_interactive_message' ),
			'permission_callback' => '__return_true'
		) );

		// REST Route for /edd Slash Command
		register_rest_route( 'edd-slack/v1', '/slack-app/slash-command/submit', array(
			'methods' => 'POST',
			'callback' => array( $this, 'route_slash_command' ),
			'permission_callback' => '__return_true'
		) );

	}

	/**
	 * Callback for our Interactive Button REST Endpoint
	 * This routes the functionality based on the passed callback_id
	 * 
	 * @param	  object $request WP_REST_Request Object
	 *											  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void, calls another function
	 */
	public function route_interactive_message( $request ) {

		$request_body = $request->get_body_params();

		// If no Payload was sent, bail
		if ( empty( $request_body['payload'] ) ) {
			return _x( 'No data payload', 'No Payload Error', 'edd-slack' );
		}

		// Decode the Payload JSON
		$payload = json_decode( $request_body['payload'] );

		// If the Verification Code doesn't match, bail
		$verification_token = $payload->token;
		if ( $verification_token !== edd_get_option( 'slack_app_verification_token' ) ) {
			return _x( 'Bad Verification Token', 'Missing/Incorrect Verification Token Error', 'edd-slack' );
		}
		
		// Just in case a command takes too long for Slack's liking
		http_response_code( 200 );

		$callback_id = $payload->callback_id;

		// Construct the callback function
		$callback_function = 'edd_slack_interactive_message_'. $callback_id;
		$callback_function = ( is_callable( $callback_function ) ) ? $callback_function : 'edd_slack_interactive_message_missing';

		// Route to a Callback Function and include all the Data we need
		call_user_func( $callback_function, $payload->actions[0], $payload->response_url, $payload );

		// We don't need to print anything outside what our callbacks provide
		die();

	}

	/**
	 * Callback for our Slash Command REST Endpoint
	 * This routes the functionality based on the first word of the passed Text
	 * 
	 * @param	  object $request WP_REST_Request Object
	 *											  
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  void, calls another function
	 */
	public function route_slash_command( $request ) {

		// Just in case a command takes too long for Slack's liking
		http_response_code( 200 );

		$request_body = $request->get_body_params();

		// If the Verification Code doesn't match, bail
		$verification_token = $request_body['token'];
		if ( $verification_token !== edd_get_option( 'slack_app_verification_token' ) ) {
			return _x( 'Bad Verification Token', 'Missing/Incorrect Verification Token Error', 'edd-slack' );
		}
		
		$passed_text = explode( ' ', $request_body['text'] );
		
		$command = trim( strtolower( $passed_text[0] ) );
		
		$parameter = '';
		if ( count( $passed_text ) > 1 ) {
			
			// Cut first array index off
			$passed_text = array_splice( $passed_text, 1 );
			
			// The passed Parameter back as a String. If a Callback needs to do something different, they can later
			$parameter = trim( implode( ' ', $passed_text ) );
			
		}
		
		$authorized_slash_command_users = edd_get_option( 'slack_app_slash_command_users', array() );
		
		// Check to see if the User is Authorized for Slash Commands
		// If no Authorized Users are set, only Slack Team Admins are allowed
		if ( ( empty( $authorized_slash_command_users ) && ! edd_slack_is_slack_admin( $request_body['user_id'] ) ) ||
			 ( ! empty( $authorized_slash_command_users ) && ! in_array( $request_body['user_id'], $authorized_slash_command_users ) ) ) {
			
			edd_slack_slash_command_access_denied( $parameter, $request_body['response_url'], $request_body );
			die();
			
		}

		// Construct the callback function
		if ( empty( $command ) ) $command = 'help';
		$callback_function = 'edd_slack_slash_command_'. $command;
		$callback_function = ( is_callable( $callback_function ) ) ? $callback_function : 'edd_slack_slash_command_missing';

		// Route to a Callback Function and include all the Data we need
		call_user_func( $callback_function, $parameter, $request_body['response_url'], $request_body );

		// We don't need to print anything outside what our callbacks provide
		die();

	}

	/**
	 * Copy of edd_get_report_dates() without the dependecy on $_GET
	 * Hopefully this won't be necessary for long
	 * 
	 * @param		string  $date_range Date Range String
	 *									  
	 * @access		public
	 * @since		1.0.0
	 * @return		array	Dates to use when calculating the Sales Report
	 */
	public function edd_get_report_dates( $date_range = 'this_month' ) {

		$dates = array();

		$current_time = current_time( 'timestamp' );

		$dates['range'] = ( ! empty( $date_range ) ) ? strtolower( $date_range ) : 'this_month';

		// We're not going to let a custom date range through
		$dates['year']	  = date( 'Y' );
		$dates['year_end']   = date( 'Y' );
		$dates['m_start']	= 1;
		$dates['m_end']	  = 12;
		$dates['day']		= 1;
		$dates['day_end']	= cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );

		// Modify dates based on predefined ranges
		switch ( $dates['range'] ) :

			case 'last_month' :
				if ( date( 'n' ) == 1 ) {
					$dates['m_start']  = 12;
					$dates['m_end']	= 12;
					$dates['year']	 = date( 'Y', $current_time ) - 1;
					$dates['year_end'] = date( 'Y', $current_time ) - 1;
				} else {
					$dates['m_start']  = date( 'n' ) - 1;
					$dates['m_end']	= date( 'n' ) - 1;
					$dates['year_end'] = $dates['year'];
				}
				$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
				break;

			case 'today' :
				$dates['day']	 = date( 'd', $current_time );
				$dates['m_start'] = date( 'n', $current_time );
				$dates['m_end']   = date( 'n', $current_time );
				$dates['year']	= date( 'Y', $current_time );
				break;

			case 'yesterday' :

				$year  = date( 'Y', $current_time );
				$month = date( 'n', $current_time );
				$day   = date( 'd', $current_time );

				if ( $month == 1 && $day == 1 ) {
					$year  -= 1;
					$month = 12;
					$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );
				} elseif ( $month > 1 && $day == 1 ) {
					$month -= 1;
					$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );
				} else {
					$day -= 1;
				}

				$dates['day']	  = $day;
				$dates['m_start']   = $month;
				$dates['m_end']	 = $month;
				$dates['year']	  = $year;
				$dates['year_end']  = $year;
				$dates['day_end']   = $day;
				break;

			case 'this_week' :
			case 'last_week' :
				$base_time = $dates['range'] === 'this_week' ? current_time( 'mysql' ) : date( 'Y-m-d h:i:s', current_time( 'timestamp' ) - WEEK_IN_SECONDS );
				$start_end = get_weekstartend( $base_time, get_option( 'start_of_week' ) );

				$dates['day']	  = date( 'd', $start_end['start'] );
				$dates['m_start']  = date( 'n', $start_end['start'] );
				$dates['year']	 = date( 'Y', $start_end['start'] );

				$dates['day_end']  = date( 'd', $start_end['end'] );
				$dates['m_end']	= date( 'n', $start_end['end'] );
				$dates['year_end'] = date( 'Y', $start_end['end'] );
				break;

			case 'this_quarter' :
				$month_now = date( 'n', $current_time );
				$dates['year']	 = date( 'Y', $current_time );
				$dates['year_end'] = $dates['year'];

				if ( $month_now <= 3 ) {
					$dates['m_start']  = 1;
					$dates['m_end']	= 3;
				} else if ( $month_now <= 6 ) {
					$dates['m_start'] = 4;
					$dates['m_end']   = 6;
				} else if ( $month_now <= 9 ) {
					$dates['m_start'] = 7;
					$dates['m_end']   = 9;
				} else {
					$dates['m_start']  = 10;
					$dates['m_end']	= 12;
				}

				$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
				break;

			case 'last_quarter' :
				$month_now = date( 'n' );

				if ( $month_now <= 3 ) {
					$dates['m_start']  = 10;
					$dates['m_end']	= 12;
					$dates['year']	 = date( 'Y', $current_time ) - 1; // Previous year
				} else if ( $month_now <= 6 ) {
					$dates['m_start'] = 1;
					$dates['m_end']   = 3;
					$dates['year']	= date( 'Y', $current_time );
				} else if ( $month_now <= 9 ) {
					$dates['m_start'] = 4;
					$dates['m_end']   = 6;
					$dates['year']	= date( 'Y', $current_time );
				} else {
					$dates['m_start'] = 7;
					$dates['m_end']   = 9;
					$dates['year']	= date( 'Y', $current_time );
				}

				$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'],  $dates['year'] );
				$dates['year_end'] = $dates['year'];
				break;

			case 'this_year' :
				$dates['m_start']  = 1;
				$dates['m_end']	= 12;
				$dates['year']	 = date( 'Y', $current_time );
				$dates['year_end'] = $dates['year'];
				break;

			case 'last_year' :
				$dates['m_start']  = 1;
				$dates['m_end']	= 12;
				$dates['year']	 = date( 'Y', $current_time ) - 1;
				$dates['year_end'] = date( 'Y', $current_time ) - 1;
				break;

			default : // this_month, since we're not allowing Custom Ranges

				$dates['range']	= 'this_month';
				$dates['m_start']  = date( 'n', $current_time );
				$dates['m_end']	= date( 'n', $current_time );
				$dates['day']	  = 1;
				$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
				$dates['year']	 = date( 'Y' );
				$dates['year_end'] = date( 'Y' );
				break;

		endswitch;

		return apply_filters( 'edd_report_dates', $dates );

	}

	/**
	 * Partial copy of edd_reports_graph()
	 * Hopefully this won't be necessary for long
	 * 
	 * @param		array $dates Dates Array from edd_get_report_dates()
	 *									  
	 * @access		public
	 * @since		1.0.0
	 * @return		array Values for the Report
	 */
	public function edd_get_report_values( $dates ) {

		// Determine graph options
		switch ( $dates['range'] ) :
			case 'today' :
			case 'yesterday' :
				$day_by_day	= true;
				break;
			case 'last_year' :
			case 'this_year' :
				$day_by_day = false;
				break;
			case 'last_quarter' :
			case 'this_quarter' :
				$day_by_day = true;
				break;
			case 'other' :
				if ( $dates['m_start'] == 12 && $dates['m_end'] == 1 ) {
					$day_by_day = true;
				} elseif ( $dates['m_end'] - $dates['m_start'] >= 3 || ( $dates['year_end'] > $dates['year'] && ( $dates['m_start'] - $dates['m_end'] ) != 10 ) ) {
					$day_by_day = false;
				} else {
					$day_by_day = true;
				}
				break;
			default:
				$day_by_day = true;
				break;
		endswitch;

		$earnings_totals = 0.00; // Total earnings for time period shown
		$sales_totals	= 0;	// Total sales for time period shown

		/**
		 * EDD handles this via a $_GET parameter, but I don't want to pass more than one thing in the Slash Command
		 *
		 * @since 1.0.0
		 */
		$include_taxes = apply_filters( 'edd_slack_app_sales_include_taxes', true );

		if ( $dates['range'] == 'today' || $dates['range'] == 'yesterday' ) {
			// Hour by hour
			$hour  = 0;
			$month = $dates['m_start'];

			$i = 0;

			$start = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
			$end = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'];

			$sales = EDD()->payment_stats->get_sales_by_range( $dates['range'], true, $start, $end );

			while ( $hour <= 23 ) {
				$date = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] ) * 1000;

				$earnings = edd_get_earnings_by_date( $dates['day'], $month, $dates['year'], $hour, $include_taxes );
				$earnings_totals += $earnings;
				$earnings_data[] = array( $date, $earnings );

				if ( isset( $sales[ $i ] ) && $sales[ $i ]['h'] == $hour ) {
					$sales_data[] = array( $date, $sales[ $i ]['count'] );
					$sales_totals += $sales[ $i ]['count'];
					$i++;
				} else {
					$sales_data[] = array( $date, 0 );
				}

				$hour++;
			}
		} elseif ( $dates['range'] == 'this_week' || $dates['range'] == 'last_week' ) {
			$report_dates = array();
			$i = 0;
			while ( $i <= 6 ) {
				if ( ( $dates['day'] + $i ) <= $dates['day_end'] ) {
					$report_dates[ $i ] = array(
						'day'   => (string) $dates['day'] + $i,
						'month' => $dates['m_start'],
						'year'  => $dates['year'],
					);
				} else {
					$report_dates[ $i ] = array(
						'day'   => (string) $i,
						'month' => $dates['m_end'],
						'year'  => $dates['year_end'],
					);
				}

				$i++;
			}

			$start_date = $report_dates[0];
			$end_date = end( $report_dates );

			$sales = EDD()->payment_stats->get_sales_by_range( $dates['range'], true, $start_date['year'] . '-' . $start_date['month'] . '-' . $start_date['day'], $end_date['year'] . '-' . $end_date['month'] . '-' . $end_date['day'] );

			$i = 0;
			foreach ( $report_dates as $report_date ) {
				$date = mktime( 0, 0, 0,  $report_date['month'], $report_date['day'], $report_date['year']  ) * 1000;

				if ( $report_date['day'] == $sales[ $i ]['d'] && $report_date['month'] == $sales[ $i ]['m'] && $report_date['year'] == $sales[ $i ]['y'] ) {
					$sales_data[] = array( $date, $sales[ $i ]['count'] );
					$sales_totals += $sales[ $i ]['count'];
					$i++;
				} else {
					$sales_data[] = array( $date, 0 );
				}

				$earnings		= edd_get_earnings_by_date( $report_date['day'], $report_date['month'], $report_date['year'] , null, $include_taxes );
				$earnings_totals += $earnings;
				$earnings_data[] = array( $date, $earnings );
			}

		} else {
			if ( cal_days_in_month( CAL_GREGORIAN, $dates['m_start'], $dates['year'] ) < $dates['day'] ) {
				$next_day = mktime( 0, 0, 0, $dates['m_start'] + 1, 1, $dates['year'] );
				$day = date( 'd', $next_day );
				$month = date( 'm', $next_day );
				$year = date( 'Y', $next_day );
				$date_start = $year . '-' . $month . '-' . $day;
			} else {
				$date_start = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
			}

			if ( cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] ) < $dates['day_end'] ) {
				$date_end = $dates['year_end'] . '-' . $dates['m_end'] . '-' . cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			} else {
				$date_end = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'];
			}

			$sales = EDD()->payment_stats->get_sales_by_range( $dates['range'], $day_by_day, $date_start, $date_end );

			$y = $dates['year'];
			$temp_data = array(
				'sales'	=> array(),
				'earnings' => array(),
			);

			foreach ( $sales as $sale ) {
				if ( $day_by_day ) {
					$temp_data['sales'][ $sale['y'] ][ $sale['m'] ][ $sale['d'] ] = $sale['count'];
				} else {
					$temp_data['sales'][ $sale['y'] ][ $sale['m'] ] = $sale['count'];
				}
				$sales_totals += $sale['count'];
			}

			while ( $dates['range'] !== 'other' && $day_by_day && ( strtotime( $date_start ) <= strtotime( $date_end ) ) ) {
				$d = date( 'd', strtotime( $date_start ) );
				$m = date( 'm', strtotime( $date_start ) );
				$y = date( 'Y', strtotime( $date_start ) );

				if ( ! isset( $temp_data['sales'][ $y ][ $m ][ $d ] ) ) {
					$temp_data['sales'][ $y ][ $m ][ $d ] = 0;
				}

				$date_start = date( 'Y-m-d', strtotime( '+1 day', strtotime( $date_start ) ) );
			}

			while ( $dates['range'] !== 'other' && ! $day_by_day && ( strtotime( $date_start ) <= strtotime( $date_end ) ) ) {
				$m = date( 'm', strtotime( $date_start ) );
				$y = date( 'Y', strtotime( $date_start ) );

				if ( ! isset( $temp_data['sales'][ $y ][ $m ] ) ) {
					$temp_data['sales'][ $y ][ $m ] = 0;
				}

				$date_start = date( 'Y-m', strtotime( '+1 month', strtotime( $date_start ) ) );
			}

			if ( cal_days_in_month( CAL_GREGORIAN, $dates['m_start'], $dates['year'] ) < $dates['day'] ) {
				$next_day = mktime( 0, 0, 0, $dates['m_start'] + 1, 1, $dates['year'] );
				$day = date( 'd', $next_day );
				$month = date( 'm', $next_day );
				$year = date( 'Y', $next_day );
				$date_start = $year . '-' . $month . '-' . $day;
			} else {
				$date_start = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'];
			}

			while ( strtotime( $date_start ) <= strtotime( $date_end ) ) {
				$m = date( 'm', strtotime( $date_start ) );
				$y = date( 'Y', strtotime( $date_start ) );
				$d = date( 'd', strtotime( $date_start ) );

				$earnings = edd_get_earnings_by_date( $d, $m, $y, null, $include_taxes );
				$earnings_totals += $earnings;

				$temp_data['earnings'][ $y ][ $m ][ $d ] = $earnings;

				$date_start = date( 'Y-m-d', strtotime( '+1 day', strtotime( $date_start ) ) );
			}

			$sales_data	= array();
			$earnings_data = array();

			// When using 3 months or smaller as the custom range, show each day individually on the graph
			if ( $day_by_day ) {
				foreach ( $temp_data['sales'] as $year => $months ) {
					foreach ( $months as $month => $days ) {
						foreach ( $days as $day => $count ) {
							$date		 = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
							$sales_data[] = array( $date, $count );
						}
					}
				}

				// Sort dates in ascending order
				foreach ( $sales_data as $key => $value ) {
					$timestamps[ $key ] = $value[0];
				}
				if ( ! empty( $timestamps ) ) {
					array_multisort( $timestamps, SORT_ASC, $sales_data );
				}

				foreach ( $temp_data['earnings'] as $year => $months ) {
					foreach ( $months as $month => $days ) {
						foreach ( $days as $day => $earnings ) {
							$date			= mktime( 0, 0, 0, $month, $day, $year ) * 1000;
							$earnings_data[] = array( $date, $earnings );
						}
					}
				}

				// When showing more than 3 months of results, group them by month, by the first (except for the last month, group on the last day of the month selected)
			} else {

				foreach ( $temp_data['sales'] as $year => $months ) {
					$month_keys = array_keys( $months );
					$last_month = end( $month_keys );

					if ( $day_by_day ) {
						foreach ( $months as $month => $days ) {
							$day_keys = array_keys( $days );
							$last_day = end( $day_keys );

							$month_keys = array_keys( $months );

							$consolidated_date = $month === end( $month_keys ) ? cal_days_in_month( CAL_GREGORIAN, $month, $year ) : 1;

							$sales		= array_sum( $days );
							$date		 = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
							$sales_data[] = array( $date, $sales );
						}
					} else {
						foreach ( $months as $month => $count ) {
							$month_keys = array_keys( $months );
							$consolidated_date = $month === end( $month_keys ) ? cal_days_in_month( CAL_GREGORIAN, $month, $year ) : 1;

							$date = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
							$sales_data[] = array( $date, $count );
						}
					}
				}

				// Sort dates in ascending order
				foreach ( $sales_data as $key => $value ) {
					$timestamps[ $key ] = $value[0];
				}
				if ( ! empty( $timestamps ) ) {
					array_multisort( $timestamps, SORT_ASC, $sales_data );
				}

				foreach ( $temp_data[ 'earnings' ] as $year => $months ) {
					$month_keys = array_keys( $months );
					$last_month = end( $month_keys );

					foreach ( $months as $month => $days ) {
						$day_keys = array_keys( $days );
						$last_day = end( $day_keys );

						$consolidated_date = $month === $last_month ? $last_day : 1;

						$earnings		= array_sum( $days );
						$date			= mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
						$earnings_data[] = array( $date, $earnings );
					}
				}
			}
		}

		if( ! empty( $dates['range'] ) && 'this_month' == $dates['range'] ) {

			// This function isn't visible from where we are
			if ( ! function_exists( 'edd_estimated_monthly_stats' ) ) {
				require_once EDD_PLUGIN_DIR . 'includes/admin/reporting/reports.php';
			}

			$estimated = edd_estimated_monthly_stats( $include_taxes );

		} else {
			$estimated = array();
		}

		return array(
			'estimated' => $estimated,
			'earnings_totals' => $earnings_totals,
			'sales_totals' => $sales_totals,
		);

	}

}

if ( ! function_exists( 'edd_slack_interactive_message_missing' ) ) {

	/**
	 * EDD Slack Rest Interative Button Missing Callback Function Fallback
	 * 
	 * @param	  object $button	  name and value from the Interactive Button. value should be json_decode()'d
	 * @param	  string $response_url Webhook to send the Response Message to
	 * @param	  object $payload	  POST'd data from the Slack Client
	 *														
	 * @since	  1.0.0
	 * @return	  void
	 */
	function edd_slack_interactive_message_missing( $button, $response_url, $payload ) {

		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'text' => sprintf( _x( 'The Callback Function `edd_slack_interactive_message_%s()` is missing!', 'Interactive Button Callback Function Missing Error', 'edd-slack' ), $payload->callback_id ),
			)
		);

	}

}

if ( ! function_exists( 'edd_slack_slash_command_missing' ) ) {
	
	/**
	 * EDD Slack Rest Slash Command Missing Callback Function Fallback
	 * 
	 * @param	  string $parameter	The remainder of the Text Passed as part of the Slash Command (Not the First Word)
	 * @param	  string $response_url Webhook to send the Response Message to
	 * @param	  array  $request_body POST'd data from the Slack Client
	 *														
	 * @since	  1.0.0
	 * @return	  void
	 */
	function edd_slack_slash_command_missing( $parameter, $response_url, $request_body ) {
		
		// We need to re-extract the Command from $request_body
		$text = explode( ' ', $request_body['text'] );
		$command = trim( strtolower( $text[0] ) );

		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'text' => sprintf( _x( 'The Callback Function `edd_slack_slash_command_%s()` is missing!', 'Slash Command Callback Function Missing Error', 'edd-slack' ), $command ),
			)
		);

	}
	
}

if ( ! function_exists( 'edd_slack_slash_command_access_denied' ) ) {
	
	/**
	 * EDD Slack Rest Slash Command Access Denied
	 * 
	 * @param	  string $parameter	The remainder of the Text Passed as part of the Slash Command (Not the First Word)
	 * @param	  string $response_url Webhook to send the Response Message to
	 * @param	  array  $request_body POST'd data from the Slack Client
	 *														
	 * @since	  1.1.0
	 * @return	  void
	 */
	function edd_slack_slash_command_access_denied( $parameter, $response_url, $request_body ) {

		// Response URLs are Incoming Webhooks
		$response_message = EDDSLACK()->slack_api->push_incoming_webhook(
			$response_url,
			array(
				'text' => _x( 'You do not have permission to use this Slash Command.', 'Slash Command Access Denied Message', 'edd-slack' ),
			)
		);

	}
	
}
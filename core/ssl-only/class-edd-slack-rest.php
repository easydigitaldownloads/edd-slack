<?php
/**
 * Creates REST Endpoints
 *
 * @since 0.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_REST {

    /**
     * EDD_Slack_REST constructor.
     * @since 1.0.0
     */
    function __construct() {

        add_action( 'rest_api_init', array( $this, 'create_routes' ) );

    }

    /**
     * Creates a WP REST API route that Listens for the Slack App's Response to the User's Action within their Slack Client
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function create_routes() {

        // REST Route for all Interactive Buttons
        register_rest_route( 'edd-slack/v1', '/slack-app/submit', array(
            'methods' => 'POST',
            'callback' => array( $this, 'route_interactive_button' ),
        ) );

        // REST Route for /eddsales Slash Command
        register_rest_route( 'edd-slack/v1', '/slack-app/slash-command/sales', array(
            'methods' => 'POST',
            'callback' => array( $this, 'get_sales' ),
        ) );

    }

    /**
     * Callback for our Interactive Button REST Endpoint
     * This routes the functionality based on the passed callback_id
     * 
     * @param       object $request WP_REST_Request Object
     *                                              
     * @access      public
     * @since       1.0.0
     * @return      void, calls another function
     */
    public function route_interactive_button( $request ) {

        $request_body = $request->get_body_params();

        // If no Payload was sent, bail
        if ( empty( $request_body['payload'] ) ) {
            return _x( 'No data payload', 'No Payload Error', EDD_Slack_ID );
        }

        // Decode the Payload JSON
        $payload = json_decode( $request_body['payload'] );

        // If the Verification Code doesn't match, bail
        $verification_token = $payload->token;
        if ( $verification_token !== edd_get_option( 'slack_app_verification_token' ) ) {
            return _x( 'Bad Verification Token', 'Missing/Incorrect Verification Token Error', EDD_Slack_ID );
        }
        
        // Just in case a command takes too long for Slack's liking
        http_response_code( 200 );

        $callback_id = $payload->callback_id;

        // Construct the callback function
        $callback_function = 'edd_slack_rest_'. $callback_id;
        $callback_function = ( is_callable( $callback_function ) ) ? $callback_function : 'edd_slack_rest_missing';

        // Route to a Callback Function and include all the Data we need
        call_user_func( $callback_function, $payload->actions[0], $payload->response_url, $payload );

        // We don't need to print anything outside what our callbacks provide
        die();

    }

    /**
     * Grabs the Sales Reports from EDD
     * 
     * @param       object $request WP_REST_Request Object
     *                                              
     * @access      public
     * @since       1.0.0
     * @return      Slack Webhook Notification
     */
    public function get_sales( $request ) {

        $request_body = $request->get_body_params();

        // If the Verification Code doesn't match, bail
        $verification_token = $request_body['token'];
        if ( $verification_token !== edd_get_option( 'slack_app_verification_token' ) ) {
            return _x( 'Bad Verification Token', 'Missing/Incorrect Verification Token Error', EDD_Slack_ID );
        }

        // Some options (Like this_year) take longer to process. Let Slack know we're working on it.
        http_response_code( 200 );

        $date_range = trim( strtolower( $request_body['text'] ) );

        // Get dates based on our Date Range
        $dates = $this->edd_get_report_dates( $date_range );

        // Get values based on our dates
        $values = $this->edd_get_report_values( $dates );
        
        $date_options = apply_filters( 'edd_report_date_options', array(
            'today'        => __( 'Today', 'easy-digital-downloads' ),
            'yesterday'    => __( 'Yesterday', 'easy-digital-downloads' ),
            'this_week'    => __( 'This Week', 'easy-digital-downloads' ),
            'last_week'    => __( 'Last Week', 'easy-digital-downloads' ),
            'this_month'   => __( 'This Month', 'easy-digital-downloads' ),
            'last_month'   => __( 'Last Month', 'easy-digital-downloads' ),
            'this_quarter' => __( 'This Quarter', 'easy-digital-downloads' ),
            'last_quarter' => __( 'Last Quarter', 'easy-digital-downloads' ),
            'this_year'    => __( 'This Year', 'easy-digital-downloads' ),
            'last_year'    => __( 'Last Year', 'easy-digital-downloads' ),
            'other'        => __( 'Custom', 'easy-digital-downloads' )
        ) );
        
        // Gets a Human Readable String for the Response
        $human_date_range = $date_options[ $dates['range'] ];
        
        $attachments = array(
            array(
                'title' => sprintf( _x( 'Earnings Report for %s', 'Earnings Report for this Period /eddsales', EDD_Slack_ID ), $human_date_range ),
                'text' => html_entity_decode( sprintf( _x( 'Total Earnings for %s: %s', 'Total Earnings for this Period /eddsales', EDD_Slack_ID ), $human_date_range, edd_currency_filter( edd_format_amount( $values['earnings_totals'] ) ) ) ) . "\n"
                    . html_entity_decode( sprintf( _x( 'Total Sales for %s: %s', 'Total Sales for this Period /eddsales', EDD_Slack_ID ), $human_date_range, edd_format_amount( $values['sales_totals'], false ) ) ),
            ),
        );

        // If we're checking for the Month, also show Estimations
        if ( $dates['range'] == 'this_month' ) {

            $attachments[0]['text'] .= "\n" . html_entity_decode( sprintf( _x( 'Estimated Monthly Earnings: %s', 'Estimated Montly Earnings /eddsales', EDD_Slack_ID ), edd_currency_filter( edd_format_amount( $values['estimated']['earnings'] ) ) ) ) . "\n"
                . html_entity_decode( sprintf( _x( 'Estimated Monthly Sales: %s', 'Estimated Montly Sales /eddsales', EDD_Slack_ID ), edd_format_amount( $values['estimated']['sales'], false ) ) );

        }

        // Response URLs are Incoming Webhooks
        $response_message = EDDSLACK()->slack_api->push_incoming_webhook(
            $request_body['response_url'],
            array(
                'username' => get_bloginfo( 'name' ),
                'icon' => function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 270 ) : '',
                'attachments' => $attachments,
            )
        );

        die();

    }

    /**
     * Copy of edd_get_report_dates() without the dependecy on $_GET
     * Hopefully this won't be necessary for long
     * 
     * @param		string  $date_range Date Range String
     *                                       
     * @access		private
     * @since		1.0.0
     * @return		array	Dates to use when calculating the Sales Report
     */
    private function edd_get_report_dates( $date_range = 'this_month' ) {

        $dates = array();

        $current_time = current_time( 'timestamp' );

        $dates['range'] = ( ! empty( $date_range ) ) ? $date_range : 'this_month';

        // We're not going to let a custom date range through
        $dates['year']       = date( 'Y' );
        $dates['year_end']   = date( 'Y' );
        $dates['m_start']    = 1;
        $dates['m_end']      = 12;
        $dates['day']        = 1;
        $dates['day_end']    = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );

        // Modify dates based on predefined ranges
        switch ( $dates['range'] ) :

            case 'last_month' :
                if ( date( 'n' ) == 1 ) {
                    $dates['m_start']  = 12;
                    $dates['m_end']    = 12;
                    $dates['year']     = date( 'Y', $current_time ) - 1;
                    $dates['year_end'] = date( 'Y', $current_time ) - 1;
                } else {
                    $dates['m_start']  = date( 'n' ) - 1;
                    $dates['m_end']    = date( 'n' ) - 1;
                    $dates['year_end'] = $dates['year'];
                }
                $dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
                break;

            case 'today' :
                $dates['day']     = date( 'd', $current_time );
                $dates['m_start'] = date( 'n', $current_time );
                $dates['m_end']   = date( 'n', $current_time );
                $dates['year']    = date( 'Y', $current_time );
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

                $dates['day']       = $day;
                $dates['m_start']   = $month;
                $dates['m_end']     = $month;
                $dates['year']      = $year;
                $dates['year_end']  = $year;
                $dates['day_end']   = $day;
                break;

            case 'this_week' :
            case 'last_week' :
                $base_time = $dates['range'] === 'this_week' ? current_time( 'mysql' ) : date( 'Y-m-d h:i:s', current_time( 'timestamp' ) - WEEK_IN_SECONDS );
                $start_end = get_weekstartend( $base_time, get_option( 'start_of_week' ) );

                $dates['day']      = date( 'd', $start_end['start'] );
                $dates['m_start']  = date( 'n', $start_end['start'] );
                $dates['year']     = date( 'Y', $start_end['start'] );

                $dates['day_end']  = date( 'd', $start_end['end'] );
                $dates['m_end']    = date( 'n', $start_end['end'] );
                $dates['year_end'] = date( 'Y', $start_end['end'] );
                break;

            case 'this_quarter' :
                $month_now = date( 'n', $current_time );
                $dates['year']     = date( 'Y', $current_time );
                $dates['year_end'] = $dates['year'];

                if ( $month_now <= 3 ) {
                    $dates['m_start']  = 1;
                    $dates['m_end']    = 3;
                } else if ( $month_now <= 6 ) {
                    $dates['m_start'] = 4;
                    $dates['m_end']   = 6;
                } else if ( $month_now <= 9 ) {
                    $dates['m_start'] = 7;
                    $dates['m_end']   = 9;
                } else {
                    $dates['m_start']  = 10;
                    $dates['m_end']    = 12;
                }

                $dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
                break;

            case 'last_quarter' :
                $month_now = date( 'n' );

                if ( $month_now <= 3 ) {
                    $dates['m_start']  = 10;
                    $dates['m_end']    = 12;
                    $dates['year']     = date( 'Y', $current_time ) - 1; // Previous year
                } else if ( $month_now <= 6 ) {
                    $dates['m_start'] = 1;
                    $dates['m_end']   = 3;
                    $dates['year']    = date( 'Y', $current_time );
                } else if ( $month_now <= 9 ) {
                    $dates['m_start'] = 4;
                    $dates['m_end']   = 6;
                    $dates['year']    = date( 'Y', $current_time );
                } else {
                    $dates['m_start'] = 7;
                    $dates['m_end']   = 9;
                    $dates['year']    = date( 'Y', $current_time );
                }

                $dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'],  $dates['year'] );
                $dates['year_end'] = $dates['year'];
                break;

            case 'this_year' :
                $dates['m_start']  = 1;
                $dates['m_end']    = 12;
                $dates['year']     = date( 'Y', $current_time );
                $dates['year_end'] = $dates['year'];
                break;

            case 'last_year' :
                $dates['m_start']  = 1;
                $dates['m_end']    = 12;
                $dates['year']     = date( 'Y', $current_time ) - 1;
                $dates['year_end'] = date( 'Y', $current_time ) - 1;
                break;

            default : // this_month, since we're not allowing Custom Ranges

                $dates['range']    = 'this_month';
                $dates['m_start']  = date( 'n', $current_time );
                $dates['m_end']    = date( 'n', $current_time );
                $dates['day']      = 1;
                $dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
                $dates['year']     = date( 'Y' );
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
	 * @access		private
	 * @since		1.0.0
	 * @return		array Values for the Report
	 */
    private function edd_get_report_values( $dates ) {

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
        $sales_totals    = 0;    // Total sales for time period shown

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

                $earnings        = edd_get_earnings_by_date( $report_date['day'], $report_date['month'], $report_date['year'] , null, $include_taxes );
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
                'sales'    => array(),
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

            $sales_data    = array();
            $earnings_data = array();

            // When using 3 months or smaller as the custom range, show each day individually on the graph
            if ( $day_by_day ) {
                foreach ( $temp_data['sales'] as $year => $months ) {
                    foreach ( $months as $month => $days ) {
                        foreach ( $days as $day => $count ) {
                            $date         = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
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
                            $date            = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
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

                            $sales        = array_sum( $days );
                            $date         = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
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

                        $earnings        = array_sum( $days );
                        $date            = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
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

if ( ! function_exists( 'edd_slack_rest_missing' ) ) {

    /**
     * EDD Slack Rest Missing Callback Function Fallback
     * 
     * @param       object $button       name and value from the Interactive Button. value should be json_decode()'d
     * @param       string $response_url Webhook to send the Response Message to
     * @param       object $payload      POST'd data from the Slack Client
     *                                                        
     * @since       1.0.0
     * @return      void
     */
    function edd_slack_rest_missing( $button, $response_url, $payload ) {

        // Response URLs are Incoming Webhooks
        $response_message = EDDSLACK()->slack_api->push_incoming_webhook(
            $response_url,
            array(
                'text' => sprintf( _x( 'The Callback Function `edd_slack_rest_%s()` is missing!', 'Callback Function Missing Error', EDD_Slack_ID ), $payload->callback_id ),
            )
        );

    }

}
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

        register_rest_route( 'edd-slack/v1', '/slack-app/submit', array(
            'methods' => 'POST',
            'callback' => array( $this, 'route_interactive_button' ),
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
     * @return      string JSON
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
        
        $callback_id = $payload->callback_id;
        
        // Construct the callback function
        $callback_function = 'edd_slack_rest_'. $callback_id;
        $callback_function = ( is_callable( $callback_function ) ) ? $callback_function : 'edd_slack_rest_missing';
        
        // Route to a Callback Function and include all the Data we need
        call_user_func( $callback_function, $payload->actions[0], $payload->response_url, $payload );
        
        // We don't need to print anything outside what our callbacks provide
        die();

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
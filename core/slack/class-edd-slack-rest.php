<?php
/**
 * Creates REST Endpoints
 *
 * @since 0.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/rest
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_REST {


    /**
     * EDD_Slack_REST constructor.
     * @since 0.1.0
     */
    function __construct() {

        add_action( 'rest_api_init', array( $this, 'create_routes' ) );

    }

    /**
     * Creates a WP REST API route that Listens for the Slack App's Response to the User's Action within their Slack Client
     * 
     * @since       0.1.0
     * @access      public
     * @return      void
     */
    public function create_routes() {

        register_rest_route( 'edd-slack/v1', '/slack-app/submit', array(
            'methods' => 'POST',
            'callback' => array( $this, 'route_action' ),
        ) );

    }

    /**
     * Callback for our REST Endpoint
     * 
     * @param       object $request WP_REST_Request Object
     * @return      string JSON
     */
    public function route_action( $request ) {

        $json = file_get_contents( 'php://input' );

        if ( empty( $json ) ) {
            return json_encode( array(
                'success' => false,
                'message' => _x( 'No data payload', 'No JSON Uploaded Error', EDD_Slack_ID ),
            ) );
        }
        
        $json = json_decode( $json );
        
        return json_encode( array(
            'success' => true,
            'message' => __( 'Success!', EDD_Slack_ID ),
        ) );

    }

}
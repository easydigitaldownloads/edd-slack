<?php
/**
 * Provides Slack API integration functionality.
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/slack
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
defined( 'ABSPATH' ) || die();

class EDD_Slack_API {
    
    /**
     * @var         EDD_Slack_API $oauth_token Authenticates requests for the Slack App Integration
     * @since       1.0.0
     */
    private $oauth_token = '';
    
    /**
     * @var         EDD_Slack_API $api_endpoint Slack's Web API Endpoint. This is only used for Slack App Integration
     * @since       1.0.0
     */
    public $api_endpoint = 'https://slack.com/api';

    /**
	 * EDD_Slack_API constructor.
	 *
	 * @since 1.0.0
	 */
    function __construct() {
        
        $this->oauth_token = ( edd_get_option( 'slack_app_oauth_token' ) ) ? edd_get_option( 'slack_app_oauth_token' ) : '';
        
    }

    /**
	 * Push an "incoming webhook" to Slack.
	 *
	 * Incoming webhooks are messages to Slack.
	 *
	 * @since 1.0.0
	 */
    public function push_incoming_webhook( $hook, $args = array() ) {

        $args = wp_parse_args( $args, array(
            'channel'    => null,
            'username'   => null,
            'icon_emoji' => null,
            'icon_url'   => null,
            'text'       => null,
        ) );

        $result = wp_remote_post( $hook, array(
            'body' => 'payload=' . wp_json_encode( $args ),
        ) );

        return $result;
        
    }

    /**
     * Make an HTTP DELETE request - for deleting data
     * 
     * @param       string      $method  URL of the API request method
     * @param       array       $args    Assoc array of arguments (if any)
     * @param       int         $timeout Timeout limit for request in seconds
     *                                                                
     * @access      public
     * @since       1.0.0
     * @return      array|false Assoc array of API response, decoded from JSON
     */
    public function delete( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'DELETE', $method, $args, $timeout );
    }
    
    /**
     * Make an HTTP GET request - for retrieving data
     * 
     * @param   string      $method  URL of the API request method
     * @param   array       $args    Assoc array of arguments (usually your data)
     * @param   int         $timeout Timeout limit for request in seconds
     * @return  array|false Assoc array of API response, decoded from JSON
     */
    public function get( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'GET', $method, $args, $timeout );
    }
    
    /**
     * Make an HTTP PATCH request - for performing partial updates
     * 
     * @param       string      $method  URL of the API request method
     * @param       array       $args    Assoc array of arguments (usually your data)
     * @param       int         $timeout Timeout limit for request in seconds
     *                                                                
     * @access      public
     * @since       1.0.0
     * @return      array|false Assoc array of API response, decoded from JSON
     */
    public function patch( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'PATCH', $method, $args, $timeout );
    }
    
    /**
     * Make an HTTP POST request - for creating and updating items
     * 
     * @param       string      $method  URL of the API request method
     * @param       array       $args    Assoc array of arguments (usually your data)
     * @param       int         $timeout Timeout limit for request in seconds
     *                                                                
     * @access      public
     * @since       1.0.0
     * @return      array|false Assoc array of API response, decoded from JSON
     */
    public function post( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'POST', $method, $args, $timeout );
    }
    
    /**
     * Make an HTTP PUT request - for creating new items
     * 
     * @param       string      $method  URL of the API request method
     * @param       array       $args    Assoc array of arguments (usually your data)
     * @param       int         $timeout Timeout limit for request in seconds
     * 
     * @access      public
     * @since       1.0.0
     * @return      array|false Assoc array of API response, decoded from JSON
     */
    public function put( $method, $args = array(), $timeout = 10 ) {
        return $this->make_request( 'PUT', $method, $args, $timeout );
    }
    
    /**
     * Performs the underlying HTTP request
     * 
     * @param       string      $http_verb The HTTP verb to use: get, post, put, patch, delete
     * @param       string      $method    The API method to be called
     * @param       array       $args      Assoc array of parameters to be passed
     * @param       int $timeout
     *                  
     * @access      private
     * @since       1.0.0
     * @return      array|false Assoc array of decoded result
     */
    private function make_request( $http_verb, $method, $args = array(), $timeout = 10 ) {
        
        $args = wp_parse_args( $args, array(
            'method' => $http_verb,
            'timeout' => $timeout,
            'headers' => array(),
        ) );

        $url = $this->api_endpoint . '/' . $method;
        $url = add_query_arg( 'token', $this->oauth_token, $url );

        $response = wp_remote_request( $url, $args );
        return json_decode( $response['body'] );

    }
    
    /**
     * Set the OAUTH Token for the Object
     * 
     * @param string $oauth_token Slack API OAUTH Token
     *                                            
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function set_oauth_token( $oauth_token ) {
        
        edd_update_option( 'slack_app_oauth_token', $oauth_token );
        $this->oauth_token = $oauth_token;
        
    }

}
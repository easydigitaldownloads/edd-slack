<?php
/**
 * Provides Slack API integration functionality.
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/slack
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_API {

	/**
	 * EDD_Slack_API constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
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
    
}
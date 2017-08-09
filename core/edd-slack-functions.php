<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	EDD_Slack
 * @subpackage EDD_Slack/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		EDD_Slack
 */
function EDDSLACK() {
	return EDD_Slack::instance();
}

/**
 * Returns all Users from the Slack API
 * 
 * @since		1.1.0
 * @return		array Slack Users
 */
function edd_slack_get_users() {
	
	$oauth_token = edd_get_option( 'slack_app_oauth_token', false );

	// Don't bother if we don't have an OAUTH Token
	if ( ! $oauth_token || $oauth_token == '-1' ) return array();

	if ( ! $users_array = maybe_unserialize( get_transient( 'edd_slack_users' ) ) ) {

		$result = EDDSLACK()->slack_api->get( 'users.list' );

		$users = $result->members;

		$users_array = array();
		foreach ( $users as $user ) {

			// Let's not bother with Deleted Users
			if ( $user->deleted ) continue;

			// No need for Slackbot to be in the list
			if ( $user->id == 'USLACKBOT' ) continue;

			$users_array[ $user->id ] = '@' . $user->name . ' (' . $user->real_name . ')';

			if ( $user->is_admin ) {
				$users_array[ $user->id ] .= ' - ' . __( 'Admin', 'edd-slack' );
			}

		}

		set_transient( 'edd_slack_users', $users_array, DAY_IN_SECONDS );

	}

	return $users_array;

}

/**
 * Check to see if a Slack User ID is a Slack Team Admin
 * 
 * @param		string  $slack_user_id Slack User ID
 *                                           
 * @since		1.1.0
 * @return		boolean True if Admin
 */
function edd_slack_is_slack_admin( $slack_user_id = '0' ) {
	
	$oauth_token = edd_get_option( 'slack_app_oauth_token', false );
	
	if ( ! $oauth_token || $oauth_token == '-1' ) return array();
	
	$users_info_url = add_query_arg(
		array(
			'user' => $slack_user_id,
		),
		'users.info'
	);
	
	$result = EDDSLACK()->slack_api->get( $users_info_url );
	
	if ( ! $result->ok ) return false;
	
	return $result->user->is_admin;
	
}
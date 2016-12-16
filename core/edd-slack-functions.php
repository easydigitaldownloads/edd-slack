<?php
/**
 * Provides helper functions.
 *
 * @since      1.0.0
 *
 * @package    EDD_Slack
 * @subpackage EDD_Slack/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since 1.0.0
 *
 * @return EDD_Slack
 */
function EDDSLACK() {
	return EDD_Slack::instance();
}
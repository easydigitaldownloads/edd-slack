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
 * Multiselect Callback
 * One day I may PR this and combine it with the actual Select callback
 * 
 * @param		array $args Arguments passed by the setting
 *                                             
 * @since		1.1.0
 * @return		void
 */
function edd_rbm_multi_select_callback( $args ) {
	
	$edd_option = edd_get_option( $args['id'] );

	if ( $edd_option ) {
		$value = $edd_option;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : array();
	}

	if ( isset( $args['placeholder'] ) ) {
		$placeholder = $args['placeholder'];
	} else {
		$placeholder = '';
	}

	$class = edd_sanitize_html_class( $args['field_class'] );

	if ( isset( $args['chosen'] ) ) {
		$class .= ' edd-chosen';
	}

	$html = '<select id="edd_settings[' . edd_sanitize_key( $args['id'] ) . ']" name="edd_settings[' . esc_attr( $args['id'] ) . '][]" class="' . $class . '" data-placeholder="' . esc_html( $placeholder ) . '" multiple="true" />';

	foreach ( $args['options'] as $option => $name ) {
		
		$html .= '<option value="' . esc_attr( $option ) . '" ' . ( ( in_array( $option, $value ) ) ? 'selected="true"' : '' ) . '>' . esc_html( $name ) . '</option>';
		
	}

	$html .= '</select>';
	$html .= '<label for="edd_settings[' . edd_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'edd_after_setting_output', $html, $args );
	
}
	
/**
 * If a Multiselect is previously saved, it is not normally possible to clear them out
 * 
 * @param		array  $value Array value of the Multi-select
 * @param		string $key   EDD Field ID
 *                    
 * @since		1.1.0
 * @return		array  Sanitized Array value of the Multi-select
 */
function edd_settings_sanitize_rbm_multi_select( $value, $key ) {

	if ( empty( $_POST['edd_settings'][ $key ] ) ) $value = array();

	return $value;

}
add_filter( 'edd_settings_sanitize_rbm_multi_select', 'edd_settings_sanitize_rbm_multi_select', 10, 2 );
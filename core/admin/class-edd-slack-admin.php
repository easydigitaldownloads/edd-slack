<?php
/**
 * The admin settings side to EDD Slack
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/admin
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Admin {

    /**
	 * EDD_Slack_Admin constructor.
	 *
	 * @since 1.0.0
	 */
    function __construct() {

        // Register Settings Section
        add_filter( 'edd_settings_sections_extensions', array( $this, 'settings_section' ) );

        // Register Settings
        add_filter( 'edd_settings_extensions', array( $this, 'settings' ) );

        // Enqueue CSS/JS on our Admin Settings Tab
        add_action( 'edd_settings_tab_top_extensions_edd-slack-settings', array( $this, 'admin_settings_scripts' ) );
        
        add_action( 'edd_slack_post_id', array( $this, 'post_id_field' ) );

    }
    
    /**
    * Register Our Settings Section
    * 
    * @access       public
    * @since        1.0.0
    * @param        array $sections EDD Settings Sections
    * @return       array Modified EDD Settings Sections
    */
    public function settings_section( $sections ) {

        $sections['edd-slack-settings'] = __( 'Slack', EDD_Slack::$plugin_id );

        return $sections;

    }

    /**
    * Adds new Settings Section under "Extensions". Throws it under Misc if EDD is lower than v2.5
    * 
    * @access      public
    * @since       1.0.0
    * @param       array $settings The existing EDD settings array
    * @return      array The modified EDD settings array
    */
    public function settings( $settings ) {

        $edd_slack_settings = array(
            array(
                'name' => __( 'Slack Notifications', EDD_Slack::$plugin_id ),
                'type' => 'hook',
                'id' => 'slack_notifications_hook',
            )
        );

        // If EDD is at version 2.5 or later...
        if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
            // Place the Settings in our Settings Section
            $edd_slack_settings = array( 'edd-slack-settings' => $edd_slack_settings );
        }

        return array_merge( $settings, $edd_slack_settings );

    }

    /**
     * Enqueue our CSS/JS on our Admin Settings Tab
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function admin_settings_scripts() {

        wp_enqueue_style( EDD_Slack::$plugin_id . '-admin' );
        wp_enqueue_script( EDD_Slack::$plugin_id . '-admin' );

    }

}
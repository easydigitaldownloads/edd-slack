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
	 * edd_slack_Admin constructor.
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
                'id'   => 'edd_slack_notification_settings',
                'name' => __( 'Slack Notifications', EDD_Slack::$plugin_id ),
                'type' => 'repeater',
                'classes' => array( 'edd-slack-settings-repeater' ),
                'add_item_text' => __( 'Add Notification', EDD_Slack::$plugin_id ),
                'delete_item_text' => __( 'Remove Notification', EDD_Slack::$plugin_id ),
                'collapsable' => true,
                'collapsable_title' => __( 'New Slack Notification', EDD_Slack::$plugin_id ),
                'fields' => array(
                    'webhook'         => array(
                        'id'    => 'webhook',
                        'desc' => __( 'Slack Webhook URL', EDD_Slack::$plugin_id ),
                        'type'  => 'text',
                        'args'  => array(
                            'placeholder' => edd_get_option( 'psp_slack_webhook' ),
                            'desc'        => '<p class="description">' .
                                             __( 'You can override the above Webhook URL here.', EDD_Slack::$plugin_id ) .
                                             '</p>',
                        ),
                    ),
                    'channel'         => array(
                        'type'  => 'text',
                        'desc' => __( 'Slack Channel', EDD_Slack::$plugin_id ),
                        'args'  => array(
                            'placeholder' => __( 'Webhook default', EDD_Slack::$plugin_id ),
                        ),
                    ),
                    'icon'            => array(
                        'type'  => 'text',
                        'desc' => __( 'Icon Emoji or Image URL', EDD_Slack::$plugin_id ),
                        'args'  => array(
                            'placeholder' => __( 'Webhook default', EDD_Slack::$plugin_id ),
                        ),
                    ),
                    'username'        => array(
                        'type'  => 'text',
                        'desc' => __( 'Username', EDD_Slack::$plugin_id ),
                        'args'  => array(
                            'placeholder' => get_bloginfo( 'name' ),
                        ),
                    ),
                    'message_pretext' => array(
                        'type'  => 'text',
                        'desc' => __( 'Message Pre-text', EDD_Slack::$plugin_id ),
                        'args'  => array(
                            'desc' => '<p class="description">' . sprintf(
                                    __( 'Possible available dynamic variables for Message, Title, and Pre-text : %s', EDD_Slack::$plugin_id ),
                                    '<br/><code>' . implode( '</code><code>', array(
                                        '%project_title%',
                                        '%phase_title%',
                                        '%task_title%',
                                        '%comment_author%',
                                        '%comment_content%',
                                        '%comment_link%',
                                    ) ) . '</code>'
                                ) . '</p>',
                        ),
                    ),
                    'color'           => array(
                        'type'  => 'color',
                        'desc' => __( 'Color (Shows next to Message Title and Message)', EDD_Slack::$plugin_id ),
                        'std' => '#3299BB',
                    ),
                    'message_title'   => array(
                        'type'  => 'text',
                        'desc' => __( 'Message Title', EDD_Slack::$plugin_id ),
                    ),
                    'message_text'    => array(
                        'type'  => 'text',
                        'desc' => __( 'Message', EDD_Slack::$plugin_id ),
                    ),
                ),
            ),
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
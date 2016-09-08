<?php
/*
Plugin Name: Easy Digital Downloads - Slack
Plugin URL: http://easydigitaldownloads.com/downloads/slack
Description: Easily create custom attributes or meta for your Downloads
Version: 1.0.0
Text Domain: edd-slack
Author: Real Big Plugins
Author URI: http://realbigplugins.com
Contributors: d4mation
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'EDD_Slack' ) ) {

    /**
     * Main EDD_Slack class
     *
     * @since       1.0.0
     */
    class EDD_Slack {
        
        /**
         * @var         EDD_Slack $plugin_data Holds Plugin Header Info
         * @since       1.0.0
         */
        public $plugin_data;
        
        /**
         * @var         EDD_Slack $admin Admin Settings
         * @since       1.0.0
         */
        public $admin;
        
        /**
         * @var         EDD_Slack $slack_api EDD Slack API calls
         * @since       1.0.0
         */
        public $slack_api;
                
        /**
         * @var         EDD_Slack $notification_handler Notifications System
         * @since       1.0.0
         */
        public $notification_handler;
        
        /**
         * @var         EDD_Slack Integrates into our Notification System. Serves as an example on how to utilize it.
         * @since       1.0.0
         */
        public $notification_integration;
        
        /**
         * @var         EDD_Slack $notification_triggers Notification Triggers. Serves as an example on how to Trigger Notifications
         * @since       1.0.0
         */
        public $notification_triggers;
        
        /**
         * @var         Plugin ID used for Localization, script names, etc.
         * @since       1.0.0
         */
        public static $plugin_id = 'edd-slack';

        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_Slack
         */
        public static function instance() {
            
            static $instance = null;
            
            if ( null === $instance ) {
                $instance = new static();
            }
            
            return $instance;

        }
        
        protected function __construct() {
            
            $this->setup_constants();
            $this->load_textdomain();
            $this->require_necessities();
            
            // Register our CSS/JS for the whole plugin
            add_action( 'init', array( $this, 'register_scripts' ) );
            
            // Handle licensing
            if ( class_exists( 'EDD_License' ) ) {
                $license = new EDD_License( __FILE__, $this->plugin_data['Name'], EDD_Slack_VER, $this->plugin_data['Author'] );
            }
            
        }

        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            
            // WP Loads things so weird. I really want this function.
            if ( ! function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }
            
            // Only call this once, accessible always
            $this->plugin_data = get_plugin_data( __FILE__ );

            if ( ! defined( 'EDD_Slack_VER' ) ) {
                // Plugin version
                define( 'EDD_Slack_VER', $this->plugin_data['Version'] );
            }

            if ( ! defined( 'EDD_Slack_DIR' ) ) {
                // Plugin path
                define( 'EDD_Slack_DIR', plugin_dir_path( __FILE__ ) );
            }

            if ( ! defined( 'EDD_Slack_URL' ) ) {
                // Plugin URL
                define( 'EDD_Slack_URL', plugin_dir_url( __FILE__ ) );
            }

        }

        /**
         * Internationalization
         *
         * @access      private 
         * @since       1.0.0
         * @return      void
         */
        private function load_textdomain() {

            // Set filter for language directory
            $lang_dir = EDD_Slack_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_slack_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), EDD_Slack::$plugin_id );
            $mofile = sprintf( '%1$s-%2$s.mo', EDD_Slack::$plugin_id, $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/' . EDD_Slack::$plugin_id . '/' . $mofile;

            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-slack/ folder
                // This way translations can be overridden via the Theme/Child Theme
                load_textdomain( EDD_Slack::$plugin_id, $mofile_global );
            }
            else if ( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-slack/languages/ folder
                load_textdomain( EDD_Slack::$plugin_id, $mofile_local );
            }
            else {
                // Load the default language files
                load_plugin_textdomain( EDD_Slack::$plugin_id, false, $lang_dir );
            }

        }
        
        /**
         * Include different aspects of the Plugin
         * 
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function require_necessities() {
            
            if ( is_admin() ) {
                
                require_once EDD_Slack_DIR . '/core/admin/class-edd-slack-admin.php';
                $this->admin = new EDD_Slack_Admin();
                
            }
            
            require_once EDD_Slack_DIR . '/core/notifications/class-edd-slack-api.php';
            $this->slack_api = new EDD_Slack_API();
            
            require_once EDD_Slack_DIR . '/core/notifications/class-edd-slack-notification-handler.php';
            $this->notification_handler = new EDD_Slack_Notification_Handler();
            
            require_once EDD_Slack_DIR . '/core/notifications/class-edd-slack-notification-integration.php';
            $this->notification_integration = new EDD_Slack_Notification_Integration();
            
            require_once EDD_Slack_DIR . '/core/notifications/class-edd-slack-notification-triggers.php';
            $this->notification_triggers = new EDD_Slack_Notification_Triggers();
            
        }
        
        /**
         * Grab EDD Slack Notification Repeater Fields
         * 
         * @param       boolean $query Whether to run through Database Queries or not
         *              
         * @access      public
         * @since       1.0.0
         * @return      array   EDD Settings API Array
         */
        public function get_notification_fields( $query = true ) {
        
            return apply_filters( 'edd_slack_notification_fields', array(
                'webhook'         => array(
                    'desc' => __( 'Slack Webhook URL', EDD_Slack::$plugin_id ),
                    'type'  => 'text',
                    'placeholder' => edd_get_option( 'edd_slack_webhook' ),
                    'args'  => array(
                        'desc'        => '<p class="description">' .
                        __( 'You can override the above Webhook URL here.', EDD_Slack::$plugin_id ) .
                        '</p>',
                    ),
                ),
                'channel'         => array(
                    'type'  => 'text',
                    'desc' => __( 'Slack Channel', EDD_Slack::$plugin_id ),
                    'placeholder' => __( 'Webhook default', EDD_Slack::$plugin_id ),
                ),
                'icon'            => array(
                    'type'  => 'text',
                    'desc' => __( 'Icon Emoji or Image URL', EDD_Slack::$plugin_id ),
                    'placeholder' => __( 'Webhook default', EDD_Slack::$plugin_id ),
                ),
                'username'        => array(
                    'type'  => 'text',
                    'desc' => __( 'Username', EDD_Slack::$plugin_id ),
                    'placeholder' => get_bloginfo( 'name' ),
                ),
                'message_pretext' => array(
                    'type'  => 'text',
                    'desc' => __( 'Message Pre-text (Shows directly below Username and above the Title/Message)', EDD_Slack::$plugin_id ),
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
            ) );
            
        }
        
        /**
         * Register our CSS/JS to use later
         * 
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function register_scripts() {
            
            wp_register_style(
                EDD_Slack::$plugin_id . '-admin',
                EDD_Slack_URL . '/admin.css',
                null,
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : EDD_Slack_VER
            );
            
            wp_register_script(
                EDD_Slack::$plugin_id . '-admin',
                EDD_Slack_URL . '/admin.js',
                array( 'jquery' ),
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : EDD_Slack_VER,
                true
            );
            
        }

    }

} // End Class Exists Check

/**
 * The main function responsible for returning the one true EDD_Slack
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_Slack The one true EDD_Slack
 */
add_action( 'plugins_loaded', 'EDD_Slack_load' );
function EDD_Slack_load() {

    if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {

        if ( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class.extension-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();

    }
    else {
        
        require_once __DIR__ . '/core/edd-slack-functions.php';
		EDDSLACK();
        
    }

}
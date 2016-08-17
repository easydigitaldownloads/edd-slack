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
        private $plugin_data;
        
        /**
         * @var         EDD_Slack $admin Admin Settings
         * @since       1.0.0
         */
        private $admin;
        
        /**
         * @var         EDD_Slack $notifications EDD Slack Notifications System
         * @since       1.0.0
         */
        private $notifications;
        
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
            
            require_once EDD_Slack_DIR . '/core/notifications/class-edd-slack-notifications.php';
            $this->notifications = new EDD_Slack_Notifications();
            
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
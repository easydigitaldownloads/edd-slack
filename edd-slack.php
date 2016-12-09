<?php
/*
Plugin Name: Easy Digital Downloads - Slack
Plugin URL: http://easydigitaldownloads.com/downloads/slack
Description: Slack Integration for Easy Digital Downloads
Version: 0.5.0
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
         * @var         EDD_Slack $oauth_settings SSL-only OAUTH Settings
         * @since       1.0.0
         */
        public $oauth_settings;
        
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
         * @var         EDD_Slack $notification_integration Integrates into our Notification System. Serves as an example on how to utilize it.
         * @since       1.0.0
         */
        public $notification_integration;
        
        /**
         * @var         EDD_Slack $notification_triggers Notification Triggers. Serves as an example on how to Trigger Notifications
         * @since       1.0.0
         */
        public $notification_triggers;
        
        /**
         * @var         EDD_Slack $rest_api holds our WP REST API endpoint which routes to the necessary functions for the callback_id
         * @since       1.0.0
         */
        public $rest_api;

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
            
            if ( ! defined( 'EDD_Slack_ID' ) ) {
                // Plugin Text Domain
                define( 'EDD_Slack_ID', $this->plugin_data['TextDomain'] );
            }

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
            
            if ( ! defined( 'EDD_Slack_FILE' ) ) {
                // Plugin File
                define( 'EDD_Slack_FILE', __FILE__ );
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
            $locale = apply_filters( 'plugin_locale', get_locale(), EDD_Slack_ID );
            $mofile = sprintf( '%1$s-%2$s.mo', EDD_Slack_ID, $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/' . EDD_Slack_ID . '/' . $mofile;

            if ( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-slack/ folder
                // This way translations can be overridden via the Theme/Child Theme
                load_textdomain( EDD_Slack_ID, $mofile_global );
            }
            else if ( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-slack/languages/ folder
                load_textdomain( EDD_Slack_ID, $mofile_local );
            }
            else {
                // Load the default language files
                load_plugin_textdomain( EDD_Slack_ID, false, $lang_dir );
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
                
                if ( is_ssl() ) {
                    
                    require_once EDD_Slack_DIR . '/core/ssl-only/class-edd-slack-app-oauth-settings.php';
                    $this->oauth_settings = new EDD_Slack_OAUTH_Settings();
                    
                }
                
            }
            
            require_once EDD_Slack_DIR . '/core/slack/class-edd-slack-api.php';
            $this->slack_api = new EDD_Slack_API();
            
            require_once EDD_Slack_DIR . '/core/notifications/class-edd-slack-notification-handler.php';
            $this->notification_handler = new EDD_Slack_Notification_Handler();
            
            require_once EDD_Slack_DIR . '/core/slack/class-edd-slack-notification-integration.php';
            $this->notification_integration = new EDD_Slack_Notification_Integration();
            
            require_once EDD_Slack_DIR . '/core/slack/class-edd-slack-notification-triggers.php';
            $this->notification_triggers = new EDD_Slack_Notification_Triggers();
            
            // Include Bundled Integrations with this Plugin
            // These also serve as an example of how to tie-in to this Plugin and utilize its functionality
            
            // If Comments are Enabled for Downloads
            if ( post_type_supports( 'download', 'comments' ) ) {
                require_once EDD_Slack_DIR . '/core/integrations/edd-comments/class-edd-slack-comments.php';
            }
            
            // If EDD Reviews is Active
            if ( class_exists( 'EDD_Reviews' ) ) {
                require_once EDD_Slack_DIR . '/core/integrations/edd-reviews/class-edd-slack-reviews.php';
            }
            
            // If EDD Software Licensing is Active
            if ( class_exists( 'EDD_Software_Licensing' ) ) {
                require_once EDD_Slack_DIR . '/core/integrations/edd-software-licensing/class-edd-slack-software-licensing.php';
            }
            
            // If EDD FES is Active
            if ( class_exists( 'EDD_Front_End_Submissions' ) ) { 
                require_once EDD_Slack_DIR . '/core/integrations/edd-frontend-submissions/class-edd-slack-frontend-submissions.php';
            }
            
            // If EDD Commissions is Active
            if ( defined( 'EDD_COMMISSIONS_VERSION' ) ) {
                require_once EDD_Slack_DIR . '/core/integrations/edd-commissions/class-edd-slack-commissions.php';
            }
            
            if ( class_exists( 'EDD_Purchase_Limit' ) ) {
                require_once EDD_Slack_DIR . '/core/integrations/edd-purchase-limit/class-edd-slack-purchase-limit.php';
            }
            
            // If we've got a linked Slack App
            if ( is_ssl() && edd_get_option( 'slack_app_oauth_token' ) ) {
                
                require_once EDD_Slack_DIR . '/core/ssl-only/class-edd-slack-rest.php';
                $this->rest_api = new EDD_Slack_REST();
                
                // If Comments are Enabled for Downloads
                if ( post_type_supports( 'download', 'comments' ) ) {
                    require_once EDD_Slack_DIR . '/core/ssl-only/integrations/edd-comments/class-edd-slack-app-comments.php';
                }
                
                // If EDD FES is Active
                if ( class_exists( 'EDD_Front_End_Submissions' ) ) {
                    require_once EDD_Slack_DIR . '/core/ssl-only/integrations/edd-frontend-submissions/class-edd-slack-app-frontend-submissions.php';
                }
                
            }
            
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
            
            $downloads_array = array();
            $discount_codes_array = array();
            
            // Only run through all these queries when we need them
            if ( $query ) {
                
                $base_args = array(
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                );
                
                $downloads = get_posts( array(
                    'post_type' => 'download',
                ) + $base_args );
                
                $downloads_array = wp_list_pluck( $downloads, 'post_title', 'ID' );
                
                $discount_codes = get_posts( array(
                    'post_type' => 'edd_discount',
                    'post_status'    => array( 'active', 'inactive', 'expired' ),
                ) + $base_args );
                
                foreach ( $discount_codes as $discount_code ) {
                    
                    // Post Meta is the Key, so wp_list_pluck() won't work here
                    $code = get_post_meta( $discount_code->ID, '_edd_discount_code', true );
                    $discount_codes_array[ $code ] = $discount_code->post_title . ' - ' . $code;
                    
                }
                
            }
        
            return apply_filters( 'edd_slack_notification_fields', array(
                'slack_post_id' => array(
                    'type' => 'hook',
                    'std' => '',
                ),
                'admin_title' => array(
                    'desc' => __( 'Indentifier for this Notification', EDD_Slack_ID ),
                    'type' => 'text',
                    'readonly' => false,
                    'placeholder' => __( 'New Slack Notification', EDD_Slack_ID ),
                    'std' => '',
                    'field_class' => '',
                ),
                'trigger' => array(
                    'desc' => __( 'Slack Trigger', EDD_Slack_ID ),
                    'type' => 'select',
                    'chosen' => true,
                    'field_class' => 'edd-slack-trigger',
                    'options' => array( 
                        0 => _x( '-- Select a Slack Trigger --', 'Slack Trigger Default Label', EDD_Slack_ID ),
                     ) + $this->get_slack_triggers(),
                    'std' => 0,
                ),
                'download' => array(
                    'desc' => edd_get_label_singular(),
                    'type' => 'select',
                    'chosen' => true,
                    'field_class' => array(
                        'edd-slack-download',
                        'edd-slack-conditional',
                        'edd_complete_purchase',
                        'edd_discount_code_applied',
                    ),
                    'options' => array(
                        0 => sprintf( _x( '-- Select %s --', 'Select Field Default', EDD_Slack_ID ), edd_get_label_singular() ),
                        'all' => sprintf( _x( 'All %s', 'All items in a Select Field', EDD_Slack_ID ), edd_get_label_plural() ),
                    ) + $downloads_array,
                    'std' => 0,
                ),
                'discount_code' => array(
                    'desc' => _x( 'Discount Code', 'Discount Code Field Label', EDD_Slack_ID ),
                    'type' => 'select',
                    'chosen' => true,
                    'field_class' => array(
                        'edd-slack-download',
                        'edd-slack-conditional',
                        'edd_discount_code_applied',
                    ),
                    'options' => array(
                        0 => _x( '-- Select Discount Code --', 'Discount Code Field Default', EDD_Slack_ID  ),
                        'all' => _x( 'All Discount Codes', 'All Discount Codes Text', EDD_Slack_ID ),
                    ) + $discount_codes_array,
                    'std' => 0,
                ),
                'replacement_hints' => array(
                    'type' => 'hook',
                    'std' => '',
                ),
                'message_pretext' => array(
                    'type'  => 'text',
                    'desc' => __( 'Message Pre-text (Shows directly below Username and above the Title/Message)', EDD_Slack_ID ),
                    'readonly' => false,
                    'placeholder' => '',
                    'std' => '',
                    'field_class' => '',
                ),
                'message_title'   => array(
                    'type'  => 'text',
                    'desc' => __( 'Message Title', EDD_Slack_ID ),
                    'readonly' => false,
                    'placeholder' => '',
                    'std' => '',
                    'field_class' => '',
                ),
                'message_text'    => array(
                    'type'  => 'textarea',
                    'desc' => __( 'Message', EDD_Slack_ID ),
                    'std' => '',
                    'field_class' => '',
                ),
                'webhook'         => array(
                    'type'  => 'text',
                    'desc' => __( 'Slack Webhook URL', EDD_Slack_ID ),
                    'readonly' => false,
                    'placeholder' => edd_get_option( 'edd_slack_webhook' ),
                    'args'  => array(
                        'desc'        => '<p class="description">' .
                        __( 'You can override the above Webhook URL here.', EDD_Slack_ID ) .
                        '</p>',
                    ),
                    'std' => '',
                    'field_class' => '',
                ),
                'channel'         => array(
                    'type'  => 'text',
                    'desc' => __( 'Slack Channel', EDD_Slack_ID ),
                    'readonly' => false,
                    'placeholder' => __( 'Webhook default', EDD_Slack_ID ),
                    'std' => '',
                    'field_class' => '',
                ),
                'username'        => array(
                    'type'  => 'text',
                    'desc' => __( 'Username', EDD_Slack_ID ),
                    'readonly' => false,
                    'placeholder' => get_bloginfo( 'name' ),
                    'std' => '',
                    'field_class' => '',
                ),
                'icon'            => array(
                    'type'  => 'text',
                    'desc' => __( 'Icon Emoji or Image URL', EDD_Slack_ID ),
                    'readonly' => false,
                    'placeholder' => __( 'Webhook default', EDD_Slack_ID ),
                    'std' => '',
                    'field_class' => '',
                ),
                'color'           => array(
                    'type'  => 'color',
                    'desc' => __( 'Color (Shows next to Message Title and Message)', EDD_Slack_ID ),
                    'std' => '#3299BB',
                    'field_class' => '',
                ),
            ) );
            
        }
        
        /**
         * Returns a List of EDD Slack Triggers and their EDD Actions
         * 
         * @access      public
         * @since       1.0.0
         * @return      array EDD Slack Triggers
         */
        public function get_slack_triggers() {
            
            $triggers = apply_filters( 'edd_slack_triggers', array(
                'edd_complete_purchase' => _x( 'Purchase Complete', 'Purchase Complete Trigger Label', EDD_Slack_ID ),
                'edd_failed_purchase' => _x( 'Purchase Failed', 'Purchase Failed Trigger Label', EDD_Slack_ID ),
                'edd_discount_code_applied' => _x( 'Discount Code Applied', 'Discount Code Applied Trigger Label', EDD_Slack_ID ),
                'edd_insert_user' => _x( 'New User Registration via EDD', 'New User Registration Trigger Label', EDD_Slack_ID ),
            ) );
            
            asort( $triggers );
            
            return $triggers;
            
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
                EDD_Slack_ID . '-admin',
                EDD_Slack_URL . '/admin.css',
                null,
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : EDD_Slack_VER
            );
            
            wp_register_script(
                EDD_Slack_ID . '-admin',
                EDD_Slack_URL . '/admin.js',
                array( 'jquery' ),
                defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : EDD_Slack_VER,
                true
            );
            
        }
        
        /**
         * Utility Function to insert one Array into another at a specified Index. Useful for the Notification Repeater Field's Filter
         * 
         * @param       array   &$array       Array being modified. This passes by reference.
         * @param       integer $index        Insertion Index. Even if it is an associative array, give a numeric index. Determine it by doing a foreach() until you hit your desired placement and then break out of the loop.
         * @param       array   $insert_array Array being Inserted at the Index
         *                                                                
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function array_insert( &$array, $index, $insert_array ) { 
            
            // First half before the cut-off for the splice
            $first_array = array_splice( $array, 0, $index ); 
            
            // Merge this with the inserted array and the last half of the splice
            $array = array_merge( $first_array, $insert_array, $array );
            
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
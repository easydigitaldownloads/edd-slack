<?php
/**
 * The Notification System for EDD Slack
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/notifications
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Notifications {

    /**
	 * EDD_Slack_Notifications constructor.
	 *
	 * @since 1.0.0
	 */
    function __construct() {

        // Create our EDD Slack Notification Feed CPT to store Data in
        add_action( 'init', array( $this, 'register_slack_notifications_cpt' ) );

        // On Form Submission, Create/Update/Delete Notification Feeds
        add_action( 'init', array( $this, 'handle_created_updated_feeds' ) );
        
        // Include our Notification Feed Repeater using saved Post Data
        add_action( 'edd_slack_notifications_hook', array( $this, 'notification_repeater' ) );
        
        // Include Hidden Field for Post ID within the Repeater
        add_action( 'edd_slack_post_id', array( $this, 'post_id_field' ) );

    }

    public function register_slack_notifications_cpt() {

        $labels = array(
            'name'               => __( 'EDD Slack Notifications', EDD_Slack::$plugin_id ),
            'singular_name'      => __( 'EDD Slack Notification', EDD_Slack::$plugin_id ),
            'menu_name'          => __( 'EDD Slack Notifications', EDD_Slack::$plugin_id ),
            'name_admin_bar'     => __( 'EDD Slack Notification', EDD_Slack::$plugin_id ),
            'add_new'            => __( 'Add New', EDD_Slack::$plugin_id ),
            'add_new_item'       => __( 'Add New EDD Slack Notification', EDD_Slack::$plugin_id ),
            'new_item'           => __( 'New EDD Slack Notification', EDD_Slack::$plugin_id ),
            'edit_item'          => __( 'Edit EDD Slack Notification', EDD_Slack::$plugin_id ),
            'view_item'          => __( 'View EDD Slack Notification', EDD_Slack::$plugin_id ),
            'all_items'          => __( 'All EDD Slack Notifications', EDD_Slack::$plugin_id ),
            'search_items'       => __( 'Search EDD Slack Notifications', EDD_Slack::$plugin_id ),
            'parent_item_colon'  => __( 'Parent EDD Slack Notifications:', EDD_Slack::$plugin_id ),
            'not_found'          => __( 'No EDD Slack Notifications found.', EDD_Slack::$plugin_id ),
            'not_found_in_trash' => __( 'No EDD Slack Notifications found in Trash.', EDD_Slack::$plugin_id ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'supports' => 'custom-fields',
        );

        register_post_type( 'edd_slack_feeds', $args );

    }
    
    /**
     * Uses a Hook Field Type to include our Notification Repeater with a custom name Attribute as well as providing it Post Data
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function notification_repeater() {
        
        $feeds = get_posts( array(
            'post_type'   => 'edd_slack_feeds',
            'numberposts' => -1,
            'order'       => 'ASC',
        ) );

        $values = array();
        
        $notification_args = array();
        $notification_args['fields'] = $this->get_notification_fields();

        if ( ! empty( $feeds ) && ! is_wp_error( $feeds ) ) {
            
            foreach ( $feeds as $feed ) {

                $value = array(
                    'admin_title'  => get_the_title( $feed ),
                    'slack_post_id'      => $feed->ID,
                    //'notification' => get_post_meta( $feed->ID, "psp_{$notification_ID}_feed_notification", true ),
                );

                foreach ( $notification_args['fields'] as $field_ID => $field ) {
                    $value[ $field_ID ] = get_post_meta( $feed->ID, "edd_slack_feed_$field_ID", true );
                }

                $values[] = $value;
                
            }
        }
        
        $repeater_args = array(
            'id'   => 'edd_slack_notification_settings',
            'type' => 'repeater',
            'classes' => array( 'edd-slack-settings-repeater' ),
            'add_item_text' => __( 'Add Notification', EDD_Slack::$plugin_id ),
            'delete_item_text' => __( 'Remove Notification', EDD_Slack::$plugin_id ),
            'sortable' => false,
            'collapsable' => true,
            'collapsable_title' => __( 'New Slack Notification', EDD_Slack::$plugin_id ),
            'layout' => 'row',
            'std' => $values,
            'input_name' => 'edd_slack_feeds',
            'fields' => array(
                'admin_title'         => array(
                    'desc' => __( 'Identifier for this Notification', EDD_Slack::$plugin_id ),
                    'type'  => 'text',
                ),
                'slack_post_id'      => array( // Second field so that the Collapsable Title Labeling still works
                    'type'  => 'hook',
                ),
            ),
        );
        
        $repeater_args['fields'] = array_merge( $repeater_args['fields'], $notification_args['fields'] );
        
		echo edd_repeater_callback( $repeater_args );
        
        ?>
        <input type="hidden" name="psp_notification_deleted_feeds"/>
        <?php
        
    }
    
    /**
     * EDD's Settings API doesn't have support for Hidden Input Fields. In this case, I think a Hook works better than creating a new Callback Function.
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function post_id_field( $args ) { 

        // Post ID of 0 on wp_insert_post() auto-generates an available Post ID
        if ( empty( $args['std'] ) ) $args['std'] = 0;

        ?>
        
        <input type="hidden" name="<?php echo $args['id']; ?>" value="<?php echo (string) $args['std']; ?>" />

    <?php }
    
    /**
     * DRY EDD Slack Notification Fields. Wrapped in a Filter for easy tweaking.
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    private function get_notification_fields() {
        
        return apply_filters( 'edd_slack_notification_fields', array(
            'webhook'         => array(
                'desc' => __( 'Slack Webhook URL', EDD_Slack::$plugin_id ),
                'type'  => 'text',
                'placeholder' => edd_get_option( 'psp_slack_webhook' ),
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

    public function handle_created_updated_feeds() {

        // Handle creating and updating feed post types
        if ( isset( $_POST['action'] ) && $_POST['action'] == 'update' ) {

            $feeds = $_POST['edd_slack_feeds'];

            if ( empty( $feeds ) ) {
                return;
            }
            
            $notification_args = array();
            $notification_args['fields'] = $this->get_notification_fields();

            $notification_args = wp_parse_args( $notification_args, array(
                'default_feed_title' => __( 'New Slack Notification', EDD_Slack::$plugin_id ),
                'fields'             => array(),
            ) );

            $notification_args['fields']['notification'] = true;

            foreach ( $feeds as &$feed ) {

                $post_args = array(
                    'ID'          => (int) $feed['slack_post_id'] > 0 ? (int) $feed['slack_post_id'] : 0,
                    'post_type'   => 'edd_slack_feeds',
                    'post_title'  => '',
                    'post_status' => 'publish',
                );

                $notification_meta = array();
                foreach ( $notification_args['fields'] as $field_name => $field ) {
                    if ( isset( $feed[ $field_name ] ) ) {
                        $notification_meta["edd_slack_feed_$field_name"] = $feed[ $field_name ];
                    }
                }

                if ( $feed['admin_title'] ) {
                    $post_args['post_title'] = $feed['admin_title'];
                } else {
                    $post_args['post_title'] = $notification_args['default_feed_title'];
                }

                $post_ID = wp_insert_post( $post_args );
                
                // Ensure we're saved
                $feed['slack_post_id'] = $post_ID;

                if ( $post_ID !== 0 && ! is_wp_error( $post_ID ) ) {
                    foreach ( $notification_meta as $field_name => $field_value ) {
                        update_post_meta( $post_ID, $field_name, $field_value );
                    }
                }
                
            }

        } 

    }

}
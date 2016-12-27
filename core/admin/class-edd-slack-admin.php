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
        
        // Localize the admin.js
        add_filter( 'edd_slack_localize_admin_script', array( $this, 'localize_script' ) );

        // Enqueue CSS/JS on our Admin Settings Tab
        add_action( 'edd_settings_tab_top_extensions_edd-slack-settings', array( $this, 'admin_settings_scripts' ) );
        
        // Callback for the Slack Notification Repeater
        add_action( 'edd_slack_notifications_field', array( $this, 'edd_slack_notifications_field' ) );
        
        // Callback for the hidden Post ID output
        add_action( 'edd_slack_post_id', array( $this, 'post_id_field' ) );
        
        // Callback for the Replacement Hints
        add_action( 'edd_replacement_hints', array( $this, 'replacement_hints_field' ) );

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

        $sections['edd-slack-settings'] = __( 'Slack', EDD_Slack_ID );

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
        
        // Initialize repeater
        $repeater_values = array();
        $fields = EDDSLACK()->get_notification_fields();
        
        $feeds = get_posts( array(
            'post_type'   => 'edd-slack-rbm-feed',
            'numberposts' => -1,
            'order'       => 'ASC',
        ) );
        
        if ( ! empty( $feeds ) && ! is_wp_error( $feeds ) ) {
            
            foreach ( $feeds as $feed ) {
                
                $value = array(
                    'admin_title'  => get_the_title( $feed->ID ), // The first element in this Array is used for the Collapsable Title
                    'slack_post_id'      => $feed->ID,
                );
                
                // Conditionally Hide certain fields
                $trigger = get_post_meta( $feed->ID, 'edd_slack_rbm_feed_trigger', true );
                $trigger = ( $trigger ) ? $trigger : 0;
                
                foreach ( $fields as $field_id => $field ) {
                    
                    if ( $field_id == 'slack_post_id' || $field_id == 'admin_title' ) continue; // We don't need to do anything special with these
                    
                    $value[ $field_id ] = get_post_meta( $feed->ID, "edd_slack_rbm_feed_$field_id", true );
                    
                    if ( $field_id = 'replacement_hints' ) {
                        
                        $value[ $field_id ] = $trigger;
                        
                    }
                    
                }
                
                $repeater_values[] = $value;
                
            }
            
        }

        $edd_slack_settings = apply_filters( 'edd_slack_settings', array(
            array(
                'type' => 'text',
                'name' => _x( 'Default Webhook URL', 'Default Webhook URL Label', EDD_Slack_ID ),
                'id' => 'slack_webhook_default',
                'desc' => sprintf(
                    _x( 'Enter the slack Webhook URL for the team you wish to broadcast to. The channel chosen in the webhook can be overridden for each notification type below. You can set up the Webhook URL %shere%s.', 'Webhook Default Help Text', EDD_Slack_ID ),
                    '<a href="//my.slack.com/services/new/incoming-webhook/" target="_blank">',
                    '</a>'
                ),
                'field_class' => 'edd-slack-webhook-default',
            ),
            array(
                'type' => 'hook',
                'id' => 'slack_notifications_field',
                'input_name' => 'edd_slack_rbm_feeds',
                'name' => _x( 'Slack Notifications', 'Slack Notifications Repeater Label', EDD_Slack_ID ),
                'std' => $repeater_values,
                'add_item_text' => _x( 'Add Slack Notification', 'Add Slack Notification Button', EDD_Slack_ID ),
                'edit_item_text' => _x( 'Edit Slack Notification', 'Edit Slack Notification Button', EDD_Slack_ID ),
                'save_item_text' => _x( 'Save Slack Notification', 'Save Slack Notification Button', EDD_Slack_ID ),
                'delete_item_text' => _x( 'Delete Slack Notification', 'Delete Slack Notification Button', EDD_Slack_ID ),
                'default_title' => _x( 'New Slack Notification', 'New Slack Notification Header', EDD_Slack_ID ),
                'fields' => $fields,
            ),
        ) );

        // If EDD is at version 2.5 or later...
        if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
            // Place the Settings in our Settings Section
            $edd_slack_settings = array( 'edd-slack-settings' => $edd_slack_settings );
        }

        return array_merge( $settings, $edd_slack_settings );

    }
    
    public function edd_slack_notifications_field( $args ) {

        $args = wp_parse_args( $args, array(
            'id' => '',
            'std' => '',
            'classes' => array(),
            'fields' => array(),
            'add_item_text' => __( 'Add Row', EDD_Slack_ID ),
            'edit_item_text' => __( 'Edit Row', EDD_Slack_ID ),
            'save_item_text' => __( 'Save Row', EDD_Slack_ID ),
            'delete_item_text' => __( 'Delete Row', EDD_Slack_ID ),
            'default_title' => __( 'New Row', EDD_Slack_ID ),
            'input_name' => false,
        ) );
        
        // Ensure Dummy Field is created
        $field_count = ( count( $args['std'] ) >= 1 ) ? count( $args['std'] ) : 1;
        
        $name = $args['input_name'] !== false ? $args['input_name'] : 'edd_settings[' . esc_attr( $args['id'] ) . ']';
        
        do_action( 'edd_slack_before_repeater' );
        
        ?>

        <div data-edd-rbm-repeater class="edd-rbm-repeater <?php echo ( isset( $args['classes'] ) ) ? ' ' . implode( ' ', $args['classes'] ) : ''; ?>">
            
            <div data-repeater-list="<?php echo ( ! $args['nested'] ) ? $name : $args['id']; ?>" class="edd-rbm-repeater-list">

                    <?php for ( $index = 0; $index < $field_count; $index++ ) : $value = ( isset( $args['std'][$index] ) ) ? $args['std'][$index] : array(); ?>
                
                        <div data-repeater-item<?php echo ( ! isset( $args['std'][$index] ) ) ? ' data-repeater-dummy style="display: none;"' : ''; ?> class="edd-rbm-repeater-item">
                            
                            <table class="repeater-header wp-list-table widefat fixed posts">

                                <thead>

                                    <tr>
                                        <th scope="col">
                                            <div class="title" data-repeater-default-title="<?php echo $args['default_title']; ?>">

                                                <?php if ( isset( $args['std'][$index] ) && reset( $args['std'][$index] ) !== '' ) : 

                                                    // Surprisingly, this is the most efficient way to do this. http://stackoverflow.com/a/21219594
                                                    foreach ( $value as $key => $setting ) : ?>
                                                        <?php echo $setting; ?>
                                                    <?php 
                                                        break;
                                                    endforeach; 

                                                else: ?>

                                                    <?php echo $args['default_title']; ?>

                                                <?php endif; ?>

                                            </div>
                                            
                                            <div class="edd-rbm-repeater-controls">
                                            
                                                <input data-repeater-edit type="button" class="button" value="<?php echo $args['edit_item_text']; ?>" />
                                                <input data-repeater-delete type="button" class="button button-danger" value="<?php echo $args['delete_item_text']; ?>" />
                                                
                                            </div>

                                        </th>

                                    </tr>

                                </thead>

                            </table>
                            
                            <div class="edd-rbm-repeater-content reveal" data-reveal data-v-offset="64">
                                
                                <div class="edd-rbm-repeater-form">

                                    <table class="widefat" width="100%" cellpadding="0" cellspacing="0">

                                        <tbody>

                                            <?php foreach ( $args['fields'] as $field_id => $field ) : ?>

                                                <tr>

                                                    <?php if ( is_callable( "edd_{$field['type']}_callback" ) ) : 

                                                        // EDD Generates the Name Attr based on ID, so this nasty workaround is necessary
                                                        $field['id'] = $field_id;
                                                        $field['std'] = ( isset( $value[ $field_id ] ) ) ? $value[ $field_id ] : $field['std'];

                                                        if ( $field['type'] == 'checkbox' ) : 

                                                            if ( isset( $field['std'] ) && (int) $field['std'] !== 0 ) {
                                                                $field['field_class'][] = 'default-checked';
                                                            }

                                                        endif;

                                                        if ( $field['type'] !== 'hook' ) : ?>

                                                            <td>

                                                                <?php call_user_func( "edd_{$field['type']}_callback", $field ); ?>

                                                            </td>

                                                        <?php else : 

                                                            // Don't wrap calls for a Hook
                                                            call_user_func( "edd_{$field['type']}_callback", $field ); 

                                                        endif;

                                                    endif; ?>

                                                </tr>

                                            <?php endforeach; ?>

                                        </tbody>

                                    </table>
                                    
                                    <input type="submit" class="button button-primary alignright" value="<?php echo $args['save_item_text']; ?>" />
                                  
                                </div>
                                
                                <a class="close-button" data-close aria-label="<?php echo _x( 'Close Notification Editor', 'Close Slack Notification Modal', EDD_Slack_ID ); ?>">
                                    <span aria-hidden="true">&times;</span>
                                </a>
                                
                            </div>
                            
                        </div>

                    <?php endfor; ?>       

            </div>
            
            <input data-repeater-create type="button" class="button" style="margin-top: 6px;" value="<?php echo $args['add_item_text']; ?>" />

        </div>
        
        <?php
        
        do_action( 'edd_slack_after_repeater' );
        
    }
    
    /**
     * Creating a Hidden Field for a Post ID works out more simply using a Hook. 
     * 
     * @param       array  Field Args
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function post_id_field( $args ) {
        
        // Post ID of 0 on wp_insert_post() auto-generates an available Post ID
        if ( ! isset( $args['std'] ) ) $args['std'] = 0;
        
        if ( isset( $args['field_class'] ) ) $args['field_class'] = edd_sanitize_html_class( $args['field_class'] );
        
        ?>

        <input type="hidden" name="<?php echo $args['id']; ?>" value="<?php echo (string) $args['std']; ?>" class="<?php echo $args['field_class']; ?>" />

    <?php
    }
    
    /**
     * Create the Replacements Field based on the returned Array
     * 
     * @param       array  $args Field Args
     *                          
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function replacement_hints_field( $args ) {
        
        $args = wp_parse_args( $args, array(
            'std' => false,
        ) );
        
        $hints = $this->get_replacement_hints();
        $selected = $args['std'];
        
        foreach ( $hints as $class => $hint ) : ?>

            <td class="edd-slack-replacement-instruction <?php echo $class; ?><?php echo ( $class !== $selected ) ? ' hidden' : ''; ?>">
                <div class="header-text">
                    <?php echo _x( 'Here are the available text replacements to use in the Message Pre-Text, Message Title, and Message Fields for the Slack Trigger selected:', 'Text Replacements Label', EDD_Slack_ID ); ?>

                </div>

                <?php foreach ( $hint as $replacement => $label ) : ?>

                    <div class="replacement-wrapper">
                        <span class="replacement"><?php echo $replacement; ?></span> : <span class="label"><?php echo $label; ?></span>
                    </div>

                <?php endforeach; ?>
                
            </td>

        <?php endforeach;
        
    }
    
    /**
     * Filterable Array holding all the Text Replacement Hints for all the Triggers
     * 
     * @access      private
     * @since       1.0.0
     * @return      array   Array of Text Replacement Hints for each Trigger
     */
    private function get_replacement_hints() {
        
        /**
         * Add extra Replacement Hints directly to the User Group
         *
         * @since 1.0.0
         */
        $user_hints = apply_filters( 'edd_slack_user_replacement_hints', array(
            '%username%' => _x( 'Display the user\'s username', '%username% Hint Text', EDD_Slack_ID ),
            '%email%' => _x( 'Display the user\'s email', '%email% Hint Text', EDD_Slack_ID ),
            '%name%' => _x( 'Display the user\'s display name', '%name% Hint Text', EDD_Slack_ID ),
        ) );
        
        /**
         * Add extra Replacement Hints directly to the Payment Triggers
         *
         * @since 1.0.0
         */
        $payment_hints = apply_filters( 'edd_slack_payment_replacement_hints', array(
            '%cart%' => _x( 'Show the contents of the Cart', '%cart% Hint Text', EDD_Slack_ID ),
            '%subtotal%' => _x( 'Show the Subtotal', '%subtotal% Hint Text', EDD_Slack_ID ),
            '%total%' => _x( 'Show the Total', '%total% Hint Text', EDD_Slack_ID ),
            '%discount_code%' => _x( 'Show the Discount Code entered', '%discount_code% Hint Text', EDD_Slack_ID ),
            '%ip_address%' => _x( 'Show the IP Address of the Customer', '%ip_address% Hint Text', EDD_Slack_ID ),
            '%payment_link%' => _x( 'Show a link to the Payment Details page for this Payment', '%payment_link% Hint Text', EDD_Slack_ID ),
        ) );

        /**
         * Allows additional Triggers to be added to the Replacement Hints
         *
         * @since 1.0.0
         */
        $replacement_hints = apply_filters( 'edd_slack_text_replacement_hints', 
                                           array(
                                                'edd_complete_purchase' => array_merge( $user_hints, $payment_hints ),
                                                'edd_discount_code_applied' => array_merge( $user_hints, $payment_hints ),
                                                'edd_failed_purchase' => array_merge( $user_hints, $payment_hints ),
                                                'edd_insert_user' => $user_hints,
                                           ),
                                           $user_hints,
                                           $payment_hints
                                          );

        return $replacement_hints;
        
    }
    
    /**
     * Localize the Admin.js with some values from PHP-land
     * 
     * @param       array $localization Array holding all our Localizations
     *                                                        
     * @access      public
     * @since       1.0.0
     * @return      array Modified Array
     */
    public function localize_script( $localization ) {
        
        $localization['i18n'] = array(
            'activeText' => _x( 'Active Notification', 'Active Notification Aria Label', EDD_Slack_ID ),
            'inactiveText' => _x( 'Inactive Notification', 'Inactive Notification Aria Label', EDD_Slack_ID ),
            'confirmDeletion' => _x( 'Are you sure you want to delete this Slack Notification?', 'Confirm Notification Deletion', EDD_Slack_ID ),
        );
        
        $localization['ajax'] = admin_url( 'admin-ajax.php' );
        
        return $localization;
        
    }

    /**
     * Enqueue our CSS/JS on our Admin Settings Tab
     * 
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function admin_settings_scripts() {

        wp_enqueue_style( EDD_Slack_ID . '-admin' );
        
        // Dependencies
        wp_enqueue_script( 'jquery-effects-core' );
        wp_enqueue_script( 'jquery-effects-highlight' );
        
        wp_enqueue_script( EDD_Slack_ID . '-admin' );

    }

}
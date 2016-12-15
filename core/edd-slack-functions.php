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

if ( ! function_exists( 'edd_rbm_repeater_callback' ) ) {
    
    function edd_rbm_repeater_callback( $args ) {

        $args = wp_parse_args( $args, array(
            'id' => '',
            'std' => '',
            'classes' => array(),
            'fields' => array(),
            'add_item_text' => __( 'Add Row', EDD_Slack_ID ),
            'edit_item_text' => __( 'Edit Row', EDD_Slack_ID ),
            'delete_item_text' => __( 'Delete Row', EDD_Slack_ID ),
            'default_title' => __( 'New Row', EDD_Slack_ID ),
            'input_name' => false,
        ) );
        
        // Ensure Dummy Field is created
        $field_count = ( count( $args['std'] ) >= 1 ) ? count( $args['std'] ) : 1;
        
        $name = $args['input_name'] !== false ? $args['input_name'] : 'edd_settings[' . esc_attr( $args['id'] ) . ']';
        
        ?>

        <div data-edd-rbm-repeater class="edd-rbm-repeater <?php echo ( isset( $args['classes'] ) ) ? ' ' . implode( ' ', $args['classes'] ) : ''; ?>">
            
            <div data-repeater-list="<?php echo ( ! $args['nested'] ) ? $name : $args['id']; ?>" class="edd-rbm-repeater-list">

                    <?php for ( $index = 0; $index < $field_count; $index++ ) : $value = ( isset( $args['std'][$index] ) ) ? $args['std'][$index] : array(); ?>
                
                        <div data-repeater-item<?php echo ( ! isset( $args['std'][$index] ) ) ? ' data-repeater-dummy style="display: none;"' : ''; ?> class="edd-rbm-repeater-item">
                            
                            <table class="repeater-header wp-list-table widefat fixed posts">

                                <thead>

                                    <tr>
                                        <th scope="col">
                                            <span class="title" data-repeater-default-title="<?php echo $args['default_title']; ?>">

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

                                            </span>

                                        </th>

                                        <th scope="col" class="edd-rbm-repeater-controls">
                                            <input data-repeater-edit type="button" class="button" value="<?php echo $args['edit_item_text']; ?>" />
                                            <input data-repeater-delete type="button" class="button" value="<?php echo $args['delete_item_text']; ?>" />
                                        </th>

                                    </tr>

                                </thead>

                            </table>
                            
                            <div class="edd-rbm-repeater-content reveal" data-reveal>

                                <table class="widefat" width="100%" cellpadding="0" cellspacing="0">

                                    <tbody>

                                        <?php foreach ( $args['fields'] as $field_id => $field ) : ?>
                                        
                                            <tr>

                                                <?php if ( is_callable( "edd_{$field['type']}_callback" ) ) : 

                                                    // EDD Generates the Name Attr based on ID, so this nasty workaround is necessary
                                                    $field['id'] = $field_id;
                                                    $field['std'] = ( isset( $value[ $field_id ] ) ) ? $value[ $field_id ] : $field['std'];

                                                    if ( $field['type'] == 'checkbox' ) : 

                                                        if ( isset( $field['std'] ) && is_array( $field['std'] ) ) {
                                                            $field['std'] = $field['std'][0];
                                                        }

                                                        if ( isset( $field['std'] ) && $field['std'] ) {
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
                                
                            </div>
                            
                        </div>

                    <?php endfor; ?>       

            </div>
            
            <input data-repeater-create type="button" class="button" style="margin-top: 6px;" value="<?php echo $args['add_item_text']; ?>" />

        </div>
        
        <?php
        
    }
    
}
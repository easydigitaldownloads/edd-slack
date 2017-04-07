<?php
/**
 * Adds SSL-Only Settings for Inviting Users to your Slack Team
 *
 * @since 1.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only/slack-invites
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Invites_Settings {
	
	/**
	 * @var		 EDD_Slack_OAUTH_Settings $general_channel If we know what the renamed General Channel is, use it instead
	 * @since	  1.1.0
	 */
	public $general_channel = 'general';
	
	/**
	 * EDD_Slack_Invites_Settings constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Updates our $general_channel and sets/updates our Transient
		add_action( 'admin_init', array( $this, 'get_public_channels' ) );
		
		add_filter( 'edd_slack_oauth_settings', array( $this, 'add_slack_invites_settings' ) );
		
	}
	
	public function add_slack_invites_settings( $settings ) {
		
		$slack_invites_settings = array(
			array(
				'type' => 'checkbox',
				'name' => _x( 'Enable Slack Team Invites for new Customers', 'Customer Slack Invite Checkbox Label', 'edd-slack' ),
				'id' => 'slack_app_team_invites_customer',
				'desc' => _x( 'This will add a checkbox to the end of the Purchase Form for Customers to be added to your Slack Team', 'Customer Slack Invite Description' ),
			),
			array(
				'type' => 'rbm_multi_select',
				'name' => _x( 'Channels for Customers', 'Channels for Customers Label', 'edd-slack' ),
				'id' => 'slack_app_team_invites_customer_channels',
				'field_class' => array(
					'edd-slack-multi-select',
					'regular-text',
					'edd-slack-customer-channels',
				),
				'chosen' => true,
				'options' => $this->get_public_channels(),
				'placeholder' => sprintf( _x( 'Just #%s', 'Just #general Channel Invite', 'edd-slack' ), $this->general_channel ),
				'std' => array(),
				'desc' => sprintf( _x( 'The <code>#%s</code> Channel is always granted by default. Choose any other additional Channels you would like to auto-invite Customers to.', 'Channels for Customers Description Text', 'edd-slack' ), $this->general_channel ),
			),
		);
		
		if ( class_exists( 'EDD_Front_End_Submissions' ) ) {
			
			$slack_invites_settings[] = array(
				'type' => 'checkbox',
				'name' => sprintf( _x( 'Enable Slack Team Invites for new %s', 'Vendor Slack Invite Checkbox Label', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( true, true ) ),
				'id' => 'slack_app_team_invites_vendor',
				'desc' => sprintf( _x( 'This will add a checkbox to the end of the Purchase Form for %s to be added to your Slack Team', 'Vendor Slack Invite Description' ), EDD_FES()->helper->get_vendor_constant_name( true, true ) ),
			);
			
			$slack_invites_settings[] = array(
				'type' => 'rbm_multi_select',
				'name' => sprintf( _x( 'Channels for %s', 'Channels for Vendors label', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( true, true ) ),
				'id' => 'slack_app_team_invites_vendor_channels',
				'field_class' => array(
					'edd-slack-multi-select',
					'regular-text',
					'edd-slack-vendor-channels',
				),
				'chosen' => true,
				'options' => $this->get_public_channels(),
				'placeholder' => sprintf( _x( 'Just #%s', 'Just #general Channel Invite', 'edd-slack' ), $this->general_channel ),
				'std' => array(),
				'desc' => sprintf( _x( 'The <code>#%s</code> Channel is always granted by default. Choose any other additional Channels you would like to auto-invite %s to.', 'Channels for Vendors Description Text', 'edd-slack' ), $this->general_channel, EDD_FES()->helper->get_vendor_constant_name( true, true ) )
			);
			
		}
		
		$settings = array_merge( $settings, $slack_invites_settings );
		
		return $settings;
		
	}
	
	/**
	 * Returns all Public Slack Channels from the Slack API
	 * 
	 * @access		public
	 * @since		1.1.0
	 * @return		array Slack Channels
	 */
	public function get_public_channels() {
		
		// Don't bother if we aren't granting Client Scope
		if ( ! edd_get_option( 'slack_app_has_client_scope' ) ) return array();
		
		if ( ! $channels_array = maybe_unserialize( get_transient( 'edd_slack_channels_list' ) ) ) {
		
			$result = EDDSLACK()->slack_api->get( 'channels.list' );

			$channels = $result->channels;

			$channels_array = array();
			foreach ( $channels as $channel ) {

				if ( $channel->is_general ) {

					// If necessary, update our General Channel
					$this->general_channel = ( $channel->name !== $this->general_channel ) ? $channel->name : $this->general_channel;

					continue; // Skip

				}

				$channels_array[ $channel->id ] = '#' . $channel->name;

			}
			
			set_transient( 'edd_slack_channels_list', $channels_array, DAY_IN_SECONDS );
			
		}
		
		return $channels_array;
		
	}
	
}

$integrate = new EDD_Slack_Invites_Settings();
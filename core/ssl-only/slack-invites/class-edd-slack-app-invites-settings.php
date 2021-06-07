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

		// Adds our Slack Team Invite Settings
		add_filter( 'edd_slack_oauth_settings', array( $this, 'add_slack_invites_settings' ) );

		// Updates #general Channel in other places
		add_filter( 'edd_slack_general_channel', array( $this, 'update_general_channel' ) );

	}

	/**
	 * Adds the Slack Team Invite Settings to the OAUTH Section
	 *
	 * @param		array $oauth_settings OAUTH Settings
	 *
	 * @access		public
	 * @since		1.1.0
	 * @return		array Modified OAUTH Settings
	 */
	public function add_slack_invites_settings( $oauth_settings ) {

		$slack_invites_settings = array(
			array(
				'type' => 'header',
				'name' => '<h3>' . _x( 'Enable Auto-Inviting Users to your Slack Team', 'Slack Team Invite Settings Header', 'edd-slack' ),
				'id' => 'edd-slack-slack-team-invite-header',
				'desc' => _x( 'This uses the same Client ID, Client Secret, and Verification Code above. It just needs special permissions, so you will need to authorize it a second time.', 'Slack Team Invite Description', 'edd-slack' ),
			),
			array(
				'type' => 'hook',
				'id' => 'slack_invites_oauth_register',
			),
		);

		if ( edd_get_option( 'slack_app_has_client_scope', false ) ) {

			$slack_invites_settings[] = array(
				'type' => 'textarea',
				'name' => 'Join Slack Team Text',
				'id' => 'slack_app_join_team_team_text',
				'std' => apply_filters( 'edd_slack_app_join_slack_team_default_text', _x( 'Join our Slack Team?', 'Join Slack Team Text Default', 'edd-slack' ) ),
				'desc' => _x( 'This text is used as the label for the checkbox when someone chooses to join your Slack Team.', 'Join Slack Team Text Description', 'edd-slack' ),
			);

			$slack_invites_settings[] = array(
				'type' => 'checkbox',
				'name' => _x( 'Enable Slack Team Invites for new Customers', 'Customer Slack Invite Checkbox Label', 'edd-slack' ),
				'id' => 'slack_app_team_invites_customer',
				'desc' => _x( 'This will add a checkbox to the end of the Purchase Form for Customers to be added to your Slack Team', 'Customer Slack Invite Description' ),
			);

			$public_channels = $this->get_public_channels();
			$slack_invites_settings[] = array(
				'type' => 'select',
				'name' => _x( 'Channels for Customers', 'Channels for Customers Label', 'edd-slack' ),
				'id' => 'slack_app_team_invites_customer_channels',
				'field_class' => array(
					'regular-text',
					'edd-slack-customer-channels',
				),
				'chosen' => true,
				'multiple' => true,
				'options' => $public_channels,
				'placeholder' => sprintf( _x( 'Just #%s', 'Just #general Channel Invite', 'edd-slack' ), $this->general_channel ),
				'std' => array(),
				'desc' => sprintf( _x( 'The <code>#%s</code> Channel is always granted by default. Choose any other additional Channels you would like to auto-invite Customers to.', 'Channels for Customers Description Text', 'edd-slack' ), $this->general_channel ),
			);

			// Add settings for Vendors
			if ( class_exists( 'EDD_Front_End_Submissions' ) ) {

				$slack_invites_settings[] = array(
					'type' => 'checkbox',
					'name' => sprintf( _x( 'Enable Slack Team Invites for new %s', 'Vendor Slack Invite Checkbox Label', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( true, true ) ),
					'id' => 'slack_app_team_invites_vendor',
					'desc' => sprintf( _x( 'This will add a checkbox to the end of the %s Submission Form for %s to be added to your Slack Team', 'Vendor Slack Invite Description' ), EDD_FES()->helper->get_vendor_constant_name( false, true ), EDD_FES()->helper->get_vendor_constant_name( true, true ) ),
				);

				$slack_invites_settings[] = array(
					'type' => 'select',
					'name' => sprintf( _x( 'Channels for %s', 'Channels for Vendors label', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( true, true ) ),
					'id' => 'slack_app_team_invites_vendor_channels',
					'field_class' => array(
						'regular-text',
						'edd-slack-vendor-channels',
					),
					'multiple' => true,
					'chosen' => true,
					'options' => $public_channels,
					'placeholder' => sprintf( _x( 'Just #%s', 'Just #general Channel Invite', 'edd-slack' ), $this->general_channel ),
					'std' => array(),
					'desc' => sprintf( _x( 'The <code>#%s</code> Channel is always granted by default. Choose any other additional Channels you would like to auto-invite %s to.', 'Channels for Vendors Description Text', 'edd-slack' ), $this->general_channel, EDD_FES()->helper->get_vendor_constant_name( true, true ) )
				);

			}

		}

		$oauth_settings = array_merge( $oauth_settings, $slack_invites_settings );

		return $oauth_settings;

	}

	/**
	 * Returns all Public Slack Channels from the Slack API
	 *
	 * @access  public
	 * @since   1.1.0
	 * @return  array Slack Channels
	 */
	public function get_public_channels() {

		$channels_array = array();
		// Don't bother if we aren't granting Client Scope
		if ( ! edd_get_option( 'slack_app_has_client_scope', false ) ) {
			return $channels_array;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return $channels_array;
		}

		$transient = maybe_unserialize( get_transient( 'edd_slack_channels_list' ) );
		if ( $transient ) {
			return $transient;
		}

		$oauth_token = edd_get_option( 'slack_app_oauth_token', false );
		// Don't bother if we don't have an OAUTH Token
		if ( ! $oauth_token || '-1' == $oauth_token ) {
			return $channels_array;
		}

		$result = EDDSLACK()->slack_api->get( 'conversations.list' );

		if ( empty( $result->channels ) ) {
			return $channels_array;
		}

		foreach ( $result->channels as $channel ) {

			if ( $channel->is_general ) {

				// If necessary, update our General Channel
				$this->general_channel = ( $channel->name !== $this->general_channel ) ? $channel->name : $this->general_channel;

				continue; // Skip
			}

			$channels_array[ $channel->id ] = '#' . $channel->name;
		}

		set_transient( 'edd_slack_channels_list', $channels_array, DAY_IN_SECONDS );

		return $channels_array;
	}

	/**
	 * Updates the #general Channel used in a few places via a Filter
	 * Since channels can be renamed, if possible we want to use their actual "general" channel name
	 *
	 * @param		string $general_channel The "general" channel without the hash
	 *
	 * @access		public
	 * @since		1.1.0
	 * @return		string The correct "general" channel name
	 */
	public function update_general_channel( $general_channel ) {

		return $this->general_channel;

	}

}

$integrate = new EDD_Slack_Invites_Settings();

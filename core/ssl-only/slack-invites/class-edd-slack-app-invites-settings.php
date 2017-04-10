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
		
		// Adds our Slack Team Invite Settings
		add_filter( 'edd_slack_oauth_settings', array( $this, 'add_slack_invites_settings' ) );
		
		// Updates #general Channel in other places
		add_filter( 'edd_slack_general_channel', array( $this, 'update_general_channel' ) );
		
		// Adds CSS/JS
		add_action( 'edd_customer_tools_top', array( $this, 'enqueue_scripts' ) );
		
		// Adds manual Slack Team Invitation buttons to the Customer Tools screen
		add_action( 'edd_customer_tools_bottom', array( $this, 'add_slack_team_customer_invite_button' ) );
		
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
				'type' => 'textarea',
				'name' => 'Join Slack Team Text',
				'id' => 'slack_app_join_team_team_text',
				'std' => apply_filters( 'edd_slack_app_join_slack_team_default_text', _x( 'Join our Slack Team?', 'Join Slack Team Text Default', 'edd-slack' ) ),
				'desc' => _x( 'This text is used as the label for the checkbox when someone chooses to join your Slack Team.', 'Join Slack Team Text Description', 'edd-slack' ),
			),
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
		
		// Add settings for Vendors
		if ( class_exists( 'EDD_Front_End_Submissions' ) ) {
			
			$slack_invites_settings[] = array(
				'type' => 'checkbox',
				'name' => sprintf( _x( 'Enable Slack Team Invites for new %s', 'Vendor Slack Invite Checkbox Label', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( true, true ) ),
				'id' => 'slack_app_team_invites_vendor',
				'desc' => sprintf( _x( 'This will add a checkbox to the end of the %s Submission Form for %s to be added to your Slack Team', 'Vendor Slack Invite Description' ), EDD_FES()->helper->get_vendor_constant_name( false, true ), EDD_FES()->helper->get_vendor_constant_name( true, true ) ),
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
		
		$oauth_settings = array_merge( $oauth_settings, $slack_invites_settings );
		
		return $oauth_settings;
		
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
		if ( ! edd_get_option( 'slack_app_has_client_scope', false ) ) return array();
		
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
	
	/**
	 * Add our CSS/JS as needed
	 * 
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function enqueue_scripts() {
		
		wp_enqueue_script( 'edd-slack-admin' );
		
	}
	
	/**
	 * Adds an "Add to Slack Team" button to manually add the Customer to the Slack Team
	 * 
	 * @param		object $customer EDD_Customer Object
	 *                                       
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function add_slack_team_customer_invite_button( $customer ) {
		
		?>
		<div class="edd-item-info customer-info">
			
			<h4><?php echo EDDSLACK()->plugin_data['Name']; ?></h4>
			
			<p class="edd-item-description">
				<?php echo _x( 'Manually Invite this Customer to your Slack Team', 'Manually Invite to Slack Team Label', 'edd-slack' ); ?>
			</p>
			
			<?php if ( ! $customer->get_meta( 'edd_slack_app_invite_sent', true ) ) : ?>
			
				<form method="post" id="edd_slack_team_customer_invite_form" class="edd-slack-team-customer-invite-form">
					<span>
						<?php wp_nonce_field( 'edd_slack_team_customer_invite', 'edd_slack_team_customer_invite' ); ?>

						<input type="submit" id="edd_slack_app_invite" value="<?php echo _x( 'Invite this Customer to your Slack Team', 'Slack Team Invite Button Text', 'edd-slack' ); ?>" class="button-secondary"/>
						<span class="spinner"></span>

					</span>
				</form>
			
			<?php else : ?>
			
				<p class="description">
					<?php echo _x( 'This Customer has already been sent an invite to your Slack Team', 'Slack Team invite already sent to Customer', 'edd-slack' ); ?>
				</p>
			
			<?php endif; ?>

		</div>

		<?php
		
	}
	
}

$integrate = new EDD_Slack_Invites_Settings();
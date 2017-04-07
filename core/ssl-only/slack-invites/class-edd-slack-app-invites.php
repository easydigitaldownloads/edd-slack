<?php
/**
 * Sends Slack Team Invites as appropriate
 *
 * @since 1.1.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/ssl-only/slack-invites
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Invites {
	
	/**
	 * EDD_Slack_Invites constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		// Adds a Checkbox to the Purchase Form for Customers to be added to the Slack Team
		add_action( 'edd_purchase_form_before_submit', array( $this, 'customers_slack_invite_checkbox' ) );
		
		// Adds a Checkbox to the Vendor Submission Form for Vendors to be added to the Slack Team
		
		// Checks if a Customer should be added to a Slack Team
		// add_action();
		
		// Checks if a Vendor should be added to a Slack Team
		
	}
	
	/**
	 * Adds a Checkbox to the Purchase Form for Customers to be added to the Slack Team
	 * 
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function customers_slack_invite_checkbox() {
		
		// Defaults to having the checkbox "checked". Allows altering the functionality to be Opt-in rather than Opt-out
		$checked = apply_filters( 'slack_app_team_invites_customer_default', 1 );
		
		if ( edd_get_option( 'slack_app_team_invites_customer', false ) ) : ?>
		
			<fieldset id="edd_slack_send_customer_team_invite_fieldset">
				<div class="edd-slack-send-customer-team-invite">
					<input name="edd_slack_send_customer_team_invite" type="checkbox" id="edd_slack_send_customer_team_invite" value="1" <?php checked( $checked, 1, true ); ?>/>
					<label for="edd_slack_send_customer_team_invite">
						test
					</label>
				</div>
			</fieldset>

	<?php endif;
		
	}
	
	/**
	 * Sends a Slack Team Invite via Email
	 * 
	 * @param		string $email      Email Address (Required)
	 * @param		string $channels   Comma-separated Channel IDs/Names to auto-sub the User to. These must be PUBLIC Channels
	 * @param		string $first_name First Name (Pre-populates the Sign-up Form in Slack)
	 * @param		string $last_name  Last Name (Pre-populates the Sign-up Form in Slack)
	 *                                                                         
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function send_invite( $email, $channels = '', $first_name = '', $last_name = '' ) {
			
		$args = array(
			'email' => $email,
			'channels' => $channels,
			'first_name' => $first_name,
			'last_name' => $last_name,
		);

		$invite = EDDSLACK()->slack_api->post( 
			'users.admin.invite',
			array(
				'body' => $args
			)
		);
		
	}
	
}

$integrate = new EDD_Slack_Invites();
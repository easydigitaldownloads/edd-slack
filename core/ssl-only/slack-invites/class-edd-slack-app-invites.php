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
			
		// Check to see if Customer Invites are enabled
		if ( edd_get_option( 'slack_app_team_invites_customer', false ) ) {

			// Adds a Checkbox to the Purchase Form for Customers to be added to the Slack Team
			add_action( 'edd_purchase_form_before_submit', array( $this, 'customers_slack_invite_checkbox' ) );

			// Checks if a Customer should be added to a Slack Team, then sends off the Invite
			add_action( 'edd_complete_purchase', array( $this, 'add_customer_to_slack_team_via_form' ) );

		}

		// Adds CSS/JS to the Customer Tools View
		add_action( 'edd_customer_tools_top', array( $this, 'enqueue_scripts' ) );
		
		// Adds manual Slack Team Invitation button to the Customer Tools screen
		add_action( 'edd_customer_tools_bottom', array( $this, 'add_slack_team_customer_invite_button' ) );
		
		// Localize the admin.js
		add_filter( 'edd_slack_localize_admin_script', array( $this, 'localize_script' ) );

		// Check to see if Vendor Invites are enabled
		if ( class_exists( 'EDD_Front_End_Submissions' ) &&
		   edd_get_option( 'slack_app_team_invites_vendor', false ) ) {

			// Adds a Checkbox to the Vendor Submission Form for Vendors to be added to the Slack Team
			add_filter( 'fes_load_registration_form_fields', array( $this, 'vendors_slack_invite_checkbox' ), 10, 2 );
			
			// Checks if a Vendor should be added to a Slack Team
			add_action( 'edd_post_insert_vendor', array( $this, 'add_vendor_to_slack_team_via_form' ), 10, 2 );
			
			// Adds CSS/JS to the Vendor screen
			add_action( 'fes_vendor_card_top', array( $this, 'enqueue_scripts' ) );
			
			// Adds manual Slack Team Invitation button to the Vendor screen
			add_action( 'fes_vendor_before_stats', array( $this, 'add_slack_team_vendor_invite_button' ) );

		}
		
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
		
		$label = edd_get_option( 'slack_app_join_team_team_text', apply_filters( 'edd_slack_app_join_slack_team_default_text', _x( 'Join our Slack Team?', 'Join Slack Team Text Default', 'edd-slack' ) ) );
		
		$show_checkbox = false;
		
		// Determine whether we should show the Checkbox
		// For non-logged in Customers, we will always need to show it as we have no way to know if they have been invited until they complete the form
		// For logged in Customers, we can know before hand
		if ( ! is_user_logged_in() ) {
			$show_checkbox = true;
		}
		else {
			
			$customer = new EDD_Customer( get_current_user_id() );
			
			// We also have to check User Meta in case they are a Vendor
			if ( ! $customer->get_meta( 'edd_slack_app_invite_sent', true ) &&
			   ! get_user_meta( get_current_user_id(), 'edd_slack_app_invite_sent', true ) ) {
				$show_checkbox = true;
			}
			
		}
		
		if ( $show_checkbox ) : ?>
		
			<fieldset id="edd_slack_send_customer_team_invite_fieldset">
				<div class="edd-slack-send-customer-team-invite">
					<input name="edd_slack_send_customer_team_invite" type="checkbox" id="edd_slack_send_customer_team_invite" value="1" <?php checked( $checked, 1, true ); ?>/>
					<label for="edd_slack_send_customer_team_invite">
						<?php echo apply_filters( 'edd_slack_app_join_slack_team_default_text', _x( 'Join our Slack Team?', 'Join Slack Team Text Default', 'edd-slack' ) ); ?>
					</label>
				</div>
			</fieldset>

		<?php endif;
		
	}
	
	/**
	 * Sends a Slack Team Invite to Customers if they've Opted-in
	 * 
	 * @param		integer $payment_id Payment ID
	 *                                     
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function add_customer_to_slack_team_via_form( $payment_id ) {
			
		$customer_id = get_post_meta( $payment_id, '_edd_payment_customer_id', true );
		$customer = new EDD_Customer( $customer_id );
		
		// If they've Opted-in to being added to the Slack Team
		if ( ! empty( $_POST['edd_slack_send_customer_team_invite'] ) ) {
			
			// If an invite has not been sent for this customer
			// This is an important distinction since we cannot know "who" a Customer is until they've filled out the Form
			// If they're logged in as a USER, then we can know. But Customers don't necessarily need to be logged in
			if ( ! $customer->get_meta( 'edd_slack_app_invite_sent', true ) &&
			   ! get_user_meta( $customer->user_id, 'edd_slack_app_invite_sent', true ) ) {
		
				$email = $_POST['edd_email'];
				$first_name = $_POST['edd_first'];
				$last_name = $_POST['edd_last'];

				$channels = implode( ',', edd_get_option( 'slack_app_team_invites_customer_channels', array() ) );
				
				$this->send_invite( $email, $channels, $first_name, $last_name );
			
				// Record that we've sent them an invite
				$customer->add_meta( 'edd_slack_app_invite_sent', true, true );
				
			}
			
		}
		
	}
	
	/**
	 * Adds our own Checkbox to the EDD FES Registration Form to send out Slack Team Invites to new Vendors
	 * 
	 * @param		array  $fields Array of EDD FES Field Objects
	 * @param		object $form   EDD FES Registration Form Object
	 *                                                  
	 * @access		public
	 * @since		1.1.0
	 * @return		array  Modified Field Object Array
	 */
	public function vendors_slack_invite_checkbox( $fields, $form ) {
		
		$show_checkbox = false;
		
		// Determine whether we should show the Checkbox
		// Vendors always have a User Account, but if they don't have an account yet, we should still show the Checkbox
		// For logged in Users, we will know before hand if they have already been added as a Customer or something
		if ( ! is_user_logged_in() ) {
			$show_checkbox = true;
		}
		else {
			
			$customer = new EDD_Customer( get_current_user_id(), true );
			
			// We need to check Customer Meta in case they were a Customer in the past
			if ( ! get_user_meta( get_current_user_id(), 'edd_slack_app_invite_sent', true ) &&
			   ! $customer->get_meta( 'edd_slack_app_invite_sent', true ) ) {
				$show_checkbox = true;
			}
			
		}
		
		if ( $show_checkbox ) {
		
			$checkbox = new FES_Checkbox_Field( 'edd_slack_send_vendor_team_invite', 'registration' );

			// Grab all default Characteristics so we can set them
			$characteristics = $checkbox->get_characteristics();

			// Name Attribute
			$characteristics['name'] = 'edd_slack_send_vendor_team_invite';

			$label = edd_get_option( 'slack_app_join_team_team_text', apply_filters( 'edd_slack_app_join_slack_team_default_text', _x( 'Join our Slack Team?', 'Join Slack Team Text Default', 'edd-slack' ) ) );

			// Checkbox Options
			$characteristics['options'] = array(
				$label,
			);

			// Give this an empty Array if you want it to default to being unselected
			$characteristics['selected'] = apply_filters( 'slack_app_team_invites_vendor_default', array( 
				$label,
			) );

			$checkbox->set_characteristics( $characteristics );

			$fields['edd_slack_send_vendor_team_invite'] = $checkbox;
			
		}
		
		return $fields;
		
	}
	
	/**
	 * Sends a Slack Team Invite to Vendors if they've Opted-in
	 * 
	 * @param	  integer $vendor_id $wpdb Insert ID, in this case, the Vendor ID
	 * @param	  array   $args		 $wpdb Column Key/Value Pairs
	 *														
	 * @access	  public
	 * @since	  1.1.0
	 * @return	  void
	 */
	public function add_vendor_to_slack_team_via_form( $vendor_id, $args ) {
		
		// If they've Opted-in to being added to the Slack Team
		if ( ! empty( $_POST['edd_slack_send_vendor_team_invite'] ) ) {
			
			$vendor = new FES_Vendor( $vendor_id );
			$customer = new EDD_Customer( $vendor->user_id, true );
			
			// If this User has not been invited to the Slack Team yet
			// We need to check Customer Meta in case they were a Customer in the past
			if ( ! get_user_meta( $vendor_id, 'edd_slack_app_invite_sent', true ) &&
			   ! $customer->get_meta( 'edd_slack_app_invite_sent', true ) ) {
		
				$email = $args['email'];
				$first_name = $_POST['first_name'];
				$last_name = $_POST['last_name'];

				$channels = implode( ',', edd_get_option( 'slack_app_team_invites_vendor_channels', array() ) );

				$this->send_invite( $email, $channels, $first_name, $last_name );
				
			}
			
		}
		
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
						<?php wp_nonce_field( 'edd_slack_team_invite_nonce', 'edd_slack_team_invite_nonce' ); ?>

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
	
	/**
	 * Adds an "Add to Slack" button to the Vendor Page
	 * 
	 * @param		object $vendor FES_Vendor Object
	 *                                   
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function add_slack_team_vendor_invite_button( $vendor ) {
		
		?>
		<div id="edd_slack_vendor_wrapper" class="vendor-section" style="padding-bottom: 10px;">
			
			<?php if ( ! get_user_meta( $vendor->user_id, 'edd_slack_app_invite_sent', true ) ) : ?>
			
				<form method="post" id="edd_slack_team_vendor_invite_form" class="edd-slack-team-vendor-invite-form">
					<span>
						<?php wp_nonce_field( 'edd_slack_team_invite_nonce', 'edd_slack_team_invite_nonce' ); ?>

						<input type="submit" id="edd_slack_app_invite" value="<?php echo sprintf( _x( 'Invite this %s to your Slack Team', 'Slack Team Invite Button Text', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( false, true ) ); ?>" class="button-secondary"/>
						<span class="spinner"></span>

					</span>
				</form>
			
			<?php else : ?>
			
				<p class="description">
					<?php echo sprintf( _x( 'This %s has already been sent an invite to your Slack Team', 'Slack Team invite already sent to Vendor', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( false, true ) ); ?>
				</p>
			
			<?php endif; ?>

		</div>

		<?php
		
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
	 * Localize the Admin.js with some values from PHP-land
	 * 
	 * @param	  array $localization Array holding all our Localizations
	 *														
	 * @access	  public
	 * @since	  1.0.0
	 * @return	  array Modified Array
	 */
	public function localize_script( $localization ) {
		
		$localization['i18n']['slackInvite'] = array(
			'channel_not_found' => _x( 'The selected Public Channels were not found. Do they still exist?', 'Channel not found Slack Invite error', 'edd-slack' ),
			'no_perms' => _x( 'The Slack User authenticated the Slack App did not have permissions to Invite Users to the Team. This is often reserved for Slack Team Admins.', 'Lacks Permission to Invite Users error', 'edd-slack' ),
			'already_in_team' => _x( 'This email already belongs to a Slack User in your Team.', 'Email already in use by Slack User error', 'edd-slack' ),
			'customer_invite_successful' => _x( 'Customer invited to your Slack Team successfully.', 'Customer Team Invite Successful', 'edd-slack' ),
		);
		
		if ( class_exists( 'EDD_Front_End_Submissions' ) ) {
			
			$localization['i18n']['slackInvite']['vendor_invite_successful'] = sprintf( _x( '%s invited to your Slack Team successfully.', 'Vendor Team Invite Successful', 'edd-slack' ), EDD_FES()->helper->get_vendor_constant_name( false, true ) );
			
		}
		
		$localization['ajax'] = admin_url( 'admin-ajax.php' );
		
		return $localization;
		
	}
	
	/**
	 * Sends a Slack Team Invite via Email
	 * 
	 * @param		string $email      Email Address (Required)
	 * @param		string $channels   Comma-separated Channel IDs/Names to auto-sub the User to. These must be PUBLIC Channels
	 * @param		string $first_name First Name (Pre-populates the Sign-up Form in Slack)
	 * @param		string $last_name  Last Name (Pre-populates the Sign-up Form in Slack)
	 *                                                                         
	 * @access		private
	 * @since		1.1.0
	 * @return		void
	 */
	private function send_invite( $email, $channels = '', $first_name = '', $last_name = '' ) {
			
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
	
	/**
	 * Generic AJAX Callback for sending Slack Team Invites
	 * 
	 * @param		integer $id   Either an EDD_Customer ID or a FES_Vendor ID
	 * @param		string  $type 'customer' or 'vendor'. Determines the Channel List used.
	 *                                                              
	 * @access		public
	 * @since		1.1.0
	 * @return		string JSON
	 */
	public static function ajax_send_invite() {
		
		check_ajax_referer( 'edd_slack_team_invite_nonce', 'edd_slack_team_invite_nonce' );
		
		$args = array(
			'email' => '',
			'channels' => array(),
			'first_name' => '',
			'last_name' => '',
		);
		
		$id = $_POST['id'];
		$type = $_POST['type'];
		
		// Determine which Channels list to use and where to grab the necessary data from
		if ( strtolower( trim( $type ) ) == 'customer' ) {
			
			$args['channels'] = implode( ',', edd_get_option( 'slack_app_team_invites_customer_channels', array() ) );
			
			// We need a Payment from the Customer in order to get details like First/Last Name.
			// This is because it is stored as a single string in the EDD_Customer Object unlike how it is handled in EDD_Payment Meta or even the WP_User object
			// We cannot reliably split on a Space since we don't know how many spaces may be in a User's actual name
			$customer = new EDD_Customer( $id );
			
			// Grab the most recent Payment, as this should hold the most recent data
			$payment_ids = explode( ',', $customer->payment_ids );
			$payment_id = $payment_ids[ count( $payment_ids ) - 1 ];
			
			$payment_meta = edd_get_payment_meta( $payment_id );
			
			$args['first_name'] = $payment_meta['user_info']['first_name'];
			$args['last_name'] = $payment_meta['user_info']['last_name'];
			$args['email'] = $payment_meta['user_info']['email'];
			
		}
		else {
			
			$args['channels'] = implode( ',', edd_get_option( 'slack_app_team_invites_vendor_channels', array() ) );
			
			// Vendors are a lot easier since we know their User ID
			$vendor = new FES_Vendor( $id );
			
			$userdata = get_userdata( $vendor->user_id );
			
			$args['first_name'] = $userdata->first_name;
			$args['last_name'] = $userdata->last_name;
			$args['email'] = $vendor->email;
			
		}
		
		$invite = EDDSLACK()->slack_api->post( 
			'users.admin.invite',
			array(
				'body' => $args
			)
		);
		
		// If it was successful, add the necessary Customer/User Meta to indicate it
		if ( property_exists( $invite, 'ok' ) &&
			$invite->ok ) {
			
			if ( strtolower( trim( $type ) ) == 'customer' ) {
				
				$customer->add_meta( 'edd_slack_app_invite_sent', true, true );
				
			}
			else {
				
				update_user_meta( $vendor->user_id, 'edd_slack_app_invite_sent', true );
				
			}
			
		}
		
		wp_send_json( $invite );
		
	}
	
}

$integrate = new EDD_Slack_Invites();

// Add AJAX Callback hook
add_action( 'wp_ajax_edd_slack_app_team_invite', array( 'EDD_Slack_Invites', 'ajax_send_invite' ) );
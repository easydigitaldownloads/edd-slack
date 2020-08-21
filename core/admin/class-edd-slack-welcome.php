<?php
/**
 * The Welcome Page for EDD Slack
 *
 * @since 1.0.0
 *
 * @package EDD_Slack
 * @subpackage EDD_Slack/core/admin
 */

defined( 'ABSPATH' ) || die();

class EDD_Slack_Welcome {

	public $minimum_capability = 'manage_options';

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menus' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_filter( 'admin_title', array( $this, 'admin_title' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'welcome' ), 11 );

	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages are used to render the Welcome and Credits pages.
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function admin_menus() {

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with EDD Slack', 'edd-slack' ),
			__( 'Getting started with EDD Slack', 'edd-slack' ),
			$this->minimum_capability,
			'edd-slack-getting-started',
			array( $this, 'getting_started_screen' )
		);

		// Changelog Page
		add_dashboard_page(
			__( 'EDD Slack Changelog', 'edd-slack' ),
			__( 'EDD Slack Changelog', 'edd-slack' ),
			$this->minimum_capability,
			'edd-slack-changelog',
			array( $this, 'changelog_screen' )
		);

		// Now remove them from the menus so plugins that allow customizing the admin menu don't show them
		remove_submenu_page( 'index.php', 'edd-slack-getting-started' );
		remove_submenu_page( 'index.php', 'edd-slack-changelog' );

	}

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function admin_head() {
		?>
		<style type="text/css" media="screen">
			/*<![CDATA[*/
			.edd-slack-about-wrap .edd-slack-badge { float: right; border-radius: 4px; margin: 0 0 15px 15px; max-width: 300px; }
			.edd-slack-about-wrap #edd-slack-header { margin-bottom: 15px; }
			.edd-slack-about-wrap #edd-slack-header h1 { margin: 0; margin-bottom: 15px !important; }
			.edd-slack-about-wrap .about-text { margin: 0 0 15px; max-width: 670px; }
			.edd-slack-about-wrap .feature-section { margin-top: 20px; }
			.edd-slack-about-wrap .feature-section-content,
			.edd-slack-about-wrap .feature-section-media { width: 50%; box-sizing: border-box; }
			.edd-slack-about-wrap .feature-section-content { float: left; padding-right: 50px; }
			.edd-slack-about-wrap .feature-section-content h4 { margin: 0 0 1em; }
			.edd-slack-about-wrap .feature-section-media { float: right; text-align: right; margin-bottom: 20px; }
			.edd-slack-about-wrap .feature-section-media img { border: 1px solid #ddd; }
			.edd-slack-about-wrap .feature-section:not(.under-the-hood) .col { margin-top: 0; }
			.edd-slack-about-wrap ul { list-style: disc; margin-left: 2em; }
			}
			/* responsive */
			@media all and ( max-width: 782px ) {
				.edd-slack-about-wrap .feature-section-content,
				.edd-slack-about-wrap .feature-section-media { float: none; padding-right: 0; width: 100%; text-align: left; }
				.edd-slack-about-wrap .feature-section-media img { float: none; margin: 0 0 20px; }
			}
			/*]]>*/
		</style>
		<?php
	}

	/**
	 * Fix the Admin Title since our pages "don't exist"
	 *
	 * @param		string $admin_title The page title, with extra context added
	 * @param		string $title       The original page title
	 *
	 * @access		public
	 * @since		1.1.0
	 * @return		string Admin Title
	 */
	public function admin_title( $admin_title, $title ) {

		global $current_screen;

		if ( $current_screen->base == 'dashboard_page_edd-slack-changelog' ) {
			return __( 'EDD Slack Changelog', 'edd-slack' ) . $admin_title;
		}
		else if ( $current_screen->base == 'dashboard_page_edd-slack-getting-started' ) {
			return __( 'Getting started with EDD Slack', 'edd-slack' ) . $admin_title;
		}

		return $admin_title;

	}

	/**
	 * Welcome message
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function welcome_message() {

		$display_version = EDD_Slack_VER;

		?>
		<div id="edd-slack-header">
			<img class="edd-slack-badge" src="<?php echo EDD_Slack_URL . 'assets/images/edd-slack-logo.png'; ?>" alt="<?php _e( 'EDD Slack', 'edd-slack' ); ?>" / >
			<h1><?php printf( __( 'Welcome to EDD Slack v%s', 'edd-slack' ), $display_version ); ?></h1>
			<p class="about-text">
				<?php printf( __( 'Thank you for updating to the latest version! EDD Slack v%s is ready to make your online store integrate seamlessly with Slack!', 'edd-slack' ), $display_version ); ?>
			</p>
		</div>
		<?php

	}

	/**
	 * Navigation tabs
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function tabs() {

		$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'edd-slack-about';

		?>
		<h1 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo $selected == 'edd-slack-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'edd-slack-getting-started' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Getting Started', 'edd-slack' ); ?>
			</a>
			<a class="nav-tab <?php echo $selected == 'edd-slack-changelog' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'edd-slack-changelog' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Changelog', 'edd-slack' ); ?>
			</a>
			<a class="nav-tab" href="//docs.easydigitaldownloads.com/category/1724-slack" target="_blank">
				<?php _e( 'Complete Documentation', 'edd-slack' ); ?>
			</a>
		</h1>
		<?php

	}

	/**
	 * Render Getting Started Screen
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function getting_started_screen() {
		?>
		<div class="wrap about-wrap edd-slack-about-wrap">
			<?php
				// load welcome message and content tabs
				$this->welcome_message();
				$this->tabs();
			?>
			<p class="about-description"><?php _e( 'Use the tips below to get started using EDD Slack. You will be up and running in no time!', 'edd-slack' ); ?></p>

			<div class="create-notifications">
				<h3><?php _e( 'Configuring your Notifications', 'edd-slack' );?></h3>
				<div class="feature-section">
					<div class="feature-section-media">
						<img src="<?php echo EDD_Slack_URL . 'assets/images/screenshots/settings-cropped.png'; ?>" class="edd-welcome-screenshots"/>
					</div>
					<div class="feature-section-content">

						<h4><a href="//my.slack.com/services/new/incoming-webhook/" target="_blank"><?php _e( 'Create an Incoming Webhook URL', 'edd-slack' ); ?></a></h4>
						<p><?php _e( 'Create an Incoming Webhook URL using the above link, then enter it in the Default Webhook URL Field on the EDD Slack Settings Page.', 'edd-slack' ); ?></p>

						<h4><a href="<?php echo admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions&section=edd-slack-settings' ) ?>"><?php printf( __( '%s &rarr; Settings &rarr; Extensions &rarr; Slack', 'edd-slack' ), edd_get_label_plural() ); ?></a></h4>
						<p><?php _e( 'Create Notifications by clicking the "Add Slack Notification" Button and filling out the form. Congratulations! You\'ve created a Notification within EDD Slack!', 'edd-slack' );?></p>

					</div>
				</div>
			</div>

			<div class="get-notifications">
				<h3><?php _e( 'Customize Unlimited Notifications', 'edd-slack' );?></h3>
				<div class="feature-section">
					<div class="feature-section-media">
						<img src="<?php echo EDD_Slack_URL . 'assets/images/screenshots/notification.png'; ?>" class="edd-welcome-screenshots"/>
					</div>
					<div class="feature-section-content">

						<p><?php _e( 'EDD Slack comes bundled with Triggers for standard Easy Digital Downloads events as well as some for other Easy Digital Downloads Extensions so that you can know what is happening on your storefront as soon as it happens.', 'edd-slack' ); ?></p>

						<p><?php _e( 'You can customize the Notification however you like to include all kinds of information about the event, with different Triggers providing you with different Text Replacements to use!', 'edd-slack' ); ?></p>

						<p><?php _e( 'Different Notifications can even be set to be sent to different Channels or even different Users! Check out our <a href="//docs.easydigitaldownloads.com/article/1726-edd-slack-creating-notifications" target="_blank">Documentation</a> for more details!', 'edd-slack' ); ?></p>

					</div>
				</div>
			</div>

			<div class="unlock-functionality">
				<h3><?php _e( 'Unlocking Extra Functionality', 'edd-slack' );?></h3>
				<div class="feature-section">
					<div class="feature-section-media">
						<img src="<?php echo EDD_Slack_URL . 'assets/images/screenshots/interactive-notification.png'; ?>" class="edd-welcome-screenshots" />
					</div>
					<div class="feature-section-content">

						<h4><?php _e( 'SSL-enabled Sites Only', 'edd-slack' ); ?></h4>

						<p><?php _e( 'If your site is SSL-enabled, you can add Slack App integration to enable Interactive Notifications, Slash Commands, and even invite Customers to your Slack Team.', 'edd-slack' ); ?></p>

						<p><?php _e( 'This will allow you to interact with events happening on your online store immediately and view data from your online store all from within Slack.', 'edd-slack' ); ?></p>

						<p><?php _e( 'Check out our <a href="//docs.easydigitaldownloads.com/article/1727-edd-slack-setting-up-a-slack-app" target="_blank">Documentation</a> for more details!', 'edd-slack' ); ?></p>

					</div>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Render Changelog Screen
	 *
	 * @access		public
	 * @since		1.1.0
	 * @return		void
	 */
	public function changelog_screen() {
		?>
		<div class="wrap about-wrap edd-slack-about-wrap">
			<?php
				// load welcome message and content tabs
				$this->welcome_message();
				$this->tabs();
			?>
			<div class="changelog">
				<h3><?php _e( 'Full Changelog', 'edd-slack' );?></h3>

				<div class="feature-section">
					<?php echo $this->parse_readme(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Parse the WordPress readme.txt to get a Changelog for Output
	 *
	 * @access		public
	 * @since		1.1.0
	 * @return		string WordPress readme.txt-style Markdown to HTML
	 */
	public function parse_readme() {

		$file = file_exists( EDD_Slack_DIR . 'readme.txt' ) ? EDD_Slack_DIR . 'readme.txt' : null;

		if ( ! $file ) {
			$readme = '<p>' . __( 'No valid changelog was found.', 'edd-slack' ) . '</p>';
		}
		else {

			$readme = file_get_contents( $file );
			$readme = explode( '== Changelog ==', $readme );
			$readme = end( $readme );

			if ( ! class_exists( 'Michelf\Markdown' ) ) {

				require_once EDD_Slack_DIR . '/includes/php-markdown/Michelf/Markdown.inc.php';

			}

			$readme = preg_replace( '/= (.*?) =/', '### \\1', $readme );

			$readme = Michelf\Markdown::defaultTransform( $readme );

		}

		return $readme;

	}

	/**
	 * Sends user to the Welcome page on first activation of EDD Slack as well as each time EDD Slack is upgraded to a new version
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function welcome() {

		// Bail if no activation redirect
		if ( ! get_transient( '_edd_slack_activation_redirect' ) )
			return;

		// Delete the redirect transient
		delete_transient( '_edd_slack_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) return;

		wp_safe_redirect( admin_url( 'index.php?page=edd-slack-getting-started' ) );

		exit;

	}

}

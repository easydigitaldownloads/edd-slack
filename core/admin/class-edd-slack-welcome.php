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

class EDD_Slack_Welcome extends EDD_Welcome {

    /**
	 * Register the Dashboard Pages which are later hidden but these pages are used to render the Welcome and Credits pages.
	 *
	 * @access     public
	 * @since      1.0.0
	 * @return     void
	 */
    public function admin_menus() {

        // Getting Started Page
        add_dashboard_page(
            __( 'Getting started with EDD Slack', EDD_Slack_ID ),
            __( 'Getting started with EDD Slack', EDD_Slack_ID ),
            $this->minimum_capability,
            'edd-slack-getting-started',
            array( $this, 'getting_started_screen' )
        );

        // Now remove them from the menus so plugins that allow customizing the admin menu don't show them
        remove_submenu_page( 'index.php', 'edd-slack-getting-started' );
        
    }
    
    /**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function admin_head() {
		?>
		<style type="text/css" media="screen">
			/*<![CDATA[*/
			.edd-slack-about-wrap .edd-slack-badge { float: right; border-radius: 4px; margin: 0 0 15px 15px; max-width: 100px; }
			.edd-slack-about-wrap #edd-slack-header { margin-bottom: 15px; }
			.edd-slack-about-wrap #edd-slack-header h1 { margin-bottom: 15px !important; }
			.edd-slack-about-wrap .about-text { margin: 0 0 15px; max-width: 670px; }
			.edd-slack-about-wrap .feature-section { margin-top: 20px; }
			.edd-slack-about-wrap .feature-section-content,
			.edd-slack-about-wrap .feature-section-media { width: 50%; box-sizing: border-box; }
			.edd-slack-about-wrap .feature-section-content { float: left; padding-right: 50px; }
			.edd-slack-about-wrap .feature-section-content h4 { margin: 0 0 1em; }
			.edd-slack-about-wrap .feature-section-media { float: right; text-align: right; margin-bottom: 20px; }
			.edd-slack-about-wrap .feature-section-media img { border: 1px solid #ddd; }
			.edd-slack-about-wrap .feature-section:not(.under-the-hood) .col { margin-top: 0; }
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
	 * Welcome message
	 *
	 * @access     public
	 * @since      1.0.0
	 * @return     void
	 */
    public function welcome_message() {
        
        $display_version = EDD_Slack_VER;
        
        ?>
        <div id="edd-slack-header">
            <img class="edd-slack-badge" src="<?php echo EDD_Slack_URL . 'assets/images/edd-slack-logo.svg'; ?>" alt="<?php _e( 'EDD Slack', EDD_Slack_ID ); ?>" / >
            <h1><?php printf( __( 'Welcome to EDD Slack %s', EDD_Slack_ID ), $display_version ); ?></h1>
            <p class="about-text">
                <?php printf( __( 'Thank you for updating to the latest version! EDD Slack %s is ready to make your online store faster, safer, and better!', EDD_Slack_ID ), $display_version ); ?>
            </p>
        </div>
        <?php
        
    }

    /**
	 * Navigation tabs
	 *
	 * @access     public
	 * @since      1.0.0
	 * @return     void
	 */
    public function tabs() {
        
        $selected = isset( $_GET['page'] ) ? $_GET['page'] : 'edd-slack-about';
        
        ?>
        <h1 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo $selected == 'edd-slack-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'edd-slack-getting-started' ), 'index.php' ) ) ); ?>">
                <?php _e( 'Getting Started', EDD_Slack_ID ); ?>
            </a>
        </h1>
        <?php
        
    }

    /**
	 * Render Getting Started Screen
	 *
	 * @access public
	 * @since 1.9
	 * @return void
	 */
	public function getting_started_screen() {
		?>
		<div class="wrap about-wrap edd-slack-about-wrap">
			<?php
				// load welcome message and content tabs
				$this->welcome_message();
				$this->tabs();
			?>
			<p class="about-description"><?php _e( 'Use the tips below to get started using EDD Slack. You will be up and running in no time!', EDD_Slack_ID ); ?></p>

			<div class="changelog">
				<h3><?php _e( 'Configuring your Notifications', EDD_Slack_ID );?></h3>
				<div class="feature-section">
					<div class="feature-section-media">
						<img src="<?php echo EDD_Slack_URL . 'assets/images/screenshots/edit-download.png'; ?>" class="edd-welcome-screenshots"/>
					</div>
					<div class="feature-section-content">
						<h4><a href="<?php echo admin_url( 'post-new.php?post_type=download' ) ?>"><?php printf( __( '%s &rarr; Add New', EDD_Slack_ID ), edd_get_label_plural() ); ?></a></h4>
						<p><?php printf( __( 'The %s menu is your access point for all aspects of your Easy Digital Downloads product creation and setup. To create your first product, simply click Add New and then fill out the product details.', EDD_Slack_ID ), edd_get_label_plural() ); ?></p>


						<h4><?php _e( 'Download Files', EDD_Slack_ID );?></h4>
						<p><?php _e( 'Uploading the downloadable files is simple. Click <em>Upload File</em> in the Download Files section and choose your download file. To add more than one file, simply click the <em>Add New</em> button.', EDD_Slack_ID );?></p>
					</div>
				</div>
			</div>
            
		</div>
		<?php
	}

    /**
	 * Sends user to the Welcome page on first activation of EDD Slack as well as each time EDD Slack is upgraded to a new version
	 *
	 * @access     public
	 * @since      1.0.0
	 * @return     void
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
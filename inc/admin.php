<?php
/**
 * Admin Class for ProFootball Player Profile
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ProFootball_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		
		add_action( 'admin_head', array( $this, 'inline_admin_css' ) );
		add_action( 'admin_footer', array( $this, 'inline_admin_js' ) );
	}

	public function register_admin_menu() {
		add_menu_page(
			'ProFootball Player Profile',
			'Player Profile',
			'manage_options',
			'profootball-player-profile',
			array( $this, 'render_settings_page' ),
			'dashicons-businessman',
			30
		);
	}

	public function register_settings() {
		register_setting( 'profootball_player_settings_group', 'profootball_player_sections' );
		register_setting( 'profootball_player_settings_group', 'profootball_allowed_memberships' );
	}

	public function inline_admin_css() {
		$screen = get_current_screen();
		if ( $screen && 'toplevel_page_profootball-player-profile' === $screen->id ) {
			$css_path = PROFOOTBALL_PLAYER_PROFILE_PATH . 'assets/css/admin.css';
			if ( file_exists( $css_path ) ) {
				echo '<style type="text/css">' . file_get_contents( $css_path ) . '</style>';
			}
		}
	}

	public function inline_admin_js() {
		$screen = get_current_screen();
		if ( $screen && 'toplevel_page_profootball-player-profile' === $screen->id ) {
			// Required dependencies (WordPress Core)
			wp_enqueue_script( 'jquery-ui-sortable' );

			$js_path = PROFOOTBALL_PLAYER_PROFILE_PATH . 'assets/js/admin.js';
			if ( file_exists( $js_path ) ) {
				echo '<script type="text/javascript">';
				
				// Global data
				$data = array( 'ajax_url' => admin_url( 'admin-ajax.php' ) );
				echo 'var profootball_admin = ' . json_encode( $data ) . ';';

				echo file_get_contents( $js_path );
				echo '</script>';
			}
		}
	}

	public function render_settings_page() {
		// Fetch existing levels from UMP if possible
		$levels = array();
		if ( class_exists( 'Indeed\Ihc\Db\Memberships' ) ) {
			$levels = \Indeed\Ihc\Db\Memberships::getAll();
		}

		// Fetch UMP Registration Fields for mapping
		$ump_fields = array();
		if ( function_exists( 'ihc_get_user_reg_fields' ) ) {
			$ump_fields = ihc_get_user_reg_fields();
		}

		$sections = get_option( 'profootball_player_sections', array() );
		$allowed_memberships = get_option( 'profootball_allowed_memberships', array() );

		include PROFOOTBALL_PLAYER_PROFILE_PATH . 'templates/admin-settings.php';
	}
}

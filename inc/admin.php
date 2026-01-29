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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
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

	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_profootball-player-profile' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'profootball-admin-style', PROFOOTBALL_PLAYER_PROFILE_URL . 'assets/css/admin.css', array(), '1.0.0' );
		wp_enqueue_script( 'profootball-admin-js', PROFOOTBALL_PLAYER_PROFILE_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), '1.0.0', true );
		
		// Pass localizations or data to JS
		wp_localize_script( 'profootball-admin-js', 'profootball_admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		) );
	}

	public function render_settings_page() {
		// Fetch existing levels from UMP if possible
		$levels = array();
		if ( class_exists( 'Indeed\Ihc\Db\Memberships' ) ) {
			$levels = \Indeed\Ihc\Db\Memberships::getAll();
		}

		$sections = get_option( 'profootball_player_sections', array() );
		$allowed_memberships = get_option( 'profootball_allowed_memberships', array() );

		include PROFOOTBALL_PLAYER_PROFILE_PATH . 'templates/admin-settings.php';
	}
}

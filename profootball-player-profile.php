<?php
/**
 * Plugin Name: ProFootball Player Profile Integration
 * Description: Integrates Indeed Membership Pro with SportsPress for custom player profiles.
 * Version: 1.0.0
 * Author: Gabriel Sandu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PROFOOTBALL_PLAYER_PROFILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PROFOOTBALL_PLAYER_PROFILE_URL', plugin_dir_url( __FILE__ ) );

// Include Admin Logic
require_once PROFOOTBALL_PLAYER_PROFILE_PATH . 'inc/admin.php';

/**
 * Main Integration Class
 */
class ProFootball_Player_Profile {

	public function __construct() {
		// Initialize Admin
		new ProFootball_Admin();

		// Enqueue Inline Styles & Scripts
		add_action( 'wp_head', array( $this, 'inline_frontend_css' ) );
		add_action( 'wp_footer', array( $this, 'inline_frontend_js' ) );

		// Hook after UMP registration
		add_action( 'ihc_action_after_register_process', array( $this, 'link_member_to_player' ), 10, 1 );

		// Custom Shortcode for Player Profile
		add_shortcode( 'profootball_player_profile', array( $this, 'render_player_profile' ) );
		
		// Filter SportsPress Player Page
		add_filter( 'the_content', array( $this, 'override_player_content' ) );

		// Plugin Action Links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=profootball-player-profile' ) . '">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function inline_frontend_css() {
		if ( ! is_singular( 'sp_player' ) ) return;
		$css_path = PROFOOTBALL_PLAYER_PROFILE_PATH . 'assets/css/style.css';
		if ( file_exists( $css_path ) ) {
			echo '<style type="text/css">' . file_get_contents( $css_path ) . '</style>';
		}
	}

	public function inline_frontend_js() {
		if ( ! is_singular( 'sp_player' ) ) return;
		$js_path = PROFOOTBALL_PLAYER_PROFILE_PATH . 'assets/js/scripts.js';
		if ( file_exists( $js_path ) ) {
			echo '<script type="text/javascript">' . file_get_contents( $js_path ) . '</script>';
		}
	}

	/**
	 * Override the content of the sp_player post type if it's a single page
	 */
	public function override_player_content( $content ) {
		if ( is_singular( 'sp_player' ) && in_the_loop() && is_main_query() ) {
			return $this->render_player_profile( array( 'id' => get_the_ID() ) );
		}
		return $content;
	}

	/**
	 * Automatically create or link a SportsPress Player when a new member registers
	 */
	public function link_member_to_player( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) return;

		// Check if player already exists
		$existing = new WP_Query( array(
			'post_type'  => 'sp_player',
			'meta_key'   => '_sp_user_id',
			'meta_value' => $user_id,
			'posts_per_page' => 1
		) );

		if ( $existing->have_posts() ) return;

		// Create a new Player post
		$player_id = wp_insert_post( array(
			'post_title'   => $user->display_name,
			'post_type'    => 'sp_player',
			'post_status'  => 'publish', // Or 'draft' depending on workflow
			'post_author'  => $user_id,
		) );

		if ( ! is_wp_error( $player_id ) ) {
			update_post_meta( $player_id, '_sp_user_id', $user_id );
			
			// Optional: mapping standard SP nationality if UMP field exists
			$nationality = get_user_meta( $user_id, 'nationality', true );
			if ( $nationality ) {
				update_post_meta( $player_id, 'sp_nationality', $nationality );
			}
		}
	}

	/**
	 * Render the custom player profile
	 */
	public function render_player_profile( $atts ) {
		$player_id = isset( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( get_post_type( $player_id ) !== 'sp_player' ) {
			return 'Invalid Player ID';
		}

		ob_start();
		$this->get_template( 'profile-layout.php', array( 'player_id' => $player_id ) );
		return ob_get_clean();
	}

	/**
	 * Helper to load templates
	 */
	public function get_template( $template_name, $args = array() ) {
		extract( $args );
		$path = PROFOOTBALL_PLAYER_PROFILE_PATH . 'templates/' . $template_name;
		if ( file_exists( $path ) ) {
			include $path;
		}
	}
}

new ProFootball_Player_Profile();

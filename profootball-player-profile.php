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

		// UMP Account Page Custom Tab
		add_filter( 'option_ihc_ap_tabs', array( $this, 'add_tab_to_enabled_list' ) );
		add_filter( 'ihc_public_account_page_menu_standard_tabs', array( $this, 'add_ump_account_tab' ), 10, 1 );
		add_filter( 'ihc_filter_custom_menu_items', array( $this, 'add_ump_account_tab' ), 10, 1 );
		add_filter( 'ihc_account_page_custom_tab_content', array( $this, 'add_ump_account_tab_content' ), 10, 2 );
		
		// Handle Profile Save
		add_action( 'init', array( $this, 'handle_player_profile_save' ) );
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=profootball-player-profile' ) . '">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Force UMP to recognize our tab as "enabled"
	 */
	public function add_tab_to_enabled_list( $tabs ) {
		if ( empty( $tabs ) ) return 'player_details';
		$list = is_string( $tabs ) ? explode( ',', $tabs ) : (array)$tabs;
		if ( ! in_array( 'player_details', $list ) ) {
			$list[] = 'player_details';
		}
		return is_string( $tabs ) ? implode( ',', $list ) : $list;
	}

	/**
	 * Add "Player Details" tab to UMP My Account page
	 */
	public function add_ump_account_tab( $menu_items ) {
		// Ensure we have an array
		if ( ! is_array( $menu_items ) ) {
			$menu_items = array();
		}

		$account_page_id = get_option( 'ihc_general_user_page' );
		$base_url = get_permalink( $account_page_id );

		$menu_items['player_details'] = array(
			'title' => 'Player Details',
			'label' => 'Player Details',
			'url'   => add_query_arg( 'ihc_ap_menu', 'player_details', $base_url ),
			'class' => 'ihc-ap-menu-item',
			'icon'  => 'f2bd', // FontAwesome code for user-circle
		);

		return $menu_items;
	}

	/**
	 * Render content for "Player Details" tab
	 */
	public function add_ump_account_tab_content( $content, $tab ) {
		if ( $tab === 'player_details' ) {
			ob_start();
			$this->get_template( 'account-player-form.php', array( 'user_id' => get_current_user_id() ) );
			return ob_get_clean();
		}
		return $content;
	}

	/**
	 * Handle the saving of dynamic fields from the account page
	 */
	public function handle_player_profile_save() {
		if ( ! isset( $_POST['profootball_save_profile'] ) || ! wp_verify_nonce( $_POST['profootball_profile_nonce'], 'profootball_save_action' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) return;

		$sections = get_option( 'profootball_player_sections', array() );
		if ( empty( $sections ) ) return;

		foreach ( $sections as $section ) {
			if ( empty( $section['fields'] ) ) continue;

			foreach ( $section['fields'] as $field ) {
				$mapping = ! empty( $field['mapping'] ) ? $field['mapping'] : '';
				if ( empty( $mapping ) ) continue;

				if ( $field['type'] === 'file' || $field['type'] === 'image' || $field['type'] === 'gallery' || $field['type'] === 'video' ) {
					if ( isset( $_POST[ $mapping ] ) ) {
						update_user_meta( $user_id, $mapping, sanitize_text_field( $_POST[ $mapping ] ) );
					}
				} else {
					if ( isset( $_POST[ $mapping ] ) ) {
						update_user_meta( $user_id, $mapping, wp_kses_post( $_POST[ $mapping ] ) );
					}
				}
			}
		}

		// Update linked SportsPress Player if exists (Nationality etc)
		$nationality = get_user_meta( $user_id, 'nationality', true );
		if ( $nationality ) {
			$player_id = $this->get_player_id_by_user( $user_id );
			if ( $player_id ) {
				update_post_meta( $player_id, 'sp_nationality', $nationality );
			}
		}

		wp_redirect( add_query_arg( 'profootball_save', 'success' ) );
		exit;
	}

	private function get_player_id_by_user( $user_id ) {
		$posts = get_posts( array(
			'post_type'  => 'sp_player',
			'meta_key'   => '_sp_user_id',
			'meta_value' => $user_id,
			'posts_per_page' => 1,
			'fields'     => 'ids'
		) );
		return ! empty( $posts ) ? $posts[0] : false;
	}

	public function inline_frontend_css() {
		$account_page_id = get_option( 'ihc_general_user_page' );
		if ( ! is_singular( 'sp_player' ) && ! is_page( $account_page_id ) ) return;

		$css_path = PROFOOTBALL_PLAYER_PROFILE_PATH . 'assets/css/style.css';
		echo '<style type="text/css">';
		if ( file_exists( $css_path ) ) {
			echo file_get_contents( $css_path );
		}
		// Force Icon for Player Details Tab
		echo '.fa-player_details-account-ihc:before { content: "\\f2bd" !important; font-family: "FontAwesome" !important; }';
		echo '</style>';
	}

	public function inline_frontend_js() {
		$account_page_id = get_option( 'ihc_general_user_page' );
		if ( ! is_singular( 'sp_player' ) && ! is_page( $account_page_id ) ) return;

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

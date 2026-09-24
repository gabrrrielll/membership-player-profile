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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Hook after UMP registration or subscription activation
		add_action( 'ihc_action_after_register_process', array( $this, 'link_member_to_player' ), 10, 1 );
		add_action( 'ihc_action_after_subscription_activated', array( $this, 'link_member_to_player' ), 10, 2 );

		// Custom Shortcode for Player Profile
		add_shortcode( 'profootball_player_profile', array( $this, 'render_player_profile' ) );
		
		// Always replace the SportsPress/theme player layout with this plugin's layout.
		add_filter( 'the_content', array( $this, 'override_player_content' ), 99 );
		add_filter( 'body_class', array( $this, 'add_player_body_class' ) );

		// Plugin Action Links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );

		// UMP Account Page Custom Tab
		add_filter( 'option_ihc_ap_tabs', array( $this, 'add_tab_to_enabled_list' ) );
		add_filter( 'ihc_public_account_page_menu_standard_tabs', array( $this, 'add_ump_account_tab' ), 10, 1 );
		add_filter( 'ihc_filter_custom_menu_items', array( $this, 'add_ump_account_tab' ), 10, 1 );
		add_filter( 'ihc_account_page_custom_tab_content', array( $this, 'add_ump_account_tab_content' ), 10, 2 );
		
		// Handle Profile Save from Frontend
		add_action( 'init', array( $this, 'handle_player_profile_save' ) );

		// Sync from Admin (SportsPress Edit Page) to User Meta
		add_action( 'save_post_sp_player', array( $this, 'sync_player_to_user_meta' ), 15, 3 );
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
			$user_id = get_current_user_id();
			ob_start();
			$this->get_template( 'account-player-form.php', array(
				'user_id'               => $user_id,
				'preview_url'           => $this->get_player_profile_url( $user_id ),
				'player_needs_publish'  => $this->player_needs_publish( $user_id ),
			) );
			return ob_get_clean();
		}
		return $content;
	}

	/**
	 * Helper to get consistent mapping key
	 */
	public function get_field_mapping( $field, $section_idx = 0, $field_idx = 0 ) {
		$mapping = ! empty( $field['mapping'] ) ? $field['mapping'] : '';
		if ( empty( $mapping ) ) {
			$label = ! empty( $field['label'] ) ? $field['label'] : '';
			if ( $label ) {
				$mapping = 'unmapped_field_' . sanitize_title( $label );
			} else {
				$mapping = 'unmapped_field_s' . $section_idx . '_f' . $field_idx;
			}
		}
		return $mapping;
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

		$player_id = $this->get_player_id_by_user( $user_id );
		if ( ! $player_id ) {
			$player_id = $this->create_player_for_user( $user_id );
		}
		$sections = get_option( 'profootball_player_sections', array() );
		if ( empty( $sections ) ) return;

		// Ensure we have the necessary functions for file uploads
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		foreach ( $sections as $s_idx => $section ) {
			if ( empty( $section['fields'] ) ) continue;

			foreach ( $section['fields'] as $f_idx => $field ) {
				$mapping = $this->get_field_mapping( $field, $s_idx, $f_idx );

				// Handle File/Image uploads separately
				if ( ( $field['type'] === 'file' || $field['type'] === 'image' ) ) {
					if ( ! empty( $_FILES[ $mapping ]['name'] ) ) {
						// Upload the file
						$attachment_id = media_handle_upload( $mapping, 0 );
						if ( ! is_wp_error( $attachment_id ) ) {
							update_user_meta( $user_id, $mapping, $attachment_id );
							$value = $attachment_id;
						} else {
							$value = get_user_meta( $user_id, $mapping, true );
						}
					} else {
						// Preserve existing value if no new file uploaded
						$value = get_user_meta( $user_id, $mapping, true );
					}
				} elseif ( $field['type'] === 'gallery' ) {
					// Keep existing images not removed by the user
					$keep_ids = isset( $_POST[ $mapping . '_keep' ] ) ? array_map( 'intval', (array) $_POST[ $mapping . '_keep' ] ) : array();

					// Upload newly added images
					$new_ids    = array();
					$upload_key = $mapping . '_new';
					if ( ! empty( $_FILES[ $upload_key ]['name'][0] ) ) {
						$files      = $_FILES[ $upload_key ];
						$file_count = count( $files['name'] );
						for ( $fi = 0; $fi < $file_count; $fi++ ) {
							if ( empty( $files['name'][ $fi ] ) ) continue;
							$_FILES['profootball_gallery_tmp'] = array(
								'name'     => $files['name'][ $fi ],
								'type'     => $files['type'][ $fi ],
								'tmp_name' => $files['tmp_name'][ $fi ],
								'error'    => $files['error'][ $fi ],
								'size'     => $files['size'][ $fi ],
							);
							$attachment_id = media_handle_upload( 'profootball_gallery_tmp', 0 );
							if ( ! is_wp_error( $attachment_id ) ) {
								$new_ids[] = $attachment_id;
							}
						}
					}

					$all_ids = array_values( array_unique( array_filter( array_merge( $keep_ids, $new_ids ) ) ) );
					$value   = wp_json_encode( $all_ids );
					update_user_meta( $user_id, $mapping, $value );
				} else {
					$value = isset( $_POST[ $mapping ] ) ? $_POST[ $mapping ] : '';
					
					// 1. Sync to User Meta (UMP)
					$value = wp_unslash($value);
					if ( $field['type'] === 'video' ) {
						update_user_meta( $user_id, $mapping, sanitize_text_field( $value ) );
					} else {
						$clean_value = is_array($value) ? array_map('sanitize_text_field', $value) : wp_kses_post( $value );
						update_user_meta( $user_id, $mapping, $clean_value );
					}
				}

				// 2. Sync to SportsPress Player Post if linked
				if ( $player_id ) {
					// Check if it's a taxonomy mapping (e.g. tax_sp_position)
					if ( strpos( $mapping, 'tax_' ) === 0 ) {
						$taxonomy = substr( $mapping, 4 );
						if ( taxonomy_exists( $taxonomy ) ) {
							$term_ids = is_array( $value ) ? array_map( 'intval', $value ) : array( intval( $value ) );
							$term_ids = array_filter( $term_ids ); // remove zeros
							wp_set_object_terms( $player_id, $term_ids, $taxonomy );
						}
					} 
					// Check if it's a specific SP meta field
					elseif ( in_array( $mapping, array( '_sp_number', 'sp_nationality', 'sp_metrics', 'sp_video', 'sp_hometown', 'sp_birthday' ) ) ) {
						update_post_meta( $player_id, $mapping, sanitize_text_field( $value ) );
					}
					// Special case for Featured Image
					elseif ( $mapping === '_thumbnail_id' ) {
						if ( is_numeric( $value ) ) {
							set_post_thumbnail( $player_id, intval( $value ) );
						}
					}
				}
			}
		}

		// Backward compatibility / convenience for Nationality
		$nationality = get_user_meta( $user_id, 'nationality', true );
		if ( $nationality && $player_id ) {
			update_post_meta( $player_id, 'sp_nationality', $nationality );
		}

		if ( $player_id ) {
			$this->ensure_player_is_public( $player_id, $user_id );
		}

		wp_redirect( add_query_arg( 'profootball_save', 'success' ) );
		exit;
	}

	public function get_player_id_by_user( $user_id ) {
		if ( ! $user_id ) {
			return false;
		}

		$statuses = array( 'publish', 'draft', 'pending', 'private', 'future' );
		$candidates = get_posts( array(
			'post_type'      => 'sp_player',
			'post_status'    => $statuses,
			'posts_per_page' => 10,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'   => '_sp_user_id',
					'value' => (string) $user_id,
				),
				array(
					'key'   => 'sp_user',
					'value' => (string) $user_id,
				),
			),
		) );

		// SportsPress user registration creates a draft and only sets post_author.
		if ( empty( $candidates ) && ! user_can( $user_id, 'edit_others_posts' ) ) {
			$candidates = get_posts( array(
				'post_type'      => 'sp_player',
				'post_status'    => $statuses,
				'author'         => $user_id,
				'posts_per_page' => 10,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			) );
		}

		if ( empty( $candidates ) ) {
			return false;
		}

		foreach ( $candidates as $candidate_id ) {
			if ( get_post_status( $candidate_id ) === 'publish' ) {
				return (int) $candidate_id;
			}
		}

		return (int) $candidates[0];
	}

	/**
	 * Public URL of the linked SportsPress player profile, if it is published.
	 */
	public function get_player_profile_url( $user_id ) {
		$player_id = $this->get_player_id_by_user( $user_id );
		if ( ! $player_id || get_post_status( $player_id ) !== 'publish' ) {
			return '';
		}
		$url = get_permalink( $player_id );
		return $url ? $url : '';
	}

	/**
	 * True when a player post exists but visitors cannot see it yet.
	 */
	public function player_needs_publish( $user_id ) {
		$player_id = $this->get_player_id_by_user( $user_id );
		if ( ! $player_id ) {
			return false;
		}
		return get_post_status( $player_id ) !== 'publish';
	}

	/**
	 * Link the player post to the member and publish drafts created at registration.
	 */
	public function ensure_player_is_public( $player_id, $user_id ) {
		if ( ! $player_id || ! $user_id ) {
			return false;
		}

		update_post_meta( $player_id, '_sp_user_id', $user_id );
		$linked_user = get_post_meta( $player_id, 'sp_user', true );
		if ( $linked_user === '' || $linked_user === '0' ) {
			update_post_meta( $player_id, 'sp_user', $user_id );
		}

		$status = get_post_status( $player_id );
		if ( ! in_array( $status, array( 'draft', 'pending', 'auto-draft', 'future' ), true ) ) {
			return (int) $player_id;
		}

		remove_action( 'save_post_sp_player', array( $this, 'sync_player_to_user_meta' ), 15 );
		wp_update_post( array(
			'ID'          => $player_id,
			'post_status' => 'publish',
		) );
		add_action( 'save_post_sp_player', array( $this, 'sync_player_to_user_meta' ), 15, 3 );

		if ( get_post_status( $player_id ) !== 'publish' ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->posts,
				array( 'post_status' => 'publish' ),
				array( 'ID' => $player_id ),
				array( '%s' ),
				array( '%d' )
			);
			clean_post_cache( $player_id );
		}

		return (int) $player_id;
	}

	/**
	 * Create a published SportsPress player linked to this member.
	 */
	public function create_player_for_user( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$title = $user->display_name ? $user->display_name : $user->user_login;
		$player_id = wp_insert_post( array(
			'post_title'  => $title,
			'post_type'   => 'sp_player',
			'post_status' => 'publish',
			'post_author' => $user_id,
		), true );

		if ( is_wp_error( $player_id ) || ! $player_id ) {
			return false;
		}

		update_post_meta( $player_id, '_sp_user_id', $user_id );
		update_post_meta( $player_id, 'sp_user', $user_id );

		$nationality = get_user_meta( $user_id, 'nationality', true );
		if ( $nationality ) {
			update_post_meta( $player_id, 'sp_nationality', $nationality );
		}

		return (int) $player_id;
	}

	/**
	 * Sync data from SportsPress Admin edit to User Meta
	 */
	public function sync_player_to_user_meta( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) ) return;
		
		$user_id = get_post_meta( $post_id, '_sp_user_id', true );
		if ( ! $user_id ) return;

		$sections = get_option( 'profootball_player_sections', array() );
		if ( empty( $sections ) ) return;

		// We need to avoid infinite loop since handle_player_profile_save also updates things
		remove_action( 'save_post_sp_player', array( $this, 'sync_player_to_user_meta' ), 15 );

		foreach ( $sections as $s_idx => $section ) {
			if ( empty( $section['fields'] ) ) continue;
			foreach ( $section['fields'] as $f_idx => $field ) {
				$mapping = $this->get_field_mapping( $field, $s_idx, $f_idx );
				if ( ! $mapping ) continue;

				// If it's a taxonomy
				if ( strpos( $mapping, 'tax_' ) === 0 ) {
					$taxonomy = substr( $mapping, 4 );
					$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
					update_user_meta( $user_id, $mapping, $terms );
				} 
				// If it's a specific SP meta
				elseif ( in_array( $mapping, array( '_sp_number', 'sp_nationality', 'sp_metrics', 'sp_video', 'sp_hometown', 'sp_birthday' ) ) ) {
					$val = get_post_meta( $post_id, $mapping, true );
					update_user_meta( $user_id, $mapping, $val );
				}
				// If it's the featured image
				elseif ( $mapping === '_thumbnail_id' ) {
					$val = get_post_thumbnail_id( $post_id );
					update_user_meta( $user_id, $mapping, $val );
				}
			}
		}

		// Re-add action
		add_action( 'save_post_sp_player', array( $this, 'sync_player_to_user_meta' ), 15, 3 );
	}

	public function add_player_body_class( $classes ) {
		if ( is_singular( 'sp_player' ) ) {
			$classes[] = 'profootball-custom-profile-active';
		}
		return $classes;
	}

	/**
	 * Account page, or any public player page (including legacy SportsPress players).
	 */
	private function should_load_frontend_assets() {
		if ( is_singular( 'sp_player' ) ) {
			return true;
		}
		$account_page_id = get_option( 'ihc_general_user_page' );
		return $account_page_id && is_page( $account_page_id );
	}

	public function inline_frontend_css() {
		if ( ! $this->should_load_frontend_assets() ) {
			return;
		}

		$css_path = PROFOOTBALL_PLAYER_PROFILE_PATH . 'assets/css/style.css';
		echo '<style type="text/css">';
		if ( file_exists( $css_path ) ) {
			echo file_get_contents( $css_path );
		}
		// Force Icon for Player Details Tab
		echo '.fa-player_details-account-ihc:before { content: "\\f2bd" !important; font-family: "FontAwesome" !important; }';
		
		// Custom CSS from admin
		$custom_css = get_option( 'profootball_custom_css', '' );
		if ( ! empty( $custom_css ) ) {
			echo wp_strip_all_tags( $custom_css );
		}
		
		echo '</style>';
	}

	public function inline_frontend_js() {
		if ( ! $this->should_load_frontend_assets() ) {
			return;
		}

		$js_path = PROFOOTBALL_PLAYER_PROFILE_PATH . 'assets/js/scripts.js';
		if ( file_exists( $js_path ) ) {
			echo '<script type="text/javascript">' . file_get_contents( $js_path ) . '</script>';
		}
	}

	/**
	 * Enqueue standard WordPress assets
	 */
	public function enqueue_frontend_assets() {
		if ( $this->should_load_frontend_assets() ) {
			wp_enqueue_style( 'dashicons' );
		}
	}

	/**
	 * Replace the single player page content for every player.
	 * Runs at priority 99, after SportsPress injects its own layout.
	 */
	public function override_player_content( $content ) {
		if ( is_singular( 'sp_player' ) && in_the_loop() && is_main_query() ) {
			return $this->render_player_profile( array( 'id' => get_the_ID() ) );
		}
		return $content;
	}

	/**
	 * Check if a user/player is allowed to use the premium integration based on membership
	 */
	public function is_player_sync_allowed( $user_id ) {
		if ( ! $user_id ) return false;
		if ( current_user_can( 'manage_options' ) ) return true;

		$sync_memberships = get_option( 'profootball_sync_memberships', array() );
		if ( empty( $sync_memberships ) ) return false;

		if ( function_exists( 'ihc_get_user_levels' ) ) {
			$user_levels = ihc_get_user_levels( $user_id, true );
			foreach ( (array)$sync_memberships as $lid ) {
				if ( in_array( $lid, $user_levels ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Automatically create or link a SportsPress Player when a member gets an allowed membership
	 */
	public function link_member_to_player( $user_id, $lid = null ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) return;

		// Get allowed memberships for sync
		$sync_memberships = get_option( 'profootball_sync_memberships', array() );
		if ( empty( $sync_memberships ) ) return;

		// If lid is not provided (registration hook), check all user levels
		if ( $lid === null ) {
			if ( function_exists( 'ihc_get_user_levels' ) ) {
				$user_levels = ihc_get_user_levels( $user_id, true );
				$has_allowed = false;
				foreach ( $sync_memberships as $allowed ) {
					if ( in_array( $allowed, $user_levels ) ) {
						$has_allowed = true;
						break;
					}
				}
				if ( ! $has_allowed ) return;
			} else {
				return; // Cannot check levels
			}
		} else {
			// Activated subscription hook - check if THIS lid is in our sync list
			if ( ! in_array( $lid, $sync_memberships ) ) return;
		}

		// Publish an existing draft instead of creating a second player.
		$player_id = $this->get_player_id_by_user( $user_id );
		if ( $player_id ) {
			$this->ensure_player_is_public( $player_id, $user_id );
			return;
		}

		$this->create_player_for_user( $user_id );
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

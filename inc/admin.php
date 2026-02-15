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

		// Metabox for Player Edit Screen
		add_action( 'add_meta_boxes', array( $this, 'add_player_dynamic_metabox' ) );
		add_action( 'save_post_sp_player', array( $this, 'save_player_dynamic_metabox' ), 20, 3 );
	}

	/**
	 * Register Metabox for SportsPress Player
	 */
	public function add_player_dynamic_metabox() {
		add_meta_box(
			'profootball_dynamic_fields',
			'ProFootball Dynamic Profile Fields',
			array( $this, 'render_player_dynamic_metabox' ),
			'sp_player',
			'normal',
			'high'
		);
	}

	/**
	 * Render Metabox Content
	 */
	public function render_player_dynamic_metabox( $post ) {
		$user_id = get_post_meta( $post->ID, '_sp_user_id', true );
		if ( ! $user_id ) {
			echo '<p>No user linked to this player yet. Profile fields cannot be edited until a user is linked.</p>';
			return;
		}

		$sections = get_option( 'profootball_player_sections', array() );
		if ( empty( $sections ) ) {
			echo '<p>No dynamic sections configured. <a href="'.admin_url('admin.php?page=profootball-player-profile').'">Configure sections here</a>.</p>';
			return;
		}

		wp_nonce_field( 'profootball_admin_save_nonce', 'profootball_admin_nonce' );

		echo '<div class="profootball-admin-metabox-wrap">';
		foreach ( $sections as $section ) {
			if ( empty( $section['fields'] ) ) continue;

			echo '<div class="admin-metabox-section">';
			echo '<h4>' . esc_html( $section['title'] ) . '</h4>';
			echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">';

			foreach ( $section['fields'] as $f_idx => $field ) {
				if ( $field['type'] === 'empty_space' || $field['type'] === 'shortcut_buttons' ) continue;

				$mapping = ! empty( $field['mapping'] ) ? $field['mapping'] : '';
				if ( empty( $mapping ) ) {
					$mapping_suffix = ! empty( $field['label'] ) ? sanitize_title( $field['label'] ) : $f_idx;
					$mapping = 'unmapped_field_' . $mapping_suffix;
				}

				// Skip standard SP fields as they are already available in SP metaboxes
				$standard_sp = array( '_sp_number', 'sp_nationality', 'sp_metrics', '_thumbnail_id', 'sp_video', 'sp_hometown', 'sp_birthday' );
				if ( in_array( $mapping, $standard_sp ) || strpos( $mapping, 'tax_' ) === 0 ) {
					// continue; // Optional: showing them anyway for convenience? Let's skip them to avoid confusion
				}

				$value = get_user_meta( $user_id, $mapping, true );
				$is_admin_only = ! empty( $field['is_admin_only'] ) && $field['is_admin_only'] === '1';

				echo '<div class="admin-metabox-field">';
				echo '<label style="display:block; font-weight:bold; margin-bottom:5px;">';
				echo esc_html( $field['label'] );
				if ( $is_admin_only ) echo ' <span style="color:#d63638; font-size:10px;">(Admin Only/Hidden from Player)</span>';
				echo '</label>';

				if ( $field['type'] === 'textarea' ) {
					echo '<textarea name="pf_meta[' . $mapping . ']" rows="3" style="width:100%">' . esc_textarea( $value ) . '</textarea>';
				} elseif ( $field['type'] === 'file' || $field['type'] === 'image' ) {
					// Basic input for now, but show current file
					echo '<input type="hidden" name="pf_meta[' . $mapping . ']" value="' . esc_attr( $value ) . '" id="pf_meta_' . $mapping . '">';
					echo '<div class="pf-admin-media-preview" style="margin-bottom:5px;">';
					if ( $value ) {
						$url = is_numeric($value) ? wp_get_attachment_url($value) : $value;
						if ( $field['type'] === 'image' ) {
							echo '<img src="'.esc_url($url).'" style="max-width:150px; display:block; margin-bottom:5px;">';
						}
						echo '<small>Current: ' . esc_html( basename( $url ) ) . '</small>';
					}
					echo '</div>';
					echo '<button type="button" class="button pf-media-upload" data-target="pf_meta_' . $mapping . '" data-type="' . $field['type'] . '">Upload/Select</button>';
					if ( $value ) {
						echo ' <button type="button" class="button pf-media-remove" data-target="pf_meta_' . $mapping . '">Remove</button>';
					}
				} else {
					echo '<input type="text" name="pf_meta[' . $mapping . ']" value="' . esc_attr( $value ) . '" style="width:100%">';
				}
				echo '</div>';
			}
			echo '</div>';
			echo '</div>';
		}
		echo '</div>';

		?>
		<style>
		.admin-metabox-section { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
		.admin-metabox-section h4 { margin: 0 0 10px; color: #1e293b; background: #f8fafc; padding: 5px 10px; border-left: 3px solid #2271b1; }
		.admin-metabox-field { margin-bottom: 10px; }
		</style>
		<script>
		jQuery(document).ready(function($){
			$('.pf-media-upload').on('click', function(e){
				e.preventDefault();
				var button = $(this);
				var targetId = button.data('target');
				var type = button.data('type');
				
				var frame = wp.media({
					title: 'Select ' + (type === 'image' ? 'Image' : 'File'),
					button: { text: 'Use this file' },
					multiple: false
				});

				frame.on('select', function(){
					var attachment = frame.state().get('selection').first().toJSON();
					$('#' + targetId).val(attachment.id);
					// Simple refresh visualizer without full post reload if needed, but for now just let save handle it
					alert('File selected! Please save the post to apply changes.');
				});
				frame.open();
			});
			$('.pf-media-remove').on('click', function(e){
				e.preventDefault();
				$('#' + $(this).data('target')).val('');
				alert('File removed! Please save the post.');
			});
		});
		</script>
		<?php
		// Ensure WP Media is enqueued
		wp_enqueue_media();
	}

	/**
	 * Save Metabox Data
	 */
	public function save_player_dynamic_metabox( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) ) return;
		if ( ! isset( $_POST['profootball_admin_nonce'] ) || ! wp_verify_nonce( $_POST['profootball_admin_nonce'], 'profootball_admin_save_nonce' ) ) {
			return;
		}

		$user_id = get_post_meta( $post_id, '_sp_user_id', true );
		if ( ! $user_id ) return;

		if ( isset( $_POST['pf_meta'] ) && is_array( $_POST['pf_meta'] ) ) {
			foreach ( $_POST['pf_meta'] as $key => $value ) {
				update_user_meta( $user_id, $key, wp_kses_post( $value ) );
			}
		}
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
		register_setting( 'profootball_player_settings_group', 'profootball_sync_memberships' );
		register_setting( 'profootball_player_settings_group', 'profootball_custom_css' );
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
		global $ump_fields, $sp_fields;
		
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

		// Fetch SportsPress Taxonomies & Meta for mapping
		$sp_fields = array(
			array( 'name' => '_sp_number', 'label' => 'SP: Squad Number' ),
			array( 'name' => 'sp_nationality', 'label' => 'SP: Nationality (ISO)' ),
			array( 'name' => 'sp_metrics', 'label' => 'SP: Metrics (Height/Weight)' ),
			array( 'name' => '_thumbnail_id', 'label' => 'SP: Profile Photo (Featured Image)' ),
			array( 'name' => 'sp_video', 'label' => 'SP: Video URL' ),
			array( 'name' => 'sp_hometown', 'label' => 'SP: Hometown' ),
			array( 'name' => 'sp_birthday', 'label' => 'SP: Birthday' ),
		);

		// Manually add common SP taxonomies in case they aren't detected for some reason
		$common_sp_tax = array(
			'sp_position' => 'Positions',
			'sp_league'   => 'Leagues',
			'sp_season'   => 'Seasons',
			'sp_team'     => 'Teams (Current/Past)',
		);

		foreach ( $common_sp_tax as $tax_slug => $tax_label ) {
			if ( taxonomy_exists( $tax_slug ) ) {
				$sp_fields[] = array( 'name' => 'tax_' . $tax_slug, 'label' => 'SP Taxonomy: ' . $tax_label );
			}
		}

		// Also try to dynamic detect others
		$taxonomies = get_object_taxonomies( 'sp_player', 'objects' );
		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $tax ) {
				$tax_key = 'tax_' . $tax->name;
				// Avoid duplicates
				$exists = false;
				foreach($sp_fields as $f) { if($f['name'] === $tax_key) { $exists = true; break; } }
				if (!$exists) {
					$sp_fields[] = array( 'name' => $tax_key, 'label' => 'SP Taxonomy: ' . $tax->label );
				}
			}
		}

		$sections = get_option( 'profootball_player_sections', array() );
		$allowed_memberships = get_option( 'profootball_allowed_memberships', array() );
		$sync_memberships = get_option( 'profootball_sync_memberships', array() );
		$custom_css = get_option( 'profootball_custom_css', '' );

		include PROFOOTBALL_PLAYER_PROFILE_PATH . 'templates/admin-settings.php';
	}
}

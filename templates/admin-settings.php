<?php
/**
 * Admin Settings Template
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap profootball-admin-wrap">
	<h1>ProFootball Player Profile Settings</h1>
	
	<form method="post" action="options.php">
		<?php settings_fields( 'profootball_player_settings_group' ); ?>
		
		<div class="profootball-main-card">
			<h2>Membership Permissions</h2>
			<p>Select which membership levels can view the premium player profile details (CV, Video, Custom Sections).</p>
			
			<div class="profootball-membership-grid">
				<?php if ( ! empty( $levels ) ) : ?>
					<?php foreach ( $levels as $level ) : ?>
						<label class="membership-item">
							<input type="checkbox" name="profootball_allowed_memberships[]" value="<?php echo esc_attr( $level['id'] ); ?>" <?php checked( in_array( $level['id'], (array)$allowed_memberships ) ); ?>>
							<span><?php echo esc_html( $level['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<div class="profootball-main-card">
			<h2>Automatic Player Profile Creation</h2>
			<p>Select which membership levels should automatically have a SportsPress <strong>sp_player</strong> post created or linked upon registration/purchase.</p>
			
			<div class="profootball-membership-grid">
				<?php if ( ! empty( $levels ) ) : ?>
					<?php foreach ( $levels as $level ) : ?>
						<label class="membership-item">
							<input type="checkbox" name="profootball_sync_memberships[]" value="<?php echo esc_attr( $level['id'] ); ?>" <?php checked( in_array( $level['id'], (array)$sync_memberships ) ); ?>>
							<span style="color: #d4af37; font-weight: bold;"><?php echo esc_html( $level['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				<?php else: ?>
					<p class="notice notice-warning">No Indeed Membership Pro levels found. Make sure the plugin is active.</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="profootball-main-card">
			<h2>Dynamic Sections Configuration</h2>
			<p>Define the sections that will appear on the player profile. You can map each field to an Ultimate Membership Pro (UMP) field slug.</p>
			
			<div id="profootball-sections-container" class="profootball-sections-list">
				<?php if ( ! empty( $sections ) ) : ?>
					<?php foreach ( $sections as $index => $section ) : ?>
						<?php render_profootball_section_row( $index, $section ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			
			<div class="profootball-admin-actions">
				<button type="button" id="add-new-section" class="button button-primary">Add New Section</button>
			</div>
		</div>

		<div class="profootball-main-card">
			<h2>Custom Styles (CSS)</h2>
			<p>Add your custom CSS here to override the default styles of the player profile.</p>
			<textarea name="profootball_custom_css" rows="10" style="width: 100%; font-family: monospace; background: #222; color: #0f0;"><?php echo esc_textarea( $custom_css ); ?></textarea>
		</div>

		<?php submit_button( 'Save All Settings' ); ?>
	</form>

	<div class="profootball-main-card" id="profootball-layout-preview-wrap">
		<h2>Live Layout Preview (Mock)</h2>
		<p>This is a simplified preview of how the elements are arranged in sections. Save settings to refresh the logic.</p>
		<div id="profootball-layout-visualizer" class="visualizer-container">
			<!-- Populated by JS -->
		</div>
	</div>
</div>

<!-- Template for JS -->
<script type="text/template" id="profootball-section-tpl">
	<?php render_profootball_section_row( '{{INDEX}}', array() ); ?>
</script>

<?php
/**
 * Helper to render a section row
 */
function render_profootball_section_row( $index, $data ) {
	$title = isset( $data['title'] ) ? $data['title'] : '';
	$fields = isset( $data['fields'] ) ? $data['fields'] : array();
	?>
	<div class="profootball-section-item" data-index="<?php echo $index; ?>">
		<div class="section-header">
			<span class="dashicons dashicons-move handle"></span>
			<input type="text" name="profootball_player_sections[<?php echo $index; ?>][title]" value="<?php echo esc_attr( $title ); ?>" placeholder="Section Title (e.g. Player CV)" class="section-title-input">
			<button type="button" class="remove-section button-link-delete">Remove Section</button>
		</div>
		
		<div class="section-fields-container">
			<!-- Fields inside this section -->
			<table class="widefat field-table">
				<thead>
					<tr>
						<th width="15%">Label</th>
						<th width="12%">Type</th>
						<th width="15%">Mapping (Slug)</th>
						<th width="10%">Width</th>
						<th width="15%">CSS Class/ID</th>
						<th width="12%">Options</th>
						<th width="5%">Action</th>
					</tr>
				</thead>
				<tbody class="fields-list">
					<?php if ( ! empty( $fields ) ) : ?>
						<?php foreach ( $fields as $f_index => $field ) : ?>
							<?php render_profootball_field_row( $index, $f_index, $field ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<div class="add-field-wrap">
				<button type="button" class="add-new-field button">Add Field to Section</button>
			</div>
		</div>
	</div>
	<?php
}

function render_profootball_field_row( $s_index, $f_index, $field ) {
	global $ump_fields, $sp_fields;
	$label = isset( $field['label'] ) ? $field['label'] : '';
	$type = isset( $field['type'] ) ? $field['type'] : 'text';
	$mapping = isset( $field['mapping'] ) ? $field['mapping'] : '';
	$width = isset( $field['width'] ) ? $field['width'] : '12';
	$css_class = isset( $field['css_class'] ) ? $field['css_class'] : '';
	$css_id = isset( $field['css_id'] ) ? $field['css_id'] : '';
	$show_download = isset( $field['show_download'] ) ? $field['show_download'] : '';
	$options = isset( $field['options'] ) ? $field['options'] : '';
	?>
	<tr class="field-config-row">
		<td>
			<input type="text" name="profootball_player_sections[<?php echo $s_index; ?>][fields][<?php echo $f_index; ?>][label]" value="<?php echo esc_attr( $label ); ?>" placeholder="Label" class="field-label-preview">
		</td>
		<td>
			<select name="profootball_player_sections[<?php echo $s_index; ?>][fields][<?php echo $f_index; ?>][type]" class="field-type-select">
				<option value="text" <?php selected( $type, 'text' ); ?>>Input Text</option>
				<option value="textarea" <?php selected( $type, 'textarea' ); ?>>Textarea / Editor</option>
				<option value="select" <?php selected( $type, 'select' ); ?>>Select (Dropdown)</option>
				<option value="multiselect" <?php selected( $type, 'multiselect' ); ?>>Select (Multiple)</option>
				<option value="image" <?php selected( $type, 'image' ); ?>>Single Image</option>
				<option value="gallery" <?php selected( $type, 'gallery' ); ?>>Gallery Slider</option>
				<option value="video" <?php selected( $type, 'video' ); ?>>Video Link</option>
				<option value="file" <?php selected( $type, 'file' ); ?>>File (CV)</option>
			</select>
		</td>
		<td>
			<select name="profootball_player_sections[<?php echo $s_index; ?>][fields][<?php echo $f_index; ?>][mapping]" class="ump-mapping-select">
				<option value="">-- Select --</option>
				<optgroup label="Ultimate Membership Pro">
					<?php if ( ! empty( $ump_fields ) ) : ?>
						<?php foreach ( $ump_fields as $u_field ) : 
							if ( empty( $u_field['name'] ) ) continue;
							$f_label = ! empty( $u_field['label'] ) ? $u_field['label'] : $u_field['name'];
							?>
							<option value="<?php echo esc_attr( $u_field['name'] ); ?>" <?php selected( $mapping, $u_field['name'] ); ?>><?php echo esc_html( $f_label ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</optgroup>
				<optgroup label="SportsPress">
					<?php if ( ! empty( $sp_fields ) ) : ?>
						<?php foreach ( $sp_fields as $sp_f ) : ?>
							<option value="<?php echo esc_attr( $sp_f['name'] ); ?>" <?php selected( $mapping, $sp_f['name'] ); ?>><?php echo esc_html( $sp_f['label'] ); ?></option>
						<?php endforeach; ?>
					<?php endif; ?>
				</optgroup>
			</select>
		</td>
		<td>
			<select name="profootball_player_sections[<?php echo $s_index; ?>][fields][<?php echo $f_index; ?>][width]" class="field-width-select">
				<option value="12" <?php selected( $width, '12' ); ?>>100% (Full)</option>
				<option value="6" <?php selected( $width, '6' ); ?>>50% (1/2)</option>
				<option value="4" <?php selected( $width, '4' ); ?>>33% (1/3)</option>
				<option value="3" <?php selected( $width, '3' ); ?>>25% (1/4)</option>
				<option value="8" <?php selected( $width, '8' ); ?>>66% (2/3)</option>
			</select>
		</td>
		<td>
			<input type="text" name="profootball_player_sections[<?php echo $s_index; ?>][fields][<?php echo $f_index; ?>][css_class]" value="<?php echo esc_attr( $css_class ); ?>" placeholder="Class" style="width:45%;">
			<input type="text" name="profootball_player_sections[<?php echo $s_index; ?>][fields][<?php echo $f_index; ?>][css_id]" value="<?php echo esc_attr( $css_id ); ?>" placeholder="ID" style="width:45%;">
		</td>
		<td>
			<div class="field-options-wrap" <?php echo ( $type === 'select' || $type === 'multiselect' ) ? '' : 'style="display:none;"'; ?>>
				<input type="text" name="profootball_player_sections[<?php echo $s_index; ?>][fields][<?php echo $f_index; ?>][options]" value="<?php echo esc_attr( $options ); ?>" placeholder="Option 1|Option 2, Option 3">
				<small>Use commas or new lines. "value|label" supported.</small>
			</div>
			<div class="download-toggle-wrap" <?php echo ( $type === 'file' || $type === 'image' ) ? '' : 'style="display:none;"'; ?>>
				<label><input type="checkbox" name="profootball_player_sections[<?php echo $s_index; ?>][fields][<?php echo $f_index; ?>][show_download]" value="1" <?php checked( $show_download, '1' ); ?>> Download</label>
			</div>
		</td>
		<td>
			<button type="button" class="remove-field button-link-delete">Del</button>
		</td>
	</tr>
	<?php
}
?>
<script type="text/template" id="profootball-field-tpl">
	<?php render_profootball_field_row( '{{S_INDEX}}', '{{F_INDEX}}', array() ); ?>
</script>

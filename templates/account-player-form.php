<?php
/**
 * Player Details Form Template for UMP Account Page
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = isset( $user_id ) ? $user_id : get_current_user_id();
$sections = get_option( 'profootball_player_sections', array() );
$ump_fields = array();
if ( function_exists( 'ihc_get_user_reg_fields' ) ) {
	$ump_fields = ihc_get_user_reg_fields();
}
$ump_fields_by_name = array();
if ( ! empty( $ump_fields ) ) {
	foreach ( $ump_fields as $ump_field ) {
		if ( ! empty( $ump_field['name'] ) ) {
			$ump_fields_by_name[ $ump_field['name'] ] = $ump_field;
		}
	}
}

if ( ! function_exists( 'profootball_get_field_options' ) ) {
	function profootball_get_field_options( $field, $ump_fields_by_name ) {
		$raw_options = '';
		if ( ! empty( $field['options'] ) ) {
			$raw_options = $field['options'];
		} else {
			$mapping = ! empty( $field['mapping'] ) ? $field['mapping'] : '';
			if ( $mapping && isset( $ump_fields_by_name[ $mapping ] ) ) {
				$ump_field = $ump_fields_by_name[ $mapping ];
				if ( isset( $ump_field['values'] ) ) {
					$raw_options = $ump_field['values'];
				} elseif ( isset( $ump_field['options'] ) ) {
					$raw_options = $ump_field['options'];
				} elseif ( isset( $ump_field['value'] ) ) {
					$raw_options = $ump_field['value'];
				}
			}
		}

		$options = array();

		if ( is_array( $raw_options ) ) {
			foreach ( $raw_options as $key => $label ) {
				if ( is_array( $label ) ) {
					if ( isset( $label['value'] ) ) {
						$value = $label['value'];
						$label_text = isset( $label['label'] ) ? $label['label'] : $value;
						$options[] = array( 'value' => (string) $value, 'label' => (string) $label_text );
					}
					continue;
				}
				if ( is_int( $key ) ) {
					$value = $label;
					$label_text = $label;
				} else {
					$value = $key;
					$label_text = $label;
				}
				if ( $value === '' ) {
					continue;
				}
				$options[] = array( 'value' => (string) $value, 'label' => (string) $label_text );
			}
			return $options;
		}

		$raw_options = is_string( $raw_options ) ? trim( $raw_options ) : '';
		if ( $raw_options === '' ) {
			return $options;
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw_options );
		if ( count( $lines ) === 1 ) {
			$lines = explode( ',', $raw_options );
		}

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( $line === '' ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$value = $parts[0];
			$label_text = isset( $parts[1] ) && $parts[1] !== '' ? $parts[1] : $value;
			$options[] = array( 'value' => $value, 'label' => $label_text );
		}

		return $options;
	}
}

if ( empty( $sections ) ) {
	echo '<p>No profile details to configure. Please contact administrator.</p>';
	return;
}
?>

<div class="profootball-account-form-wrap">
	<h3>Edit Player Details</h3>
	<p>Complete the information below to update your public player profile.</p>

	<?php if ( isset( $_GET['profootball_save'] ) && $_GET['profootball_save'] === 'success' ) : ?>
		<div class="ihc-success-box">Your profile details have been updated successfully!</div>
	<?php endif; ?>

	<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'profootball_save_action', 'profootball_profile_nonce' ); ?>
		
		<?php foreach ( $sections as $section ) : ?>
			<div class="profootball-form-section">
				<h4><?php echo esc_html( $section['title'] ); ?></h4>
				
				<?php if ( ! empty( $section['fields'] ) ) : ?>
					<?php foreach ( $section['fields'] as $field ) : 
						$mapping = ! empty( $field['mapping'] ) ? $field['mapping'] : '';
						// We show the field even if not mapped, but mapping is recommended for sync
						if ( empty( $mapping ) ) {
							$mapping = 'unmapped_field_' . sanitize_title( $field['label'] );
						}
						
						$value = get_user_meta( $user_id, $mapping, true );
						$is_taxonomy = ( strpos( $mapping, 'tax_' ) === 0 );
						$taxonomy = $is_taxonomy ? substr( $mapping, 4 ) : '';
						?>
						<div class="profootball-form-field">
							<label><?php echo esc_html( $field['label'] ); ?></label>
							
							<?php if ( $is_taxonomy && taxonomy_exists( $taxonomy ) ) : 
								$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
								// Get current terms for player if exists
								$player_id = ( new ProFootball_Player_Profile() )->get_player_id_by_user( $user_id );
								$current_terms = $player_id ? wp_get_object_terms( $player_id, $taxonomy, array( 'fields' => 'ids' ) ) : array();
								?>
								<select name="<?php echo esc_attr( $mapping ); ?>[]" multiple class="profootball-select2-style">
									<?php foreach ( $terms as $term ) : ?>
										<option value="<?php echo $term->term_id; ?>" <?php selected( in_array( $term->term_id, $current_terms ) ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="field-desc">Hold Ctrl (Cmd) to select multiple values.</p>

							<?php elseif ( $field['type'] === 'textarea' ) : ?>
								<textarea name="<?php echo esc_attr( $mapping ); ?>" rows="4"><?php echo esc_textarea( $value ); ?></textarea>
							
							<?php elseif ( $field['type'] === 'select' ) : ?>
								<?php
								$options = profootball_get_field_options( $field, $ump_fields_by_name );
								$selected_value = is_array( $value ) ? reset( $value ) : $value;
								?>
								<select name="<?php echo esc_attr( $mapping ); ?>">
									<option value="">-- Select --</option>
									<?php foreach ( $options as $option ) : ?>
										<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( (string) $selected_value, (string) $option['value'] ); ?>>
											<?php echo esc_html( $option['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>

							<?php elseif ( $field['type'] === 'multiselect' ) : ?>
								<?php
								$options = profootball_get_field_options( $field, $ump_fields_by_name );
								$selected_values = is_array( $value ) ? $value : array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' );
								$selected_values = array_map( 'strval', $selected_values );
								?>
								<select name="<?php echo esc_attr( $mapping ); ?>[]" multiple class="profootball-select2-style">
									<?php foreach ( $options as $option ) : ?>
										<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( in_array( (string) $option['value'], $selected_values, true ) ); ?>>
											<?php echo esc_html( $option['label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="field-desc">Hold Ctrl (Cmd) to select multiple values.</p>

							<?php elseif ( $field['type'] === 'video' ) : ?>
								<input type="url" name="<?php echo esc_attr( $mapping ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="https://www.youtube.com/watch?v=...">
								<p class="field-desc">Paste the link to your video (YouTube, Vimeo, etc.).</p>

							<?php elseif ( $field['type'] === 'file' || $field['type'] === 'image' ) : ?>
								<div class="profootball-upload-container">
									<input type="file" name="<?php echo esc_attr( $mapping ); ?>" class="profootball-file-input">
									<?php if ( $value ) : 
										$file_display = is_numeric($value) ? basename(wp_get_attachment_url($value)) : basename($value);
										?>
										<p class="current-file">
											Current: <a href="<?php echo esc_url(is_numeric($value) ? wp_get_attachment_url($value) : $value); ?>" target="_blank"><strong><?php echo esc_html($file_display); ?></strong></a>
										</p>
									<?php endif; ?>
								</div>
								<p class="field-desc">Upload your <?php echo esc_html(strtolower($field['label'])); ?> directly from your device.</p>
							
							<?php else : ?>
								<input type="text" name="<?php echo esc_attr( $mapping ); ?>" value="<?php echo esc_attr( $value ); ?>">
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="profootball-form-submit">
			<button type="submit" name="profootball_save_profile" class="ihc-submit-bttn">Save All Details</button>
		</div>
	</form>
</div>

<style>
.profootball-form-section {
	background: #f9f9f9;
	padding: 20px;
	margin-bottom: 30px;
	border-radius: 8px;
	border-left: 4px solid #d4af37;
}
.profootball-form-section h4 {
	margin-top: 0;
	color: #333;
	border-bottom: 1px solid #ddd;
	padding-bottom: 10px;
}
.profootball-form-field {
	margin-bottom: 15px;
}
.profootball-form-field label {
	display: block;
	font-weight: bold;
	margin-bottom: 5px;
}
.profootball-form-field input[type="text"],
.profootball-form-field input[type="url"],
.profootball-form-field textarea,
.profootball-form-field select {
	width: 100%;
	padding: 10px;
	border: 1px solid #ccc;
	border-radius: 4px;
}
.field-desc {
	font-size: 0.85em;
	color: #666;
	margin-top: 5px;
}
.profootball-form-submit {
	margin-top: 20px;
}
.profootball-form-field select[multiple] {
	height: auto;
	min-height: 120px;
	background: #fff;
}
.profootball-form-field select option {
	padding: 8px;
	border-bottom: 1px solid #eee;
}
.profootball-form-field select option:checked {
	background: #d4af37 content-box;
	color: #fff;
}
.profootball-upload-container {
	background: #fff;
	border: 1px dashed #ccc;
	padding: 15px;
	border-radius: 4px;
	text-align: center;
}
.profootball-file-input {
	margin-bottom: 10px;
}
.current-file {
	margin: 10px 0 0;
	padding: 8px;
	background: #e9f7ef;
	border: 1px solid #d4efdf;
	border-radius: 4px;
	font-size: 0.9em;
}
.current-file a {
	color: #27ae60;
	text-decoration: none;
}
.current-file a:hover {
	text-decoration: underline;
}
</style>

<?php
/**
 * Player Details Form Template for UMP Account Page
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = isset( $user_id ) ? $user_id : get_current_user_id();
$sections = get_option( 'profootball_player_sections', array() );

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
								<select name="<?php echo esc_attr( $mapping ); ?>">
									<option value="">-- Select --</option>
									<!-- Options would need to be defined in admin, for now simple text -->
								</select>

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

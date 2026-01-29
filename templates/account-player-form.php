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
						if ( empty( $mapping ) ) continue;
						$value = get_user_meta( $user_id, $mapping, true );
						?>
						<div class="profootball-form-field">
							<label><?php echo esc_html( $field['label'] ); ?></label>
							
							<?php if ( $field['type'] === 'textarea' ) : ?>
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
								<!-- Integration with Media Library or simple URL for now -->
								<input type="text" name="<?php echo esc_attr( $mapping ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="File/Image ID or URL">
								<p class="field-desc">Use UMP Profile Details tab to upload files if needed, then paste the ID here.</p>
							
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
</style>

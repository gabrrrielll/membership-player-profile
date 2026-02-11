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

if ( ! function_exists( 'profootball_get_countries' ) ) {
	function profootball_get_countries() {
		return array(
			'af' => 'Afghanistan', 'ax' => 'Åland Islands', 'al' => 'Albania', 'dz' => 'Algeria', 'as' => 'American Samoa',
			'ad' => 'Andorra', 'ao' => 'Angola', 'ai' => 'Anguilla', 'aq' => 'Antarctica', 'ag' => 'Antigua and Barbuda',
			'ar' => 'Argentina', 'am' => 'Armenia', 'aw' => 'Aruba', 'au' => 'Australia', 'at' => 'Austria',
			'az' => 'Azerbaijan', 'bs' => 'Bahamas', 'bh' => 'Bahrain', 'bd' => 'Bangladesh', 'bb' => 'Barbados',
			'by' => 'Belarus', 'be' => 'Belgium', 'bz' => 'Belize', 'bj' => 'Benin', 'bm' => 'Bermuda',
			'bt' => 'Bhutan', 'bo' => 'Bolivia', 'ba' => 'Bosnia and Herzegovina', 'bw' => 'Botswana', 'bv' => 'Bouvet Island',
			'br' => 'Brazil', 'io' => 'British Indian Ocean Territory', 'bn' => 'Brunei Darussalam', 'bg' => 'Bulgaria', 'bf' => 'Burkina Faso',
			'bi' => 'Burundi', 'kh' => 'Cambodia', 'cm' => 'Cameroon', 'ca' => 'Canada', 'cv' => 'Cape Verde',
			'ky' => 'Cayman Islands', 'cf' => 'Central African Republic', 'td' => 'Chad', 'cl' => 'Chile', 'cn' => 'China',
			'cx' => 'Christmas Island', 'cc' => 'Cocos (Keeling) Islands', 'co' => 'Colombia', 'km' => 'Comoros', 'cg' => 'Congo',
			'cd' => 'Congo, The Democratic Republic of the', 'ck' => 'Cook Islands', 'cr' => 'Costa Rica', 'ci' => 'Cote D\'Ivoire', 'hr' => 'Croatia',
			'cu' => 'Cuba', 'cy' => 'Cyprus', 'cz' => 'Czech Republic', 'dk' => 'Denmark', 'dj' => 'Djibouti',
			'dm' => 'Dominica', 'do' => 'Dominican Republic', 'ec' => 'Ecuador', 'eg' => 'Egypt', 'sv' => 'El Salvador',
			'gq' => 'Equatorial Guinea', 'er' => 'Eritrea', 'ee' => 'Estonia', 'et' => 'Ethiopia', 'fk' => 'Falkland Islands (Malvinas)',
			'fo' => 'Faroe Islands', 'fj' => 'Fiji', 'fi' => 'Finland', 'fr' => 'France', 'gf' => 'French Guiana',
			'pf' => 'French Polynesia', 'tf' => 'French Southern Territories', 'ga' => 'Gabon', 'gm' => 'Gambia', 'ge' => 'Georgia',
			'de' => 'Germany', 'gh' => 'Ghana', 'gi' => 'Gibraltar', 'gr' => 'Greece', 'gl' => 'Greenland',
			'gd' => 'Grenada', 'gp' => 'Guadeloupe', 'gu' => 'Guam', 'gt' => 'Guatemala', 'gg' => 'Guernsey',
			'gn' => 'Guinea', 'gw' => 'Guinea-Bissau', 'gy' => 'Guyana', 'ht' => 'Haiti', 'hm' => 'Heard Island and Mcdonald Islands',
			'va' => 'Holy See (Vatican City State)', 'hn' => 'Honduras', 'hk' => 'Hong Kong', 'hu' => 'Hungary', 'is' => 'Iceland',
			'in' => 'India', 'id' => 'Indonesia', 'ir' => 'Iran, Islamic Republic Of', 'iq' => 'Iraq', 'ie' => 'Ireland',
			'im' => 'Isle of Man', 'il' => 'Israel', 'it' => 'Italy', 'jm' => 'Jamaica', 'jp' => 'Japan',
			'je' => 'Jersey', 'jo' => 'Jordan', 'kz' => 'Kazakhstan', 'ke' => 'Kenya', 'ki' => 'Kiribati',
			'kp' => 'Korea, Democratic People\'S Republic of', 'kr' => 'Korea, Republic of', 'kw' => 'Kuwait', 'kg' => 'Kyrgyzstan', 'la' => 'Lao People\'S Democratic Republic',
			'lv' => 'Latvia', 'lb' => 'Lebanon', 'ls' => 'Lesotho', 'lr' => 'Liberia', 'ly' => 'Libyan Arab Jamahiriya',
			'li' => 'Liechtenstein', 'lt' => 'Lithuania', 'lu' => 'Luxembourg', 'mo' => 'Macao', 'mk' => 'Macedonia, The Former Yugoslav Republic of',
			'mg' => 'Madagascar', 'mw' => 'Malawi', 'my' => 'Malaysia', 'mv' => 'Maldives', 'ml' => 'Mali',
			'mt' => 'Malta', 'mh' => 'Marshall Islands', 'mq' => 'Martinique', 'mr' => 'Mauritania', 'mu' => 'Mauritius',
			'yt' => 'Mayotte', 'mx' => 'Mexico', 'fm' => 'Micronesia, Federated States of', 'md' => 'Moldova, Republic of', 'mc' => 'Monaco',
			'mn' => 'Mongolia', 'ms' => 'Montserrat', 'ma' => 'Morocco', 'mz' => 'Mozambique', 'mm' => 'Myanmar',
			'na' => 'Namibia', 'nr' => 'Nauru', 'np' => 'Nepal', 'nl' => 'Netherlands', 'an' => 'Netherlands Antilles',
			'nc' => 'New Caledonia', 'nz' => 'New Zealand', 'ni' => 'Nicaragua', 'ne' => 'Niger', 'ng' => 'Nigeria',
			'nu' => 'Niue', 'nf' => 'Norfolk Island', 'mp' => 'Northern Mariana Islands', 'no' => 'Norway', 'om' => 'Oman',
			'pk' => 'Pakistan', 'pw' => 'Palau', 'ps' => 'Palestinian Territory, Occupied', 'pa' => 'Panama', 'pg' => 'Papua New Guinea',
			'py' => 'Paraguay', 'pe' => 'Peru', 'ph' => 'Philippines', 'pn' => 'Pitcairn', 'pl' => 'Poland',
			'pt' => 'Portugal', 'pr' => 'Puerto Rico', 'qa' => 'Qatar', 're' => 'Reunion', 'ro' => 'Romania',
			'ru' => 'Russian Federation', 'rw' => 'Rwanda', 'sh' => 'Saint Helena', 'kn' => 'Saint Kitts and Nevis', 'lc' => 'Saint Lucia',
			'pm' => 'Saint Pierre and Miquelon', 'vc' => 'Saint Vincent and the Grenadines', 'ws' => 'Samoa', 'sm' => 'San Marino', 'st' => 'Sao Tome and Principe',
			'sa' => 'Saudi Arabia', 'sn' => 'Senegal', 'cs' => 'Serbia and Montenegro', 'sc' => 'Seychelles', 'sl' => 'Sierra Leone',
			'sg' => 'Singapore', 'sk' => 'Slovakia', 'si' => 'Slovenia', 'sb' => 'Solomon Islands', 'so' => 'Somalia',
			'za' => 'South Africa', 'gs' => 'South Georgia and the South Sandwich Islands', 'es' => 'Spain', 'lk' => 'Sri Lanka', 'sd' => 'Sudan',
			'sr' => 'Suriname', 'sj' => 'Svalbard and Jan Mayen', 'sz' => 'Swaziland', 'se' => 'Sweden', 'ch' => 'Switzerland',
			'sy' => 'Syrian Arab Republic', 'tw' => 'Taiwan, Province of China', 'tj' => 'Tajikistan', 'tz' => 'Tanzania, United Republic of', 'th' => 'Thailand',
			'tl' => 'Timor-Leste', 'tg' => 'Togo', 'tk' => 'Tokelau', 'to' => 'Tonga', 'tt' => 'Trinidad and Tobago',
			'tn' => 'Tunisia', 'tr' => 'Turkey', 'tm' => 'Turkmenistan', 'tc' => 'Turks and Caicos Islands', 'tv' => 'Tuvalu',
			'ug' => 'Uganda', 'ua' => 'Ukraine', 'ae' => 'United Arab Emirates', 'gb' => 'United Kingdom', 'us' => 'United States',
			'um' => 'United States Minor Outlying Islands', 'uy' => 'Uruguay', 'uz' => 'Uzbekistan', 'vu' => 'Vanuatu', 've' => 'Venezuela',
			'vn' => 'Viet Nam', 'vg' => 'Virgin Islands, British', 'vi' => 'Virgin Islands, U.S.', 'wf' => 'Wallis and Futuna', 'eh' => 'Western Sahara',
			'ye' => 'Yemen', 'zm' => 'Zambia', 'zw' => 'Zimbabwe',
		);
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
				
				<div class="profootball-grid-row">
					<?php if ( ! empty( $section['fields'] ) ) : ?>
					<?php foreach ( $section['fields'] as $f_index => $field ) : 
						$mapping = ! empty( $field['mapping'] ) ? $field['mapping'] : '';
						// We show the field even if not mapped, but mapping is recommended for sync
						if ( empty( $mapping ) ) {
							$mapping_suffix = ! empty( $field['label'] ) ? sanitize_title( $field['label'] ) : $f_index;
							$mapping = 'unmapped_field_' . $mapping_suffix;
						}
						
						$value = get_user_meta( $user_id, $mapping, true );
						$is_taxonomy = ( strpos( $mapping, 'tax_' ) === 0 );
						$taxonomy = $is_taxonomy ? substr( $mapping, 4 ) : '';
						
						$col_width = ! empty( $field['width'] ) ? $field['width'] : '12';
						$css_class = ! empty( $field['css_class'] ) ? $field['css_class'] : '';
						$css_id = ! empty( $field['css_id'] ) ? $field['css_id'] : '';
						?>
						<div <?php echo $css_id ? 'id="'.esc_attr($css_id).'"' : ''; ?> class="profootball-grid-col col-<?php echo esc_attr($col_width); ?> profootball-field-item field-type-<?php echo esc_attr( $field['type'] ); ?> <?php echo esc_attr($css_class); ?>">
							<?php if ( $field['type'] === 'empty_space' ) : ?>
								<!-- Empty Space Placeholder -->
							<?php else : ?>
								<?php if ( ! empty( $field['label'] ) ) : ?>
									<label><?php echo esc_html( $field['label'] ); ?></label>
								<?php endif; ?>
								
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
								
								<?php elseif ( $field['type'] === 'shortcut_buttons' ) : ?>
									<div class="profootball-shortcuts-edit-notice">
										<p><em>Shortcut buttons will be displayed on the public profile.</em></p>
									</div>

								<?php elseif ( $field['type'] === 'nationality' ) : ?>
									<select name="<?php echo esc_attr( $mapping ); ?>">
										<option value="">-- Select Country --</option>
										<?php 
										$countries = profootball_get_countries();
										foreach ( $countries as $code => $name ) : ?>
											<option value="<?php echo esc_attr( $code ); ?>" <?php selected( strtolower( (string) $value ), strtolower( $code ) ); ?>>
												<?php echo esc_html( $name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<?php if ( $value ) : 
										$custom_width = ! empty( $field['options'] ) ? trim( $field['options'] ) : '40px';
										if ( is_numeric( $custom_width ) ) { $custom_width .= 'px'; }
										?>
										<div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
											<img src="https://flagcdn.com/w160/<?php echo esc_attr( strtolower($value) ); ?>.png" 
												 onerror="this.style.display='none'" 
												 class="country-flag" 
												 style="width:<?php echo esc_attr($custom_width); ?>; height:auto;">
											<span><?php echo esc_html( strtoupper($value) ); ?></span>
										</div>
									<?php endif; ?>

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
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
				</div>
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
